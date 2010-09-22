<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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

namespace Volkszaehler\View;

use Volkszaehler\Interpreter;

use Volkszaehler\View\HTTP;
use Volkszaehler\Util;
use Volkszaehler\Model;

/**
 * XML view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class XML extends View {
	protected $xmlDoc = NULL;
	protected $xmlRoot = NULL;
	protected $xmlChannels = NULL;
	protected $xmlAggregators = NULL;
	protected $xmlDatas = NULL;

	public function __construct(HTTP\Request $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');

		$this->xmlRoot = $this->xmlDoc->createElement('volkszaehler');
		$this->xmlDoc->appendChild($this->xmlRoot);

		$this->xmlRoot->setAttribute('version', VZ_VERSION);
		$this->xmlRoot->setAttribute('source', 'volkszaehler.org');

		$this->response->setHeader('Content-type', 'application/xml; charset=UTF-8');
	}

	protected function addData(Interpreter\Interpreter $interpreter) {
		$data = $interpreter->getValues();

		$xmlData = $this->xmlDoc->createElement('data');

		foreach ($data as $reading) {
			$xmlReading = $this->xmlDoc->createElement('reading');

			$xmlReading->setAttribute('timestamp', $reading[0]);	// hardcoded data fields for performance optimization
			$xmlReading->setAttribute('value', $reading[1]);
			$xmlReading->setAttribute('count', $reading[2]);

			$xmlData->appendChild($xmlReading);
		}

		if (!isset($this->xmlDatas)) {
			$this->xmlDatas = $this->xmlDoc->createElement('datas');
			$this->xmlRoot->appendChild($this->xmlDatas);
		}

		$this->xmlDatas->appendChild($xmlData);
	}

	public function addChannel(Model\Channel $channel) {
		$xmlChannel = $this->xmlDoc->createElement('channel');
		$xmlChannel->setAttribute('uuid', $channel->getUuid());

		$xmlChannel->appendChild($this->xmlDoc->createElement('indicator', $channel->getIndicator()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('unit', $channel->getUnit()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('name', $channel->getName()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('description', $channel->getDescription()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('resolution', (int) $channel->getResolution()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('cost', (float) $channel->getCost()));

		if (!isset($this->xmlChannels)) {
			$this->xmlChannels = $this->xmlDoc->createElement('channels');
			$this->xmlRoot->appendChild($this->xmlChannels);
		}

		$this->xmlChannels->appendChild($xmlChannel);
	}

	public function addAggregator(Model\Aggregator $aggregator, $recursive = FALSE) {
		if (!isset($this->xmlAggregators)) {
			$this->xmlAggregators = $this->xmlDoc->createElement('groups');
			$this->xmlRoot->appendChild($this->xmlAggregators);
		}

		$this->xmlAggregators->appendChild($this->toXml($aggregator, $recursive));
	}

	public function toXml(Model\Aggregator $aggregator, $recursive = FALSE) {
		$xmlAggregator = $this->xmlDoc->createElement('group');
		$xmlAggregator->setAttribute('uuid', $aggregator->getUuid());
		$xmlAggregator->appendChild($this->xmlDoc->createElement('name', $aggregator->getName()));
		$xmlAggregator->appendChild($this->xmlDoc->createElement('description', $aggregator->getDescription()));

		if ($recursive) {
			$xmlChildren = $this->xmlDoc->createElement('children');

			foreach ($aggregator->getChildren() as $child) {
				$xmlChildren->appendChild($this->toXml($child, $recursive));
			}

			$xmlAggregator->appendChild($xmlChildren);
		}

		return $xmlAggregator;
	}

	public function addDebug(Util\Debug $debug) {
		$xmlDebug = $this->xmlDoc->createElement('debug');

		$xmlDebug->appendChild($this->xmlDoc->createElement('time', $debug->getExecutionTime()));
		$xmlDebug->appendChild($this->xmlDoc->createElement('database', Util\Configuration::read('db.driver')));

		// TODO add queries to xml debug
		// TODO add messages to xml output

		$this->xmlRoot->appendChild($xmlDebug);
	}

	protected function addException(\Exception $exception) {
		$xmlException = $this->xmlDoc->createElement('exception');
		$xmlException->setAttribute('code', $exception->getCode());
		$xmlException->appendChild($this->xmlDoc->createElement('message', $exception->getMessage()));
		$xmlException->appendChild($this->xmlDoc->createElement('line', $exception->getLine()));
		$xmlException->appendChild($this->xmlDoc->createElement('file', $exception->getFile()));
		$xmlException->appendChild($this->fromTrace($exception->getTrace()));

		$this->xmlRoot->appendChild($xmlException);
	}

	protected function render() {
		echo $this->xmlDoc->saveXML();
	}

	private function fromTrace($traces) {
		$xmlTraces = $this->xmlDoc->createElement('backtrace');

		foreach ($traces as $step => $trace) {
			$xmlTrace = $this->xmlDoc->createElement('trace');
			$xmlTraces->appendChild($xmlTrace);
			$xmlTrace->setAttribute('step', $step);

			foreach ($trace as $key => $value) {
				switch ($key) {
					case 'args':
						$xmlArgs = $this->xmlDoc->createElement($key);
						$xmlTrace->appendChild($xmlArgs);
						foreach ($value as $arg) {
							$xmlArgs->appendChild($this->xmlDoc->createElement('arg', (is_scalar($value)) ? $value : 'object'));
						}
						break;

					case 'type':
					case 'function':
					case 'line':
					case 'file':
					case 'class':
					default:
						$xmlTrace->appendChild($this->xmlDoc->createElement($key, $value));
				}
			}
		}

		return $xmlTraces;
	}
}

?>
