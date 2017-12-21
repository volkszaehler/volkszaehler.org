<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\ORM\EntityManager;

use Volkszaehler\Util\EntityFactory;
use Volkszaehler\View\View;

/**
 * Controller superclass for all controllers
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
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
	 * @var Symfony\Component\HttpFoundation\ParameterBag
	 */
	protected $parameters;

	/**
	 * @var Volkszaehler\View
	 */
	protected $view;

	/**
	 * @var EntityFactory
	 */
	protected $ef;

	/**
	 * Constructor
	 *
	 * @param Request $request
	 * @param EntityManager $em
	 * @param View $view
	 */
	public function __construct(Request $request, EntityManager $em, View $view) {
		$this->request = $request;
		$this->em = $em;
		$this->view = $view;
		$this->ef = EntityFactory::getInstance($em);
	}

	/**
	 * Return request parameter from query or post body
	 */
	public function getParameters() {
		if ($this->parameters === null) {
			$this->parameters = new ParameterBag($this->request->query->all());
			if ($this->request->getMethod() !== Request::METHOD_GET) {
				$this->parameters->add($this->request->request->all());
			}
		}

		return $this->parameters;
	}

	/**
	 * Run operation
	 *
	 * @param $op Operation to run
	 * @param null $uuid Uuid to operate on
	 * @return operation result
	 * @throws \Exception
	 */
	public function run($op, $uuid) {
		if (!method_exists($this, $op)) {
			throw new \Exception('Invalid context operation: \'' . $op . '\'');
		}

		// one or more uuid(s) as query parameters?
		if (null == $uuid) {
			$uuid = $this->getParameters()->get('uuid');
		}

		// call the operation
		return $this->{$op}($uuid);
	}
}

?>
