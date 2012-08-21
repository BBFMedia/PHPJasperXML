<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperExp
 *
 * @author adrian
 */

class JasperExp  {

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