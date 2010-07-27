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

namespace Volkszaehler;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 */
use Doctrine\DBAL;

class DataIterator implements \Iterator, \Countable {
	protected $current;
	protected $key;			// incrementing key
	protected $pdoStmt;		// PDOStatement
	protected $size;	// total readings in PDOStatement

	public function __construct(DBAL\Statement $stmt, $size) {
		$this->size = $size;

		$this->pdoStmt = $stmt->getWrappedStatement();
		$this->pdoStmt->setFetchMode(\PDO::FETCH_NUM);
	}

	public function current() {
		return $this->current;
	}

	public function next() {
		$this->key++;
		$this->current = $this->pdoStmt->fetch();
	}

	public function key() {
		return $this->key;
	}

	public function valid() {
		return (boolean) $this->current;
	}

	/**
	 * NoRewindIterator
	 */
	public function rewind() {
		$this->key = 0;
		$this->current = $this->pdoStmt->fetch();
	}

	public function count() { return $this->size; }
}

?>