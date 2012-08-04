#!/usr/bin/perl -w
#Designed to get all temperature values from Onewire Sensors connected to a microcontroller running ethersex and send these to your Volkszaehler server.
# add it with crontab -e
# */5 * * * * /usr/local/bin/vz/1wVZ.pl >>/var/log/1wVZ.log 2>&1

use strict;
#use diagnostics;
use 5.010;
use Net::Telnet ();
require LWP::UserAgent;
use HTTP::Request::Common;

#configuration start
my $esexip   = "<host>";			#ip or hostname for ethersex
my $esexport = "2701";				#ECMD port
my $url      = "http://<your volkszaehler domain>/volkszaehler/middleware.php"; #url to volkszaehler middleware
my $uname    = "<username>";			#username for basic-auth from apache
my $password = "<password>";			#password
my $timeout  = 10;				#timeout for all in seconds
my $debug    = $ENV{debug} // 0;
# 0: normally no output
# 1: only sensor values
# 2: all
# you can use export debug=2 before executing the perl script to get verbose output.

#Temperature Sensors
my @DS18S20 = (
	[ '<first sensor description>', '<sensor id>', '<uuid>' ],
	[ '<second sensor description>', '<sensor id>', '<uuid>' ],
	[ '<last sensor description>', '<sensor id>', '<uuid>' ],
#	[ '', '', '' ],
);
#configuration end

my ($esex, @sensor, $sensor, $temp);

$esex = Net::Telnet->new || die "Fail with Net::Telnet";
$esex->open(
	Host	=> $esexip,
	Port	=> $esexport,
	Timeout	=> $timeout
);
print localtime() . " Ethersex connected\n" if $debug >= 2;

#Alle Sensor-IDs auslesen und dem Array @sensor zuweisen
$esex->print("1w list");
($sensor) = $esex->waitfor(
	Timeout	=> $timeout,
	String	=> "OK"
);
@sensor = split( /\s+/, $sensor );
#print localtime() . " DS18S20_IDs: @sensor\n";

my $sensor_count = @sensor;
die "There are no sensors" if $sensor_count == 0;
print "Number of sensors: $sensor_count\n" if $debug >= 2;

#Alle Sensor Temperaturen einlesen
foreach (@sensor) {
	$esex->print("1w convert $_");
	$esex->waitfor(
		Timeout => $timeout,
		String  => "OK"
	);
	print "Sensor $_ done\n" if $debug >= 2;
}
print localtime() . " Temperature conversation done\n" if $debug >= 2;

#Sensor-ID inklusive Wert ausgeben und zum Volkszaehler uebermitteln
my $familiar;	## Benutzt um zu testen ob der Sensor schon in @DS18S20 ist
print "-" x 81 . "\n" if $debug >= 1;
foreach (@sensor) {
	$esex->print("1w get $_");
	$temp = ($esex->waitfor(
		Match   => '/-?\d+\.\d+/',
		Timeout => $timeout
	))[1];
	if ($temp == 85) {
		print localtime() . " Temperature out of range $_: $temp\n" if $debug <= 0;
		next;
	}

	foreach my $ref (@DS18S20) {
		if ( @$ref[1] eq $_ ) {
			print "ID_DS18S20: @$ref[1] Temp: ${temp}°C "
				. "uuid: @$ref[2] name: @$ref[0]\n" if $debug >= 1;
			$familiar = 1;

			my $h = HTTP::Headers->new;
			my $reqString = "$url/data/@$ref[2].json?value=$temp";

			my $ua = LWP::UserAgent->new;
			$h->authorization_basic($uname, $password);

			my $r = HTTP::Request->new('POST', $reqString, $h);

			my $response = $ua->request($r);
			print "Server response: ". $response->content ."\n" if $debug >= 1;
			last;

		} else {
			$familiar = 0;
		}
	}
	print "ID_DS18S20: $_ Temp: ${temp}°C uuid: I don't know this sensor …\n" if $familiar == 0;
}
print "*" x 81 . "\n" if $debug >= 1;
