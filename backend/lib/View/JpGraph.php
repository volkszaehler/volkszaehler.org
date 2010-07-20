<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Volkszaehler\View;

require_once \Volkszaehler\BACKEND_DIR . '/lib/vendor/JpGraph/jpgraph.php';
require_once \Volkszaehler\BACKEND_DIR . '/lib/vendor/JpGraph/jpgraph_scatter.php';
require_once \Volkszaehler\BACKEND_DIR . '/lib/vendor/JpGraph/jpgraph_date.php';

/*
 * JpGraph plotting
 * 
 * @todo add caching
 * @todo unifiy axes of same unit
 */
class JpGraph extends View {
	protected $width = 800;
	protected $height = 600;
	
	protected $channels = array();
	
	protected static $colors = array('chartreuse', 'chocolate1', 'cyan', 'blue', 'lightcyan4', 'gold');

	protected $graph;

	public function __construct(Http\Request $request, Http\Response $response, $format) {
		parent::__construct($request, $response);
		
		$this->graph = new \Graph($this->width,$this->height);

		// Specify what scale we want to use,
		$this->graph->SetScale('datlin');
		
		$this->graph->legend->setPos(0.15,0.025, 'left', 'top');
		
		$this->graph->SetMarginColor('white');
		$this->graph->SetMargin(90,65,10,90);
		$this->graph->SetYDeltaDist(65);
		$this->graph->yaxis->SetTitlemargin(36);
		
		$this->graph->SetTickDensity(TICKD_DENSE, TICKD_SPARSE);
		$this->graph->xaxis->SetFont(FF_ARIAL);
		
		$this->graph->xaxis->SetLabelAngle(60);
		$this->graph->xaxis->SetLabelFormatCallback(function($label) { return date('j.n.y G:i', $label); });
		
		//$this->graph->img->SetAntiAliasing(); 
	}
	
	public function add(\Volkszaehler\Model\Channel $obj, $data = NULL) {
		$count = count($this->channels);
		$xData = $yData = array();
		foreach ($data as $reading) {
			$xData[] = $reading['timestamp']/1000;
			$yData[] = $reading['value'];
		}
		
		// Create the linear plot
		$plot = new \ScatterPlot($yData, $xData);
		
		$plot->setLegend($obj->getName() . ': ' . $obj->getDescription() . ' [' . $obj->getUuid() . ']');
		
		$plot->mark->SetColor(self::$colors[$count]);
		$plot->mark->SetFillColor(self::$colors[$count]);
		
		$plot->mark->SetType(MARK_DIAMOND);
		$plot->mark->SetWidth(1);
		$plot->SetLinkPoints(true, self::$colors[$count]);
		
		if ($count == 0) {
			$yaxis = $this->graph->yaxis;
			$this->graph->Add($plot);
		}
		else {
			$this->graph->SetYScale($count-1,'lin');
			$yaxis = $this->graph->ynaxis[$count-1];
			$this->graph->SetMargin(60,($count) * 65,10,90);
			$this->graph->AddY($count-1, $plot);
		}
		
		$yaxis->title->Set($obj->getUnit());
		$yaxis->title->SetFont(FF_ARIAL);
		$yaxis->SetColor(self::$colors[$count]);
		$yaxis->SetTitleMargin('50');
		
		$this->channels[] = $obj;
	}
	
	public function addException(\Exception $e) { echo $e; }
	public function addDebug() {}

	public static function factory(Http\Request $request, Http\Response $response) {

	}

	public function render() {
		// Display the graph
		$this->graph->Stroke();

		$this->response->send();
	}

}

?>