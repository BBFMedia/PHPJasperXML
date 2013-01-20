<?php
require_once dirname(__FILE__) . '/../../../class/parser/JasperBand.php';
require_once dirname(__FILE__) . '/../../../class/parser/JasperReport.php';



/**
 * Description of JasperReportTest
 *
 * @author adrianjones
 */
class JasperReportTest extends PHPUnit_Framework_TestCase {

   function testParse()
   {
       $report = new JasperReport();
       
       
       $xml = simplexml_load_file(dirname(__FILE__) . '/reportTest.jrxml');

       $report->parse($xml);
       
     //  $report->layout();
       
   }
}
