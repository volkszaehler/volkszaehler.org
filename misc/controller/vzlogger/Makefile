CC=cc
CFLAGS=-c -Wall -g
LDFLAGS=
TARGET=vzlogger

all: $(TARGET)

clean:
	rm -rf *.o

vzlogger: main.c ehz.c
	$(CC) $(LDFLAGS) main.o ehz.o `curl-config --libs` -o $(TARGET)

main.c:
	$(CC) $(CFLAGS) src/main.c -o main.o `curl-config --cflags`

ehz.c:
	$(CC) $(CFLAGS) src/ehz.c -o ehz.o
