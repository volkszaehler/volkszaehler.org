CC=cc
CFLAGS=-c -Wall -g
LDFLAGS=
TARGET=vzlogger

all: $(TARGET)

clean:
	rm -rf *.o

vzlogger: main.c obis.c
	$(CC) $(LDFLAGS) main.o obis.o `curl-config --libs` -l json -o $(TARGET)

main.c:
	$(CC) $(CFLAGS) src/main.c -o main.o `curl-config --cflags`

obis.c:
	$(CC) $(CFLAGS) src/protocols/obis.c -o obis.o
