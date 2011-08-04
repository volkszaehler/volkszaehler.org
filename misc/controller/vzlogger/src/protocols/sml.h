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
 
#ifndef _SML_H_
#define _SML_H_

#include "../protocol.h"

typedef struct {
	int fd;
	// termios etc..
} sml_state_t;

void * sml_init(char *port);
void sml_close(void *handle);
reading_t sml_get(void *handle);
int sml_open_port(char *device);
void sml_transport_receiver(unsigned char *buffer, size_t buffer_len);

#endif /* _SML_H_ */
