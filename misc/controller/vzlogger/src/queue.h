/**
 * Circular queue to buffer readings
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
 
#ifndef _QUEUE_H_
#define _QUEUE_H_

#include "protocol.h"

#ifndef TRUE
#define TRUE 1
#endif

#ifndef FALSE
#define FALSE 0
#endif

typedef char bool_t;

typedef struct {
	size_t size;
	
	int read_p;
	int write_p;
	int fill_count;
	
	reading_t *buf;
} queue_t;

queue_t * queue_init(queue_t *q, size_t size);
bool_t queue_is_empty(queue_t *q);
void queue_push(queue_t *q, reading_t rd);
void queue_clear(queue_t *q);
void queue_free(queue_t *q);
void queue_print(queue_t *q);


#endif /* _QUEUE_H_ */

