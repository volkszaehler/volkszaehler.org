<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
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

namespace Volkszaehler\View;

use Volkszaehler\View\HTTP;
use Volkszaehler\Util;
use Volkszaehler\Model;
use Volkszaehler\Interpreter;

/**
 * Plain text view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class PlainText extends View {
	/**
	 * constructor
	 */
	public function __construct(HTTP\Request  $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		echo 'source: volkszaehler.org' . PHP_EOL;
		echo 'version: ' . VZ_VERSION . PHP_EOL;

		$this->response->setHeader('Content-type', 'text/plain');
	}
	
	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter ||
			$data instanceof Interpreter\AggregatorInterpreter ||
			$data instanceof Model\Entity ||
			$data instanceof Util\Debug ||
			$data instanceof \Exception ||
			is_array($data)) {
			\Doctrine\Common\Util\Debug::dump($data);
		}
		elseif (isset($data)) { // ignores NULL data
			//throw new \Exception('Can\'t show ' . get_class($data));
		}
	}

	/**
	 * Process, encode and print output to stdout
	 */
	protected function render() { }
}

?>
