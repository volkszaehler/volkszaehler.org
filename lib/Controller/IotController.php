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
use Volkszaehler\View\View;

/**
 * Controller for mapping external identifiers to entity uuids
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 */
class IotController extends Controller {

	/**
	 * @var Volkszaehler\Controller\EntityController
	 */
	protected $ec;

	public function __construct(Request $request, EntityManager $em, View $view) {
		parent::__construct($request, $em, $view);
		$this->ec = new EntityController($request, $em);
	}

	/**
	 * Run operation
	 *
	 * @param string $operation runs the operation if class method is available
	 */
	public function get($uuid = null) {
		if ($uuid === null || is_array($uuid) || strlen($uuid) < 32) {
			throw new \Exception('Invalid identifier');
		}

		// treat uuid as identifier stored in (hidden) owner parameter
		return array('entities' => $this->ec->filter(array('owner' => $uuid)));
	}
}

?>
