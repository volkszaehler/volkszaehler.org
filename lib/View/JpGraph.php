<?php
/**
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
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

use Volkszaehler\Interpreter;
use Volkszaehler\Model;
use Volkszaehler\Util;

/**
 * Plotting and graphing of data on the server side
 *
 * This view uses the JpGraph PHP5 plotting library
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @link http://jpgraph.net/
 */
class JpGraph extends View {
	/**
	 * @var indicator => ynaxis[n] mapping
	 */
	protected $axes = array();

	protected $count = 0;

	/**
	 * @var default width
	 */
	protected $width = 800;

	/**
	 * @var default height
	 */
	protected $height = 400;

	/**
	 * @var color palette for the scatter plots
	 * This are the same colors as in the webfronted
	 */
	protected $colors;

	/**
	 * @var JPGraph handle
	 */
	protected $graph;

	/**
	 * Constructor
	 *
	 * @param Symfony\Component\HttpFoundation\Request $request
	 * @param string $format one of png, jpeg, gif
	 */
	public function __construct(Request $request, $format = 'png') {
		parent::__construct($request);
		$this->response->headers->set('Content-Type', 'image/' . $format);

		// load JpGraph
		// NOTE: JpGraph installs its own graphical error handler
		\JpGraph\JpGraph::load();
		\JpGraph\JpGraph::module('date');
		\JpGraph\JpGraph::module('line');

		if ($this->request->query->has('width')) {
			$this->width = $this->request->query->get('width');
		}

		if ($this->request->query->has('height')) {
			$this->height = $this->request->query->get('height');
		}

		$this->colors = Util\Configuration::read('colors');
		$this->graph = new \Graph($this->width, $this->height);

		// disable JpGraph default handler
		restore_exception_handler();

		$this->graph->img->SetImgFormat($format);

		// Specify what scale we want to use,
		$this->graph->SetScale('datlin');

		$this->graph->legend->SetPos(0.03, 0.06);
		$this->graph->legend->SetShadow(FALSE);
		$this->graph->legend->SetFrameWeight(1);

		$this->graph->SetMarginColor('white');
		$this->graph->SetYDeltaDist(65);
		$this->graph->yaxis->SetTitlemargin(36);

		$this->graph->SetTickDensity(TICKD_DENSE, TICKD_SPARSE);
		$this->graph->xaxis->SetFont(FF_ARIAL);

		$this->graph->xaxis->SetLabelAngle(45);
		$this->graph->xaxis->SetLabelFormatCallback(function($label) { return date('j.n.y G:i', $label); });

		if (function_exists('imageantialias')) {
			$this->graph->img->SetAntiAliasing(true);
		}
	}

	/**
	 * Creates exception response
	 * NOTE: this will not work in CLI due to JpGraph design issues
	 *
	 * @param \Exception $exception
	 */
	public function getExceptionResponse(\Throwable $exception) {
		if (!($exception instanceof \JpGraphException)) {
			$exception = new \JpGraphException($exception->getMessage(), $exception->getCode());
		}

		ob_start();
		$exception->Stroke();
		$output = ob_get_contents();
		ob_end_clean();

		$this->response->setContent($output);

		return $this->response;
	}

	/**
	 * Add object to output
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
		else {
			// suppress other classes
			//throw new \JpGraphException('Can\'t show: \'' . get_class($data) . '\'');
		}
	}

	/**
	 * Adds new plot to the graph
	 *
	 * @param $interpreter
	 */
	public function addData($interpreter) {
		if (is_null($interpreter->getTupleCount())) {
			$interpreter->setTupleCount($this->width);
		}

		$data = array();
		// iterate through PDO resultset
		foreach ($interpreter as $tuple) {
			$tuple[0] /= 1000;
			$data[] = $tuple;
		}

		if (count($data) > 0) {
			$xData = $yData = array();
			// TODO adjust x-Axis

			foreach ($data as $reading) {
				$xData[] = $reading[0];
				$yData[] = $reading[1];
			}

			// Create the scatter plot
			$plot = new \LinePlot($yData, $xData);

			$plot->setLegend($interpreter->getEntity()->getProperty('title') . ':  [' . $interpreter->getEntity()->getDefinition()->getUnit() . ']');
			$plot->SetColor($this->colors[$this->count]);
			$plot->SetStepStyle($interpreter instanceof Interpreter\ImpulseInterpreter);

			$axis = $this->getAxisIndex($interpreter->getEntity());
			if ($axis >= 0) {
				$this->graph->AddY($axis, $plot);
			}
			else {
				$this->graph->Add($plot);
			}

			$this->count++;
		}
	}

	/**
	 * Check weather a axis for the indicator of $channel exists
	 *
	 * @param Model\Channel $channel
	 */
	protected function getAxisIndex(Model\Channel $channel) {
		$type = $channel->getType();

		if (!array_key_exists($type, $this->axes)) {
			$count = count($this->axes);
			if ($count == 0) {
				$this->axes[$type] = -1;

				$yaxis = $this->graph->yaxis;
			}
			else {
				$this->axes[$type] = $count - 1;
				$this->graph->SetYScale($this->axes[$type], 'lin');

				$yaxis = $this->graph->ynaxis[$this->axes[$type]];
			}

			$yaxis->title->Set($channel->getDefinition()->getUnit());

			$yaxis->SetFont(FF_ARIAL);
			$yaxis->title->SetFont(FF_ARIAL);

			$yaxis->SetTitleMargin('50');
		}

		return $this->axes[$type];
	}

	/**
	 * Render graph and return output
	 *
	 * Headers has been set automatically
	 */
	protected function render() {
		$this->graph->SetMargin(75, (count($this->axes) - 1) * 65 + 10, 20, 90);

		// display the graph
		ob_start();
		$this->graph->Stroke();
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}

?>
