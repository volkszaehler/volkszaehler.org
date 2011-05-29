/**
 * OBIS protocol parser
 *
 * 
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


#include "../main.h"
#include "../protocol.h"
#include "rawS0.h"

typedef struct {
	int fd; /* file descriptor of port */
	struct termios old_tio;
	struct termios tio;
} rawS0_state_t;

void * rawS0_init(char * port) {
	struct termios tio;
	int *fd = malloc(sizeof(int));
	
	memset(&tio, 0, sizeof(tio));
	
	tio.c_iflag = 0;
	tio.c_oflag = 0;
	tio.c_cflag = CS7|CREAD|CLOCAL; // 7n1, see termios.h for more information
	tio.c_lflag = 0;
	tio.c_cc[VMIN] = 1;
	tio.c_cc[VTIME] = 5;

	*fd = open(port, O_RDWR | O_NONBLOCK);
	cfsetospeed(&tio, B9600); // 9600 baud
	cfsetispeed(&tio, B9600); // 9600 baud
	
	return (void *) fd;
}

void obis_close(void *handle) {
	// TODO close serial port

	free(handle);
}

reading_t obis_get(void *handle) {
	reading_t rd;
	
	read();
	gettimeofday(&rd.tv, NULL);
	
	return rd;
}

