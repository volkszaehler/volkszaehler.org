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

namespace Volkszaehler\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Channel entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
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
	 * @todo filter from & to
	 */
	public function clearData(\Doctrine\ORM\EntityManager $em) {
		$sql = 'DELETE FROM data WHERE channel_id = ?';
		return $em->getConnection()->executeQuery($sql, array($this->id));
	}
}

?>
