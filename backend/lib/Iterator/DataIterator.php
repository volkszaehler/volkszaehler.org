<?php
/**
 * @package default
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
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

namespace Volkszaehler\Iterator;

use Doctrine\DBAL;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataIterator implements \Iterator, \Countable {
	protected $current;
	protected $key;			// incrementing key
	protected $stmt;		// PDOStatement
	protected $size;		// total readings in PDOStatement

	/**
	 * Constructor
	 *
	 * @param \PDOStatement $stmt
	 * @param unknown_type $size
	 */
	public function __construct(\PDOStatement $stmt, $size) {
		$this->size = $size;

		$this->stmt = $stmt;
		$this->stmt->setFetchMode(\PDO::FETCH_NUM);
	}

	/**
	 * @return array with data
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * Fetch next row from database
	 */
	public function next() {
		$this->key++;
		$this->current = $this->stmt->fetch();

	}

	/**
	 * @return integer the nth data row
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * @return boolean do we have another row in the resultset?
	 */
	public function valid() {
		return (boolean) $this->current;
	}

	/**
	 * Rewind the iterator
	 *
	 * Should only be called once
	 * PDOStatements doest support rewind()
	 */
	public function rewind() {
		$this->key = 0;
		$this->current = $this->stmt->fetch();
	}

	/**
	 * @return integer
	 */
	public function count() { return $this->size; }
}

?>