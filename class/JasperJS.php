<?php


require_once(dirname(__FILE__).'/j4p5/js.php');

                  
class jasperJavascript extends JasperExp
{

 function _setVar($name,$codename,$value) {
           
  js::define($codename ,array(),array('value'=>$value));        
 
}


function run($code)
{
   // echo $code .' - ';
   $code = trim($code);
if (empty($code))
    return $code;
$code = $this->replaceVars($code); 

$code = 'var result = ( '.$code .');';

#-- Run the js code.


js::run($code);


$result = php_str(jsrt::idv('result'));

return $result ;

 }
 
 }

 