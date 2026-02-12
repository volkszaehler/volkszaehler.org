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

#use Doctrine\ORM\Mapping as ORM;

/**
 * Data entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 *
 * @Doctrine\ORM\Mapping\Entity
 * @Doctrine\ORM\Mapping\Table(name="data")
 */
class Data
{
	/**
	 * @Doctrine\ORM\Mapping\Id
	 * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Channel", inversedBy="data")
	 * @Doctrine\ORM\Mapping\JoinColumn(name="channel_id", referencedColumnName="id")
	 */
	protected $channel;

	/**
	 * @Doctrine\ORM\Mapping\Id
	 * @Doctrine\ORM\Mapping\Column(type="bigint")
	 */
	protected $timestamp;

	/**
	 * @Doctrine\ORM\Mapping\Column(type="float", nullable=false)
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
