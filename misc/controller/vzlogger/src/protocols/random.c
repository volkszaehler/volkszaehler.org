/**
 * Generate pseudo random data series with a random walk
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
 
#include <stdio.h>
#include <stdlib.h>
#include <math.h>

#include "../main.h"
#include "../protocol.h"
#include "random.h"

/**
 * Initialize prng
 * @return random_state_t
 */
void * random_init(char *options) {
	random_state_t *state;
	state = malloc(sizeof(random_state_t));

	srand(time(NULL));
	
	state->min = 0; // TODO parse from options
	state->max = strtof(options, NULL);
	state->last = state->max * ((float) rand() / RAND_MAX); /* start value */
	
	return (void *) state;
}

void random_close(void *handle) {
	free(handle);
}

reading_t random_get(void *handle) {
	random_state_t *state = (random_state_t *) handle;
	reading_t rd;
	
	state->last += ltqnorm((float) rand() / RAND_MAX);
	
	/* check bounaries */
	if (state->last > state->max) {
		state->last = state->max;
	}
	else if (state->last < state->min) {
		state->last = state->min;
	}
	
	rd.value = state->last;
	gettimeofday(&rd.tv, NULL);
	
	return rd;
}
