<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
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

	use Virtual\InterpreterCoordinatorTrait;

	const PRIMARY = 'in1';

	/**
	 * @var ORM\EntityManager
	 */
	protected $em;

	protected $ctx;
	protected $parser;

	protected $count; 			// number of rows
	protected $consumption; 	// in Wms (Watt milliseconds)
	protected $ts; 				// current tuple timestamp
	protected $ts_last; 		// previous tuple timestamp

	/**
	 * Constructor
	 *
	 * @param Model\Channel $channel
	 * @param ORM\EntityManager $em
	 */
	public function __construct(Model\Channel $channel, ORM\EntityManager $em, $from, $to, $tupleCount = null, $groupBy = null, $options = array()) {
		parent::__construct($channel, $em, $from, $to, $tupleCount, $groupBy, $options);
		$this->em = $em;

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
		// php function wrappers (see php manual for arguments)
		$this->ctx->def('abs');           //Absolute value
		$this->ctx->def('ceil');          //Round fractions up
		$this->ctx->def('exp');           //Calculates the exponent of e
		$this->ctx->def('floor');         //Round fractions down
		$this->ctx->def('fmod');          //Returns the floating point remainder (modulo) of the division of the arguments
		$this->ctx->def('log10');         //Base-10 logarithm
		$this->ctx->def('log');           //Natural logarithm
		$this->ctx->def('max');           //Find highest value
		$this->ctx->def('min');           //Find lowest value
		$this->ctx->def('pow');           //Exponential expression
		$this->ctx->def('round');         //Rounds a float
		$this->ctx->def('sqrt');          //Square root
		$this->ctx->def('sin');           //Sine parameter in radians
		$this->ctx->def('cos');           //Cosine parameter in radians
		$this->ctx->def('rand');          //Random(lower bound, upper bound)

		// non-php mathematical functions
		$this->ctx->def('sgn', function($v) { if ($v == 0) return 0; return ($v > 0) ? 1 : -1; }); // signum
		$this->ctx->def('avg', function() { return (array_sum(func_get_args()) / func_num_args()); }); // avg

		// logical functions
		$this->ctx->def('if', function($if, $then, $else = 0) { return $if ? $then : $else; });
		$this->ctx->def('ifnull', function($if, $then) { return $if ?: $then; });
		$this->ctx->def('or', function() { $res=false; foreach ( func_get_args() as $v ) $res = $res || $v; return $res; });
		$this->ctx->def('and', function() { $res=true; foreach ( func_get_args() as $v ) $res = $res && $v; return $res; });

		// date/time functions
		$this->ctx->def('year', function($ts) { return (int) date('Y', (int) $ts); });
		$this->ctx->def('month', function($ts) { return (int) date('n', (int) $ts); });
		$this->ctx->def('day', function($ts) { return (int) date('d', (int) $ts); });
		$this->ctx->def('hour', function($ts) { return (int) date('H', (int) $ts); });
		$this->ctx->def('minutes', function($ts) { return (int) date('i', (int) $ts); });
		$this->ctx->def('seconds', function($ts) { return (int) date('s', (int) $ts); });
		$this->ctx->def('weekday', function($ts) { return (int) date('N', (int) $ts); }); //1=Monday 7=Sunday
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
		$this->ctx->def('prev', array($this, '_prev')); // previous timestamp
		$this->ctx->def('from', array($this, '_from')); // from timestamp
		$this->ctx->def('to', array($this, '_to')); 	// to timestamp

		$this->ctx->def('cons', array($this, '_consumption')); 	// period consumption

		// child interpreter options for calculation consumption
		// at virtual interpreter level
		$options = $this->options;
		if (false !== $idx = array_search('consumption', $options)) {
			$options[$idx] = 'consumptionto';
		}

		$ef = Util\EntityFactory::getInstance($this->em);

		// assign input channel functions
		foreach ($uuids as $key => $value) {
			$this->ctx->def($key, $key, 'string'); // as key constant
			$this->ctx->def($key, function() use ($key) { return $this->_val($key); }); // as value function

			// get chached entity
			$entity = $ef->get($value, true);

			// define named parameters
			$title = preg_replace('/\s*/', '', $entity->getProperty('title'));
			$this->ctx->def($title, $key, 'string'); // as key constant
			$this->ctx->def($title, function() use ($key) { return $this->_val($key); }); // as value function

			$class = $entity->getDefinition()->getInterpreter();
			$interpreter = new $class($entity, $this->em, $this->from, $this->to, $this->tupleCount, $this->groupBy, $options);

			// add interpreter to timestamp coordination
			$this->addCoordinatedInterpreter($key, $interpreter);
		}
	}

	/*
	 * Context functions
	 */

	// get channel timestamp
	public function _ts($key = self::PRIMARY) {
		return $this->ts;
	}

	// get previous channel timestamp
	public function _prev() {
		return $this->ts_last;
	}

	// get channel value
	public function _val($key = self::PRIMARY) {
		return $this->getCoordinatedInterpreter($key)->getValueForTimestamp($this->ts);
	}

	// get channel first timestamp
	public function _from($key = self::PRIMARY) {
		return $this->getCoordinatedInterpreter($key)->getFrom();
	}

	// get channel last timestamp
	public function _to($key = self::PRIMARY) {
		return $this->getCoordinatedInterpreter($key)->getTo();
	}

	// get period consumption
	public function _consumption($value) {
		if (null === $prev = $this->_prev()) {
			throw new \LogicException("_consumption could not determine previous timestamp");
			return 0;
		}

		$period = $this->_ts() - $prev;
		$consumption = $value * $period / 3.6e6;
		return $consumption;
	}

	/**
	 * Generate database tuples
	 *
	 * @return \Generator
	 */
	public function getIterator() {
		$this->rowCount = 0;
		$this->ts_last = null;

		foreach ($this->getTimestampGenerator() as $this->ts) {
			if (!isset($this->ts_last)) {
				// create first timestmap as min from interpreters
				$this->ts_last = $this->from = $this->getCoordinatedFrom();
			}

			// calculate
			$value = $this->parser->reduce($this->ctx);

			if (!is_numeric($value)) {
				throw new \Exception("Virtual channel rule must yield numeric value.");
			}

			// if ($this->output == self::CONSUMPTION_VALUES) {
			// 	$value *= ($this->ts - $this->ts_last) / 3.6e6;
			// 	$this->consumption += $value;
			// }
			// else {
			// 	$this->consumption += $value * ($this->ts - $this->ts_last) / 3.6e6;
			// }
			$this->consumption += $value * ($this->ts - $this->ts_last) / 3.6e6;

			$tuple = array($this->ts, $value, 1);
			$this->ts_last = $this->ts;

			$this->updateMinMax($tuple);
			$this->rowCount++;

			yield $tuple;
		}

		$this->to = $this->ts_last;
	}

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
		return $row; // not implemented
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
		return $this->channel->getDefinition()->hasConsumption ? $this->consumption : NULL; // convert to Wh
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
