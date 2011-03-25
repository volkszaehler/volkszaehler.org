/**
 * main source
 *
 * @package controller
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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
#include <getopt.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <unistd.h>

#include <sys/time.h>

#include <curl/curl.h>
#include <curl/types.h>
#include <curl/easy.h>

#include "main.h"
#include "ehz.h"

struct options opt;

static struct device devices[] = {
//	{"1wire",	"Dallas 1-Wire Sensors",		1wire_get},
//	{"ccost",	"CurrentCost",				ccost_get},
//	{"fusb",	"FluksoUSB prototype board",		fusb_get}
	{"ehz",		"German \"elektronische Heimzähler\"",	ehz_get},
	{NULL} /* stop condition for iterator */
};

static struct option long_options[] = {
	{"middleware",	required_argument,	0,	'm'},
	{"uuid",	required_argument,	0,	'u'},
	{"value",	required_argument,	0,	'v'},
	{"device",	required_argument,	0,	'd'},
	{"port",	required_argument,	0,	'p'},
//	{"config", 	required_argument,	0,	'c'},
	{"daemon", 	required_argument,	0,	'D'},
	{"interval", 	required_argument,	0,	'i'},
//	{"local", 	no_argument,		0,	'l'},
//	{"local-port",	required_argument,	0,	'p'},
	{"help",	no_argument,		0,	'h'},
	{"verbose",	no_argument,		0,	'v'},
	{NULL} /* stop condition for iterator */
};

static char * long_options_descs[] = {
	"url to middleware",
	"channel uuid",
	"sensor value or meter consumption to log",
	"device type",
	"port the device is connected to",
//	"config file with channel -> uuid mapping",
	"run as daemon",
	"interval in seconds to log data",
//	"activate local interface (tiny webserver)",
//	"TCP port for local interface"	
	"show this help",
	"enable verbose output",
	NULL /* stop condition for iterator */
};

void usage(char * argv[]) {
	char ** desc = long_options_descs;
	struct option * op = long_options;
	struct device * dev = devices;

	printf("Usage: %s [options]\n\n", argv[0]);
	printf("  following options are available:\n");
	
	while (op->name && desc) {
		printf("\t--%-12s\t-%c\t%s\n", op->name, op->val, *desc);
		op++;
		desc++;
	}
	
	printf("\n");
	printf("  following device types are available:\n");
	
	while (dev->name) {
		printf("\t%-12s\t%s\n", dev->name, dev->desc);
		dev++;
	}
	
	printf("\nvzlogger - volkszaehler.org logging utility VERSION\n");
	printf("by Steffen Vogel <volkszaehler@steffenvogel.de>\n");
}

CURLcode backend_log(char * middleware, char * uuid, struct timeval tv, float value) {
	CURL *curl;
	CURLcode res;

	char url[255], useragent[255], post[255];

	sprintf(url, "%s/data/%s.json", middleware, uuid); /* build url */
	sprintf(useragent, "vzlogger/%s (%s)", VZ_VERSION, curl_version());
	sprintf(post, "?timestamp=%lu%lu&value=%f", tv.tv_sec, tv.tv_usec, value);
 
	curl_global_init(CURL_GLOBAL_ALL);
 
	curl_formadd(&formpost,
		&lastptr,
		CURLFORM_COPYNAME, "value",
		CURLFORM_PTRCONTENTS , value_str,
		CURLFORM_END);
		
	curl_formadd(&formpost,
		&lastptr,
		CURLFORM_COPYNAME, "timestamp",
		CURLFORM_PTRCONTENTS , &timestamp,
		CURLFORM_END);
 
	curl = curl_easy_init();
	
	if (curl) {
		/* what URL that receives this POST */ 
		curl_easy_setopt(curl, CURLOPT_URL, url);
		curl_easy_setopt(curl, CURLOPT_HTTPPOST, formpost);
		curl_easy_setopt(curl, CURLOPT_USERAGENT, useragent);
		curl_easy_setopt(curl, CURLOPT_VERBOSE, (int) opt.verbose);

    		res = curl_easy_perform(curl);
    
		curl_easy_cleanup(curl); /* always cleanup */ 
		curl_formfree(formpost); /* then cleanup the formpost chain */
		
		return res;
	}
	
	return -1;
}

int main(int argc, char * argv[]) {
	/* setting default options */
	opt.interval = 300; /* 5 minutes */

	/* parse cli arguments */
 	while (1) {
		/* getopt_long stores the option index here. */
		int option_index = 0;

		int c = getopt_long(argc, argv, "i:m:u:v:t:p:c:hdv", long_options, &option_index);

		/* detect the end of the options. */
		if (c == -1)
			break;

		switch (c) {
			case 'v':
				opt.verbose = 1;
				break;
				
			case 'd':
				opt.daemon = 1;
				break;
				
			case 'i':
				opt.interval = atoi(optarg);
				break;
				
			case 'u':
				opt.uuid = (char *) malloc(strlen(optarg)+1);
				strcpy(opt.uuid, optarg);
				break;
				
			case 'm':
				opt.middleware = (char *) malloc(strlen(optarg)+1);
				strcpy(opt.middleware, optarg);
				break;
				
			case 'p':
				opt.port = (char *) malloc(strlen(optarg)+1);
				strcpy(opt.port, optarg);
				break;
				
			//case 'c': /* read config file */ 
			
			//	break;

			case 'h':
			case '?':
				usage(argv);
				exit((c == '?') ? -1 : 0);
		}
	}
	
	/* setup devices */
	
	struct timeval tv;
	
	
	log: /* start logging */
	
	gettimeofday(&tv, NULL);

	CURLcode rc = backend_log(opt.middleware, opt.uuid, tv, 33.333);
	
	if (rc != CURLE_OK) {
		fprintf(stderr, "curl error: %s\n", curl_easy_strerror(rc));
	} else if (opt.verbose) {
		fprintf(stdout, "logging %s against %s with value %f", opt.uuid, opt.middleware, 33.333);
	}
	
	if (opt.daemon) {
		sleep(opt.interval);
		goto log;
	}

	return 0;
}
