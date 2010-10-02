<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//include_once('class/fpdf/FPDF.php');
include_once('class/fpdf/chinese-unicode.php');
//include_once('class/tcpdf/tcpdf.php');
include_once("class/PHPJasperXML.inc.php");
include_once ('setting.php');



$xml =  simplexml_load_file("sample4.jrxml");


$PHPJasperXML = new PHPJasperXML("cn");
$PHPJasperXML->debugsql=false;
$PHPJasperXML->arrayParameter=array("parameter1"=>1, "parameter2"=>"试试万维网看",$parameter3="ÄÖÜäöü");
$PHPJasperXML->xml_dismantle($xml);

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
$PHPJasperXML->outpage("I","a.pdf");    //page output method I:standard output  D:Download file, F: store file at server (2nd parameter for you to declare filename)


?>
