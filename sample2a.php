<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include_once('class/tcpdf/tcpdf.php');
include_once("class/PHPJasperXML.inc.php");
include_once ('setting.php');  // set here

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


$xml =  simplexml_load_file("sample2.jrxml");

$PHPJasperXML = new PHPJasperXML();
//$PHPJasperXML->debugsql=true;
$PHPJasperXML->arrayParameter=array("parameter1"=>1);
$PHPJasperXML->pchartfolder="./class/pchart2"; // you can setup like this

$PHPJasperXML->xml_dismantle($xml);



$pdo = new PDO("mysql:host=$server;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
      
// you can connect using PDO
$PHPJasperXML->transferPDOtoArray($pdo);

$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>
