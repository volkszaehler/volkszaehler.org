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

/**
 * Aggregate materialized view entity
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 *
 * @todo change index name to something more meaningful like aggregate_channel_type_ts_idx
 *
 * @Entity
 * @Table(name="aggregate")
 */
class Aggregate
{
	/**
	 * @Id
	 * @ManyToOne(targetEntity="Channel", inversedBy="aggregate")
	 * @JoinColumn(name="channel_id", referencedColumnName="id", nullable=false)
	 *
	 * @todo implement inverse side (Channel->aggregate)
	 */
	protected $channel;

	/**
	 * Aggregation type
	 *
	 * @Id
	 * @Column(type="smallint", nullable=false)
	 */
	protected $type;

	/**
	 * Ending timestamp of period in ms since 1970
	 *
	 * @Id
	 * @Column(type="bigint", nullable=false)
	 */
	protected $timestamp;

	/**
	 * @Column(type="float", nullable=false)
	 */
	protected $value;

	/**
	 * Aggregated row count
	 *
	 * @Column(type="integer", nullable=false)
	 */
	protected $count;

	public function __construct(Channel $channel, $type, $timestamp, $value, $count)
	{
		$this->channel = $channel;
		$this->type = $type;

		$this->value = $value;
		$this->timestamp = $timestamp;
		$this->count = $count;
	}

	public function toArray()
	{
		return array('channel' => $this->channel, 'type' => $this->type, 'timestamp' => $this->timestamp, 'value' => $this->value, 'count' => $this->count);
	}

	/**
	 * setter & getter
	 */
	public function getValue()
	{
		return $this->value;
	}

	public function getTimestamp()
	{
		return $this->timestamp;
	}

	public function getChannel()
	{
		return $this->channel;
	}

	public function getCount()
	{
		return $this->count;
	}

	public function getType()
	{
		return $this->type;
	}
}
