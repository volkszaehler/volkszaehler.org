<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Volkszaehler\View\Json;

use Volkszaehler\Util;

abstract class Json extends \Volkszaehler\View\View {
	protected $json = array();

	/*
	 * constructor
	 */
	public function __construct(\Volkszaehler\View\Http\Request $request, \Volkszaehler\View\Http\Response $response) {
		parent::__construct($request, $response);

		$this->json['source'] = 'volkszaehler.org';
		$this->json['version'] = \Volkszaehler\VERSION;

		$this->response->setHeader('Content-type', 'application/json');
	}

	public function render() {
		echo self::format(json_encode($this->json));
		
		parent::render();
	}

	protected static function format($json) {
		$tab = "\t";
		$formatted = '';
		$indentLevel = 0;
		$inString = false;

		$len = strlen($json);
		for($c = 0; $c < $len; $c++) {
			$char = $json[$c];
			switch($char) {
				case '{':
				case '[':
					$formatted .= $char;
					if (!$inString && (ord($json[$c+1]) != ord($char)+2)) {
						$indentLevel++;
						$formatted .= "\n" . str_repeat($tab, $indentLevel);
					}
					break;
				case '}':
				case ']':
					if (!$inString && (ord($json[$c-1]) != ord($char)-2)) {
						$indentLevel--;
						$formatted .= "\n" . str_repeat($tab, $indentLevel);
					}
					$formatted .= $char;
					break;
				case ',':
					$formatted .= $char;
					if (!$inString) {
						$formatted .= "\n" . str_repeat($tab, $indentLevel);
					}
					break;
				case ':':
					$formatted .= $char;
					if (!$inString) {
						$formatted .= ' ';
					}
					break;
				case '"':
					if ($c > 0 && $json[$c-1] != '\\') {
						$inString = !$inString;
					}
				default:
					$formatted .= $char;
					break;
			}
		}

		return $formatted;
	}

	public function addDebug() {
		$config = Util\Registry::get('config');

		$this->json['debug'] = array('time' => $this->getTime(),
										'database' => array('driver' => $config['db']['driver'],
																'queries' => Util\Debug::getSQLLogger()->queries)
		);

	}

	public function addException(\Exception $exception) {
		$this->json['exception'] = array('type' => get_class($exception),
										'message' => $exception->getMessage(),
										'code' => $exception->getCode(),
										'file' => $exception->getFile(),
										'line' => $exception->getLine(),
										'trace' => $exception->getTrace()
		);
	}
}

?>