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
 * VirtualInterpreter is able to calculate data on the fly
 * using the provided `rule` and `in1`..`in9` inputs.
 */
class VirtualInterpreter extends Interpreter {

	const PRIMARY = 'in1';

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	protected $em;

	protected $interpreters;	// array of input interpreters
	protected $ic;				// collection of iterators

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
		$this->timestampGenerator = new Virtual\TimestampGenerator();

		// create parser for rule
		$rule = $channel->getProperty('rule');
		$this->parser = new Shunt\Parser(new Shunt\Scanner($rule));

		// create parser context
		$this->ctx = new Shunt\Context();
		$this->createStaticContextFunctions();
		$this->createDynamicContextFunctions($channel->getPropertiesByRegex('/in\d/'));

		// consolidate timestamps by period is required
		if ($this->groupBy) {
			$this->timestampGenerator = new Virtual\GroupedTimestampIterator($this->timestampGenerator, $this->groupBy);
		}
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

		// child interpreter options for calculation consumption
		// at virtual interpreter level
		$options = $this->options;
		if (false !== $idx = array_search('consumption', $options)) {
			$options[$idx] = 'consumptionto';
		}

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
			$interpreter = new $class($entity, $this->em, $this->from, $this->to, $this->tupleCount, $this->groupBy, $options);

			// timestamp strategy mode
			$proxy = new Virtual\InterpreterProxy($interpreter);
			if ($this->groupBy)
				$proxy->setStrategy(Virtual\InterpreterProxy::STRATEGY_TS_BEFORE);
			else
				$proxy->setStrategyByEntityType($entity);
			$this->interpreters[$key] = $proxy;

			// add timestamp iterator to generator
			$iterator = new Virtual\TimestampIterator($proxy->getIterator());
			$this->timestampGenerator->add($iterator);
		}
	}

	/**
	 * Context function: get channel timestamp
	 */
	public function _ts($key = self::PRIMARY) {
		throw new \Exception("Not tested");
		return $this->interpreters[$key]->getTimestamp();
	}

	/**
	 * Context function: get channel value
	 */
	public function _val($key = self::PRIMARY) {
		return $this->interpreters[$key]->getValueForTimestamp($this->ts);
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
		$ts_last = null;

		foreach ($this->timestampGenerator as $this->ts) {
			if (!isset($ts_last)) {
				// create first timestmap as min from interpreters
				foreach ($this->interpreters as $interpreter) {
					$from = $interpreter->getFrom();
					$ts_last = ($ts_last === null) ? $from : min($ts_last, $from);
				}
				$this->from = $ts_last;
			}

			// calculate
			$value = $this->parser->reduce($this->ctx);

			if (!is_numeric($value)) {
				throw new \Exception("Virtual channel rule must yield numeric value.");
			}

			if ($this->output == self::CONSUMPTION_VALUES) {
				$this->consumption += $value * 3.6e6;
			}
			else {
				$this->consumption += $value * ($this->ts - $ts_last);
			}

			$tuple = array($this->ts, $value, 1);
			$ts_last = $this->ts;

			$this->updateMinMax($tuple);
			$this->rowCount++;

			yield $tuple;
		}

		$this->to = $ts_last;
	}

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
		return $this->current = $row;
	}

	/*
	 * From/to timestamps delegated to leading interpreter
	 */

	public function getFrom() {
		return $this->from;
	}

	public function getTo() {
		return $this->to;
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
