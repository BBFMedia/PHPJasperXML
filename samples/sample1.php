<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include_once('../class/tcpdf/tcpdf.php');
include_once("../class/PHPJasperXML.inc.php");
include_once ('setting.php');
require_once '../class/JasperDatabase.php';
require_once '../class/JasperMysql.php';
require_once '../class/JasperExp.php';
require_once '../class/JasperGroovy.php';
require_once '../class/JasperJS.php';

// $javas = new JasperJavascript();
// $result = $javas->run('1 + 5');
// echo $result;
// 
//die;


class myTCPDF extends TCPDF
  {
  	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false) {

	parent::__construct($orientation , $unit , $format , true, 'UTF-8', $diskcache);
	$this->jpeg_quality = 90;
	$fontname = $this->addTTFfont('pala.ttf', 'TrueTypeUnicode');
	$fontname = $this->addTTFfont('simsunb.ttf', 'TrueTypeUnicode');
	$this->SetFont('freeserif'); 
       $this->SetFont('cid0jp', '', 9);
        $this->SetFont('times', 'BI', 20, '', 'false');
	}
  }


 $test = array('javascript');//,'groovy','javascript','groovy','javascript','groovy');
  
   
foreach($test as $test)
{
    $t1 = microtime(1);
$xml =  simplexml_load_file("report1.jrxml");


$PHPJasperXML = new PHPJasperXML("en","TCPDF","myTCPDF");
//$PHPJasperXML->debugsql=true;

 xhprof_enable(); 
$PHPJasperXML->xml_dismantle($xml);
$xhprof_data = xhprof_disable();
$PHPJasperXML->report->PageSetting["language"] = $test;
$PHPJasperXML->report->parameters=array("parameter1"=>1);
$PHPJasperXML->report->parameters["name"] = $PHPJasperXML->report->parameters["language"];
$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
$PHPJasperXML->outpage("S");    //page output method I:standard output  D:Download file
 //  $t2 = microtime(2);
 //  echo $PHPJasperXML->arrayPageSetting["language"] ." - done in ".($t2-$t1). " seconds"."\n";
   
}


// display raw xhprof data for the profiler run
//print_r($xhprof_data);


$XHPROF_ROOT = 'd:\code\slcode\lib\xhprof';
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

// save raw data for this profiler run using default
// implementation of iXHProfRuns.
$xhprof_runs = new XHProfRuns_Default();

// save the run under a namespace "xhprof_foo"
$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");  

echo "---------------\n".
     "Assuming you have set up the http based UI for \n".
     "XHProf at some address, you can view run at \n".
     "http://<xhprof-ui-address>/index.php?run=$run_id&source=xhprof_foo\n".
     "---------------\n";
