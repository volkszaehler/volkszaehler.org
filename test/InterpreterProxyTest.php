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

class ComplexInterpreter extends BaseInterpreter
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
			yield 1000 * $ts;
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

	/**
	 * @dataProvider skewDataProvider
	 */
	function testSimpleTimestampMatch($skew) {
		$interpreter = new InterpreterProxy(new SkewInterpreter($skew));

		foreach ($this->iterator() as $ts) {
			// printf(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];
			// printf("<\t%d\n", $its);

			$this->assertEquals($ts + $skew, $its);
			// printf("\n");
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
			// printf(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];
			// printf("<\t%d\n", $its);

			$expectation = $its <= $ts ? $its : $last;

			$this->assertEquals($expectation, $its);
			// printf("\n");

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
			// printf(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];
			// printf("<\t%d\n", $its);

			$expectation = $its <= $ts + $delta ? $its : $last;

			$this->assertEquals($expectation, $its);
			// printf("\n");

			$last = $its;
		}
	}

	/**
	 * @dataProvider skewBeforeDataProvider
	 */
	function testMatchComplexTimestampBefore($delta) {
		$interpreter = new InterpreterProxy(new ComplexInterpreter());
		$interpreter->setMatchMode($delta); // InterpreterProxy::MODE_BEFORE or better

		$last = 0;

		foreach ($this->iterator() as $ts) {
			// printf(">\t%d\n", $ts);

			// move all interpreters
			$interpreter->advanceIteratorToTimestamp($ts);

			$its = $interpreter->current()[0];
			// printf("<\t%d\n", $its);

			$expectation = $its <= $ts + $delta ? $its : $last;

			$this->assertEquals($expectation, $its);
			// printf("\n");

			$last = $its;
		}
	}
}

?>
