<?php

namespace Drupal\cebaf_status\Graph;

use Drupal\cebaf_status\Utility\MySampler;
use mitoteam\jpgraph\MtJpGraph;
use Carbon\Carbon;

class BeamCurrentGraph {

  const currentSignals = [
    'A' => 'IBC1H04CRCUR2',
    'B' => 'IPM2C21A',
    'C' => 'IBC3H00CRCUR4',
    'D' => 'IBCAD00CRCUR6',
  ];

  const pssSignals = [
    'A' => 'PLC_HLA',
    'B' => 'PLC_HLB',
    'C' => 'PLC_HLC',
    'D' => 'PLC_HLD',
  ];

  protected array $archiverData = [];

  protected array $graphData = [];

  protected array $graphs = [];

  protected $multiGraph;

  public function __construct() {
    MtJpGraph::load(['line', 'date', 'scatter', 'utils.inc', 'mgraph']);
    $this->init();
  }

  public function write($filename) {
    $this->multiGraph->Stroke($filename);
  }

  protected function init() {
    $sampler = new MySampler($this->begin(), $this->channels(), 120);
    $this->archiverData = $sampler->getData();
    $this->makeGraphData();
    $this->makeHallGraphs();
    $this->makeMultiGraph();
  }

  /**
   * The list of PVs to fetch.
   */
  protected function channels(): array {
    return array_merge(array_values(self::currentSignals), array_values(self::pssSignals));
  }

  /*
   * Beginning date to use
   */
  protected function begin(): string {
    // We want to always begin data set on an hour boundary
    return Carbon::now()->subHours(8)->minute(0)->format('Y-m-d H:i');
  }

  protected function makeGraphData() {
    foreach (self::currentSignals as $hall => $signal){
      $pssSignal = self::pssSignals[$hall];
      foreach ($this->archiverData as $dateStr => $data){
        $timestamp = strtotime($dateStr);
        // First look at the PSS state.
        // If the hall was not in Beam Permit, assume any current values
        // are just electronic noise and force the value to 0.
        $pssValue = $data[$pssSignal];
        if ($pssValue != 6 ) {  // State 6 is Beam Permit
          $this->graphData[$hall][$timestamp] = 0.0;
        }else{
          $this->graphData[$hall][$timestamp] = (float) $data[$signal];
        }
      }
    }
  }

  /**
   * Make a graph for each of the four halls.
   * @return void
   */
  protected function makeHallGraphs() {
    $this->graphs['A'] = $this->getGraph('Hall A', $this->graphData['A'], 'blue', 'uA', $this->yScale($this->graphData['A'], 'A'), true);
    $this->graphs['B'] = $this->getGraph('Hall B', $this->graphData['B'], 'red', 'nA', $this->yScale($this->graphData['B'], 'B'), true);
    $this->graphs['C'] = $this->getGraph('Hall C', $this->graphData['C'], 'green', 'uA', $this->yScale($this->graphData['C'], 'C'), true);
    $this->graphs['D'] = $this->getGraph('Hall D', $this->graphData['D'], 'goldenrod', 'nA', $this->yScale($this->graphData['D'], 'D'), false);
  }

  /**
   * Make a multi-graph that stacks the four individual hall graphs
   * @return void
   */
  function makeMultiGraph() {

    // And then position the four into a single
    $this->multiGraph = new \MGraph();
    $xpos1=3;$ypos1=3;
    $xpos2=3;$ypos2=130;
    $xpos3=3;$ypos3=280;
    $xpos4=3;$ypos4=410;
    $this->multiGraph->Add($this->graphs['A'],$xpos1,$ypos1);
    $this->multiGraph->Add($this->graphs['B'],$xpos2,$ypos2);
    $this->multiGraph->Add($this->graphs['C'],$xpos3,$ypos3);
    $this->multiGraph->Add($this->graphs['D'],$xpos4,$ypos4);

  }

  // Build a nice looking graph
  function getGraph($title, $data, $color, $units, $scale=0, $hideXLabels=true ){

    if ($hideXLabels){
      $graph  = new \Graph(800, 120,"auto");
      $graph->SetMargin(45,25,10,10);            //(left, right, top, bottom)
    }else{
      $graph  = new \Graph(800, 160,"auto");
      $graph->SetMargin(45,25,10,50);
    }
    $graph->SetColor(array(255,255,255));

    // Title
    $graph->title->Set($title);
    $graph->title->SetFont(FF_ARIAL,FS_BOLD,11);

    //We're going to declare white transparent, so we assign that color to areas
    //we don't want to see.
    $graph->SetFrame(true,'silver',0);
    $graph->SetMarginColor('white');
    $graph->img->SetCanvasColor('white');
    $graph->img->SetTransparent("white");

    // We probably need to vary the scale max based upon data set max
    $graph->SetScale( "datlin",0,$scale);
    $graph->ygrid->Show (true);

    // X Axis
    $graph->xaxis->SetFont(FF_ARIAL);   // Must use TT fonts for 45 degrees
    $graph->xaxis->SetLabelAngle(45);
    $graph->xaxis->scale-> SetDateFormat( 'H:i');
    $graph->xaxis->scale->ticks->SetWeight(2);
    $graph->xaxis->scale->ticks->SetColor('gray');
    $graph->xaxis->SetColor('black');
    $graph->xaxis->HideLabels($hideXLabels);

    // Y Axis
    $graph->yaxis->SetColor('black');
    $graph->yaxis->scale->ticks->SetSide(SIDE_RIGHT);
    $graph->yaxis->title->Set($units);

    $graph->xaxis->scale-> SetTimeAlign( HOURADJ_1);
    $graph->SetTickDensity(TICKD_NORMAL,TICKD_VERYSPARSE);


    $plot = new \ScatterPlot(array_values($data), array_keys($data));
    $plot->mark->SetType(MARK_FILLEDCIRCLE);
    $plot->mark->SetFillColor($color);
    $plot->mark->SetWidth(1);
    $plot->link->Show();
    $plot->link->SetWeight(1);
    $plot->link->SetColor($color.'@0.7');


    // Add the plots to the graph
    $graph->Add( $plot);

    return $graph;
  }



  /**
   * Returns a nice (10, 20, 50, 100, etc.) maximum value for the Y axis of a
   * graph.
   * @return integer
   */
  function yScale($data, $hall){
    $max = max($data);
    if (0 == $max){
      return 20;
    }

    // Hall D seems to get spikes of noise that can blow the scale
    // The hall won't really ever take more than 200 nA, but just in
    // case, we'll set the threshold at 500.
    if (500 < $max && 'D' == $hall){
      return 250;
    }

    return 0;  // signifies auto-scale in jpgraph 4.x
  }


  /**
   * Parses multi-line output returned by mySampler
   *
   * @return array [ts => val]
   */
  function parseSampleOutput($output){
    $data = array();
    foreach ($output as $line){
      [$ts, $val] = parseSampleData($line);
      if ($ts){
        $data[$ts] = $val;
      }
    }
    return $data;
  }

  /**
   * Parses a line of output from the mySampler program that looks like:
   *      2009-03-10 05:28:53 0.03
   *
   * @return array [timestamp,value]
   */
  function parseSampleData($line){
    if (preg_match('/^(\d\d\d\d-\d\d-\d\d\s\d\d:\d\d:\d\d)\s+([\d\.]+)$/',$line,$m)){
      $datetime = strtotime($m[1]);
      $value = $m[2];
      return array($datetime, $value);
    }
    return array();
  }


}
