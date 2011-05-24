/**
 * Main source file
 *
 * @package vzlogger
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Steffen Vogel <info@steffenvogel.de>
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */
 
#define VZ_VERSION "0.2"
 
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <unistd.h>
#include <getopt.h>
#include <pthread.h>

#include <curl/types.h>

#include "main.h"
#include "api.h"

#include "protocols/obis.h"

static struct protocol protocols[] = {
	{"obis",	"Plaintext OBIS",	obis_get,	obis_init},
	{NULL} /* stop condition for iterator */
};

static struct option long_options[] = {
	{"config", 	required_argument,	0,	'c'},
	{"daemon", 	required_argument,	0,	'd'},
	{"interval", 	required_argument,	0,	'i'},
//	{"local", 	no_argument,		0,	'l'},
//	{"local-port",	required_argument,	0,	'p'},
	{"help",	no_argument,		0,	'h'},
	{"verbose",	no_argument,		0,	'v'},
	{NULL} /* stop condition for iterator */
};

static char * long_options_descs[] = {
	"config file with channel -> uuid mapping",
	"run as daemon",
	"interval in seconds to read meters",
//	"activate local interface (tiny webserver)",
//	"TCP port for local interface"	
	"show this help",
	"enable verbose output",
	NULL /* stop condition for iterator */
};

/* globals */
struct options opts;

/**
 * Print availble options and some other usefull information
 */
void usage(char * argv[]) {
	char ** desc = long_options_descs;
	struct option * op = long_options;
	struct protocol * prot = protocols;

	printf("Usage: %s [options]\n\n", argv[0]);
	printf("  following options are available:\n");
	
	while (op->name && desc) {
		printf("\t--%-12s\t-%c\t%s\n", op->name, op->val, *desc);
		op++;
		desc++;
	}
	
	printf("\n");
	printf("  following protocol types are supported:\n");
	
	while (prot->name) {
		printf("\t%-12s\t%s\n", prot->name, prot->desc);
		prot++;
	}
	
	printf("\nvzlogger - volkszaehler.org logging utility VERSION\n");
	printf("by Steffen Vogel <stv0g@0l.de>\n");
}

/**
 * Parse options from command line
 */
struct options parse_options(int argc, char * argv[]) {
	struct options opts;
	
	/* setting default options */
	opts.interval = 300; /* seconds */
	opts.verbose = FALSE;
	opts.daemon = FALSE;
	//opts.local = FALSE;
	opts.config = NULL;

	while (TRUE) {
		/* getopt_long stores the option index here. */
		int option_index = 0;

		int c = getopt_long(argc, argv, "i:c:hdv", long_options, &option_index);

		/* detect the end of the options. */
		if (c == -1)
			break;

		switch (c) {
			case 'v':
				opts.verbose = 1;
				break;

			case 'd':
				opts.daemon = 1;
				break;

			case 'c': /* read config file */
				opts.config = (char *) malloc(strlen(optarg)+1);
				strcpy(opts.config, optarg);
				break;

			case 'h':
			case '?':
				usage(argv);
				exit((c == '?') ? EXIT_FAILURE : EXIT_SUCCESS);
		}
	}
	
	return opts;
}

struct channel parse_channel(char * line) {
	struct channel ch;
	struct protocol * prot;
	char *tok = strtok(line, ";");
			
	for (int i = 0; i < 7 && tok != NULL; i++) {
		switch(i) {
			case 0: /* middleware */
				ch.middleware = (char *) malloc(strlen(tok)+1);
				strcpy(ch.middleware, tok);
				break;
				
			case 1: /* uuid */
				ch.uuid = (char *) malloc(strlen(tok)+1);
				strcpy(ch.uuid, tok);
				break;
			
			case 2: /* protocol */
				prot = protocols; /* reset pointer */
				while (prot->name && strcmp(prot->name, tok) != 0) prot++; /* linear search */
				ch.prot = prot;
				break;
			
			case 3: /* interval */
				ch.interval = atoi(tok);
				break;
			
			case 4: /* options */
				ch.options = (char *) malloc(strlen(tok)+1);
				strcpy(ch.options, tok);
				break;
		}
	
		tok = strtok(NULL, ";");
	}
	
	if (opts.verbose) printf("Channel parsed: %s\n", line);	
	
	return ch;
}

/**
 * Logging thread
 */
void *log_thread(void *arg) {
	static int threads; /* number of threads already started */
	int thread_id = threads++; /* current thread identifier */
	struct channel ch;
	struct reading rd;
	CURLcode rc;
	
	if (opts.verbose) printf("Thread #%i started\n", thread_id);
	
	ch = *(struct channel *) arg; /* copy channel struct */
	
	ch.prot->init_func(ch.options); /* init sensor/meter */
	
	log:
	
	rd = ch.prot->read_func(); /* aquire reading */
	rc = api_log(ch.middleware, ch.uuid, rd); /* log reading */
	
	if (rc != CURLE_OK) {
		if (opts.verbose) printf("Delaying next transmission for 15 minutes due to pervious error\n");
		sleep(15);
	}
	
	if (opts.daemon) {
		if (opts.verbose) printf("Sleeping %i seconds for next transmission\n", ch.interval);
		sleep(ch.interval);
		goto log;
	}
	
	return NULL;
}

/**
 * The main loop
 */
int main(int argc, char * argv[]) {
	opts = parse_options(argc, argv); /* parse command line arguments */
	
	FILE *file = fopen(opts.config, "r"); /* open configuration */

	if (file == NULL) {
		perror(opts.config); /* why didn't the file open? */
		exit(EXIT_FAILURE);
	}
	
	int i = 0;
	char line[256];
	pthread_t pthreads[16];
	while (fgets(line, sizeof line, file) != NULL) { /* read a line */
		if (line[0] == '#' || line[0] == '\n') continue; /* skip comments */
	
		struct channel ch = parse_channel(line);
	
		/* start logging threads */
		pthread_create(&pthreads[i++], NULL, log_thread, (void *) &ch);
	}
	
	fclose(file);
	
	for (int n = 0; n < i; n++) { /* wait for all threads to terminate */
		pthread_join(pthreads[n], NULL);
	}

	return 0;
}


