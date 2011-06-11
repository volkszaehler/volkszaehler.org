/**
 * OBIS protocol parser
 *
 * This is our example protocol. Use this skeleton to add your own
 * protocols and meters.
 *
 * @package vzlogger
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Mathias Dalheimer <md@gonium.net>
 * based heavily on libehz (https://github.com/gonium/libehz.git)
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

#include <stdlib.h>
#include <string.h>
#include <fcntl.h>
#include <unistd.h>

#include "obis.h"

void * obis_init(char *port) {
	struct termios tio;
	int *fd = malloc(sizeof(int));
	
	memset(&tio, 0, sizeof(tio));
	
	tio.c_iflag = 0;
	tio.c_oflag = 0;
	tio.c_cflag = CS7|CREAD|CLOCAL; // 7n1, see termios.h for more information
	tio.c_lflag = 0;
	tio.c_cc[VMIN] = 1;
	tio.c_cc[VTIME] = 5;

	*fd = open(port, O_RDWR); // | O_NONBLOCK);
	cfsetospeed(&tio, B9600); // 9600 baud
	cfsetispeed(&tio, B9600); // 9600 baud
	
	return (void *) fd;
}

void obis_close(void *handle) {
	int *fd = (int *) handle;
	
	close(*fd);
	free(handle);
}

reading_t obis_get(void *handle) {
	reading_t rd;
	
	rd.value = 33.3334;
	gettimeofday(&rd.tv, NULL);
	
	return rd;
}

