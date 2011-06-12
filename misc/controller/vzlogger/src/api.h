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

#include <stddef.h>
#include <curl/curl.h>
#include <json/json.h>

#include "main.h"
#include "protocol.h"

typedef struct {
	char *data;
	size_t size;
} CURLresponse;

/* curl callbacks */
int curl_custom_debug_callback(CURL *curl, curl_infotype type, char *data, size_t size, void *custom);
size_t curl_custom_write_callback(void *ptr, size_t size, size_t nmemb, void *data);

json_object * api_json_tuples(channel_t *ch, bool_t all);
void * api_thread(void *arg);

#endif /* _API_H_ */
