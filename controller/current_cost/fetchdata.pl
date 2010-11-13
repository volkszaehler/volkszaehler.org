#!/usr/bin/perl -w
# This program reads data from a Current Cost device via serial port and puts it into an RRD database (also prints on console)

#Avoid Line buffering for stdout. Avoids problems when sending the script output to a pipe
local $| = 1;

use strict;
use Device::SerialPort qw( :PARAM :STAT 0.07 );

#USB  (change into /dev/ttyS0 for Serial port)
my $PORT = "/dev/ttyUSB0";

my $ob = Device::SerialPort->new($PORT);

# change into appropriate baudrate. Envi classic needs $ob->baudrate(57600); 
$ob->baudrate(57600);
$ob->write_settings;

open(SERIAL, "+>$PORT");
while (my $line = <SERIAL>) {

# Envi White old 
#    if ($line =~ m!<ch1><watts>0*(\d+)</watts></ch1>.*<ch2><watts>0*(\d+)</watts></ch2>.*<ch3><watts>0*(\d+)</watts></ch3><tmpr> *([\-\d.]+)</tmpr>!) {

#Envi Black >CC128-v0.12 
 if ($line =~ m!<tmpr> *([\-\d.]+)</tmpr>.*<ch1><watts>0*(\d+)</watts></ch1>.*<ch2><watts>0*(\d+)</watts></ch2>.*<ch3><watts>0*(\d+)</watts></ch3>!) {
        my $watts1 = $2;
        my $watts2 = $3;
        my $watts3 = $4;
        my $temp = $1;
        print "$watts1, $watts2, $watts3, $temp\n";
        system("rrdupdate","/path/to/your/powertemp.rrd","N:$watts1:$watts2:$watts3:$temp");
    }
}
