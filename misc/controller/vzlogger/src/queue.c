#include <stdlib.h>
#include <stdio.h>

#include "queue.h"

queue_t * queue_init(queue_t *q, size_t size) {
	q->buf = malloc(sizeof(reading_t) * size); /* keep one slot open */

	if (q->buf) {
		q->size = size;
		q->read_p = q->write_p = 0; /* queue is empty */
		
		return q;
	}
	else { /* cannot allocat memory */
		return NULL;
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

void queue_push(queue_t *q, reading_t rd) {
	q->buf[q->write_p] = rd;
	q->write_p++;
	q->write_p %= q->size;
}
 
void queue_print(queue_t *q) {
	printf("Queue dump: [%.1f", q->buf[0].value);
	for (int i = 1; i < q->size; i++) {
		printf("|%.2f", q->buf[i].value);
	}
	printf("] write_p = %i\t read_p = %i\n", q->write_p, q->read_p);
}
