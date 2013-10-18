<?php
/**
 * Basic test functionality
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

abstract class Middleware extends PHPUnit_Framework_TestCase  
{
	static $mw = '';			// middleware url (auto-detected or manually set)

	static $context;			// context base url

	protected $url;				// request url
	protected $response;		// request response
	protected $json;			// decoded json response

	static function setupBeforeClass() {
		if (empty(self::$mw)) {
			self::$mw = self::getMwUrl();
		}
	}

	public static function HTTP_GET($url) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1, 
		));
		$result = curl_exec($curl);
		curl_close($curl);
		return($result);
	}

	public static function HTTP_POST($url, $post = array()) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1, 
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $post,
		));
		$result = curl_exec($curl);
		curl_close($curl);
		return($result);
	}

	/**
	 * auto-discover middleware url from request
	 */
	public static function getMwUrl($postfix = '') {
		if (isset($_SERVER['HTTP_HOST']) && $_SERVER['REQUEST_URI']) {
			$host = $_SERVER['HTTP_HOST'];
			$uri  = $_SERVER['REQUEST_URI'];

			$uricomponents = preg_split('/\//', $uri);
			while (array_pop($uricomponents) !== 'test');

			$mw = 'http://' . $host . (isset($_SERVER['HTTP_PORT']) ? ':'.$_SERVER['HTTP_PORT'] : '');
			$mw .= join('/', $uricomponents) . '/middleware' . $postfix . '/';
		}
		else {
			// fallback if run from command line
			$mw = 'http://localhost/vz/middleware' . $postfix . '/';
		}

		return($mw);
	}

	/**
	 * Execute barebones JSON middleware request
	 */
	static function _getJson($url) {
		$response = self::HTTP_GET($url);
		return (json_decode($response));
	}

	/**
	 * Execute JSON middleware request and validate result for 
	 * - no exception ($hasException = false or omitted)
	 * - has exception ($hasException = true or string)
	 * - has exception with specific message ($hasException = message string)
	 */
	protected function getJson($url, $hasException = false) {
		$this->url = $url;
		$this->json = self::_getJson($url);

		$this->assertTrue($this->json !== null, "Expected JSON got " . print_r($this->response,1));

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
	}
}

?>
