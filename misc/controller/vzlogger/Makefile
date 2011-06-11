CC=cc
CFLAGS=-c -Wall -g -D_REENTRANT -std=gnu99
LDFLAGS=
TARGET=vzlogger

all: $(TARGET)

clean:
	rm -rf *.o $(TARGET)

vzlogger: main.c api.c local.c queue.c 1wire.c obis.c rawS0.c random.c ltqnorm.c
	$(CC) $(LDFLAGS) main.o api.o local.o queue.o 1wire.o obis.o rawS0.o random.o ltqnorm.o `curl-config --libs` -ljson -lpthread -o $(TARGET) -lmicrohttpd -lm

main.c:
	$(CC) $(CFLAGS) src/main.c -o main.o
	
api.c:
	$(CC) $(CFLAGS) src/api.c -o api.o `curl-config --cflags`

local.c:
	$(CC) $(CFLAGS) src/local.c -o local.o
	
queue.c:
	$(CC) $(CFLAGS) src/queue.c -o queue.o

1wire.c:
	$(CC) $(CFLAGS) src/protocols/1wire.c -o 1wire.o
	
obis.c:
	$(CC) $(CFLAGS) src/protocols/obis.c -o obis.o
	
rawS0.c:
	$(CC) $(CFLAGS) src/protocols/rawS0.c -o rawS0.o
	
random.c:
	$(CC) $(CFLAGS) src/protocols/random.c -o random.o

ltqnorm.c:
	$(CC) $(CFLAGS) src/protocols/ltqnorm.c -o ltqnorm.o
