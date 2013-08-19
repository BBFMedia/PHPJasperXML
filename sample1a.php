<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include_once('class/tcpdf/tcpdf.php');
include_once("class/PHPJasperXML.inc.php");
// include_once ('setting.php'); now optional



$xml =  simplexml_load_file("sample1.jrxml");


$PHPJasperXML = new PHPJasperXML();
//$PHPJasperXML->debugsql=true;
$PHPJasperXML->arrayParameter=array("parameter1"=>1);
$PHPJasperXML->xml_dismantle($xml);

$array_fields = array( array( "sample1_no"=>2, 
                              "sample1_date"=>"2009-08-26", 
                              "sample1_itemname"=>"???", 
                              "sample1_qty"=>2,
                              "sample1_uom"=>"PCS"), 
                       array( "sample1_no"=>3, 
                              "sample1_date"=>"2009-08-15", 
                              "sample1_itemname"=>"LCD Monitor", 
                              "sample1_qty"=>1,
                              "sample1_uom"=>"PCS"), 
                       array( "sample1_no"=>4, 
                              "sample1_date"=>"2009-08-11", 
                              "sample1_itemname"=>"test item 3", 
                              "sample1_qty"=>3,
                              "sample1_uom"=>"pcs"),
                       array( "sample1_no"=>6, 
                              "sample1_date"=>"2009-08-11", 
                              "sample1_itemname"=>"Again, sample data", 
                              "sample1_qty"=>8,
                              "sample1_uom"=>"day"));

$PHPJasperXML->transferFieldtoArray( $array_fields);

// $PHPJasperXML->transferDBtoArray($server,$user,$pass,$db); now optional
$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>
