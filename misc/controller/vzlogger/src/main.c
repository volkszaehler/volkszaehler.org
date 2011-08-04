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

#include <stdlib.h>
#include <stdio.h>
#include <stdint.h>
#include <stdarg.h>
#include <unistd.h>
#include <math.h>
#include <string.h>
#include <curl/curl.h>
#include <getopt.h>

#ifdef LOCAL
	#include <microhttpd.h>
	#include "local.h"
#endif

#include "main.h"
#include "queue.h"
#include "api.h"

#include "protocols/obis.h"
#include "protocols/1wire.h"
#include "protocols/rawS0.h"
#include "protocols/random.h"

/**
 * List of available protocols
 * incl. function pointers
 */
static protocol_t protocols[] = {
	{"1wire",	"Dallas 1-Wire sensors (via OWFS)",	onewire_get,	onewire_init,	onewire_close,	MODE_SENSOR},
//	{"obis",	"Plaintext OBIS",			obis_get,	obis_init,	obis_close,	MODE_SENSOR},
	{"random",	"Random walk",				random_get,	random_init,	random_close,	MODE_SENSOR},
	{"rawS0",	"S0 on RS232",				rawS0_get, 	rawS0_init,	rawS0_close,	MODE_METER},
//	{"sml",		"Smart Meter Language",			sml_get,	sml_init,	sml_close,	MODE_SENSOR},
//	{"fluksousb", 	"FluksoUSB board", 			flukso_get,	flukso_init,	flukso_close,	MODE_SENSOR},
	{NULL} /* stop condition for iterator */
};


/**
 * Command line options
 */
static struct option long_options[] = {
	{"config", 	required_argument,	0,	'c'},
	{"daemon", 	required_argument,	0,	'd'},
#ifdef LOCAL
	{"local", 	no_argument,		0,	'l'},
	{"local-port",	required_argument,	0,	'p'},
#endif /* LOCAL */
	{"verbose",	optional_argument,	0,	'v'},
	{"help",	no_argument,		0,	'h'},
	{"version",	no_argument,		0,	'V'},
	{NULL} /* stop condition for iterator */
};

/**
 * Descriptions vor command line options
 */
static char *long_options_descs[] = {
	"config file with channel -> uuid mapping",
	"run as daemon",
#ifdef LOCAL
	"activate local interface (tiny webserver)",
	"TCP port for local interface",
#endif /* LOCAL */
	"enable verbose output",
	"show this help",
	"show version of vzlogger",
	NULL /* stop condition for iterator */
};

/*
 * Global variables
 */
channel_t chans[MAX_CHANNELS]; // TODO use dynamic allocation
options_t opts = { /* setting default options */
	NULL,		/* config file */
	8080,		/* port for local interface */
	0,		/* debug level / verbosity */
	FALSE,		/* daemon mode */
	FALSE		/* local interface */
};

/**
 * Print availble options and some other usefull information
 */
void usage(char * argv[]) {
	char ** desc = long_options_descs;
	struct option * op = long_options;
	protocol_t * prot = protocols;

	printf("Usage: %s [options]\n\n", argv[0]);
	printf("  following options are available:\n");

	while (op->name && desc) {
		printf("\t-%c, --%-12s\t%s\n", op->val, op->name, *desc);
		op++;
		desc++;
	}

	printf("\n");
	printf("  following protocol types are supported:\n");

	while (prot->name) {
		printf("\t%-12s\t%s\n", prot->name, prot->desc);
		prot++;
	}

	printf("\n%s - volkszaehler.org logging utility\n", PACKAGE_STRING);
	printf("by Steffen Vogel <stv0g@0l.de>\n");
	printf("send bugreports to %s\n", PACKAGE_BUGREPORT);
}

/**
 * Wrapper to log notices and errors
 *
 * @param ch could be NULL for general messages
 * @todo integrate into syslog
 */
void print(int level, char * format, channel_t *ch, ... ) {
	static pthread_mutex_t mutex = PTHREAD_MUTEX_INITIALIZER;
	va_list args;

	struct timeval now;
	struct tm * timeinfo;
	char buffer[16];

	if (level <= (signed int) opts.verbose) {
		gettimeofday(&now, NULL);
		timeinfo = localtime(&now.tv_sec);

		strftime(buffer, 16, "%b %d %H:%M:%S", timeinfo);

		pthread_mutex_lock(&mutex);
			fprintf((level > 0) ? stdout : stderr, "[%s.%3lu]", buffer, now.tv_usec / 1000);

			if (ch != NULL) {
				fprintf((level > 0) ? stdout : stderr, "[ch#%i]\t", ch->id);
			}
			else {
				fprintf((level > 0) ? stdout : stderr, "\t\t");
			}

			va_start(args, ch);
			vfprintf((level > 0) ? stdout : stderr, format, args);
			va_end(args);
			fprintf((level > 0) ? stdout : stderr, "\n");
		pthread_mutex_unlock(&mutex);
	}
}

