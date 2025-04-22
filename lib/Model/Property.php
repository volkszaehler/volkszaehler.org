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

use Volkszaehler\Definition;

/**
 * Property entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 * @ORM\Entity
 * @ORM\Table(name="properties")
 * @ORM\HasLifecycleCallbacks
 */
class Property
{
	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Entity", inversedBy="properties")
	 * @ORM\JoinColumn(name="entity_id")
	 */
	protected $entity;

	/**
	 * @ORM\Id
	 * @ORM\Column(name="pkey", type="string")
	 * HINT: column name "key" is reserved by mysql
	 */
	protected $key;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	protected $value;

	/**
	 * Constructor
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function __construct(Entity $entity, $key, $value)
	{
		$this->entity = $entity;
		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Cast property value according to $this->type
	 *
	 * @ORM\PostLoad
	 */
	public function cast()
	{
		$type = $this->getDefinition()->getType();
		if ($type == 'multiple') {
			// TODO
		} elseif ($type == 'text') {
			settype($this->value, 'string');
		} else {
			settype($this->value, $type);
		}
	}

	/**
	 * Undo type cast to prevent Doctrine storing unmodified properties
	 *
	 * @ORM\PreFlush
	 */
	public function uncast()
	{
		if ($this->value === false) { // force boolean false to 0 instead of ''
			$this->value = '0';
		} else {
			settype($this->value, 'string');
		}
	}

	/**
	 * Validate property key & value
	 *
	 * Throws an exception if something is incorrect
	 *
	 * @ORM\PrePersist
	 * @ORM\PreUpdate
	 */
	public function validate()
	{
		if (!Definition\PropertyDefinition::exists($this->key)) {
			throw new \Exception('Invalid property: \'' . $this->key . '\'');
		}

		if (!$this->getDefinition()->validateValue($this->value)) {
			throw new \Exception('Invalid property value: \'' . $this->value . '\'');
		}
	}

	/*
	 * Setter & getter
	 */

	public function getKey()
	{
		return $this->key;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function getDefinition()
	{
		return Definition\PropertyDefinition::get($this->key);
	}

	public function setValue($value)
	{
		$this->value = $value;
	}
}
