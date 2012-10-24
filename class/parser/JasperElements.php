<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperElements
 *
 * @author adrian
 */
class Jasper_staticText extends JasperText {

    protected $_text = "";

    public function parse($data) {

        parent::parse($data);



        //   $this->report->pointer[] = array("type" => "SetXY", "x" => $data->reportElement["x"], "y" => $data->reportElement["y"], "hidden_type" => "SetXY");
        //   $this->report->pointer[] = array("type" => "SetTextColor", "r" => $textcolor["r"], "g" => $textcolor["g"], "b" => $textcolor["b"], "hidden_type" => "textcolor");
        //    $this->report->pointer[] = array("type" => "SetDrawColor", "r" => $drawcolor["r"], "g" => $drawcolor["g"], "b" => $drawcolor["b"], "hidden_type" => "drawcolor");
        //    $this->report->pointer[] = array("type" => "SetFillColor", "r" => $fillcolor["r"], "g" => $fillcolor["g"], "b" => $fillcolor["b"], "hidden_type" => "fillcolor");
        //    $this->report->pointer[] = array("type" => "SetFont", "font" => $font, "fontstyle" => $fontstyle, "fontsize" => $fontsize, "hidden_type" => "font");
        //"height"=>$data->reportElement["height"]
//### UTF-8 characters, a must for me.	
        $txtEnc = ($data->text);
        $this->text = $$txtEnc;


        /** add printWhenExpression */
        //     $this->report->pointer[] = array("type" => "MultiCell",
        //       "printWhenExpression" => $data->reportElement->printWhenExpression,
        //     "width" => $data->reportElement["width"], "height" => $height, "txt" => $txtEnc, "border" => $border, "align" => $align, "fill" => $fill, "hidden_type" => "statictext", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "rotation" => $rotation);
//### End of modification, below is the original line		
//        $this->report->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$data->text,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"statictext","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"rotation"=>$rotation);
    }

}

/**
 *
 * 
 * @todo hyperlinks need to be covered 
 */
class Jasper_textField extends JasperText {

    protected $_textFieldExpression = "";

    public function parse($data) {

        parent::parse($data);


        $this->textFieldExpression = $data->textFieldExpression;
    }

}
/**
 * 
 * @link http://jasperforge.org/uploads/publish/jasperreportswebsite/trunk/schema.reference.html#group
 */
class Japser_group extends JasperElement {

    public $_groupFooter;
    public $_groupHeader;
    public $_isStartNewPage;
    public $_isStartNewColumn;
    public $_isResetPageNumber;
    public $_footerPosition;
    public $_isReprintHeaderOnEachPage;
    public $_minHeightToStartNewPage;

    public function parse($data) {

        parent::parse($data);

        $this->isStartNewPage = $this->getBool($xml_path["isStartNewPage"],false);
        $this->isStartNewColumn = $this->getBool($xml_path["isStartNewColumn"],false);
        $this->isResetPageNumber = $this->getBool($xml_path["isResetPageNumber"],false);
        $this->isReprintHeaderOnEachPage = $this->getBool($xml_path["isReprintHeaderOnEachPage"],false);
       $this->minHeightToStartNewPage = $this->getInt($xml_path["minHeightToStartNewPage"],0);
      $this->footerPosition = $this->getString($xml_path["footerPosition"],'Normal');
      	

        foreach ($data as $tag => $out) {
            switch ($tag) {
                case "groupHeader":
                    $this->groupHeader = new JasperBand();
                    $this->groupHeader->parse($out);
                    break;
                case "groupFooter":

                    $this->groupFooter = new JasperBand();
                    $this->groupFooter->parse($out);
break;
                default:

                    break;
            }
        }
    }

}
