<?php
/**
 * @copyright Copyright (c) 2013, The volkszaehler.org project
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
use Volkszaehler\Model;

/**
 * Aggregate materialized view entity
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 *
 * @Entity
 * @Table(
 * 	name="aggregate",
 *	indexes={@index(name="search_idx", columns={"channel_id", "type", "timestamp"})},
 *	uniqueConstraints={@UniqueConstraint(name="aggregate_unique", columns={"channel_id", "type", "timestamp"})}
 * )
 */
class Aggregate {
	/**
	 * @Id
	 * @Column(type="integer", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @todo wait until DDC-117 is fixed (PKs on FKs)
	 */
	protected $id;

	/**
	 * @ManyToOne(targetEntity="Channel", inversedBy="aggregate")
	 * @JoinColumn(name="channel_id", referencedColumnName="id")
	 *
	 * @todo implement inverse side (Channel->aggregate)
	 */
	protected $channel;

	/**
	 * Aggregation type
	 *
	 * @Column(type="smallint")
	 */
	protected $type;

	/**
	 * Ending timestamp of period in ms since 1970
	 *
	 * @Column(type="bigint")
	 */
	protected $timestamp;

	/**
	 * @Column(type="float")
	 */
	protected $value;

	/**
	 * Aggregated row count
	 *
	 * @Column(type="integer")
	 */
	protected $count;

	public function __construct(Model\Channel $channel, $type, $timestamp, $value, $count) {
		$this->channel = $channel;
		$this->type = $type;

		$this->value = $value;
		$this->timestamp = $timestamp;
		$this->count = $count;
	}

	public function toArray() {
		return array('channel' => $this->channel, 'type' => $this->type, 'timestamp' => $this->timestamp, 'value' => $this->value, 'count' => $this->count);
	}

	/**
	 * setter & getter
	 */
	public function getValue() { return $this->value; }
	public function getTimestamp() { return $this->timestamp; }
	public function getChannel() { return $this->channel; }
	public function getCount() { return $this->count; }
	public function getType() { return $this->type; }
}

?>
