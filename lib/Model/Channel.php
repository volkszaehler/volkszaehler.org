<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @license https://opensource.org/licenses/gpl-license.php GNU Public License
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

use Volkszaehler\Util;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Channel entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 * @Entity
 */
class Channel extends Entity {
	/**
	 * @OneToMany(targetEntity="Data", mappedBy="channel", cascade={"persist"}, orphanRemoval=true)
	 * @OrderBy({"timestamp" = "ASC"})
	 */
	protected $data = NULL;

	/**
	 * Constructor
	 */
	public function __construct($type) {
		parent::__construct($type);

		$this->data = new ArrayCollection();
		$this->groups = new ArrayCollection();
	}

	/**
	 * Add a new data to the database
	 */
	public function addData(\Volkszaehler\Model\Data $data) {
		$this->data->add($data);
	}

	/**
	 * Purge data
	 *
	 * prevents doctrine of using single delete statements
	 */
	public function clearData(\Doctrine\DBAL\Connection $conn, $from = null, $to = null) {
		$conn->transactional(function() use ($conn, $from, $to, &$res) {
			$params = array($this->id);

			$sql = 'WHERE channel_id = ?';
			if (isset($from)) {
				$params[] = $from;
				$sql .= ' AND timestamp >= ?';

				if (isset($to)) {
					$params[] = $to;
					$sql .= ' AND timestamp <= ?';
				}
			}

			$res = $conn->executeUpdate('DELETE FROM data ' . $sql, $params);

			// clean aggregation table as well
			if (Util\Configuration::read('aggregation')) {
				$conn->executeUpdate('DELETE FROM aggregate ' . $sql, $params);
			}
		});

		return $res;
	}
}

?>
