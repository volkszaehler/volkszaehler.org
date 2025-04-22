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

use Doctrine\ORM\Mapping as ORM;

/**
 * Aggregate materialized view entity
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 *
 * @ORM\Entity
 * @ORM\Table(name="aggregate")
 */
class Aggregate
{
	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Channel", inversedBy="aggregate")
	 * @ORM\JoinColumn(name="channel_id", referencedColumnName="id")
	 *
	 * @todo implement inverse side (Channel->aggregate)
	 */
	protected $channel;

	/**
	 * Aggregation type
	 *
	 * @ORM\Id
	 * @ORM\Column(type="smallint")
	 */
	protected $type;

	/**
	 * Ending timestamp of period in ms since 1970
	 *
	 * @ORM\Id
	 * @ORM\Column(type="bigint")
	 */
	protected $timestamp;

	/**
	 * @ORM\Column(type="float", nullable=false)
	 */
	protected $value;

	/**
	 * Aggregated row count
	 *
	 * @ORM\Column(type="integer", nullable=false)
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
