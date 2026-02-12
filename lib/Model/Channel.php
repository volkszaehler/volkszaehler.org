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

namespace Volkszaehler\Model;

use Volkszaehler\Interpreter\SQL;
use Doctrine\DBAL;
use Doctrine\Common\Collections\ArrayCollection;
#use Doctrine\ORM\Mapping as ORM;

/**
 * Channel entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 * @Doctrine\ORM\Mapping\Entity
 */
class Channel extends Entity
{
	/**
	 * @Doctrine\ORM\Mapping\OneToMany(targetEntity="Data", mappedBy="channel", cascade={"persist"}, orphanRemoval=true)
	 * @Doctrine\ORM\Mapping\OrderBy({"timestamp" = "ASC"})
	 * @var ArrayCollection|null
	 */
	protected $data = NULL;

	/**
	 * @var ArrayCollection|null
	 */
	protected $groups = NULL;

	/**
	 * Constructor
	 */
	public function __construct($type)
	{
		parent::__construct($type);

		$this->data = new ArrayCollection();
		$this->groups = new ArrayCollection();
	}

	/**
	 * Add a new data to the database
	 */
	public function addData(Data $data)
	{
		$this->data->add($data);
	}

	/**
	 * Purge data
	 *
	 * prevents doctrine of using single delete statements
	 */
	public function clearData(DBAL\Connection $conn, $from = null, $to = null, $filters = [])
	{
		$res = $conn->transactional(function () use ($conn, $from, $to, $filters, &$res) {
			$params = array($this->id);

			$sql = 'WHERE channel_id = ?';
			$sql .= SQL\SQLOptimizer::buildDateTimeFilterSQL($from, $to, $params);

			// clean aggregation table as well
			$conn->executeStatement('DELETE FROM aggregate ' . $sql, $params);

			if ($filter = SQL\SQLOptimizer::buildValueFilterSQL($filters, $params)) {
				$sql .= $filter;
			}

			return $conn->executeStatement('DELETE FROM data ' . $sql, $params);
		});

		return $res;
	}
}
