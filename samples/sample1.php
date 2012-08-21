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
require_once '../class/JasperJS.php';
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



$xml =  simplexml_load_file("sample1.jrxml");


$PHPJasperXML = new PHPJasperXML("en","TCPDF","myTCPDF");
//$PHPJasperXML->debugsql=true;
$PHPJasperXML->arrayParameter=array("parameter1"=>1);
$PHPJasperXML->xml_dismantle($xml);

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>
