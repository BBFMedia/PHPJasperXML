<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperDatabase
 *
 * @author adrian
 */
abstract class JasperDatabase {
   abstract  public function query($sql);
  abstract  public function next($queryObject);
}

