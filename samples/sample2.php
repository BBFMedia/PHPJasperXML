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

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


$xml =  simplexml_load_file("sample2.jrxml");

$PHPJasperXML = new PHPJasperXML();
//$PHPJasperXML->debugsql=true;
$PHPJasperXML->report->parameters=array("parameter1"=>1);
$PHPJasperXML->xml_dismantle($xml);
$PHPJasperXML->report->pageSetting["language"] = 'javascript';

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db); 
// use this line if you want to connect with mysql

//if you want to use universal odbc connection, please create a dsn connection in odbc first
//$PHPJasperXML->transferDBtoArray($server,"postgres","postgres","phpjasperxml","odbc"); //odbc = connect to odbc
$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>
