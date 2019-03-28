<?php
/**
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

namespace Volkszaehler\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Volkszaehler\Util;
use Volkszaehler\Interpreter;

/**
 * Atom view
 *
 * @author Jakob Hirsch <jh.vz-2019@plonk.de>
 */
class Atom extends Text {
	/**
	 * @var \DOMDocument the XML document
	 */
	protected $dom;
	/**
	 * @var \DOMElement feed node in document
	 */
	protected $feed;
	/**
	 * @var \DOMElement title node in document
	 */
	protected $titleNode;
	/**
	 * @var array holds the channel titles
	 */
	protected $titles;

	/**
	 * Constructor
	 */
	public function __construct(Request  $request) {
		// avoid calling parent::__construct($request);
		$this->request = $request;
		$this->response = new Response();
		$this->response->headers->set('Content-Type', 'application/atom+xml');
		// set default timestamp format
		if (!$this->request->query->has('tsfmt')) {
			$this->request->query->set('tsfmt', 'sql');
		}

		$this->dom = new \DOMDocument('1.0', 'utf-8');
		$this->dom->formatOutput = true;
		$this->feed = $this->dom->appendChild($this->dom->createElement('feed'));
		$this->feed->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
		$this->feed->appendChild($this->dom->createElement('author'))->appendChild($this->dom->createElement('name', 'volkszaehler.org'));
		$this->titleNode = $this->feed->appendChild($this->dom->createElement('title'));
		$this->feed->appendChild($this->dom->createElement('id'))->appendchild($this->dom->createTextNode($this->request->getUri()));
		//$this->feed->appendChild($this->dom->createElement('updated', date(DATE_ATOM, intval($interpreter->getTo()/1000))));
		$this->feed->appendChild($this->dom->createElement('updated', date(DATE_ATOM)));
		$titles = array();
	}

	/**
	 /* Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter) {
			$this->addData($data);
		}
		elseif (is_array($data) && isset($data[0]) && $data[0] instanceof Interpreter\Interpreter) {
			foreach ($data as $interpreter) {
				$this->add($interpreter);
			}
		}
		elseif ($data instanceof \Exception) {
			$this->addException($data);
		}
		elseif ($data instanceof Util\Debug) {
			$this->addDebug($data);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show: \'' . self::getClassOrType($data) . '\'');
		}
	}

	/**
	 * Add data to output queue
	 *
	 * @param Interpreter\Interpreter $interpreter
	 * @todo  Aggregate first is assumed- this deviates from json view behaviour
	 */
	protected function addData(Interpreter\Interpreter $interpreter) {
		$entity = $interpreter->getEntity();

		if ($interpreter instanceof Interpreter\AggregatorInterpreter) {
			// min/ max etc are not populated if $children->processData hasn't been called
			return;
		}

		$data = array();
		// iterate through PDO resultset
		foreach ($interpreter as $tuple) {
			$data[] = $tuple;
		}

		// get meta data
		$definition = $entity->getDefinition();
		$unit = isset($definition->unit) ? ' ' . $definition->unit : '';
		$title = $entity->getProperty('title');
		$link = $this->request->getUri();
		$this->titles[] = $title;

		if (sizeof($data) == 0 ||
			$this->request->query->has('tuples') && $this->request->query->get('tuples') == 1
		) {
			// only one tuple requested -> return consumption
			$ts = $this->formatTimestamp($interpreter->getFrom()) . ' .. ' .$this->formatTimestamp($interpreter->getTo());
			$out = View::formatNumber($interpreter->getConsumption()) . $unit . 'h';
			$this->addEntry($title, $ts, $out, $link, $interpreter->getTo());
		}
		else {
			if ($interpreter->getOutputType() === $interpreter::CONSUMPTION_VALUES)
				$unit .= 'h';
			foreach ($data as $tuple) {
				$out = View::formatNumber($tuple[1]) . $unit;
				$this->addEntry($title, $this->formatTimestamp($tuple[0]), $out, $link, $tuple[0]);
			}
		}
	}

	/**
	 * Add entry node to XML document feed node
	 *
	 * @param string $title channel title
	 * @param string $ts timstamp (range) 
	 * @param string $summary value with unit
	 * @param string $link query link
	 * @param int $updated timestamp of the last tuple
	 */
	protected function addEntry($title, $ts, $summary, $link, $updated) {
		$entry = $this->feed->appendChild($this->dom->createElement('entry'));
		$entry->appendChild($this->dom->createElement('title'))->appendChild($this->dom->createTextNode($title . ' ' . $ts));
		$entry->appendChild($this->dom->createElement('summary', $summary));
		$entry->appendChild($this->dom->createElement('link'))->setAttribute('href', $link);
		$entry->appendChild($this->dom->createElement('id'))->appendChild($this->dom->createTextNode($title . ' ' . $ts));
		$entry->appendChild($this->dom->createElement('updated', date(DATE_ATOM, intval($updated/1000))));
	}

	/**
	 * Process, encode and print output to stdout
	 */
	protected function render() {
		$this->titleNode->appendChild($this->dom->createTextNode(implode(', ', $this->titles)));

		return $this->dom->saveXML();
	}
}
?>
