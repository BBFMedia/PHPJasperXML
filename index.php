<?php
include "setting.php";
echo <<< EOF

<html>
<image src="simitlogo.jpg"><br>
Developer: Ng Jey Ruey (jeyruey@gmail.com)<br>
Project Leader: KS Tan (kstan@simit.com.my)<br>
Organization: <a href='http://www.simit.com.my'>Sim IT Sdn Bhd</a><br>
    <h1>PHP Jasper XML ($version) Example</h1><br>
	
    <p><B>Example:</B></p>
    <li><a href='sample1.php' target='_blank'>Sample 1 <a> (Standard column base report, with charts)</li>
    <li><a href='sample2.php' target='_blank'>Sample 2</a> (Standard official document, with ODBC) * You need to create a DSN with name=phpjasperxml</li>
    <li><a href='sample3.php' target='_blank'>Sample 3</a> (A5 Landscape Receipt)</li>
    <li><a href='sample4.php' target='_blank'>Sample 4</a>(Export as excel, doen't support subreport)</li>
    <li><a href='sample5.php?id=1' target='_blank'>Sample 5</a> (Use TCPDF, with writeHTML output) (add text properties expression "writeHTML"="true")</li>
    <li><a href='sample6.php?filename=sample.pdf' target='_blank'>Sample 6</a> Grouping by new page, pattern formating and hide repeated value, report export as file (sample.pdf) at tmp/ folder.</li>
    <li><a href='sample7.php' target='_blank'>Sample 7</a>(Similar with sample2, using postgresql native driver, postgresql database 'phpjasperxml' is needed )</li>
    <li><a href='sample8.php' target='_blank'>Sample 8</a>(Support sub-reports, not yet stable)</li>
</html>
EOF;
?>
