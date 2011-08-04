/**
 * Wrapper around libsml
 *
 * @package vzlogger
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @copyright Copyright (c) 2011, DAI-Labor, TU-Berlin
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Juri Glass
 * @author Mathias Runge
 * @author Nadim El Sayed 
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
#include <fcntl.h>
#include <unistd.h>
#include <errno.h>
#include <termios.h>
#include <stdlib.h>
#include <string.h>
#include <sys/ioctl.h>

#include <sml/sml_file.h>
#include <sml/sml_transport.h>

int sml_init(char *device) {
	sml_state_t *state;
	
	/* initialize handle */
	state = malloc(sizeof(sml_state_t));

	/* this example assumes that a EDL21 meter sending SML messages via a
	 * serial device. Adjust as needed. */
	int fd = serial_port_open(device);
	
	if (fd > 0) {
		// start thread
	
		/* listen on the serial device, this call is blocking */
		sml_transport_listen(fd, &transport_receiver);
		close(fd);
	}	
}

void sml_close(void *handle) {

}

reading_t sml_get(void *handle) {

}

void sml_transport_receiver(unsigned char *buffer, size_t buffer_len) {
	// the buffer contains the whole message, with transport escape sequences.
	// these escape sequences are stripped here. 
	sml_file *file = sml_file_parse(buffer + 8, buffer_len - 16);
	// the sml file is parsed now
	
	// read here some values ..
	
	// this prints some information about the file
	sml_file_print(file);
	
	// free the malloc'd memory
	sml_file_free(file);
}

int sml_open_port(char *device) {
	int bits;
	struct termios config;
	memset(&config, 0, sizeof(config));
	
	int fd = open(device, O_RDWR | O_NOCTTY | O_NDELAY);
	if (fd < 0) {
		printf("error: open(%s): %s\n", device, strerror(errno));
		return -1;
	}
	
	// set RTS
	ioctl(fd, TIOCMGET, &bits);
	bits |= TIOCM_RTS;
	ioctl(fd, TIOCMSET, &bits);
     
	tcgetattr( fd, &config ) ;
	
	// set 8-N-1
	config.c_iflag &= ~(IGNBRK | BRKINT | PARMRK | ISTRIP | INLCR | IGNCR | ICRNL | IXON);
	config.c_oflag &= ~OPOST;
	config.c_lflag &= ~(ECHO | ECHONL | ICANON | ISIG | IEXTEN);
	config.c_cflag &= ~(CSIZE | PARENB | PARODD | CSTOPB);
	config.c_cflag |= CS8;

	// set speed to 9600 baud
	cfsetispeed( &config, B9600);
	cfsetospeed( &config, B9600);
	
	tcsetattr(fd, TCSANOW, &config);
	return fd;
}
