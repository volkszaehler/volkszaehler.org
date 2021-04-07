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
 * Data entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 *
 * @todo change index name to something more meaningful like data_channel_ts_idx
 *
 * @Entity
 * @Table(name="data")
 */
class Data
{
	/**
	 * @Id
	 * @ManyToOne(targetEntity="Channel", inversedBy="data")
	 * @JoinColumn(name="channel_id", referencedColumnName="id", nullable=false)
	 */
	protected $channel;

	/**
	 * @Id
	 * @Column(type="bigint", nullable=false)
	 */
	protected $timestamp;

	/**
	 * @Column(type="float", nullable=false)
	 */
	protected $value;

	public function __construct(Channel $channel, $timestamp, $value)
	{
		$this->channel = $channel;

		$this->value = $value;
		$this->timestamp = $timestamp;
	}

	public function toArray()
	{
		return array('channel' => $this->channel, 'timestamp' => $this->timestamp, 'value' => $this->value);
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
}
