/**
 * main source
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
 
#include <stdio.h>
#include <getopt.h>
#include <stdlib.h>
#include <stdint.h>

#include "main.h"
#include "ehz.h"

static struct type types[] = {
//	{"1wire", "Dallas 1-Wire Sensors", 1wire_get},
	{"ehz", "German \"elektronische Heimzähler\"", ehz_get}
//	{"ccost", "CurrentCost", ccost_get},
//	{"fusb", "FluksoUSB prototype board", fusb_get}
};

static struct option long_options[] = {
	{"middleware",	required_argument,	0,		'm'},
	{"uuid",	required_argument,	0,		'u'},
	{"value",	required_argument,	0,		'v'},
	{"type",	required_argument,	0,		't'},
	{"device",	required_argument,	0,		'd'},
	{"config", 	required_argument,	0,		'c'},
	//{"daemon", 	required_argument,	0,		'D'},
	//{"interval", 	required_argument,	0,		'i'},
	//{"local", 	no_argument,		0,		'l'},
	//{"local-port",required_argument,	0,		'p'},
	{"help",	no_argument,		0,		'h'},
	{"verbose",	no_argument,		0,		'v'},
	{0}  /* stop condition for iterator */
};

void usage(char ** argv) {
	printf("usage: %s [options]\n\n", argv[0]);

	printf("\n");
	printf("  following options are available\n");

	struct option * op = long_options;
	while (op->name) {
		printf("\t--%s,\t-%c\n", op->name, op->val);
		op++;
	}
}


int main(int argc, char * argv[]) {
	uint8_t verbose = 0;

	/* parse cli arguments */
 	while (1) {
		/* getopt_long stores the option index here. */
		int option_index = 0;

		int c = getopt_long(argc, argv, "m:u:v:t:d:c:hv", long_options, &option_index);

		/* detect the end of the options. */
		if (c == -1)
			break;

		switch (c) {
			case 'v':
				verbose = 1;
				break;

			case 'h':
			case '?':
				usage(argv);
				exit((c == '?') ? -1 : 0);
		}
	}

	return 0;
}
