<?php

namespace Wrapper\View\HTTP;

/**
 * HTTP request wrapper for testing
 *
 * @package test
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class Request extends \Volkszaehler\View\HTTP\Request {

	/**
	 * constructor
	 */
	public function __construct($url, $method = 'get', $parameters = null, $headers = null) {
		$this->url = $url;
		$this->headers = $headers;
		$this->method = $method;

		// convert url to parameters array
		if (empty($parameters)) {
			$parameters = array();
			$kvpairs = preg_split('/&/', parse_url($url, PHP_URL_QUERY), -1, PREG_SPLIT_NO_EMPTY);
			foreach ($kvpairs as $kv) {
				list($k,$v) = preg_split('/=/', $kv);
				$parameters[urldecode($k)] = urldecode($v);
			}
		}

		$this->parameters = array_merge(
			array(
				'get'		=> array(),
				'post'		=> array(),
				'cookies'	=> array(),
				'files'		=> array()
			),
			array(
				$method => $parameters
			)
		);
	}

	public function getUrl() { return($this->url); }
}

?>
