/**
 * Main header file
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

#ifndef _MAIN_H_
#define _MAIN_H_

#include <sys/time.h>
#include <pthread.h>

#include "protocol.h"
#include "queue.h"

#define VZ_VERSION "0.2"
#define MAX_CHANNELS 16

#define RETRY_PAUSE 16		/* seconds to wait after failed request */
#define BUFFER_LENGTH 25	/* in seconds */

#ifndef TRUE
#define TRUE 1
#endif

#ifndef FALSE
#define FALSE 0
#endif

/**
 * Datatype for every channel
 */
typedef struct {
	char * middleware;
	char * uuid;
	unsigned int interval;
	char * options;
	
	int id;				/* only for internal usage & debugging */
	
	queue_t queue;			/* circular queue to buffer readings */
	protocol_t *prot;		/* pointer to protocol */
	void * handle;			/* handle to store connection status */
	
	pthread_t reading_thread;	/* pthread for asynchronus reading */
	pthread_t logging_thread;	/* pthread for asynchronus logging */
	pthread_mutex_t mutex;
	pthread_cond_t condition;
} channel_t;

/**
 * Options from command line
 */
typedef struct {
	char * config;		/* path to config file */
	unsigned int port;	/* tcp port for local interface */
	unsigned int verbose;	/* verbosity level */

	/* boolean bitfields, at the end of struct */
	int daemon:1;
	int local:1;		/* enable local interface */	
} options_t;

/* Prototypes */
options_t parse_options(int argc, char * argv[]);
channel_t * parse_channels(char * filename, int * num_chans);
void print(int level, char * format, channel_t *ch, ... );
void usage(char ** argv);

#endif /* _MAIN_H_ */
