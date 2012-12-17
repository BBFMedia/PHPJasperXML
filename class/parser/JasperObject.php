<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperObject
 *
 * @author adrian
 */

class JasperObject implements Iterator {

    protected $elements = array();
    protected $elements_position = 0;
    protected $parent = null;
    public $report = null;
    public function __construct($parent) {
        $this->elements_position = 0;
      $this->parent = $parent;
      if (isset($parent))
      $this->report = $this->parent->report;
     
    }
    function rewind() {
        $this->elements_position = 0;
    }

    function current() {
        return $this->elements[$this->elements_position];
    }

    function key() {
        return $this->elements_position;
    }

    function next() {
        ++$this->elements_position;
    }

    function valid() {
        return isset($this->elements[$this->elements_position]);
    }
    
    function __get($name)
    {
        $name = '_'.$name;
      if (!property_exists($this, $name))
                 die('does not exists '. $name);
        return $this->$name;
    }
    
    
    function __set($name,$value)
    {
        $name = '_'.$name;
        if (!property_exists($this, $name))
                 die('does not exists '. $name);
        $this->$name = $value;
    }
    
    
    function loadValues($values)
    {
        foreach($values->attributes() as $name => $value)
        {
           $name = '_'.$name;
           if (property_exists($this, $name)){
              $type =  gettype($this->$name ) ;
               if ($type== 'string' ) 
                      $this->$name = (string) $value;   
             elseif ($type== 'integer' ) 
                      $this->$name = (integer) $value;   
             elseif ($type == 'double' ) 
                      $this->$name = (float) $value;   
             else
                     $this->$name =  $value;   
           }
                     
        }
    }
    

}

