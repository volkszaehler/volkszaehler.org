CC=cc
CFLAGS=-c -Wall -g -D_REENTRANT -std=gnu99
LDFLAGS=
TARGET=vzlogger

all: $(TARGET)

clean:
	rm -rf *.o

vzlogger: main.c api.c local.c queue.c obis.c
	$(CC) $(LDFLAGS) main.o api.o local.o queue.o obis.o `curl-config --libs` -ljson -lpthread -o $(TARGET) -lmicrohttpd -lm

main.c:
	$(CC) $(CFLAGS) src/main.c -o main.o
	
api.c:
	$(CC) $(CFLAGS) src/api.c -o api.o `curl-config --cflags`

local.c:
	$(CC) $(CFLAGS) src/local.c -o local.o
	
	
queue.c:
	$(CC) $(CFLAGS) src/queue.c -o queue.o
	
obis.c:
	$(CC) $(CFLAGS) src/protocols/obis.c -o obis.o
