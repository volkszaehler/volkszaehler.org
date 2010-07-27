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

namespace Volkszaehler\Logger;

use Volkszaehler\View\HTTP;
use Doctrine\ORM;
use Volkszaehler\Model;

/**
 * interface for parsing diffrent logging APIs (google, flukso etc..)
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 * @todo to be implemented
 */
interface LoggerInterface {
	public function __construct(HTTP\Request  $request, ORM\EntityManager $em);

	/**
	 * @return array of Model\Data
	 */
	public function getData();

	public function getVersion();
}

abstract class Logger implements LoggerInterface {
	protected $request;
	protected $em;

	public function __construct(HTTP\Request  $request, ORM\EntityManager $em) {
		$this->request = $request;
		$this->em = $em;
	}

	public function log() {
		$data = $this->getData();

		if (!is_array($data)) {
			$data = array($data);
		}

		foreach ($data as $reading) {
			$this->em->persist($reading);
		}
		$this->em->flush();
	}
}

?>