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

    public function __construct() {
        $this->elements_position = 0;
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
    
    

}

