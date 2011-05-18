/**
 * main source
 *
 * @package controller
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
#include <getopt.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <unistd.h>

#include <sys/time.h>

#include <json/json.h>

#include <curl/curl.h>
#include <curl/types.h>
#include <curl/easy.h>

#include "main.h"

#include "protocols/obis.h"

static struct device devices[] = {
//	{"1wire",	"Dallas 1-Wire Sensors",		1wire_get},
//	{"ccost",	"CurrentCost",				ccost_get},
//	{"fusb",	"FluksoUSB prototype board",		fusb_get},
	{"obis",	"Plaintext OBIS",			obis_get},
	{NULL} /* stop condition for iterator */
};

static struct option long_options[] = {
	{"middleware",	required_argument,	0,	'm'},
	{"uuid",	required_argument,	0,	'u'},
	{"value",	required_argument,	0,	'V'},
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

/* globals */
struct options opts;

/**
 * Print availble options and some other usefull information
 */
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
	printf("by Steffen Vogel <stv0g@0l.de>\n");
}

/**
 * Parse options from command line
 */
struct options parse_options(int argc, char * argv[]) {
	struct options opts;
	
	/* setting default options */
	opts.interval = 300;
	opts.verbose = 0;
	opts.daemon = 0; 

	while (1) {
		/* getopt_long stores the option index here. */
		int option_index = 0;

		int c = getopt_long(argc, argv, "i:m:u:V:t:p:c:hdv", long_options, &option_index);

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

			case 'i':
				opts.interval = atoi(optarg);
				break;

			case 'u':
				opts.uuid = (char *) malloc(strlen(optarg)+1);
				strcpy(opts.uuid, optarg);
				break;

			case 'm':
				opts.middleware = (char *) malloc(strlen(optarg)+1);
				strcpy(opts.middleware, optarg);
				break;

			case 'p':
				opts.port = (char *) malloc(strlen(optarg)+1);
				strcpy(opts.port, optarg);
				break;

			//case 'c': /* read config file */
				// break;

			case 'h':
			case '?':
				usage(argv);
				exit((c == '?') ? EXIT_FAILURE : EXIT_SUCCESS);
		}
	}
	
	return opts;
}

/**
 * Reformat CURLs debugging output
 */
int curl_custom_debug_callback(CURL *curl, curl_infotype type, char *data, size_t size, void *custom) {
	switch (type) {
		case CURLINFO_TEXT:
		case CURLINFO_END:
			printf("%.*s", (int) size, data);
			break;
			
		case CURLINFO_HEADER_IN:
		case CURLINFO_HEADER_OUT:
			//printf("header in: %.*s", size, data);
			break;
		
		case CURLINFO_SSL_DATA_IN:
		case CURLINFO_DATA_IN:
			printf("Received %lu bytes\n", (unsigned long) size);
			break;
		
		case CURLINFO_SSL_DATA_OUT:
		case CURLINFO_DATA_OUT:
			printf("Sent %lu bytes.. ", (unsigned long) size);
			break;
	}
	
	return 0;
}

size_t curl_custom_write_callback(void *ptr, size_t size, size_t nmemb, void *data) {
	size_t realsize = size * nmemb;
	struct curl_response *response = (struct curl_response *) data;
 
	response->data = realloc(response->data, response->size + realsize + 1);
	if (response->data == NULL) { /* out of memory! */ 
		fprintf(stderr, "Not enough memory (realloc returned NULL)\n");
		exit(EXIT_FAILURE);
	}
 
	memcpy(&(response->data[response->size]), ptr, realsize);
	response->size += realsize;
	response->data[response->size] = 0;
 
	return realsize;
}

/**
 * Log against the vz.org middleware with simple HTTP requests via CURL
 */
CURLcode api_log(char * middleware, char * uuid, struct reading read) {
	CURL *curl;
	CURLcode rc = -1;

	char url[255], useragent[255], post[255];
	int curl_code;
	struct curl_response chunk = {NULL, 0};
	
	sprintf(url, "%s/data/%s.json", middleware, uuid); /* build url */
	sprintf(useragent, "vzlogger/%s (%s)", VZ_VERSION, curl_version());
	sprintf(post, "?timestamp=%lu%lu&value=%f", read.tv.tv_sec, read.tv.tv_usec, read.value);
 
	curl_global_init(CURL_GLOBAL_ALL);
 
	curl = curl_easy_init();
	
	if (curl) {
		curl_easy_setopt(curl, CURLOPT_URL, url);
		curl_easy_setopt(curl, CURLOPT_POSTFIELDS, post);
		curl_easy_setopt(curl, CURLOPT_USERAGENT, useragent);
		curl_easy_setopt(curl, CURLOPT_VERBOSE, (int) opts.verbose);
		curl_easy_setopt(curl, CURLOPT_DEBUGFUNCTION, curl_custom_debug_callback);
		curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, curl_custom_write_callback);
		curl_easy_setopt(curl, CURLOPT_WRITEDATA, (void *) &chunk);

		if (opts.verbose) printf("Sending request: %s%s\n", url, post);

    		rc = curl_easy_perform(curl);
    		
    		curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &curl_code);
    		
    		if (opts.verbose) printf("Request %s with code: %i\n", (curl_code == 200) ? "succeded" : "failed", curl_code);
    		
    		if (rc != CURLE_OK) {
			fprintf(stderr, "CURL error: %s\n", curl_easy_strerror(rc));
		}
    		else if (chunk.size == 0 || chunk.data == NULL) {
			fprintf(stderr, "No data received!\n");
			rc = -1;
		}
		else if (curl_code != 200) { /* parse exception */
	    		struct json_tokener * json_tok;
			struct json_object * json_obj;
	
	    		json_tok = json_tokener_new();
			json_obj = json_tokener_parse_ex(json_tok, chunk.data, chunk.size);

			if (json_tok->err == json_tokener_success) {
				json_obj = json_object_object_get(json_obj, "exception");
			
				if (json_obj) {
					fprintf(stderr, "%s [%i]: %s\n",
						json_object_get_string(json_object_object_get(json_obj,  "type")),
						json_object_get_int(json_object_object_get(json_obj,  "code")),
						json_object_get_string(json_object_object_get(json_obj,  "message"))
					);
				}
				else {
					fprintf(stderr, "Malformed middleware response: missing exception\n");
				}
			}
			else {
				fprintf(stderr, "Malformed middleware response: %s\n", json_tokener_errors[json_tok->err]);
			}
			
			rc = -1;
		}
    
		curl_easy_cleanup(curl); /* always cleanup */
		free(chunk.data); /* free response */
	}
	else {
		fprintf(stderr, "Failed to create CURL handle\n");
	}
	
	return rc;
}

/**
 * The main loop
 */
int main(int argc, char * argv[]) {
	opts = parse_options(argc, argv); /* parse command line arguments */
	
	struct reading rd;
	
	log: /* start logging */
	
	rd.value = 33.333;
	gettimeofday(&rd.tv, NULL);

	CURLcode rc = api_log(opts.middleware, opts.uuid, rd);
	
	if (rc != CURLE_OK) {
		if (opts.verbose) printf("Delaying next transmission for 15 minutes due to pervious error\n");
		sleep(15*60);
	}
	
	if (opts.daemon) {
		if (opts.verbose) printf("Sleeping %i seconds for next transmission\n", opts.interval);
		sleep(opts.interval);
		goto log;
	}

	return 0;
}


