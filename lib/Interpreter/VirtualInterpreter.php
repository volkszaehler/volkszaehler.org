<?php
/**
 * @copyright Copyright (c) 2016, The volkszaehler.org project
 * @author Andreas Goetz <cpuidle@gmx.de>
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

namespace Volkszaehler\Interpreter;

use Volkszaehler\Controller;
use Volkszaehler\Model;
use Volkszaehler\Util;
use Volkszaehler\Interpreter\Virtual;
use Doctrine\ORM;
use RR\Shunt;

/**
 * Interpreter for channels of type 'virtual'
 *
 * VirtualInterpreter is able to calculate data on the fly using the provided `rule` and `in1`..`in9` inputs.
 */
class VirtualInterpreter extends Interpreter {

	const PRIMARY = 'in1';

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	protected $em;

	protected $interpreters;	// array of input interpreters

	protected $ctx;
	protected $parser;

	protected $count; 			// number of rows
	protected $consumption; 	// in Wms (Watt milliseconds)
	protected $ts_last; 		// previous tuple timestamp

	/**
	 * Constructor
	 *
	 * @param Channel $channel
	 * @param EntityManager $em
	 */
	public function __construct(Model\Channel $channel, ORM\EntityManager $em, $from, $to, $tupleCount = null, $groupBy = null, $options = array()) {
		parent::__construct($channel, $em, $from, $to, $tupleCount, $groupBy, $options);

		$this->em = $em;
		$this->interpreters = array();

		// create parser for rule
		$rule = $channel->getProperty('rule');
		$this->parser = new Shunt\Parser(new Shunt\Scanner($rule));

		// create parser context
		$this->ctx = new Shunt\Context();
		$this->createStaticContextFunctions();
		$this->createDynamicContextFunctions($channel->getPropertiesByRegex('/in\d/'));
	}

	/**
	 * Create static, non-data context functions
	 */
	protected function createStaticContextFunctions() {
		// php function wrappers
		$this->ctx->def('abs');
		$this->ctx->def('min');
		$this->ctx->def('max');
		$this->ctx->def('sin');
		$this->ctx->def('cos');
		$this->ctx->def('rand'); // random(lower bound, upper bound)

		// non-php mathematical functions
		$this->ctx->def('sgn', function($v) { if ($v == 0) return 0; return ($v > 0) ? 1 : -1; }); // signum
		$this->ctx->def('avg', function() { return (array_sum(func_get_args()) / func_num_args()); }); // avg

		// logical functions
		$this->ctx->def('if', function($if, $then, $else = 0) { return $if ? $then : $else; });
		$this->ctx->def('ifnull', function($if, $then) { return $if ?: $then; });

		// date/time functions
		$this->ctx->def('year', function($ts) { return (int) date('Y', (int) $ts); });
		$this->ctx->def('month', function($ts) { return (int) date('n', (int) $ts); });
		$this->ctx->def('day', function($ts) { return (int) date('d', (int) $ts); });
		$this->ctx->def('hour', function($ts) { return (int) date('H', (int) $ts); });
		$this->ctx->def('minutes', function($ts) { return (int) date('i', (int) $ts); });
		$this->ctx->def('seconds', function($ts) { return (int) date('s', (int) $ts); });
	}

	/**
	 * Create interpreters and parser and assign inputs and functions
	 *
	 * @param Iterable $uuids list of input channel uuids
	 */
	protected function createDynamicContextFunctions($uuids) {
		// assign data functions
		$this->ctx->def('val', array($this, '_val'));	// value
		$this->ctx->def('ts', array($this, '_ts')); 	// timestamp
		$this->ctx->def('from', array($this, '_from')); // from timestamp
		$this->ctx->def('to', array($this, '_to')); 	// to timestamp

		// assign input channel functions
		foreach ($uuids as $key => $value) {
			$this->ctx->def($key, $key, 'string'); // as key constant
			$this->ctx->def($key, function() use ($key) { return $this->_val($key); }); // as value function

			// get chached entity
			$entity = Controller\EntityController::factory($this->em, $value, true);

			// define named parameters
			$title = preg_replace('/\s*/', '', $entity->getProperty('title'));
			$this->ctx->def($title, $key, 'string'); // as key constant
			$this->ctx->def($title, function() use ($key) { return $this->_val($key); }); // as value function

			$class = $entity->getDefinition()->getInterpreter();
			$interpreter = new $class($entity, $this->em, $this->from, $this->to, $this->tupleCount, $this->groupBy, $this->options);

			$proxy = new Virtual\InterpreterProxy($interpreter, $key == self::PRIMARY);

			if ($key !== self::PRIMARY) {
				$this->setProxyMatchStrategy($entity, $proxy);
			}

			$this->interpreters[$key] = $proxy;
		}
	}

