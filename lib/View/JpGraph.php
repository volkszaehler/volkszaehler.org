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
use Volkszaehler\Model;
use Volkszaehler\Util;

require_once VZ_DIR . '/lib/vendor/JpGraph/jpgraph.php';
require_once VZ_DIR . '/lib/vendor/JpGraph/jpgraph_scatter.php';
require_once VZ_DIR . '/lib/vendor/JpGraph/jpgraph_date.php';

/**
 * Plotting and graphing of data on the server side
 *
 * This view uses the JpGraph PHP5 plotting library
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 * @link http://jpgraph.net/
 * @todo add caching
 * @todo rework
 */
class JpGraph extends View {
	/**
	 * @var indicator => ynaxis[n] mapping
	 */
	protected $axes = array();

	protected $channels = array();

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
	protected static $colors = array('#83CAFF', '#7E0021', '#579D1C', '#FFD320', '#FF420E', '#004586', '#0084D1', '#C5000B', '#FF950E', '#4B1F6F', '#AECF00', '#314004');

	/**
	 * @var JPGrpah handle
	 */
	protected $graph;

	/**
	 * Constructor
	 *
	 * @param HTTP\Request $request
	 * @param HTTP\Response $response
	 * @param string $format one of png, jpeg, gif
	 */
	public function __construct(HTTP\Request $request, HTTP\Response $response, $format = 'png') {
		parent::__construct($request, $response);

		// to enabled jpgraphs graphical exception handler
		restore_exception_handler();

		if ($this->request->getParameter('width')) {
			$this->width = $this->request->getParameter('width');
		}

		if ($this->request->getParameter('height')) {
			$this->height = $this->request->getParameter('height');
		}

		$this->graph = new \Graph($this->width, $this->height);

		$this->graph->img->SetImgFormat($format);

		// Specify what scale we want to use,
		$this->graph->SetScale('datlin');

		$this->graph->legend->SetPos(0.1,0.02, 'left', 'top');
		$this->graph->legend->SetShadow(FALSE);

		$this->graph->SetMarginColor('white');
		$this->graph->SetYDeltaDist(65);
		$this->graph->yaxis->SetTitlemargin(36);

		$this->graph->SetTickDensity(TICKD_DENSE, TICKD_SPARSE);
		$this->graph->xaxis->SetFont(FF_ARIAL);

		$this->graph->xaxis->SetLabelAngle(45);
		$this->graph->xaxis->SetLabelFormatCallback(function($label) { return date('j.n.y G:i', $label); });

		$this->graph->img->SetAntiAliasing(function_exists('imageantialias'));
	}

	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter || $data instanceof Interpreter\AggregatorInterpreter) {
			$this->addData($data);
		}
		elseif($data instanceof Interpreter\AggregatorInterpreter) {
			foreach ($data->getEntity()->getChildren() as $child) {
				$this->add($child);
			}
		}
		else {
			// suppress other classes
			//throw new \Exception('Can\'t show ' . get_class($data));
		}
	}

	/**
	 * adds new plot to the graph
	 *
	 * @param $obj
	 * @param $data
	 */
	public function addData(Interpreter\InterpreterInterface $interpreter){
		$data = $interpreter->processData($this->width/4);

		if (count($data) > 0) {
			$count = count($this->channels);
			$xData = $yData = array();

			foreach ($data as $reading) {
				$xData[] = $reading[0] / 1000;
				$yData[] = $reading[1];
			}

			// Create the scatter plot
			$plot = new \ScatterPlot($yData, $xData);

			$plot->setLegend($interpreter->getEntity()->getProperty('title') . ':  [' . $interpreter->getEntity()->getDefinition()->getUnit() . ']');
			$plot->SetLinkPoints(TRUE, self::$colors[$count]);

			$plot->mark->SetColor(self::$colors[$count]);
			$plot->mark->SetFillColor(self::$colors[$count]);
			$plot->mark->SetType(MARK_DIAMOND);
			$plot->mark->SetWidth(1);

			$axis = $this->getAxisIndex($interpreter->getEntity());
			if ($axis >= 0) {
				$this->graph->AddY($axis, $plot);
			}
			else {
				$this->graph->Add($plot);
			}

			$this->channels[] = $interpreter->getEntity();
		}
	}

	/**
	 * Shows exception
	 *
	 * @todo avoid graph plotting and set content-type to text/plain
	 * @param \Exception $exception
	 */
	protected function addException(\Exception $exception) {
		echo $exception;
	}

	/**
	 * check weather a axis for the indicator of $channel exists
	 *
	 * @param \Volkszaehler\Model\Channel $channel
	 */
	protected function getAxisIndex(\Volkszaehler\Model\Channel $channel) {
		$type = $channel->getType();

		if (!array_key_exists($type, $this->axes)) {
			$count = count($this->axes);
			if ($count == 0) {
				$this->axes[$type] = -1;

				$yaxis = $this->graph->yaxis;
			}
			else {
				$this->axes[$type] = $count - 1;
				$this->graph->SetYScale($this->axes[$type],'lin');

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
	 * render graph and send output directly to browser
	 *
	 * headers has been set automatically
	 */
	protected function render() {
		$this->graph->SetMargin(75, (count($this->axes) - 1) * 65 + 10, 20, 90);

		// display the graph
		$this->graph->Stroke();
	}
}

?>
