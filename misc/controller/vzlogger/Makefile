CC=cc
CFLAGS=-c -Wall -g -D_REENTRANT -std=c99
LDFLAGS=
TARGET=vzlogger

all: $(TARGET)

clean:
	rm -rf *.o

vzlogger: main.c obis.c api.c
	$(CC) $(LDFLAGS) main.o obis.o api.o `curl-config --libs` -ljson -lpthread -o $(TARGET)

main.c:
	$(CC) $(CFLAGS) src/main.c -o main.o
	
api.c:
	$(CC) $(CFLAGS) src/api.c -o api.o `curl-config --cflags`

obis.c:
	$(CC) $(CFLAGS) src/protocols/obis.c -o obis.o
