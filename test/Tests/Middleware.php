<?php
/**
 * Basic test functionality
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

// enable strict error reporting
error_reporting(E_ALL | E_STRICT);

if (!defined('VZ_DIR')) define('VZ_DIR', realpath(__DIR__ . '/../..'));
if (!defined('VZ_VERSION')) define('VZ_VERSION', '0.3');

require_once VZ_DIR . '/lib/Util/ClassLoader.php';
require_once VZ_DIR . '/lib/Util/Configuration.php';

// load configuration\Volkszaehler\
\Volkszaehler\Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');

// set timezone
$tz = (\Volkszaehler\Util\Configuration::read('timezone')) ? \Volkszaehler\Util\Configuration::read('timezone') : @date_default_timezone_get();
date_default_timezone_set($tz);

// set locale
setlocale(LC_ALL, \Volkszaehler\Util\Configuration::read('locale'));

// define include dirs for vendor libs
if (!defined('DOCTRINE_DIR')) define('DOCTRINE_DIR', \Volkszaehler\Util\Configuration::read('lib.doctrine') ? \Volkszaehler\Util\Configuration::read('lib.doctrine') : 'Doctrine');
if (!defined('JPGRAPH_DIR')) define('JPGRAPH_DIR', \Volkszaehler\Util\Configuration::read('lib.jpgraph') ? \Volkszaehler\Util\Configuration::read('lib.jpgraph') : 'JpGraph');

$classLoaders = array(
	new \Volkszaehler\Util\ClassLoader('Doctrine', DOCTRINE_DIR),
	new \Volkszaehler\Util\ClassLoader('Volkszaehler', VZ_DIR . '/lib')
);

foreach ($classLoaders as $loader) {
	$loader->register(); // register on SPL autoload stack
}

$loader = new \Volkszaehler\Util\ClassLoader('Wrapper', VZ_DIR . '/test/Wrapper');
$loader->register();

abstract class Middleware extends PHPUnit_Framework_TestCase
{
	// middleware url (basis for parsing)
	static $mw = 'http://localhost/volkszaehler/middleware/';

	// all basic members static for consistency
	static $context;			// context base url
	static $response;			// request response
	static $url;				// request url

	protected $json;			// decoded json response

	/**
	 * Setup evironment for executing 'fake' middleware call
	 */
	static function callMiddleware($url) {
		self::$url = $url;

		$request = new \Wrapper\View\HTTP\Request($url);
		$response = new \Wrapper\View\HTTP\Response();

		$r = new \Wrapper\Router($request, $response);
		$r->run();
		$r->view->send();

		self::$response = $response->getResponseContents();
		return self::$response;
	}

	/**
	 * Execute barebones JSON middleware request
	 */
	static function getJsonRaw($url) {
		$response = self::callMiddleware($url);
		$json = json_decode($response);
		return $json;
	}

	/**
	 * Execute JSON middleware request and validate result for
	 * - no exception ($hasException = false or omitted)
	 * - has exception ($hasException = true or string)
	 * - has exception with specific message ($hasException = message string)
	 */
	protected function getJson($url, $hasException = false) {
		$this->json = self::getJsonRaw($url);
		$this->assertTrue($this->json !== null, "Expected JSON got " . print_r(self::$response,1));

		if (isset($this->json)) {
			if ($hasException) {
				if ($this->assertTrue(isset($this->json->exception), 'Expected <exception> got none.')) {
					if (is_string($hasException)) {
						$this->assertTrue($this->json->exception->message == $hasException);
					}
				}
			} else {
				$this->assertFalse(isset($this->json->exception),
					'Expected no <exception> got ' .
					(isset($this->json->exception) ? print_r($this->json->exception,1) : '') . '.');
			}
		}

		return $this->json;
	}
}

?>
