#include <stdlib.h>
#include <stdio.h>

#include "queue.h"

bool_t queue_init(queue_t *q, size_t size) {
	q->buf = malloc(sizeof(reading_t) * size); /* keep one slot open */

	if (q->buf) {
		q->size = size;
		q->read_p = q->write_p = 0; /* queue is empty */
		
		return TRUE;
	}
	else { /* cannot allocate memory */
		return FALSE;
	}
}

bool_t queue_is_empty(queue_t *q) {
	return (q->read_p == q->write_p);
}

void queue_free(queue_t *q) {
	queue_clear(q);
	free(q->buf);
}

void queue_clear(queue_t *q) {
	q->read_p = q->write_p;
}

bool_t queue_push(queue_t *q, reading_t rd) {
	q->buf[q->write_p] = rd; /* copy data to buffer */
	q->write_p++; /* increment write pointer */
	q->write_p %= q->size;
	
	if (q->read_p == q->write_p) { /* queue full */
		q->read_p++; /* discarding first tuple */
		return FALSE;
	}
	
	return TRUE;
}

bool_t queue_get(queue_t *q, size_t index, reading_t *rd) {
	*rd = q->buf[index];
	
	return (index < q->size);
}
 
char * queue_print(queue_t *q) {
	char *buf = malloc(q->size * 6);
	char *ret = buf;

	buf += sprintf(buf, "[%.1f", q->buf[0].value);
	for (int i = 1; i < q->size; i++) {
		buf += sprintf(buf, "|%.2f", q->buf[i].value);
	}
	buf += sprintf(buf, "]");
	
	return ret;
}
