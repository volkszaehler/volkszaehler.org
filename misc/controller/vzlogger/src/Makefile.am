# what flags you want to pass to the C compiler & linker
AM_CFLAGS = -Wall -D_REENTRANT -std=gnu99 $(DEPS_CFLAGS)
AM_LDFLAGS =

# this lists the binaries to produce, the (non-PHONY, binary) targets in
# the previous manual Makefile
bin_PROGRAMS = vzlogger

vzlogger_SOURCES = main.c api.c queue.c ltqnorm.c protocols/1wire.c protocols/obis.c protocols/rawS0.c protocols/random.c
vzlogger_LDADD = -lpthread -lm $(DEPS_LIBS)

if LOCAL
vzlogger_SOURCES += local.c
vzlogger_LDADD += $(DEPS_LOCAL_LIBS)
AM_CFLAGS += $(DEPS_LOCAL_CFLAGS)
endif
