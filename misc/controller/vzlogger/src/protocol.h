#ifndef _PROTOCOL_H_
#define _PROTOCOL_H_

#include <sys/time.h>

typedef struct {
	float value;
	struct timeval tv;
} reading_t;

typedef void *(*ifp_t)(char *options);
typedef void (*cfp_t)(void *handle);
typedef reading_t (*rfp_t)(void *handle);

typedef enum {
	MODE_METER,
	MODE_SENSOR
} mode_t;

typedef struct {
	char * name;		/* short identifier for protocol */
	char * desc;		/* more detailed description */
	rfp_t read_func;	/* function pointer to read data */
	ifp_t init_func;	/* function pointer to init a channel */
	cfp_t close_func;	/* function pointer to close a channel */
	mode_t mode;		/* should we wait for next pulse? */
}  protocol_t;

#endif /* _PROTOCOL_H_ */
