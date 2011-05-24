/**
 * Implementation of volkszaehler.org API calls
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

#include <stdlib.h>
#include <string.h>
#include <json/json.h>

#include "main.h"
#include "api.h"

extern struct options opts;

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
			//printf("header: %.*s", size, data);
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
	int curl_code;
	struct curl_response chunk = {NULL, 0};

	char url[255], useragent[255], post[255];
	
	/* build request strings */
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
