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

namespace Volkszaehler\Definition;

use Volkszaehler\Util;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class EntityDefinition extends Definition {
	/**
	 * File containing the JSON definitons
	 *
	 * @var string
	 */
	const FILE = '/lib/Definition/EntityDefinition.json';

	/**
	 * List of required properties
	 *
	 * @var array
	 */
	protected $required = array();

	/**
	 * List of optional properties
	 *
	 * @var array
	 */
	protected $optional = array();

	/**
	 * Classname of intepreter (see backend/lib/Interpreter/)
	 *
	 * @var string
	 */
	protected $interpreter;

	/**
	 * Classname of model (see backend/lib/Model/)
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Optional for Aggregator class entities
	 *
	 * @var string
	 */
	protected $unit;

	/**
	 * Relative url to an icon
	 * @var string
	 */
	protected $icon;

	/**
	 * @var array holds definitions
	 */
	protected static $definitions = NULL;

	/*
	 * Setter & Getter
	 */
	public function getInterpreter() { return $this->interpreter; }
	public function getUnit() { return $this->unit; }
	public function getRequiredProperties() { return $this->required; }
	public function getValidProperties() { return array_merge($this->required, $this->optional); }
}

?>