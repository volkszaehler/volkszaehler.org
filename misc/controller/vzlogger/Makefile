CC=cc
CFLAGS=-c -Wall
LDFLAGS=
TARGET=vzlogger

all: $(TARGET)

clean:
	rm -rf *.o

vzlogger: main.c ehz.c
	$(CC) $(LDFLAGS) main.o ehz.o -o $(TARGET)

main.c:
	$(CC) $(CFLAGS) src/main.c -o main.o

ehz.c:
	$(CC) $(CFLAGS) src/ehz.c -o ehz.o
