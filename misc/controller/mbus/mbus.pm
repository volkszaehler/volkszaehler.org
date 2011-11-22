#
# Messbus-perl library (c) by Sven Anders <mbus@sven.anders.im> 2011
#  @copyright Copyright (c) 2011, The volkszaehler.org project
#  @license http://www.opensource.org/licenses/gpl-license.php GNU Public License

#
#  This file is part of volkzaehler.org
# 
#  volkzaehler.org is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  any later version.
#
#  volkzaehler.org is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
# 
#  You should have received a copy of the GNU General Public License
#  along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
#

use Device::SerialPort qw( :PARAM :STAT 0.07 );


sub initPort {
    my $portName=shift;
    my $baudrate=shift;
    my $PortObj = new Device::SerialPort ($portName, $quiet)
              || die "Can't open $portName: $!\n";
    $PortObj->baudrate($baudrate);
    $PortObj->parity("even");
    $PortObj->databits(8);
    $PortObj->stopbits(1);
    return $PortObj;
}

sub INTtoBCD {
    my $int=shift;
    my $bytes=shift;
    while (length($int)<($bytes*2)) {
	$int="0".$int;
    }
    my $i=0;
    my $out="";
    while ($i<$bytes) 
    {
	$hex=chr(eval("0x".substr($int,$i*2,2)));
	$out=$hex.$out;
	$i++;
    }
    return $out;
}

