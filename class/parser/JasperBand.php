<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once dirname(__FILE__).'/JasperObject.php';
require_once dirname(__FILE__).'/JasperElement.php';
require_once dirname(__FILE__).'/JasperElements.php';

/**
 * Description of JasperBand
 *
 * 
 * @link http://jasperforge.org/uploads/publish/jasperreportswebsite/trunk/schema.reference.html#band
 * @author adrian
 */

class Jasper_band extends JasperObject {

    protected $_height = 0;    //	Height of the band.
    protected $_isSplitAllowed = '';   //	Deprecated. Replaced by attribute splitType. Flag that indicates if the band is allowed to split when it stretches.
    
    /** 
     *	Specifies the band split behavior.
     * 
     * Stretch		The band is allowed to split, but never within its declared height. This means the band splits only when its content stretches.
     * Prevent		Prevents the band from splitting on first break attempt. On subsequent pages/columns, the band is allowed to split, to avoid infinite loops.
     * Immediate		The band is allowed to split anywhere, as early as needed, but not before at least one element being printed on the current page/column.
     * @var string 
     */
    protected $_splitType;  
    
    function addElement($element) {
        $this->elements[] = $element;
    }
    function parse($band)
    {
    
    //     $this->height =   $this-> $band['height'];
     //    $this->isSplitAllowed = (string) $band['isSplitAllowed'];
      //   $this->splitType =  (string) $band['splitType'];
     
        $this->loadValues($band);
        $this->parseElements($band);
        //array("type" => "band", "height" => $object["height"], "splitType" => $object["splitType"], "y_axis" => $this->y_axis);
          
    }
    
    
    public function parseElements($xml_path) {
        foreach ($xml_path as $k => $out) {

            $elementName = 'Jasper_'.$k;
            if (class_exists($elementName))
                 $element = new $elementName($this);
               else
                 $element = new Jasper_reportElement($this);
            $element->parse($out);
            $this->addElement($element);
//                case "stackedBarChart":
//                    $this->element_barChart($out,'StackedBarChart');
//                    break;
//                case "barChart":
//                    $this->element_barChart($out,'BarChart');
//                    break;
//                case "pieChart":
//                    $this->element_pieChart($out);
//                    break;
//                case "pie3DChart":
//                    $this->element_pie3DChart($out);
//                    break;
//                case "lineChart":
//                    $this->element_lineChart($out);
//                    break;
//                case "stackedAreaChart":
//                    $this->element_areaChart($out,'stackedAreaChart');
//                    break;
   /*             case "stackedBarChart":
                    $this->element_Chart($out, 'stackedBarChart', $band);
                    break;
                case "barChart":
                    $this->element_Chart($out, 'barChart', $band);
                    break;
                case "pieChart":
                    $this->element_Chart($out, 'pieChart', $band);
                    break;
                case "pie3DChart":
                    $this->element_pie3DChart($out, 'pie3DChart', $band);
                    break;
                case "lineChart":
                    $this->element_Chart($out, 'lineChart', $band);
                    break;
                case "stackedAreaChart":
                    $this->element_Chart($out, 'stackedAreaChart', $band);
                    break;
                case "subreport":
                    $this->element_subReport($out, $band);
                    break;
                default:
                    break;
            }*/
        }
    }
}
