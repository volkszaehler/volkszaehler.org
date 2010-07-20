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
	/*
	 * indicator => ynaxis[n] mapping
	 */
	protected $axes = array();
	
	protected $channels = array();
	
	protected $width = 800;
	protected $height = 400;

	protected static $colors = array('chartreuse', 'chocolate1', 'cyan', 'blue', 'lightcyan4', 'gold');

	protected $graph;

	/*
	 * constructor
	 */
	public function __construct(Http\Request $request, Http\Response $response, $format) {
		parent::__construct($request, $response);
		
		$this->graph = new \Graph($this->width,$this->height);
		
		$this->graph->img->SetImgFormat($format);

		// Specify what scale we want to use,
		$this->graph->SetScale('datlin');
		
		$this->graph->legend->SetPos(0.1,0.02, 'left', 'top');
		$this->graph->legend->SetShadow(false);
		
		$this->graph->SetMarginColor('white');
		$this->graph->SetYDeltaDist(65);
		$this->graph->yaxis->SetTitlemargin(36);
		
		$this->graph->SetTickDensity(TICKD_DENSE, TICKD_SPARSE);
		$this->graph->xaxis->SetFont(FF_ARIAL);
		
		$this->graph->xaxis->SetLabelAngle(45);
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
		
		// Create the scatter plot
		$plot = new \ScatterPlot($yData, $xData);
		
		$plot->setLegend($obj->getName() . ': ' . $obj->getDescription() . ' [' . $obj->getUnit() . ']');
		$plot->SetLinkPoints(true, self::$colors[$count]);
		
		$plot->mark->SetColor(self::$colors[$count]);
		$plot->mark->SetFillColor(self::$colors[$count]);
		$plot->mark->SetType(MARK_DIAMOND);
		$plot->mark->SetWidth(1);
		
		$axis = $this->getAxisIndex($obj);
		if ($axis >= 0) {
			$this->graph->AddY($axis, $plot);
		}
		else {
			$this->graph->Add($plot);
		}
		
		$this->channels[] = $obj;
	}
	
	protected function getAxisIndex(\Volkszaehler\Model\Channel $obj) {
		if (!in_array($obj->getIndicator(), array_keys($this->axes))) {
			$count =count($this->axes); 
			if ($count == 0) {
				$this->axes[$obj->getIndicator()] = -1;
				
				$yaxis = $this->graph->yaxis;
			}
			else {
				$this->axes[$obj->getIndicator()] = $count - 1;
				
				$this->graph->SetYScale($this->axes[$obj->getIndicator()],'lin');
				
				$yaxis = $this->graph->ynaxis[$this->axes[$obj->getIndicator()]];
			}
				
			$yaxis->title->Set($obj->getUnit());
				
			$yaxis->SetFont(FF_ARIAL);
			$yaxis->title->SetFont(FF_ARIAL);
			
			$yaxis->SetTitleMargin('50');
		}
		
		return $this->axes[$obj->getIndicator()];
	}
	
	public function render() {
		$this->graph->SetMargin(75, (count($this->axes) - 1) * 65 + 10, 20, 90);
		
		// Display the graph
		$this->graph->Stroke();

		parent::render();
	}
}

?>