sub BCDtoINT {
    my $bcd=shift;
    @chars=split(//,$bcd);
    my $out="";
    foreach(@chars) {
	$out=sprintf("%02x",ord($_)).$out;

    }
    if ($out=~/^f/) {
	$out=~s/^f//;
# Offizizelle Berechnung
#	print "Negativ!",length($out),"\n";
#	
#	$len=length($out);
#	my $new="";
#	while (length($new)<$len)
#	{
#	    $new.="9";
#	}
#	$out++;
#	$out-=$new;
	$out="-".$out;
    }

    return "$out";

}


sub checksum {
    my $input=shift;
    my @chars=split(//,$input);
    my $sum=0;
    foreach (@chars)
    {
	$sum=($sum+ord($_)) % 0x100;
    }
    return chr($sum)
}

sub send {
    my $PortObj=shift;
    my $output_string=shift;
    my $count_out = $PortObj->write($output_string);
    warn "write failed\n"         unless ($count_out);
    warn "write incomplete\n"     if ( $count_out != length($output_string) );
#    my $done=0;
#    while ($done==0) {
#	($done, $count_out) = $PortObj->write_done(0);
#        ($done=1) if (!(defined($done)));	
#    }
}

sub sendShortFrame {
    my $PortObj=shift;
    my $cfield=shift;
    my $afield=shift;
    my $str=chr($cfield).chr($afield);
    my $out=chr(0x10).$str.&checksum($str).chr(0x16);
    &send($PortObj,$out);
}

sub readAnswer {
    my $PortObj=shift;
    my $timeout=3;
    my $readstr="";
    my $chars=0;
    my $okay=-1;
    while ($timeout>0) {
	my ($count,$saw)=$PortObj->read(255); # will read _up to_ 255 chars
	if ($count > 0) {
	    $chars+=$count;
	    $readstr.=$saw;
	} else {
	    $timeout--;
	    sleep(1);
	}
	# FIXME: Wenn Korrekt empfangen $timeout=0;
	@buchstabe=split(//,$readstr);
	if (length($readstr)==0) {
         # nichts machen
	} elsif ((length($readstr)==1) and (ord($buchstabe[0])==0xE5)) {
	    $okay=1;
	} elsif ((ord($buchstabe[0])==0x10) and (length($readstr)<=5)) {
	    if (length($readstr)==5) {
		if ((ord($buchstabe[4])!=0x16) and
		    (checksum($buchstabe[1].$buchstabe[2]) eq $buchstabe[3]))
		{
		    $okay=1;
		} else {
		    $okay=0;
		}
	    }
	} elsif (ord($buchstabe[0])==0x68) {
	    if (length($readstr)>4) {
		if (($buchstabe[1] eq $buchstabe[2]) and (ord($buchstabe[3])==0x68))
		{
		    my $len=ord($buchstabe[1])+6;
		    if (length($readstr)>$len) {
			$okay=0; #
		    } elsif (length($readstr)==$len) { 
			$okay=1;
			# Stopbyte
			ord($buchstabe[$len-1])==0x16 or $okay=0; 
			my $i=4;
			my $str="";
			while ($i<($len-2))
			{
			    $str.=$buchstabe[$i];
			    $i++;
			}
			(&checksum($str) eq $buchstabe[$len-2]) or $okay=0;
		    }
		} else {
		    $okay=0;
		}
	    }
	} else {
	    $okay=0;
	}
	if ($okay==1) {
	    $timeout=0;
	}
	      
    }
    return ($readstr,$okay);
}

sub sendLongFrame {
    my $PortObj=shift; 
    my $cfield=shift;
    my $afield=shift;
    my $cifield=shift;
    my $str=chr($cfield).chr($afield).chr($cifield).shift;
    my $len=chr(length($str));
    my $out=chr(0x68).$len.$len.chr(0x68).$str.&checksum($str).chr(0x16);
    &send($PortObj,$out);

}

sub findenummer {
 my $anfang=shift;
 my $PortObj=shift;
 my @out=();
 my $num=0;
 my $fill="";
 while (length($fill)+length($anfang)+1<8) {
 	$fill.="F";
 }
 while ($num<10) {
	my $cfield=0x53;
	my $afield=0xFD;
	my $cifield=0x52;
	$serialnum=&INTtoBCD($anfang.$num.$fill,4).chr(0xFF).chr(0xFF).chr(0xFF).chr(0xFF);
	&sendLongFrame($PortObj,$cfield,$afield,$cifield,$serialnum);
	my ($str,$okay)=&readAnswer($PortObj);
	print "Suchen nach $anfang$num*: ";
	if ($okay==1) {
		$cfield=0x5B;# REQ_UD2
        	$afield=0xFD;
	 	&sendShortFrame($PortObj,$cfield,$afield);
		my ($str,$okay)=&readAnswer($PortObj);
		my $ostr=&BCDtoINT(substr($str,7,4));
		print "Gefunden: Seriennummer: $ostr\n";
                push @out,$ostr;
	} elsif ($okay==0) {
        	# Kollision
		print "Kollision (mehrere gefunden). Suche tiefer...\n";
		push @out,&findenummer($anfang.$num,$PortObj);
	} else {
		print "Timeout. Nichts gefunden.\n";
        }
	$num++;
 }
 return @out;
}

sub strToArray {
   my $str=shift;
   my @rtn=();
   my @arr=split(//,$str);
   foreach my $val (@arr) {
	$val=ord($val);
   }
   my $len=$arr[1]+4;
   my $pos=19;
   my $seriennr=&BCDtoINT(substr($str,7,4));
   while ($pos<$len) {
    my $dif=$arr[$pos];
    my $coding=$dif & 1+2+4+8;
    my $function=($dif & 16+32)/16;
    my $storageNum=($dif & 64) /64;
    my $extension=$dif & 128;
    print "dif:$dif cod: $coding func:$function stor: $storageNum ext:$extension\n" if $debug;
    $pos++;
    my $i=1;
    my $outdif="$dif";
    while ($extension!=0) {
	my $dife=$arr[$pos];
        $outdif.=" $dife";
        # DIFE auswerten...
	$storageNum+=($dife & (1+2+4+8))*(2**$i);
	$i++;
	my $tarif=($dife & (16+32));
	my $deviceunit=$dife & 64;
	my $extension=$dife & 128;
	print "dife: $dife storageNum: $storageNum, tarif: $tarif, devunit: $deviceunit ext: $extension \n" if $debug;
        $pos++;
     }
    my $vif=$arr[$pos];
    $extension=$vif & 128;
    my $n3=$vif & 1+2+4;
    my $n2=$vif & 1+2;
    my $description4=$vif & 8+16+32+64;
    my $description5=$vif & 4+8+16+32+64;
    $pos++;
    print "vif $counter: $vif ext:$extension n2: $n2 n3:$n3 desc4:$description4  desc5:$description5 \n" if $debug;
    my $outvif="$vif";
    my $vife;
    while ($extension!=0) {
     $vife=$arr[$pos];
     print "vife: $vife\n" if $debug;
     $outvif.=" $vife";
     $extension=$vife &128;
     $pos++;
    } 
    my $val="";
    my $exitnow=0;
    if ($coding==0) {# NoData
     $pos+=0;
    } elsif ($coding==1) {# 8 bit int
     $val=$arr[$pos];     
     $pos+=1;
    } elsif ($coding==2) {# 16 bit int
     $val=$arr[$pos+1]*0x100+$arr[$pos];
     $pos+=2;
    } elsif ($coding==3) {# 24 bit int
     $val=$arr[$pos+2]*0x10000+$arr[$pos+1]*0x100+$arr[$pos];
     $pos+=3;
    } elsif ($coding==4) {# 32 bit int
     $val=$arr[$pos+3]*0x1000000+$arr[$pos+2]*0x10000+$arr[$pos+1]*0x100+$arr[$pos];
     $pos+=4;
    } elsif ($coding==5) {# 32 bit real 32/N
     $pos+=4;
     die("32 bit real not implemented");
    } elsif ($coding==6) {# 48 bit int
     $pos+=6;
    } elsif ($coding==7) {# 64 bit int
     $pos+=8;
    } elsif ($coding==8) {# selection for readout
     $pos+=0;
    } elsif ($coding==9) {# 2 digit BCD
     $val=&BCDtoINT(substr($str,$pos,1));
     #print "VAL9: $val \n";
     $pos+=1;
    } elsif ($coding==10) {# 4 digit BCD
     $val=&BCDtoINT(substr($str,$pos,2));
     $pos+=2;
    } elsif ($coding==11) {# 6 digit BCD
     $val=&BCDtoINT(substr($str,$pos,3));
     $pos+=3;
    } elsif ($coding==12) {# 8 digit BCD
     $val=&BCDtoINT(substr($str,$pos,4));
     $pos+=4;
    } elsif ($coding==13) {# variable length
     my $lval=$arr[$pos];
     $pos++;
     if ($lval<0xC0) {
     #LVAR = 00h .. BFh : ASCII string with LVAR characters
       $val=substr($str,$pos,$lval);       
       $pos+=$lval;
     } elsif ($lval<0xD0) {
     # LVAR = C0h .. CFh : positive BCD number with (LVAR - C0h) Â· 2 digits
       $val=&BCDtoINT(substr($str,$pos,($lval-0xC0)));
       $pos+=($lval-0xC0);
     } elsif ($lval<0xE0) {
     # LVAR = D0h .. DFH : negative BCD number with (LVAR - D0h) Â· 2 digits
       $val="-".&BCDtoINT(substr($str,$pos,($lval-0xD0)));
       $pos+=($lval-0xD0);
     } elsif ($lval<0xF0) {
     # LVAR = E0h .. EFh : binary number with (LVAR - E0h) bytes
       $pos+=($lval-0xE0);
     } else {
     # LVAR = F0h .. FAh : floating point number  with (LVAR - F0h) bytes [to be defined] 
     # LVAR = FBh .. FFh : Reserved
      die("variable length not implemented");
     }
    } elsif ($coding==14) {# 12 digit BCD
     $val=&BCDtoINT(substr($str,$pos,12));
     $pos+=6;
    } elsif ($coding==15) {# special function
     $pos+=8;
     $exitnow=1;
    }
    my $unit="unklar";
    my $zweck="unbekannt";
    my $newvif=$vif-($vif &128);
    my $n=0;
    if ($description4==0) {
     $unit="Wh";
     $n=$n3-3;
     $zweck="Heizenergie";
    } elsif ($description4==8) {
     $unit="J";
     $n=$n3;
     $zweck="Heizenergie";
    } elsif ($description4==16) {
     $unit="m^3";
     $n=$n3-6;
     $zweck="Volumen";
    } elsif ($description4==24) {
     $unit="kg";
     $n=$n3-3;
     $zweck="Masse";
    } elsif ($description5==32) {
    	if ($n2==0)  {
     	  $unit="sec";
	} elsif ($n2==1) {
	   $unit="min";
	} elsif ($n2==2) {
	   $unit="h";
	}else {
	   $unit="Tage";
	}
        $zweck="Einschaltzeit";
    } elsif ($description5==36) {
    	if ($n2==0)  {
     	  $unit="sec";
	} elsif ($n2==1) {
	   $unit="min";
	} elsif ($n2==2) {
	   $unit="h";
	}else {
	   $unit="Tage";
	}
        $zweck="Betriebszeit";
    } elsif ($description4==40) {
     $unit="W";
     $n=$n3-3;
     $zweck="Leistung";
    } elsif ($description4==48) {
     $unit="J/h";
     $n=$n3;
     $zweck="Leistung";
    } elsif ($description4==56) {
     $unit="m^3/h";
     $n=$n3-6;
     $zweck="Fliessgeschwindigkeit";
    } elsif ($description4==64) {
     $unit="m^3/min";
     $n=$n3-7;
     $zweck="Fliessgeschwindigkeit ext.";
    } elsif ($description4==72) {
     $unit="m^3/s";
     $n=$n3-9;
     $zweck="Fliessgeschwindigkeit ext.";
    } elsif ($description4==80) {
     $unit="kg/h";
     $n=$n3-3;
     $zweck="Massenverlust";
    } elsif ($description5==88) {
     $unit="C";
     $n=$n2-3;
     $zweck="Vorlauftemperatur";
    } elsif ($description5==92) {
     $unit="C";
     $n=$n2-3;
     $zweck="Rücklauftemperatur";
    } elsif ($description5==96) {
     $unit="K";
     $n=$n2-3;
     $zweck="Temperaturdifferenz";
   } elsif ($description5==100) {
     $unit="C";
     $n=$n2-3;
     $zweck="Außentemperatur";
   } elsif ($description5==104) {
     $unit="bar";
     $n=$n2-3;
     $zweck="Druck";
   } elsif ($description5==108) {
     $zweck="Zeitpunkt";
     if ($n2==0) 
     {
     	$unit="Datum";# Bit 0-4 Tag 5,6,7,12-15 Jahr 8-11 Monat
	my $day=$val & (2**0+2**1+2**2+2**3+2**4);
	my $year=($val & (2**5+2**6+2**7))/2**5;
	my $month=($val & (2**8+2**9+2**10+2**11))/2**8;
	$year+=($val & (2**12+2**13+2**14+2**15))/2**12;
	$val=sprintf("20%02d-%02d-%02d",$year,$month,$day);
     } elsif ($n2==1) 
     {
     	$unit="Uhr";
	my $min=$val & (1+2+4+8+16+32); # bit 0 bis 5
	# Bit 7 Reserved 64
	my $invalidTime=$val & 2**7; # bit7 (0=valid 1=invalid)
	my $h=($val & 256+512+1024+2048+4096) /256; # bit 8 bis 12
	my $day=($val & (2**16+2**17+2**18+2**19+2**20))/2**16; # bit 16 bis 20
	my $year=($val & (2**21+2**22+2**23))/2**21; # 21 bis 23 und 28 bis 31
	my $month=($val & (2**24+2**25+2**26+2**27))/2**24; # 24 bis 27
	$year+=($val & (2**28+2**29+2**30+2**31))/2**28; 
	# sommerzeit bis 15
	my $sommerzeit=($val & 2**15);
	if ($invalidTime==0) {
		$val=sprintf("20%02d-%02d-%02d %02d:%02d",$year,$month,$day,$h,$min);
		if ($sommerzeit!=0) {
		  $val.="SZ";
		}
	} else {
		$val=sprintf("20%02d-%02d-%02d xx:xx",$year,$month,$day,$h,$min);
	}	
     } elsif ($n2==2) 
     {
     	$zweck="unklar (H.C.A)";
     } else #($n2==3) 
     {
        $zweck="reserved (108)";
     }
   } elsif (($newvif)==120) {
     $unit="";
     $zweck="Fabriknummer";
   } elsif (($newvif)==124) {
     $unit="";
     $zweck="Benutzerdefiniert in folgenden String";
     die("hier muesste ein String ausgelesen werden");
    } elsif ($vif==253) {
      # VIF wird im VIFE bestimmt
      $unit="";
      if ($vife==14) {
	$zweck="Firmware version";
      } elsif ($vife==15) {
	$zweck="Software version";
      } elsif ($vife<31) {
        $zweck="Fehler (vife=$vife)";
      } else {
        $zweck="vife: $vife"
      }
      
    } else {
	print "noch nicht definiert d5:  $description5 newvif $newvif\n" if $debug;   
    }
    if (($unit eq "Wh") and ($n=3)) {
    	$unit="kWh";
	$n=0;
    }
    if ($val eq "") {
     #print "val bei $counter ist leer\n";
    } elsif ($n!=0) {

	if ($val =~ /f/) {
	    print "val $val potenzieren mit 10 ** $n\n";
	    $val=~s/f/0/;
	}
        $val=$val*(10**$n);
    }
    my $func;
    if ($function==0) {
    	$func="normaler Wert";
    } elsif ($function==1) {
    	$func="max.Wert";
    } elsif ($function==2) {
    	$func="min.Wert";
    } else {
    	$func="Fehlerwert";
    }
    if ($exitnow==0) {
    my @zeile=($outdif,$outvif,$val,$unit,$zweck,$seriennr,$func,$storageNum);
    push @rtn,\@zeile;
    } else {
     $pos=$len;
    }
    $counter++;
    
   }
   return @rtn;

}

return 1;
