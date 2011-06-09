/**
 * S0 Hutschienenzähler directly connected to an rs232 port
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

#include <fcntl.h>
#include <termios.h> 
#include <unistd.h>
#include <stdlib.h>
#include <string.h>

#include "../main.h"
#include "../protocol.h"
#include "rawS0.h"

typedef struct {
	int fd; /* file descriptor of port */
	struct termios oldtio; /* required to reset port */
} rawS0_state_t;

/**
 * Setup serial port
 */
void * rawS0_init(char * port) {
	rawS0_state_t *state;
	struct termios newtio;
	
	/* initialize handle */
	state = malloc(sizeof(rawS0_state_t));
	
	state->fd = open(port, O_RDWR | O_NOCTTY); 
        if (state->fd < 0) {
        	char err[255];
        	strerror_r(errno, err, 255);
		print(-1, "%s: %s", NULL, port, err);
        	exit(EXIT_FAILURE);
        }
	
	tcgetattr(state->fd, &state->oldtio); /* save current port settings */


	/* configure port */
	memset(&newtio, 0, sizeof(struct termios));
	
	newtio.c_cflag = B300 | CS8 | CLOCAL | CREAD;
        newtio.c_iflag = IGNPAR;
        newtio.c_oflag = 0;
        newtio.c_lflag = 0; /* set input mode (non-canonical, no echo,...) */        
        newtio.c_cc[VTIME] = 0;	/* inter-character timer unused */
        newtio.c_cc[VMIN] = 1; 	/* blocking read until data is received */
        
        /* apply configuration */
        tcsetattr(state->fd, TCSANOW, &newtio);
        
	return (void *) state;
}

void rawS0_close(void *handle) {
	rawS0_state_t *state = (rawS0_state_t *) handle;

	tcsetattr(state->fd, TCSANOW, &state->oldtio);

	close(state->fd);
	free(handle);
}

reading_t rawS0_get(void *handle) {
	char buf[255];
	
	rawS0_state_t *state = (rawS0_state_t *) handle;
	reading_t rd;
	
	rd.value = 1;
	
        tcflush(state->fd, TCIOFLUSH);
	
	read(state->fd, buf, 255); /* blocking until one character/pulse is read */
	gettimeofday(&rd.tv, NULL);
	
	/* wait some ms for debouncing */
	usleep(30000);
	
	return rd;
}

