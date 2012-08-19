<?php


require_once(dirname(dirname(__FILE__)).'/j4p5/js.php');

class jasperExp  {

private $vars = array();


function addVar($name ,$value)
{

$this->vars[$name] = $value;
}

function addVars($vars)
{
  $this->vars = array_merge($this->vars , $vars);

}
function replaceVars($code)
{


 foreach( $this->vars as $name => $value )
   {
    
      $codename = str_replace('{','',$name);
      $codename = str_replace('$','',$codename);
      $codename = str_replace('}','',$codename);
      $codename = str_replace(' ','',$codename);
 // $codename = preg_replace("/[^a-z]/i",'',$codename);
	 //  $codename .=   rand();
	  $code = str_replace($name,$codename.'.value',$code);
      
      $this->_setVar($name,$codename,$value);
    
   
   }

   
   return $code;
}

  function _setVar($name,$codename,$value) { }

}
                  
class jasperJavascript extends jasperExp
{

 function _setVar($name,$codename,$value) {
           
		   
  js::define($codename ,array(),array('value'=>$value));        
 
}


function run($code)
{

$code = $this->replaceVars($code); 

$code = 'var result = ( '.$code .');';

#-- Run the js code.
js::run($code);
$result = php_str(jsrt::idv('result'));

return $result ;

 }
 
 }

 