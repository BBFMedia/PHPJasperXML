<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperOutputType
 *
 * @author adrian
 */
abstract class JasperOutputType {

    public $_fonts;
    public $roster = array();
    /**
     *
     * @param type $jasperName
     * @param type $pdfName
     * @param type $stye 
     */
    public function addFont($jasperName, $pdfName, $stye = '') {
        $this->_fonts[strtolower($jasperName)] = array('outputName' => $pdfName, 'sytle' => $style);
    }

    private function applyFonts() {
        
    }

    function getFontMap($family) {

        $sub = $this->_fonts[strtolower($family)];
        if (!empty($sub['outputName']))
            return $sub['outputName'];

        return $family;
    }

    
    
    
    abstract function getTextHeight($text,$wrapWidth = null);
}

