<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

namespace Volkszaehler\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

/**
 * Controller superclass for all controllers
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 *
 * @todo Check how controllers can modify the response/headers
 */
abstract class Controller {

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	protected $em;

	/**
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Constructor
	 *
	 * @param Request $request
	 * @param EntityManager $em
	 */
	public function __construct(Request $request, EntityManager $em) {
		$this->request = $request;
		$this->em = $em;
	}

	/**
	 * Run operation
	 *
	 * @param string $operation runs the operation if class method is available
	 */
	public function run($op, $uuid = null) {
		if (!method_exists($this, $op)) {
			throw new \Exception('Invalid context operation: \'' . $op . '\'');
		}

		// one or more uuid(s) as query parameters?
		if (null == $uuid) {
			$uuid = $this->request->query->get('uuid');
		}

		// call the operation
		return $this->{$op}($uuid);
	}

	/**
	 * Helper function to convert single/multiple parameters to array format
	 */
	protected static function makeArray($data) {
		if (!is_array($data)) {
			if (isset($data))
				$data = array($data);
			else
				$data = array();
		}
		return $data;
	}
}

?>
