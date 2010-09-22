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

require_once VZ_BACKEND_DIR . '/lib/vendor/JpGraph/jpgraph.php';
require_once VZ_BACKEND_DIR . '/lib/vendor/JpGraph/jpgraph_scatter.php';
require_once VZ_BACKEND_DIR . '/lib/vendor/JpGraph/jpgraph_date.php';

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
	 * indicator => ynaxis[n] mapping
	 */
	protected $axes = array();

	protected $channels = array();

	const WIDTH = 800;
	const HEIGHT = 400;

	protected static $colors = array('chartreuse', 'chocolate1', 'cyan', 'blue', 'lightcyan4', 'gold');

	protected $graph;

	/**
	 *
	 * @param HTTP\Request $request
	 * @param HTTP\Response $response
	 * @param string $format one of png, jpeg, gif
	 */
	public function __construct(HTTP\Request $request, HTTP\Response $response, $format = 'png') {
		parent::__construct($request, $response);

		$width = $this->request->getParameter('width') ? $this->request->getParameter('width') : self::WIDTH;
		$height = $this->request->getParameter('height') ? $this->request->getParameter('height') : self::HEIGHT;

		$this->graph = new \Graph($width, $height);

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

		//$this->graph->img->SetAntiAliasing();
	}

	/**
	 * adds new plot to the graph
	 *
	 * @param $obj
	 * @param $data
	 */
	public function addData(Interpreter\Interpreter $interpreter){
		$data = $interpreter->getValues(800);

		if (count($data) > 0) {
			$count = count($this->channels);
			$xData = $yData = array();

			foreach ($data as $reading) {
				$xData[] = $reading[0] / 1000;
				$yData[] = $reading[1];
			}

			// Create the scatter plot
			$plot = new \ScatterPlot($yData, $xData);

			$plot->setLegend($interpreter->getChannel()->getProperty('title') . ':  [' . $interpreter->getChannel()->getDefinition()->getUnit() . ']');
			$plot->SetLinkPoints(TRUE, self::$colors[$count]);

			$plot->mark->SetColor(self::$colors[$count]);
			$plot->mark->SetFillColor(self::$colors[$count]);
			$plot->mark->SetType(MARK_DIAMOND);
			$plot->mark->SetWidth(1);

			$axis = $this->getAxisIndex($interpreter->getChannel());
			if ($axis >= 0) {
				$this->graph->AddY($axis, $plot);
			}
			else {
				$this->graph->Add($plot);
			}

			$this->channels[] = $interpreter->getChannel();
		}
	}

	public function addChannel(Model\Channel $channel) {
		throw new \Exception(get_class($this) . ' cant show ' . get_class($channel));
	}

	public function addAggregator(Model\Aggregator $aggregator) {
		throw new \Exception(get_class($this) . ' cant show ' . get_class($aggregator));
	}

	public function addDebug(Util\Debug $debug) {
		throw new \Exception(get_class($this) . ' cant show ' . get_class($debug));
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

		// Display the graph
		//$this->graph->Stroke();
	}
}

?>