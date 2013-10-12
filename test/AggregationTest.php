<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataBaseFunctions.php');

use Volkszaehler\Util;

class DataMeterTest extends DataBaseFunctions
{
	// channel properties
	protected $title = 'Aggregation';
	protected $type = 'power';
	protected $resolution = 100;

	private $conn;
	private $agg;

	private $tuples;	// for comparison

	function __destruct() {	}

	function __construct() {
		$this->uuid = '0d1e6340-3318-11e3-ae38-5b51c01ed9e7';
		parent::__construct();

		// create aggregation environment
		self::setupDoctrine();
		$this->conn = \Doctrine\DBAL\DriverManager::getConnection(Util\Configuration::read('db'));

		$this->agg = new Util\Aggregation($this->conn);
	}

	static function setupDoctrine() {
		define('VZ_DIR', realpath(__DIR__).'/..');

		// class autoloading
		require_once VZ_DIR . '/lib/Util/ClassLoader.php';
		require_once VZ_DIR . '/lib/Util/Configuration.php';

		// load configuration
		Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');

		define('DOCTRINE_DIR', Util\Configuration::read('lib.doctrine') ? Util\Configuration::read('lib.doctrine') : 'Doctrine');

		$classLoaders = array(
			new Util\ClassLoader('Doctrine', DOCTRINE_DIR),
			new Util\ClassLoader('Volkszaehler', VZ_DIR . '/lib')
		);

		foreach ($classLoaders as $loader) {
			$loader->register(); // register on SPL autoload stack
		}
	}

	protected function countAggregationRows() {
		return $this->conn->fetchColumn(
			'SELECT COUNT(aggregate.id) FROM aggregate ' .
			'LEFT JOIN entities ON aggregate.channel_id = entities.id ' .
			'WHERE entities.uuid = ?', array($this->uuid)
		);
	}
/*
	function testClearAggregation() {
		$this->agg->clear();

		$rows = $this->conn->fetchColumn('SELECT COUNT(id) FROM aggregate');
		$this->assertTrue($rows == 0, 'aggregate table cannot be cleared');
	}

	// TODO fix DST calculations
	function testDeltaAggregation() {
		// 0:00 today current timezone - must not be aggregated
		$this->addDatapoint(strtotime('today 0:00') * 1000, 100);
		$this->agg->aggregate('delta');

		$rows = $this->countAggregationRows();
		$this->assertTrue($rows == 0, 'current period wrongly appears in aggreate table');

		// 0:00 last two days - must be aggregated
		$this->addDatapoint((strtotime('1 days ago 0:00')) * 1000, 100);
		$this->addDatapoint((strtotime('1 days ago 12:00')) * 1000, 100);
		$this->addDatapoint((strtotime('2 days ago 0:00')) * 1000, 100);
		$this->addDatapoint((strtotime('2 days ago 12:00')) * 1000, 100);
		$this->agg->aggregate('delta');

		$rows = $this->countAggregationRows();
		$this->assertTrue($rows == 2, 'last period missing from aggreate table');

		// 0:00 three days ago - must not be aggregated
		$this->addDatapoint((strtotime('3 days ago 0:00')) * 1000, 100);
		$this->agg->aggregate('delta');

		$rows = $this->countAggregationRows();
		$this->assertTrue($rows == 2, 'period before last wrongly appears in aggreate table');
	}
*/
	function testGetBaseline() {
		$this->agg->clear();

		// get unaggregated datapoints for comparison
		$this->getDatapointsRaw(strtotime('3 days ago 0:00') * 1000, null, 'day');
		$this->assertTrue($this->json->data->rows == 4);

		$this->tuples = $this->json->data->tuples;
print_r($this->json->data);
echo("<br/>");

		$this->agg->aggregate('delta');
	}

	function testFullAggregation() {
		// currently not implemented for performance reasons
	}
/*
	function testAggregateRetrievalFrom() {
		// 1 data
		$this->getDatapointsRaw(strtotime('today 0:00') * 1000, null, 'day');
		$this->assertTrue($this->json->data->rows == 1);

		// 1 agg + 1 data
		$this->getDatapointsRaw(strtotime('1 days ago 0:00') * 1000, null, 'day');
		$this->assertTrue($this->json->data->rows == 2);

		//  1 agg + 1 agg + 1 data
		$this->getDatapointsRaw(strtotime('2 days ago 0:00') * 1000, null, 'day');
		$this->assertTrue($this->json->data->rows == 3);

		// 1 data + 1 agg + 1 agg + 1 data
		$this->getDatapointsRaw(strtotime('3 days ago 0:00') * 1000, null, 'day');
		$this->assertTrue($this->json->data->rows == 4);
	}
*/
	/*
	function testAggregateRetrievalTo() {
		// 1 data + 1 agg + 1 agg + 1 data
		$this->getDatapointsRaw(strtotime('3 days ago 0:00') * 1000, strtotime('today 18:00') * 1000, 'day');
		$this->assertTrue($this->json->data->rows == 4);

		// 1 data + 1 agg + 1 data(aggregated)
		$this->getDatapointsRaw(strtotime('3 days ago 0:00') * 1000, strtotime('1 days ago 6:00') * 1000, 'day');
		$this->assertTrue($this->json->data->rows == 3);
print_r($this->json->data->tuples);
echo("<br/>");

		// 1 data + 1 data(aggregated)
		$this->getDatapointsRaw(strtotime('3 days ago 0:00') * 1000, strtotime('2 days ago 6:00') * 1000, 'day');
		$this->assertTrue($this->json->data->rows == 2);
print_r($this->json->data->tuples);
echo("<br/>");

		// // 1 data
		// $this->getDatapointsRaw(strtotime('3 days ago 0:00') * 1000, strtotime('3 days ago 18:00') * 1000, 'day');
		// $this->assertTrue($this->json->data->rows == 1);
	}
	*/
}

?>