/**
 * Parse options from command line
 */
void parse_options(int argc, char * argv[], options_t * opts) {
	while (TRUE) {
		/* getopt_long stores the option index here. */
		int option_index = 0;

		int c = getopt_long(argc, argv, "i:c:p:lhVdv::", long_options, &option_index);

		/* detect the end of the options. */
		if (c == -1)
			break;

		switch (c) {
			case 'v':
				opts->verbose = (optarg == NULL) ? 1 : atoi(optarg);
				break;

			case 'l':
				opts->local = TRUE;
				break;

			case 'd':
				opts->daemon = TRUE;
				break;

			case 'p': /* port for local interface */
				opts->port = atoi(optarg);
				break;

			case 'c': /* read config file */
				opts->config = (char *) malloc(strlen(optarg)+1);
				strcpy(opts->config, optarg);
				break;

			case 'V':
				printf("%s\n", VERSION);
				exit(EXIT_SUCCESS);
				break;

			case 'h':
			case '?':
				usage(argv);
				exit((c == '?') ? EXIT_FAILURE : EXIT_SUCCESS);
		}
	}

	if (opts->config == NULL) { /* search for config file */
		if (access("vzlogger.conf", R_OK) == 0) {
			opts->config = "vzlogger.conf";
		}
		else if (access("/etc/vzlogger.conf", R_OK) == 0) {
			opts->config = "/etc/vzlogger.conf";
		}
		else { /* search in home directory */
			char *home_config = malloc(255);
			strcat(home_config, getenv("HOME")); /* get home dir */
			strcat(home_config, "/.vzlogger.conf"); /* append my filename */

			if (access(home_config, R_OK) == 0) {
				opts->config = home_config;
			}
		}
	}
}

int parse_channels(char *filename, channel_t *chans) {
	if (filename == NULL) {
		fprintf(stderr, "No config file found! Please specify with --config!\n");
		exit(EXIT_FAILURE);
	}

	FILE *file = fopen(filename, "r"); /* open configuration */

	if (file == NULL) {
		perror(filename); /* why didn't the file open? */
		exit(EXIT_FAILURE);
	}
	else {
		print(2, "Start parsing configuration from %s", NULL, filename);
	}

	char line[256];
	int chan_num = 0, line_num = 1;

	while (chan_num < MAX_CHANNELS && fgets(line, sizeof line, file) != NULL) { /* read a line */
		if (line[0] == ';' || line[0] == '\n') continue; /* skip comments */

		channel_t ch = {
			chan_num,
			NULL,
			NULL,
			NULL,
			0,
			NULL,
			protocols
		};

		char *tok = strtok(line, " \t");

		for (int i = 0; i < 7 && tok != NULL; i++) {
			size_t len = strlen(tok);

			switch(i) {
				case 0: /* protocol */
					while (ch.prot->name && strcmp(ch.prot->name, tok) != 0) ch.prot++; /* linear search */

					if (ch.prot == NULL) {
						print(-1, "Invalid protocol: %s in %s:%i", NULL, tok, filename, line_num);
						exit(EXIT_FAILURE);
					}
					break;

				case 1: /* interval */
					ch.interval = strtol(tok, (char **) NULL, 10);

					if (errno == EINVAL || errno == ERANGE) {
						print(-1, "Invalid interval: %s in %s:%i", NULL, tok, filename, line_num);
						exit(EXIT_FAILURE);
					}
					break;

				case 2: /* uuid */
					if (len == 0) { // TODO add uuid validation
						print(-1, "Missing uuid in %s:%i", NULL, filename, line_num);
						exit(EXIT_FAILURE);
					}

					ch.uuid = (char *) malloc(len+1); /* including string termination */
					strcpy(ch.uuid, tok);
					break;

				case 3: /* middleware */
					if (len == 0) { // TODO add uuid validation
						print(-1, "Missing middleware in %s:%i", NULL, filename, line_num);
						exit(EXIT_FAILURE);
					}

					ch.middleware = (char *) malloc(len+1); /* including string termination */
					strcpy(ch.middleware, tok);
					break;

				case 4: /* options */
					ch.options = (char *) malloc(len);
					strncpy(ch.options, tok, len-1);
					ch.options[len-1] = '\0'; /* replace \n by \0 */
					break;
			}

			tok = strtok(NULL, " \t");
		}

		print(1, "Parsed ch#%i (protocol=%s interval=%i uuid=%s middleware=%s options=%s)", &ch, ch.id, ch.prot->name, ch.interval, ch.uuid, ch.middleware, ch.options);
		chans[chan_num] = ch;

		chan_num++;
		line_num++;
	}

	fclose(file);

	return chan_num;
}

