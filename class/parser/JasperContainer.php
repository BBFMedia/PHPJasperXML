<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperContainer
 *
 * @author adrianjones
 */
class JasperContainer extends JasperObject {
     protected $_band = null;
     
      function parse($xm_path)
    {
        $this->_band = new Jasper_band($this);

        $this->loadValues($xm_path);
        $this->_band->parse($xm_path->band);
          
    }
}



class Jasper_pageHeader extends JasperContainer {
    
    
    
}
