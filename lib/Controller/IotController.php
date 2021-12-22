<?php
/**
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
 */
class IotController extends Controller {

	/**
	 * Run operation
	 *
	 * @param string|array|null $secret
	 * @return array
	 * @throws \Exception
	 */
	public function get($secret) {
		if ($secret === null || is_array($secret) || strlen($secret) < 32) {
			throw new \Exception('Invalid identifier');
		}

		// treat secret as identifier stored in (hidden) owner parameter
		return array('entities' => $this->ef->getByProperties(array('owner' => $secret)));
	}
}

?>
