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
 * @package default
 */
class EntityDefinition extends Util\Definition {
	/** @var string File containing the JSON definitons */
	const FILE = '/share/entities.json';

	/** @var array list of required properties */
	protected $required = array();

	/** @var array list of optional properties */
	protected $optional = array();

	/** @var string classname of intepreter (see backend/lib/Interpreter/) */
	protected $interpreter;

	/** @var string optional for Aggregator class entities */
	protected $unit;

	static protected $definitions = NULL;

	/**
	 * @todo url relative or absolute?
	 * @var string
	 */
	protected $icon;

	/*
	 * Setter & Getter
	 */
	public function getInterpreter() { return $this->interpreter; }
	public function getUnit() { return $this->unit; }
}

?>