/**
 * Read thread
 *
 * Aquires reading from meters/sensors
 */
void *read_thread(void *arg) {
	channel_t *ch = (channel_t *) arg; /* casting argument */
	print(1, "Started reading thread", ch);

	/* initalize channel */
	ch->handle = ch->prot->init_func(ch->options); /* init sensor/meter */

	do {
		/**
		 * Aquire reading,
		 * may be blocking if mode == MODE_METER
		 */
		reading_t rd = ch->prot->read_func(ch->handle);

		pthread_mutex_lock(&ch->mutex);
			if (!queue_push(&ch->queue, rd)) {
				print(6, "Warning queue is full, discarding first tuple!", ch);
			}
			pthread_cond_broadcast(&ch->condition); /* notify webserver and logging thread */
		pthread_mutex_unlock(&ch->mutex);

		print(1, "Value read: %.1f", ch, rd.value);

		/* Debugging */
		if (opts.verbose >= 10) {
			char *queue_str = queue_print(&ch->queue);
			print(10, "Queue dump: %s write_p = %lu\t read_p = %lu", ch, queue_str, ch->queue.write_p, ch->queue.read_p);
			free(queue_str);
		}

		if (ch->prot->mode != MODE_METER) { /* for meters, the read_func call is blocking */
			print(5, "Next reading in %i seconds", ch, ch->interval);
			sleep(ch->interval); /* else sleep and restart aquisition */
		}

		pthread_testcancel(); /* test for cancelation request */
	} while (opts.daemon || opts.local);

	/* close channel */
	ch->prot->close_func(ch->handle);

	return NULL;
}

/**
 * The main loop
 */
int main(int argc, char *argv[]) {
	int num_chans;

#ifdef LOCAL
	struct MHD_Daemon *httpd_handle = NULL;
#endif /* LOCAL */

	parse_options(argc, argv, &opts); /* parse command line arguments */
	num_chans = parse_channels(opts.config, chans); /* parse channels from configuration */

	print(1, "Started %s with verbosity level %i", NULL, argv[0], opts.verbose);

	curl_global_init(CURL_GLOBAL_ALL); /* global intialization for all threads */

	for (int i = 0; i < num_chans; i++) {
		channel_t *ch = &chans[i];

		/* initialize queue to buffer data */
		queue_init(&ch->queue, (BUFFER_LENGTH / ch->interval) + 1);

		/* initialize thread syncronization helpers */
		pthread_mutex_init(&ch->mutex, NULL);
		pthread_cond_init(&ch->condition, NULL);

		/* start threads */
		pthread_create(&ch->reading_thread, NULL, read_thread, (void *) ch);
		pthread_create(&ch->logging_thread, NULL, api_thread, (void *) ch);
	}

#ifdef LOCAL
	/* start webserver for local interface */
	if (opts.local) {
		httpd_handle = MHD_start_daemon(
			MHD_USE_THREAD_PER_CONNECTION,
			opts.port,
			NULL, NULL,
			handle_request,
			&num_chans,
			MHD_OPTION_END
		);
	}
#endif /* LOCAL */

	/* wait for all threads to terminate */
	// TODO bind signal for termination
	for (int i = 0; i < num_chans; i++) {
		channel_t * ch = &chans[i];

		pthread_join(ch->reading_thread, NULL);
		pthread_join(ch->logging_thread, NULL);

		// TODO close protocol handles

		free(ch->middleware);
		free(ch->uuid);
		free(ch->options);
		queue_free(&ch->queue);

		pthread_cond_destroy(&ch->condition);
		pthread_mutex_destroy(&ch->mutex);
	}

#ifdef LOCAL
	/* stop webserver */
	if (httpd_handle) {
		MHD_stop_daemon(httpd_handle);
	}
#endif /* LOCAL */

	return EXIT_SUCCESS;
}
