/**
 * main header
 *
 * @package controller
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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
 
#ifndef _MAIN_H_
#define _MAIN_H_

typedef float (*rfp)();

struct device {
	char * name;
	char * desc;
	rfp read_fnct;
};

struct options {
	char * uuid;		/* universal unique channel identifier */
	char * middleware;	/* url to middleware server */
	char * port;		/* port your sensor is connected to */
	unsigned interval;	/* interval in seconds, the daemon send data */
	unsigned verbose:1;	/* boolean bitfield, at the end of struct */
	unsigned daemon:1;	/* boolean bitfield */
};

void usage(char ** argv);

#endif /* _MAIN_H_ */
