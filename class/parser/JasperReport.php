<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once dirname(__FILE__).'/JasperContainer.php';
require_once dirname(__FILE__).'/JasperBand.php';

/**
 * Description of jasperReport
 *
 * @author adrianjones
 */
class JasperReport extends JasperObject {

    protected $_name = '';
    protected $_language = 'java';
    protected $_pageWidth = 595;
    protected $_pageHeight = 842;
    protected $_orientation = 'Portrait';
    protected $_leftMargin = 20;
    protected $_rightMargin = 20;
    protected $_topMargin = 30;
    protected $_bottomMargin = 30;
    
    
    
    protected $_pageHeader = null;

    public function parse($xml_path) {



      //  parent::parse($xml_path);


        $this->loadValues($xml_path);

        foreach ($xml_path as $k => $out) {

            $elementName = 'Jasper_' . $k;
            if (class_exists($elementName)) {
                $element = new $elementName($this);

                $element->parse($out);
                $propertyName = '_' . $k;
                if (property_exists($this, $propertyName))
                    $this->$propertyName = $element;
            }
          if ($k == 'parameter')
          {
              $name = $out['name'];
              $value = (string)$out->defaultValueExpression;
              if (isset($name))
              $this->parameters[$name] = $value;
          }
        }
    }

}

