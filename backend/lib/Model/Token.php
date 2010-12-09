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

namespace Volkszaehler\Model;

use Volkszaehler\Util;

/**
 * Token entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 * @todo this is a draft! has to be discussed and implemented
 *
 * @Entity
 * @Table(name="tokens")
 */
class Token {
	/**
	 * @Id
	 * @Column(type="integer", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @todo wait until DDC-117 is fixed (PKs on FKs)
	 */
	protected $id;

	/**
	 * @Column(type="string", nullable=false, unique=true)
	 */
	protected $token;

	/**
	 * var integer timestamp until token is valid
	 *
	 * @Column(type="bigint")
	 * @todo to be implemented
	 */
	protected $valid;

	/**
	 * @ManyToOne(targetEntity="Entity", inversedBy="tokens")
	 */
	protected $entity;

	const LENGTH = 10;

	protected static $chars = array(
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'v', 'u', 'w', 'x', 'y', 'z',
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'V', 'U', 'W', 'X', 'Y', 'Z'
	);

	/**
	 * Constructor
	 *
	 * @param integer $length of the token
	 */
	public function __construct() {
		$this->token = self::generate();
	}

	protected static function generate($length = Token::LENGTH) {
		return Util\Random::getString(self::$chars, $length);
	}

	public function __toString() {
		return $this->getToken();
	}

	public function getToken() {
		return $this->token;
	}
}

?>
