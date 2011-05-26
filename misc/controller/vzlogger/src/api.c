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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <json/json.h>

#include "api.h"
#include "main.h"

extern options_t opts;

/**
 * Reformat CURLs debugging output
 */
int curl_custom_debug_callback(CURL *curl, curl_infotype type, char *data, size_t size, void *custom) {
	switch (type) {
		case CURLINFO_TEXT:
		case CURLINFO_END:
			print(4, "%.*s", NULL, (int) size, data);
			break;
			
		case CURLINFO_HEADER_IN:
		case CURLINFO_HEADER_OUT:
			//print(4, "Header: %.*s", NULL, size, data);
			break;
		
		case CURLINFO_SSL_DATA_IN:
		case CURLINFO_DATA_IN:
			print(4, "Received %lu bytes\n", NULL, (unsigned long) size);
			break;
		
		case CURLINFO_SSL_DATA_OUT:
		case CURLINFO_DATA_OUT:
			print(4, "Sent %lu bytes.. ", NULL, (unsigned long) size);
			break;
	}
	
	return 0;
}

size_t curl_custom_write_callback(void *ptr, size_t size, size_t nmemb, void *data) {
	size_t realsize = size * nmemb;
	curl_response_t *response = (curl_response_t *) data;
 
	response->data = realloc(response->data, response->size + realsize + 1);
	if (response->data == NULL) { /* out of memory! */ 
		print(-1, "Not enough memory (realloc returned NULL)\n", NULL);
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
CURLcode api_log(channel_t *ch, reading_t rd) {
	CURL *curl;
	CURLcode rc = -1;
	int curl_code;
	curl_response_t chunk = {NULL, 0};

	char url[255], useragent[255], post[255];
	
	/* build request strings */
	sprintf(url, "%s/data/%s.json", ch->middleware, ch->uuid); /* build url */
	sprintf(useragent, "vzlogger/%s (%s)", VZ_VERSION, curl_version());
	sprintf(post, "?timestamp=%lu%lu&value=%f", rd.tv.tv_sec, rd.tv.tv_usec / 1000, rd.value);
 
	curl = curl_easy_init();
	
	if (curl) {
		curl_easy_setopt(curl, CURLOPT_URL, url);
		curl_easy_setopt(curl, CURLOPT_POSTFIELDS, post);
		curl_easy_setopt(curl, CURLOPT_USERAGENT, useragent);
		curl_easy_setopt(curl, CURLOPT_VERBOSE, (int) opts.verbose);
		curl_easy_setopt(curl, CURLOPT_DEBUGFUNCTION, curl_custom_debug_callback);
		curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, curl_custom_write_callback);
		curl_easy_setopt(curl, CURLOPT_WRITEDATA, (void *) &chunk);

		print(1, "Sending request: %s%s", ch, url, post);

    		rc = curl_easy_perform(curl);
    		
    		curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &curl_code);
    		
    		print((curl_code == 200) ? 1 : -1, "Request %s with code: %i", ch, (curl_code == 200) ? "succeded" : "failed", curl_code);
    		
    		if (rc != CURLE_OK) {
			print(-1, "CURL error: %s", ch, curl_easy_strerror(rc));
		}
    		else if (chunk.size == 0 || chunk.data == NULL) {
			print(-1, "No data received!", ch);
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
					print(-1, "%s : %s", ch,
						json_object_get_string(json_object_object_get(json_obj,  "type")),
						json_object_get_string(json_object_object_get(json_obj,  "message"))
					);
				}
				else {
					print(-1, "Malformed middleware response: missing exception", ch);
				}
			}
			else {
				print(-1, "Malformed middleware response: %s", ch, json_tokener_errors[json_tok->err]);
			}
			
			rc = -1;
		}
    
		curl_easy_cleanup(curl); /* always cleanup */
		free(chunk.data); /* free response */
	}
	else {
		print(-1, "Failed to create CURL handle", ch);
	}
	
	return rc;
}
