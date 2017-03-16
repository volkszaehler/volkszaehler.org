<?php
/**
 * InterpreterProxy tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Interpreter\Interpreter;
use Volkszaehler\Interpreter\Virtual\InterpreterProxy;

abstract class BaseInterpreter extends Interpreter
{
	public function __construct() {
	}

	public function convertRawTuple($row) {
		throw new \Exception();
	}
}

/**
 * Add defined skew to timestamps of base iterator
 */
class SkewInterpreter extends BaseInterpreter
{
	public function __construct($skew) {
		$this->skew = $skew;
	}

	public function getIterator() {
		foreach (InterpreterProxyTest::iterator() as $ts) {
			yield array($ts + $this->skew, 0, 0);
		}
	}
}

/**
 * Skip defined timestamps of base iterator
 */
class SkipInterpreter extends BaseInterpreter
{
	public function __construct($skip) {
		$this->skip = $skip;
	}

	public function getIterator() {
		$idx = 0;
		foreach (InterpreterProxyTest::iterator() as $ts) {
			if (!in_array($idx, $this->skip)) {
				// static::debug("g\t%d\n", $ts);
				yield array($ts, 0, 0);
			}
			$idx++;
		}
	}
}

class SeriesInterpreter extends BaseInterpreter
{
	public function getIterator() {
		$timestamps = [
			1100,
			1400,
			2300,
			3100,
			3500,
			4900
		];
		foreach ($timestamps as $ts) {
			yield array($ts, 0, 0);
		}
	}
}

class InterpreterProxyTest extends \PHPUnit_Framework_TestCase
{
	public static function iterator() {
		$timestamps = [1, 2, 3, 4, 5];
		foreach ($timestamps as $ts) {
			yield 1000 * $ts * $ts;
		}
	}

	public static function skewDataProvider() {
		return array(
			[ 0], [-200], [+200]
		);
	}

	public static function skewBeforeDataProvider() {
		return array(
			[ 0], [+100], [+200]
		);
	}

	static function debug() {
		return;

		$args = func_get_args();
	call_user_func_array('printf', $args);
	}

	function testSkipMissingTimestampMatch() {
		$skip = [1, 2];
		$interpreter = new InterpreterProxy(new SkipInterpreter($skip));

		$idx = 0;
		$validList = [];

		// build list of valid non-skipped timestamps
		foreach ($this->iterator() as $ts) {
			if (!in_array($idx++, $skip)) {
				$validList[] = $ts;
			}
		}

		$idx = 0;
		$its = null;
		$last_val = null;

		foreach ($this->iterator() as $ts) {
			// static::debug("%d.\n", $idx);
			// static::debug(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			// find lowest difference
			$expectation = array_reduce($validList, function($carry, $item) use ($ts) {
				$delta = abs($ts - $item);

				if ($delta < $carry[1])
					return [$item, $delta];
				else
					return $carry;
			}, [null, PHP_INT_MAX]);

			$expectation = $expectation[0];
			// static::debug("e\t%d\n", $expectation);

			$its = $interpreter->current()[0];
			// static::debug("<\t%d\n", $its);

			$this->assertEquals($expectation, $its);
			// static::debug("\n");

			$idx++;
		}
	}

	/**
	 * @dataProvider skewDataProvider
	 */
	function testSimpleTimestampMatch($skew) {
		$interpreter = new InterpreterProxy(new SkewInterpreter($skew));

		foreach ($this->iterator() as $ts) {
			// static::debug(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];
			// static::debug("e\t%d\n", $ts + $skew);
			// static::debug("<\t%d\n", $its);

			$this->assertEquals($ts + $skew, $its);
			// static::debug("\n");
		}
	}

	/**
	 * @dataProvider skewDataProvider
	 */
	function testMatchTimestampBefore($skew) {
		$interpreter = new InterpreterProxy(new SkewInterpreter($skew));
		$interpreter->setMatchMode(InterpreterProxy::MODE_BEFORE);

		$last = 0;

		foreach ($this->iterator() as $ts) {
			// static::debug(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];

			$expectation = $its <= $ts ? $its : $last;
			// static::debug("e\t%d\n", $expectation);
			// static::debug("<\t%d\n", $its);

			$this->assertEquals($expectation, $its);
			// static::debug("\n");

			$last = $its;
		}
	}

	/**
	 * @dataProvider skewDataProvider
	 */
	function testMatchTimestampBeforeDelta($skew) {
		$delta = 100;

		$interpreter = new InterpreterProxy(new SkewInterpreter($skew));
		$interpreter->setMatchMode($delta);

		$last = 0;

		foreach ($this->iterator() as $ts) {
			// static::debug(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];
			// static::debug("<\t%d\n", $its);

			$expectation = $its <= $ts + $delta ? $its : $last;

			$this->assertEquals($expectation, $its);
			// static::debug("\n");

			$last = $its;
		}
	}

	/**
	 * @dataProvider skewBeforeDataProvider
	 */
	function testMatchComplexTimestampBefore($delta) {
		$interpreter = new InterpreterProxy(new SeriesInterpreter());
		$interpreter->setMatchMode($delta); // InterpreterProxy::MODE_BEFORE or better

		$last = 0;

		foreach ($this->iterator() as $ts) {
			// static::debug(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];
			// static::debug("<\t%d\n", $its);

			$expectation = $its <= $ts + $delta ? $its : $last;

			$this->assertEquals($expectation, $its);
			// static::debug("\n");

			$last = $its;
		}
	}
}

?>
