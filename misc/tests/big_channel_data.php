<?php
/**
 * Creates a big test Counter
 *
 * @package tests
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Sven Anders <volkszaehler2011@sven.anders.im>
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Volkszaehler;

use Volkszaehler\Util;
use Volkszaehler\Controller;

// enable strict error reporting
error_reporting(E_ALL | E_STRICT);

define('VZ_DIR', realpath(__DIR__ . '/../..'));
define('VZ_VERSION', '0.2');

// class autoloading
require_once VZ_DIR . '/lib/Util/ClassLoader.php';
require_once VZ_DIR . '/lib/Util/Configuration.php';

// load configuration
Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');
// set timezone
$tz = (Util\Configuration::read('timezone')) ? Util\Configuration::read('timezone') : @date_default_timezone_get();
date_default_timezone_set($tz);

// set locale
setlocale(LC_ALL, Util\Configuration::read('locale'));

// define include dirs for vendor libs
define('DOCTRINE_DIR', Util\Configuration::read('lib.doctrine') ? Util\Configuration::read('lib.doctrine') : 'Doctrine');
define('JPGRAPH_DIR', Util\Configuration::read('lib.jpgraph') ? Util\Configuration::read('lib.jpgraph') : 'JpGraph');

$classLoaders = array(
        new Util\ClassLoader('Doctrine', DOCTRINE_DIR),
        new Util\ClassLoader('Volkszaehler', VZ_DIR . '/lib')
);

foreach ($classLoaders as $loader) {
        $loader->register(); // register on SPL autoload stack
}




$_SERVER['REQUEST_METHOD']="get";
$_SERVER['PATH_INFO']="bla.json";

$r = new Router();

$class = "Volkszaehler\Controller\ChannelController";
//$r->view->request->parameters["get"]["type"]="heat";
$controller = new $class($r->view, $r->em);
$channel = new Model\Channel("heat");
//$controller->setProperties($channel, $parameters);
$channel->setProperty("title","TestCounterHeat");
$channel->setProperty("public","1");
$channel->setProperty("resolution","1000");
$r->em->persist($channel);
$r->em->flush();

$from_ts=1230764400000; # 2009-01-01
$to_ts=1320102000000; # 2011-11-01
$delta_ts=3*60*1000; # every 3 Minutes

$ts=$from_ts;
$i=0;
while ($ts< $to_ts) {
   $i++;
   $value=rand(1,1000);
   $channel->addData(new Model\Data($channel,$ts,$value));
   $ts=$ts+$delta_ts;
   if ($i>1000) {
     $r->em->flush();
     $i=0;
   }
}


print "Channel created:\n";
print $channel->getUuid();