	/**
	 * Take entity line style into consideration for how timestamps need be interpreted
	 */
	private function setProxyMatchStrategy(Model\Entity $entity, Virtual\InterpreterProxy $proxy) {
		if (!$entity->hasProperty('style')) {
			// assume MODE_BEST which is the default
			return;
		}
		if ($interpretationStyle = $entity->getProperty('style')) {
			switch ($interpretationStyle) {
				case 'states':
					// only use values of timestamps < current
					// @TODO check if < is enforced or <=
					$proxy->setMatchMode(Virtual\InterpreterProxy::MODE_BEFORE);
					break;
				case 'steps':
					// only use values of timestamps >= current
					$proxy->setMatchMode(Virtual\InterpreterProxy::MODE_AFTER);
					break;
			}
		}
	}

	/**
	 * Context function: get channel timestamp
	 */
	public function _ts($key = self::PRIMARY) {
		return $this->interpreters[$key]->current()[0] / 1e3;
	}

	/**
	 * Context function: get channel value
	 */
	public function _val($key = self::PRIMARY) {
		return $this->interpreters[$key]->current()[1];
	}

	/**
	 * Context function: get channel first timestamp
	 */
	public function _from($key = self::PRIMARY) {
		return $this->interpreters[$key]->getFrom();
	}

	/**
	 * Context function: get channel last timestamp
	 */
	public function _to($key = self::PRIMARY) {
		return $this->interpreters[$key]->getTo();
	}

	/**
	 * Generate database tuples
	 *
	 * @return \Generator
	 */
	public function getIterator() {
		$this->rowCount = 0;

		// loop primary channel for timestamps
		foreach ($this->interpreters[self::PRIMARY] as $tuple) {
			// move interpreter to match primary timestamp
			foreach ($this->interpreters as $key => $interpreter) {
				if ($key == self::PRIMARY) {
					// get initial timestamp after iterator is initialized
					if (!isset($ts_last)) {
						$ts_last = $this->getFrom();
					}
				}
				else {
					$interpreter->advanceIteratorToTimestamp($tuple[0]);
				}
			}

			// calculate
			$value = $this->parser->reduce($this->ctx);

			if (!is_numeric($value)) {
				throw new \Exception("Virtual channel rule must yield numeric value.");
			}

			// implement consumption calculation
			//
			// if ($this->output == self::CONSUMPTION_VALUES) {
			// 	$this->consumption += $value * 3.6e6;
			// }
			// else {
			// 	$this->consumption += $value * ($tuple[0] - $ts_last);
			// }

			$this->consumption += $value * ($tuple[0] - $ts_last);

			$ts_last = $tuple[0];

			$res = array($tuple[0], $value, 1);

			$this->updateMinMax($res);
			$this->rowCount++;

			yield $res;
		}
	}

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
		return $this->current = $row;
	}

	/**
	 * From/to timestamps delegated to leading interpreter
	 */
	public function getFrom() {
		return $this->interpreters[self::PRIMARY]->getFrom();
	}

	public function getTo() {
		return $this->interpreters[self::PRIMARY]->getTo();
	}

	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		return $this->channel->getDefinition()->hasConsumption ? $this->consumption / 3.6e6 : NULL; // convert to Wh
	}

	/**
	 * Get Average
	 *
	 * @return float average
	 */
	public function getAverage() {
		if (!$this->consumption) {
			return 0;
		}

		if ($this->output == self::CONSUMPTION_VALUES) {
			return $this->getConsumption() / $this->rowCount;
		}
		else {
			$delta = $this->getTo() - $this->getFrom();
			return $this->consumption / $delta;
		}
	}

	/**
	 * Return sql grouping expression.
	 *
	 * Override Interpreter->groupExpr
	 *
	 * @author Andreas Götz <cpuidle@gmx.de>
	 * @param string $expression sql parameter
	 * @return string grouped sql expression
	 */
	public static function groupExprSQL($expression) {
		return 'AVG(' . $expression . ')';
	}
}

?>
