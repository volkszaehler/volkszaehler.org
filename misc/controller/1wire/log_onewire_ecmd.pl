#!/usr/bin/perl -w
#Auslesen der 1 Wire Temperatursensoren vom Typ DS18S20(+) an einem AVR-NET-IO mit ethersex und Daten¸bertragung zum Volkszaehler
# add it with crontab -e
# */1 * * * * /usr/local/bin/vz/1wVZ.pl >>/var/log/1wVZ.log 2>&1

use Net::Telnet ();
require LWP::UserAgent;
use HTTP::Request::Common;

#configuration start

my $esexip   = "<host>";                    #ip or hostname for ethersex
my $esexport = "2701";			    #ECMD port
my $url      = "http://<your volkszaehler domain>/middleware.php"; #url to volkszaehler middleware
my $uname    = "<username>";                #username for basic-auth from apache
my $password = "<password>";                #password
my $timeout  = 10;			    #timeout for all in seconds

#Temperature Sensors
my @DS18S20 = (
    [ '<first sensor description>',  '<sensor id>', '<uuid>' ],
    [ '<second sensor description>', '<sensor id>', '<uuid>' ],
    [ '<last sensor description>',   '<sensor id>', '<uuid>' ]
);

#configuration end

my $esex;
my @sensor;
my $sensor;
my $dummy;
my $temp;

print localtime() . " Script started\n";
$esex = Net::Telnet->new || die "kann Ethersex nicht finden";
$esex->open(
    Host    => $esexip,
    Port    => $esexport,
    Timeout => $timeout
);
print localtime() . " Ethersex connected\n";

#Alle Sensor-IDs auslesen und dem Array @sensor zuweisen
$esex->print("1w list");
($sensor) = $esex->waitfor(
    Timeout => $timeout,
    String  => "OK"
);
@sensor = split( /\s+/, $sensor );
print localtime() . " DS18S20_IDs: @sensor", "\n";    #Kontrollausgabe

my $zahler = @sensor;
print "Anzahl der Sensoren:", $zahler, "\n";

#Alle Sensor Temperaturen einlesen
$esex->print("1w convert");
$esex->waitfor(
    Timeout => $timeout,
    String  => "OK"
);
print localtime() . " Temperature conversation done\n";

#Sensor ID inklusive Wert ausgeben und zum Volkszaehler uebermitteln
foreach (@sensor) {
    $esex->print("1w get $_");
    ( $dummy, $temp ) = $esex->waitfor(
        Match   => '/[-]?\d+\.\d+/',
        Timeout => $timeout
    );

    print
"---------------------------------------------------------------------------------\n";

    foreach $ref (@DS18S20) {
        if ( @$ref[1] eq $_ ) {
            print "Ort:" . @$ref[0] . "\n";
            print "ID_DS18S20:" . @$ref[1] . " Temp:" . $temp . "∞C ";
            print "uuid:" . @$ref[2] . "\n";

            $h = HTTP::Headers->new;
            $reqString =
                $url
              . '/data/'
              . @$ref[2]
              . '.json?value='
              . $temp;

            $ua = LWP::UserAgent->new;
            $h->authorization_basic( $uname, $password );

            $r = HTTP::Request->new( 'POST', $reqString, $h );

            $response = $ua->request($r);
            print localtime() . " Server response:" . $response->content . "\n";

        }

    }
}
print
"*********************************************************************************\n";

