<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Volkszaehler\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="data")
 */
class Data {
	/**
	 * ending timestamp of period in ms since 1970
	 * 
	 * @Id
	 * @Column(type="bigint")
	 */
	private $timestamp;

	/**
	 * @Column(type="decimal", precision="10", scale="5")
	 * @todo change to float after DCC-67 has been closed
	 */
	private $value;

	/**
	 * @Id
	 * @ManyToOne(targetEntity="Volkszaehler\Model\Channel\Channel", inversedBy="data")
	 * @JoinColumn(name="channel_id", referencedColumnName="id")
	 */
	private $channel;
	
	public function __construct(Channel\Channel $channel, $value, $timestamp) {
		$this->channel = $channel;
		$this->value = $value;
		$this->timestamp = $timestamp;
	}
	
	/*
	 * setter & getter
	 */
	public function getValue() { return $this->value; }
	public function getTimestamp() { return $this->timestamp; }
	public function getChannel() { return $this->channel; }
}

?>
