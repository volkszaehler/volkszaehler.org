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
	else {
		return FALSE; /* cannot allocate memory */
	}
}

bool_t queue_is_full(queue_t *q) {
	return (((q->write_p + 1) % q->size) == q->read_p);
}
 
bool_t queue_is_empty(queue_t *q) {
	return (q->read_p == q->write_p);
}
 
bool_t queue_enque(queue_t *q, reading_t rd) {
	q->buf[q->write_p] = rd;
	q->write_p++;
	q->write_p %= q->size;

        return !queue_is_full(q);
}
 
bool_t queue_deque(queue_t *q, reading_t *rd) {
	*rd = q->buf[q->read_p];
	q->read_p++;
	q->read_p %= q->size;

	return !queue_is_empty(q);
}

size_t queue_size(queue_t *q) {
	return q->write_p - q->read_p + (q->read_p > q->write_p) * q->size;
}

bool_t queue_first(queue_t *q, reading_t *rd) {
	*rd = q->buf[q->read_p];
	
	return !queue_is_empty(q);
}

void queue_print(queue_t *q) {
	printf("Queue dump: [%.1f", q->buf[0].value);
	for (int i = 1; i < q->size; i++) {
		printf("|%.1f", q->buf[i].value);
	}
	printf("]\n");
}

void queue_free(queue_t *q) {
	free(q->buf);
}
