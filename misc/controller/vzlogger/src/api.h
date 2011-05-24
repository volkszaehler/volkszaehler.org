/**
 * Header file for volkszaehler.org API calls
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @package vzlogger
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

#ifndef _API_H_
#define _API_H_

#include <curl/curl.h>
#include <curl/types.h>
#include <curl/easy.h>

#define BUFFER_LENGTH 64

typedef struct reading (*rfp)();
typedef void (*ifp)(char *options);

struct curl_response {
	char *data;
	size_t size;
};

struct reading {
	float value;
	struct timeval tv;
};

struct protocol {
	char * name;	/* short identifier for protocol */
	char * desc;	/* more detailed description */
	rfp read_func;	/* function pointer to read data */
	ifp init_func;	/* function to init a channel */
};

/**
 * Datatype for every channel
 */
struct channel {
	char * uuid;
	char * middleware;
	int interval;
	char * options;
	struct protocol *prot;
	struct reading buffer[BUFFER_LENGTH]; /* ring buffer */
};

/**
 * Options from command line
 */
struct options {
	char * config; /* path to config file */
	unsigned int interval; /* seconds */

	/* boolean bitfields, at the end of struct */
	unsigned int verbose:1;	
	unsigned int daemon:1;
//	unsigned local:1;	/* enable local interface */	
};

/* Prototypes */
void usage(char ** argv);
struct options parse_options(int argc, char * argv[]);
//struct channels parse_channels(char * filename);

int curl_custom_debug_callback(CURL *curl, curl_infotype type, char *data, size_t size, void *custom);
size_t curl_custom_write_callback(void *ptr, size_t size, size_t nmemb, void *data);
CURLcode api_log(char * middleware, char * uuid, struct reading read);

#endif /* _API_H_ */
