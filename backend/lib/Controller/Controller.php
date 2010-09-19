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

namespace Volkszaehler\Controller;

/**
 * Controller superclass for all controllers
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
abstract class Controller {
	protected $view;
	protected $em;

	/**
	 * Constructor
	 *
	 * @param View $view
	 * @param EntityManager $em
	 */
	public function __construct(\Volkszaehler\View\View $view, \Doctrine\ORM\EntityManager $em) {
		$this->view = $view;
		$this->em = $em;
	}

	/**
	 * Run operation
	 *
	 * @param string $operation runs the operation if class method is available
	 */
	public function run($operation, $params = NULL) {
		if (!is_callable(array($this, $operation))) {
			throw new \Exception('Invalid context operation: ' . $operation);
		}

		return $this->$operation($params);
	}
}

?>