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
#include <unistd.h>

#include "api.h"
#include "main.h"

extern options_t opts;

/**
 * Reformat CURLs debugging output
 */
int curl_custom_debug_callback(CURL *curl, curl_infotype type, char *data, size_t size, void *ch) {
	char *end = strchr(data, '\n');
	
	if (data == end) return 0; /* skip empty line */
	
	switch (type) {
		case CURLINFO_TEXT:
		case CURLINFO_END:
			if (end) *end = '\0'; /* terminate without \n */
			print(3, "%.*s", (channel_t *) ch, (int) size, data);
			break;
			
		case CURLINFO_SSL_DATA_IN:
		case CURLINFO_DATA_IN:
			print(6, "Received %lu bytes", (channel_t *) ch, (unsigned long) size);
			break;
		
		case CURLINFO_SSL_DATA_OUT:
		case CURLINFO_DATA_OUT:
			print(6, "Sent %lu bytes.. ", (channel_t *) ch, (unsigned long) size);
			break;
			
		case CURLINFO_HEADER_IN:
		case CURLINFO_HEADER_OUT:
			break;
	}
	
	return 0;
}

size_t curl_custom_write_callback(void *ptr, size_t size, size_t nmemb, void *data) {
	size_t realsize = size * nmemb;
	CURLresponse *response = (CURLresponse *) data;
 
	response->data = realloc(response->data, response->size + realsize + 1);
	if (response->data == NULL) { /* out of memory! */ 
		print(-1, "Not enough memory", NULL);
		exit(EXIT_FAILURE);
	}
 
	memcpy(&(response->data[response->size]), ptr, realsize);
	response->size += realsize;
	response->data[response->size] = 0;
	
	print(1, "Addr: %lu", NULL, &(response->data));
 
	return realsize;
}

json_object * api_build_json(channel_t *ch) {
	reading_t rd;

	json_object *json_obj = json_object_new_object();
	json_object *json_tuples = json_object_new_array();
	
	for (int j = 0; j < ch->queue.size; j++) {
		queue_deque(&ch->queue, &rd);
		
		if (rd.tv.tv_sec) { /* skip empty readings */
			json_object *json_tuple = json_object_new_array();
			json_object_array_add(json_tuple, json_object_new_int(rd.tv.tv_sec * 1000 + rd.tv.tv_usec / 1000));
			json_object_array_add(json_tuple, json_object_new_double(rd.value));
			json_object_array_add(json_tuples, json_tuple);
		}
	}
	
	json_object_object_add(json_obj, "tuples", json_tuples);
	
	return json_obj;
}

CURL * api_curl_init(channel_t *ch) {
	CURL *curl;
	
	char buffer[255];

	curl = curl_easy_init();
	if (!curl) {
		print(-1, "Cannot create curl handle", ch);
		exit(EXIT_FAILURE);
	}
	
	sprintf(buffer, "%s/data/%s.json", ch->middleware, ch->uuid); /* build url */
	curl_easy_setopt(curl, CURLOPT_URL, buffer);
	
	sprintf(buffer, "vzlogger/%s (%s)", VZ_VERSION, curl_version()); /* build user agent */
	curl_easy_setopt(curl, CURLOPT_USERAGENT, buffer);
	
	curl_easy_setopt(curl, CURLOPT_VERBOSE, (int) opts.verbose);
	curl_easy_setopt(curl, CURLOPT_DEBUGFUNCTION, curl_custom_debug_callback);
	curl_easy_setopt(curl, CURLOPT_DEBUGDATA, (void *) ch);

	return curl;
}

char * api_parse_exception(CURLresponse response) {
	struct json_tokener * json_tok;
	struct json_object * json_obj;
	char *errstr;

	json_tok = json_tokener_new();
	json_obj = json_tokener_parse_ex(json_tok, response.data, response.size);
	if (json_tok->err == json_tokener_success) {
		json_obj = json_object_object_get(json_obj, "exception");
	
		if (json_obj) {
			errstr = json_object_get_string(json_object_object_get(json_obj,  "message"));
		}
		else {
			errstr = "missing exception";
		}
	}
	else {
		errstr = json_tokener_errors[json_tok->err];
	}
	
	json_tokener_free(json_tok);
	
	return errstr;
}


/**
 * Logging thread
 *
 * Logs buffered readings against middleware
 */
void *api_thread(void *arg) {
	CURL *curl;
	channel_t *ch = (channel_t *) arg; /* casting argument */
	
	print(1, "Started logging thread", ch);

	curl = api_curl_init(ch);	
	
	do { /* start thread mainloop */
		CURLresponse response;
		int curl_code, http_code;
		char *json_str;
		
		/* initialize response */
		response.data = NULL;
		response.size = 0;
	
		//pthread_mutex_lock(&ch->mutex);
		//while (queue_is_empty(&ch->queue)) { /* detect spurious wakeups */
		//	pthread_cond_wait(&ch->condition, &ch->mutex); /* sleep until new data has been read */
		//}
		//pthread_mutex_unlock(&ch->mutex);
		
		//if (opts.verbose > 5) queue_print(&ch->queue); /* Debugging */
		
		//pthread_mutex_lock(&ch->mutex);
		//json_str = json_object_to_json_string(api_build_json(ch));
		//pthread_mutex_unlock(&ch->mutex);
		
		//print(1, "JSON body: %s", ch, json_str);
		
		//curl_easy_setopt(curl, CURLOPT_POSTFIELDS, json_str);
		curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, curl_custom_write_callback);
		curl_easy_setopt(curl, CURLOPT_WRITEDATA, (void *) &response);
		
		curl_code = curl_easy_perform(curl);
		curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
		
		print(1, "Addr: %lu", ch, &(response.data));
		print(1, "Response: %s", ch, response.data);
		
		if (curl_code == CURLE_OK && http_code == 200) { /* everything is ok */
			print(1, "Request succeeded with code: %i", ch, http_code);
			
			// TODO clear queue
		}
		else { /* error */
			if (curl_code != CURLE_OK) {
				print(-1, "CURL failed: %s", ch, curl_easy_strerror(curl_code));
			}
			else if (http_code != 200) {
				print(-1, "Invalid middlware response: %s", ch, api_parse_exception(response));
			}
			
			print(2, "Sleeping %i seconds due to previous failure", ch, RETRY_PAUSE);
			sleep(RETRY_PAUSE);
		}

		/* householding */
		free(json_str);
		// TODO free json objects
		
		//if (response.data) free(response.data);
			
		pthread_testcancel(); /* test for cancelation request */
	} while (opts.daemon);
	
	curl_easy_cleanup(curl); /* always cleanup */
	
	return NULL;
}
