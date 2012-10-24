<?php
include dirname(__FILE__).'/JasperBand.php';
/**
 * ## JapserParser
 *
 * convert report xml object into an array or object tree that the layout engine can read.
 *
 * ### Methods
 *
 * -  parseReport
 *
 *
 * @author adrian

 *
 * 
 * 
 *  
 */
class group {

    public $group = array();
    public $newPageGroup;
    public $head = array();
    public $headheight;
    public $group_count;
    public $grboupfootheight;

}

class report extends JasperObject {

    public $pageSetting = array();   //$this->arrayPageSetting
    public $parameters = array();   // $this->report->parameters
    public $pointer;  //$this->pointer
    public $sql;
    public $field;
    public $groups = array();
    public $Variable;
    public $background_band;
    public $title_band;
    public $pageHeader_band;
    public $columnHeader_band;
    public $detail_band;
    public $columnFooter_band;
    public $pageFooter_band;
    public $lastPageFooter_band;
    public $summary_band;
    public $noData_band;

}

class JasperParser {

    private $y_axis;
    public $report;
    public $gnam;
    public $arraytitle;
    public $filterExpression;
    public $subdataset;
    public $arraycolumnHeader;
    public $report_count;
    public $arraycolumnFooter;
    public $arraypageFooter;
    public $arraysummary;
    //band 
    public $arrayband;
    // these fields need be removed
    public $arraystyle;
    public $arraypageHeader;
    public $arraydetail;

    function __construct() {
        
    }

    function __get($name) {
        echo $name . " -p\n";
        flsds();
    }

    function __set($name, $var) {
        echo $name . " -pr\n";
    }

    /**
     * runs the xml report through parser.
     * this will run though the jasperReport dom
     * {@link http://jasperforge.org/uploads/publish/jasperreportswebsite/trunk/schema.reference.html#jasperReport}
     * 
     * the jasperReport dom contains
     * 
     * 
     * 
      property*
      import*
      template*
      reportFont*
      style*
      subDataset*
      scriptlet*
      parameter*
      queryString?
      field*
      sortField*
      variable*
      filterExpression?
      group*
      background?
      title?
      pageHeader?
      columnHeader?
      detail?
      columnFooter?
      pageFooter?
      lastPageFooter?
      summary?
      noData?
     * 
     * 
     * @param type $xml 
     */
    public function parse($xml, &$report) {

        if (empty($report))
            $report = new report();

        $this->report = $report;
        $this->page_setting($xml);
        foreach ($xml as $k => $out) {
            $hander = $k . '_handler';
            if (method_exists($this, $hander))
                $this->$hander($out, $this->report);
            else {
                $hander = $k . '_band';
                if (property_exists($this->report, $hander)) {
                    $this->report->$hander = new JasperBand();
                    $this->report->$hander->parse($out);
                    $this->y_axis = $this->y_axis + $this->report->$hander->height;
                }



             

        }
         }
        $report = $this->report;
        return true;
   
    }

 /*   public function background_handler($xml_path, $report) {

        //    $report->background = array("height" => $out->band["height"], "splitType" => $out->band["splitType"]);
        $this->band_handler($xml_path, $report->background);
    }*/

    public function parameter_handler($xml_path) {
        $defaultValueExpression = (string) $xml_path->defaultValueExpression;
        if (!isset($this->report->parameters[(string) $xml_path["name"]])) {
            if ($defaultValueExpression != '')
                $this->report->parameters[(string) $xml_path["name"]] = $defaultValueExpression;
            else
                $this->report->parameters[(string) $xml_path["name"]] = '';
        }
    }

    public function filterExpression_handler($xml_path) {


        $this->filterExpression = (string) $xml_path;
    }

    public function queryString_handler($xml_path) {
        $this->report->sql = $xml_path;
    }

//read level 0,Jasperreport page setting

    /**
     * 
     * 
     * @param type $xml_path 
     */
    public function page_setting($xml_path) {
        $this->report->pageSetting = $xml_path;

        if (!isset($xml_path["orientation"])) {
            $this->report->pageSetting["orientation"] = 'P';
        }
        $this->y_axis = (integer) $xml_path["topMargin"];
    }

    public function field_handler($xml_path) {
        $this->report->field["name"] = $xml_path["class"];
    }

    public function variable_handler($xml_path) {

        $this->report->Variable["$xml_path[name]"] = array("calculation" => $xml_path["calculation"], "target" => substr($xml_path->variableExpression, 3, -1), "class" => $xml_path["class"], "resetType" => $xml_path["resetType"]);
    }

    public function group_handler($xml_path, $report) {

        $group = new Japser_group();
        $group->parse($xml_path);
        $this->report->groups[] = $group;

    }

    public function band_hander($xml_path, $band) {
        
    }


   
    public function element_image($data) {
        $imagepath = $data->imageExpression;
        //$imagepath= substr($data->imageExpression, 1, -1);
        //$imagetype= substr($imagepath,-3);
        // $imagepath=$this->analyse_expression($imagepath);

        switch ($data[scaleImage]) {
            case "FillFrame":
                /** add hAlign */
                $this->report->pointer[] = array("type" => "Image", "path" => $imagepath, "x" => $data->reportElement["x"] + 0, "y" => $data->reportElement["y"] + 0, "width" => $data->reportElement["width"] + 0, "height" => $data->reportElement["height"] + 0, "imgtype" => $imagetype, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "hidden_type" => "image", 'hAlign' => $data['hAlign']);
                break;
            default:
                $this->report->pointer[] = array("type" => "Image", "path" => $imagepath, "x" => $data->reportElement["x"] + 0, "y" => $data->reportElement["y"] + 0, "width" => $data->reportElement["width"] + 0, "height" => $data->reportElement["height"] + 0, "imgtype" => $imagetype, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "hidden_type" => "image");
                break;
        }
    }

    public function element_line($data) { //default line width=0.567(no detect line width)
        $drawcolor = array("r" => 0, "g" => 0, "b" => 0);
        $hidden_type = "line";
        if (isset($data->reportElement["forecolor"])) {
            $drawcolor = array("r" => hexdec(substr($data->reportElement["forecolor"], 1, 2)), "g" => hexdec(substr($data->reportElement["forecolor"], 3, 2)), "b" => hexdec(substr($data->reportElement["forecolor"], 5, 2)));
        }
        /** add drawing of lines */
        if ((isset($data->graphicElement) ) and (isset($data->graphicElement->pen))) {
            if (isset($data->graphicElement->pen["lineColor"]))
                $drawcolor = array("r" => hexdec(substr($data->graphicElement->pen["lineColor"], 1, 2)), "g" => hexdec(substr($data->graphicElement->pen["lineColor"], 3, 2)), "b" => hexdec(substr($data->graphicElement->pen["lineColor"], 5, 2)));
            if (isset($data->graphicElement->pen["lineWidth"]))
                $this->report->pointer[] = array("type" => "SetLineWidth", "width" => $data->graphicElement->pen["lineWidth"]);
        }

        $this->report->pointer[] = array("type" => "SetDrawColor", "r" => $drawcolor["r"], "g" => $drawcolor["g"], "b" => $drawcolor["b"], "hidden_type" => "drawcolor");
        if (isset($data->reportElement[positionType]) && $data->reportElement[positionType] == "FixRelativeToBottom") {
            $hidden_type = "relativebottomline";
        }
        if ($data->reportElement["width"][0] + 0 > $data->reportElement["height"][0] + 0) { //width > height means horizontal line
            $this->report->pointer[] = array("type" => "Line", "x1" => $data->reportElement["x"], "y1" => $data->reportElement["y"], "x2" => $data->reportElement["x"] + $data->reportElement["width"], "y2" => $data->reportElement["y"] + $data->reportElement["height"] - 1, "hidden_type" => $hidden_type);
        } elseif ($data->reportElement["height"][0] + 0 > $data->reportElement["width"][0] + 0) {  //vertical line
            $this->report->pointer[] = array("type" => "Line", "x1" => $data->reportElement["x"], "y1" => $data->reportElement["y"], "x2" => $data->reportElement["x"] + $data->reportElement["width"] - 1, "y2" => $data->reportElement["y"] + $data->reportElement["height"], "hidden_type" => $hidden_type);
        }
        $this->report->pointer[] = array("type" => "SetLineWidth", "width" => "1");

        $this->report->pointer[] = array("type" => "SetDrawColor", "r" => 0, "g" => 0, "b" => 0, "hidden_type" => "drawcolor");
        $this->report->pointer[] = array("type" => "SetFillColor", "r" => 255, "g" => 255, "b" => 255, "hidden_type" => "fillcolor");
    }

    public function element_rectangle($data) {

        $radius = $data['radius'];
        $drawcolor = array("r" => 0, "g" => 0, "b" => 0);

        $fillcolor = array("r" => 255, "g" => 255, "b" => 255);


        if (isset($data->reportElement["forecolor"])) {
            $drawcolor = array("r" => hexdec(substr($data->reportElement["forecolor"], 1, 2)), "g" => hexdec(substr($data->reportElement["forecolor"], 3, 2)), "b" => hexdec(substr($data->reportElement["forecolor"], 5, 2)));
        }

        if (isset($data->reportElement["backcolor"])) {
            $fillcolor = array("r" => hexdec(substr($data->reportElement["backcolor"], 1, 2)), "g" => hexdec(substr($data->reportElement["backcolor"], 3, 2)), "b" => hexdec(substr($data->reportElement["backcolor"], 5, 2)));
        }


        $this->report->pointer[] = array("type" => "SetDrawColor", "r" => $drawcolor["r"], "g" => $drawcolor["g"], "b" => $drawcolor["b"], "hidden_type" => "drawcolor");
        $this->report->pointer[] = array("type" => "SetFillColor", "r" => $fillcolor["r"], "g" => $fillcolor["g"], "b" => $fillcolor["b"], "hidden_type" => "fillcolor");

        if ($radius == '')
            $this->report->pointer[] = array("type" => "Rect", "x" => $data->reportElement["x"], "y" => $data->reportElement["y"], "width" => $data->reportElement["width"], "height" => $data->reportElement["height"], "hidden_type" => "rect", "drawcolor" => $drawcolor, "fillcolor" => $fillcolor, "mode" => $data->reportElement["mode"]);
        else
            $this->report->pointer[] = array("type" => "RoundedRect", "x" => $data->reportElement["x"], "y" => $data->reportElement["y"], "width" => $data->reportElement["width"], "height" => $data->reportElement["height"], "hidden_type" => "roundedrect", "radius" => $radius, "drawcolor" => $drawcolor, "fillcolor" => $fillcolor, "mode" => $data->reportElement["mode"]);
        $this->report->pointer[] = array("type" => "SetDrawColor", "r" => 0, "g" => 0, "b" => 0, "hidden_type" => "drawcolor");
        $this->report->pointer[] = array("type" => "SetFillColor", "r" => 255, "g" => 255, "b" => 255, "hidden_type" => "fillcolor");
    }

    public function element_ellipse($data) {
        $drawcolor = array("r" => 0, "g" => 0, "b" => 0);
        $fillcolor = array("r" => 255, "g" => 255, "b" => 255);
        if (isset($data->reportElement["forecolor"])) {
            $drawcolor = array("r" => hexdec(substr($data->reportElement["forecolor"], 1, 2)), "g" => hexdec(substr($data->reportElement["forecolor"], 3, 2)), "b" => hexdec(substr($data->reportElement["forecolor"], 5, 2)));
        }
        if (isset($data->reportElement["backcolor"])) {
            $fillcolor = array("r" => hexdec(substr($data->reportElement["backcolor"], 1, 2)), "g" => hexdec(substr($data->reportElement["backcolor"], 3, 2)), "b" => hexdec(substr($data->reportElement["backcolor"], 5, 2)));
        }

        //$color=array("r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"]);
        $this->report->pointer[] = array("type" => "SetFillColor", "r" => $fillcolor["r"], "g" => $fillcolor["g"], "b" => $fillcolor["b"], "hidden_type" => "fillcolor");
        $this->report->pointer[] = array("type" => "SetDrawColor", "r" => $drawcolor["r"], "g" => $drawcolor["g"], "b" => $drawcolor["b"], "hidden_type" => "drawcolor");
        $this->report->pointer[] = array("type" => "Ellipse", "x" => $data->reportElement["x"], "y" => $data->reportElement["y"], "width" => $data->reportElement["width"], "height" => $data->reportElement["height"], "hidden_type" => "ellipse", "drawcolor" => $drawcolor, "fillcolor" => $fillcolor);
        $this->report->pointer[] = array("type" => "SetDrawColor", "r" => 0, "g" => 0, "b" => 0, "hidden_type" => "drawcolor");
        $this->report->pointer[] = array("type" => "SetFillColor", "r" => 255, "g" => 255, "b" => 255, "hidden_type" => "fillcolor");
    }

    public function element_textField($data) {
        $align = "L";
        $fill = 0;
        $border = 0;
        $fontsize = 10;
        $font = "helvetica";
        $rotation = "";
        $fontstyle = "";
        $textcolor = array("r" => 0, "g" => 0, "b" => 0);
        $fillcolor = array("r" => 255, "g" => 255, "b" => 255);
        $stretchoverflow = "false";
        $printoverflow = "false";
        $height = $data->reportElement["height"];
        $drawcolor = array("r" => 0, "g" => 0, "b" => 0);
        if (isset($data->reportElement["forecolor"])) {
            $textcolor = array("r" => hexdec(substr($data->reportElement["forecolor"], 1, 2)), "g" => hexdec(substr($data->reportElement["forecolor"], 3, 2)), "b" => hexdec(substr($data->reportElement["forecolor"], 5, 2)));
        }
        if (isset($data->reportElement["backcolor"])) {
            $fillcolor = array("r" => hexdec(substr($data->reportElement["backcolor"], 1, 2)), "g" => hexdec(substr($data->reportElement["backcolor"], 3, 2)), "b" => hexdec(substr($data->reportElement["backcolor"], 5, 2)));
        }
        if ($data->reportElement["mode"] == "Opaque") {
            $fill = 1;
        }
        if (isset($data["isStretchWithOverflow"]) && $data["isStretchWithOverflow"] == "true") {
            $stretchoverflow = "true";
        }
        if (isset($data->reportElement["isPrintWhenDetailOverflows"]) && $data->reportElement["isPrintWhenDetailOverflows"] == "true") {
            $printoverflow = "true";
        }
        if (isset($data->box) && $data->box->pen["lineWidth"] > 0) {
            $border = 1;
            if (isset($data->box->pen["lineColor"])) {
                $drawcolor = array("r" => hexdec(substr($data->box->pen["lineColor"], 1, 2)), "g" => hexdec(substr($data->box->pen["lineColor"], 3, 2)), "b" => hexdec(substr($data->box->pen["lineColor"], 5, 2)));
            }
        }
        if (isset($data->reportElement["key"])) {
            $height = $fontsize * $this->adjust;
        }
        if (isset($data->textElement["textAlignment"])) {
            $align = $this->get_first_value($data->textElement["textAlignment"]);
        }
        /** get verital align */
        if (isset($data->textElement["textAlignment"])) {
            $valign = $this->get_first_value($data->textElement["verticalAlignment"]);
        }
        if (isset($data->textElement["rotation"])) {
            $rotation = $data->textElement["rotation"];
        }
        if (isset($data->textElement->font["fontName"])) {
            $font = $data->textElement->font["fontName"];
        }
        if (isset($data->textElement->font["pdfFontName"])) {
            $font = $data->textElement->font["pdfFontName"];
        }
        if (isset($data->textElement->font["size"])) {
            $fontsize = $data->textElement->font["size"];
        }
        if (isset($data->textElement->font["isBold"]) && $data->textElement->font["isBold"] == "true") {
            $fontstyle = $fontstyle . "B";
        }
        if (isset($data->textElement->font["isItalic"]) && $data->textElement->font["isItalic"] == "true") {
            $fontstyle = $fontstyle . "I";
        }
        if (isset($data->textElement->font["isUnderline"]) && $data->textElement->font["isUnderline"] == "true") {
            $fontstyle = $fontstyle . "U";
        }
        $this->report->pointer[] = array("type" => "SetXY", "x" => $data->reportElement["x"], "y" => $data->reportElement["y"], "hidden_type" => "SetXY");
        /** todo: need to check that forecolor and backcolor work. I add it from older code without checking it */
        $this->report->pointer[] = array("type" => "SetTextColor", "forecolor" => $data->reportElement["forecolor"], "r" => $textcolor["r"], "g" => $textcolor["g"], "b" => $textcolor["b"], "hidden_type" => "textcolor");
        $this->report->pointer[] = array("type" => "SetDrawColor", "r" => $drawcolor["r"], "g" => $drawcolor["g"], "b" => $drawcolor["b"], "hidden_type" => "drawcolor");
        $this->report->pointer[] = array("type" => "SetFillColor", "backcolor" => $data->reportElement["backcolor"], "r" => $fillcolor["r"], "g" => $fillcolor["g"], "b" => $fillcolor["b"], "hidden_type" => "fillcolor");
        $this->report->pointer[] = array("type" => "SetFont", "font" => $font, "fontstyle" => $fontstyle, "fontsize" => $fontsize, "hidden_type" => "font");
        //$data->hyperlinkReferenceExpression=$this->analyse_expression($data->hyperlinkReferenceExpression);
        //if( $data->hyperlinkReferenceExpression!=''){echo "$data->hyperlinkReferenceExpression";die;}


        switch ($data->textFieldExpression) {
            case 'new java.util.Date()':
//### New: =>date("Y.m.d.",....
                /** added valign  for the next 35 lines */
                $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => date("Y-m-d H:i:s"), "border" => $border, "align" => $align, 'valign' => $valign, "fill" => $fill, "hidden_type" => "date", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "link" => substr($data->hyperlinkReferenceExpression, 1, -1));
//### End of modification				
                break;
            case '"Page "+$V{PAGE_NUMBER}+" of"':

                $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => 'Page $this->PageNo() of', "border" => $border, "align" => $align, 'valign' => $valign, "fill" => $fill, "hidden_type" => "pageno", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "pattern" => $data["pattern"]);
                break;
            case '$V{PAGE_NUMBER}':
                if (isset($data["evaluationTime"]) && $data["evaluationTime"] == "Report") {
                    $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => '{nb}', "border" => $border, "align" => $align, 'valign' => $valign, "fill" => $fill, "hidden_type" => "pageno", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "pattern" => $data["pattern"]);
                } else {
                    $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => '$this->PageNo()', "border" => $border, "align" => $align, 'valign' => $valign, "fill" => $fill, "hidden_type" => "pageno", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "pattern" => $data["pattern"]);
                }
                break;
            case '" " + $V{PAGE_NUMBER}':
                $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => ' {nb}', "border" => $border, "align" => $align, 'valign' => $valign, "fill" => $fill, "hidden_type" => "nb", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "pattern" => $data["pattern"]);
                break;
            case '$V{REPORT_COUNT}':
//###                $this->report_count=0;	
                $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => &$this->report_count, "border" => $border, "align" => $align, 'valign' => $valign, "fill" => $fill, "hidden_type" => "report_count", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "pattern" => $data["pattern"]);
                break;
            case '$V{' . $this->gnam . '_COUNT}':
//            case '$V{'.$this->report->band[0]["gname"].'_COUNT}':
//###                $this->report->group->count=0;
         //       $gnam = $this->report->band[0]["gname"];
                $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => &$this->report->group->count["$this->gnam"], "border" => $border, "align" => $align, 'valign' => $valign, "fill" => $fill, "hidden_type" => "group_count", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow, "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "pattern" => $data["pattern"]);
                break;
            default:
                $writeHTML = false;
                if ($data->reportElement->property["name"] == "writeHTML")
                    $writeHTML = $data->reportElement->property["value"];
                if (isset($data->reportElement["isPrintRepeatedValues"]))
                    $isPrintRepeatedValues = $data->reportElement["isPrintRepeatedValues"];


                $this->report->pointer[] = array("type" => "MultiCell", "width" => $data->reportElement["width"], "height" => $height, "txt" => $data->textFieldExpression,
                    "border" => $border, "align" => $align, "fill" => $fill, 'valign' => $valign,
                    "hidden_type" => "field", "soverflow" => $stretchoverflow, "poverflow" => $printoverflow,
                    "printWhenExpression" => $data->reportElement->printWhenExpression,
                    "link" => substr($data->hyperlinkReferenceExpression, 1, -1), "pattern" => $data["pattern"],
                    "writeHTML" => $writeHTML, "isPrintRepeatedValues" => $isPrintRepeatedValues, "rotation" => $rotation);
                break;
        }
    }

    public function element_subReport($data) {
//        $b=$data->subreportParameter;
        $srsearcharr = array('.jasper', '"', "'", ' ', '$P{SUBREPORT_DIR}+');
        $srrepalcearr = array('.jrxml', "", "", '', $this->report->parameters['SUBREPORT_DIR']);

        if (strpos($data->subreportExpression, '$P{SUBREPORT_DIR}') === false) {
            $subreportExpression = str_replace($srsearcharr, $srrepalcearr, $data->subreportExpression);
        } else {
            $subreportExpression = str_replace($srsearcharr, $srrepalcearr, $data->subreportExpression);
        }
        $b = array();
        foreach ($data as $name => $out) {
            if ($name == 'subreportParameter') {
                $b[$out['name'] . ''] = $out->subreportParameterExpression;
            }
        }//loop to let multiple parameter pass to subreport pass to subreport
        $this->report->pointer[] = array("type" => "subreport", "x" => $data->reportElement["x"], "y" => $data->reportElement["y"],
            "width" => $data->reportElement["width"], "height" => $data->reportElement["height"],
            "subreportparameterarray" => $b, "connectionExpression" => $data->connectionExpression,
            "subreportExpression" => $subreportExpression, "hidden_type" => "subreport");
    }

    public function element_pieChart($data) {

        $height = $data->chart->reportElement["height"];
        $width = $data->chart->reportElement["width"];
        $x = $data->chart->reportElement["x"];
        $y = $data->chart->reportElement["y"];
        $charttitle['position'] = $data->chart->chartTitle['position'];

        $charttitle['text'] = $data->chart->chartTitle->titleExpression;
        $chartsubtitle['text'] = $data->chart->chartSubTitle->subtitleExpression;
        $chartLegendPos = $data->chart->chartLegend['position'];

        $dataset = $data->pieDataset->dataset->datasetRun['subDataset'];

        $seriesexp = $data->pieDataset->keyExpression;
        $valueexp = $data->pieDataset->valueExpression;
        $bb = $data->pieDataset->dataset->datasetRun['subDataset'];
        $sql = $this->arraysubdataset["$bb"]['sql'];

        // $ylabel=$data->linePlot->valueAxisLabelExpression;


        $param = array();
        foreach ($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag => $value) {
            $param[] = array("$value[name]" => $value->datasetParameterExpression);
        }
//          print_r($param);

        $this->report->pointer[] = array('type' => 'PieChart', 'x' => $x, 'y' => $y, 'height' => $height, 'width' => $width, 'charttitle' => $charttitle,
            'chartsubtitle' => $chartsubtitle,
            'chartLegendPos' => $chartLegendPos, 'dataset' => $dataset, 'seriesexp' => $seriesexp,
            'valueexp' => $valueexp, 'param' => $param, 'sql' => $sql, 'ylabel' => $ylabel);
    }

    public function element_pie3DChart($data) {
        
    }

    public function subDataset_handler($data) {
        $this->subdataset[$data['name'] . ''] = $data->queryString;
    }

    public function element_Chart($data, $type) {
        $seriesexp = array();
        $catexp = array();
        $valueexp = array();
        $labelexp = array();
        $height = $data->chart->reportElement["height"];
        $width = $data->chart->reportElement["width"];
        $x = $data->chart->reportElement["x"];
        $y = $data->chart->reportElement["y"];
        $charttitle['position'] = $data->chart->chartTitle['position'];
        $titlefontname = $data->chart->chartTitle->font['pdfFontName'];
        $titlefontsize = $data->chart->chartTitle->font['size'];
        $charttitle['text'] = $data->chart->chartTitle->titleExpression;
        $chartsubtitle['text'] = $data->chart->chartSubTitle->subtitleExpression;
        $chartLegendPos = $data->chart->chartLegend['position'];
        $dataset = $data->categoryDataset->dataset->datasetRun['subDataset'];
        $subcatdataset = $data->categoryDataset;
        //echo $subcatdataset;
        $i = 0;
        foreach ($subcatdataset as $cat => $catseries) {
            foreach ($catseries as $a => $series) {
                if ("$series->categoryExpression" != '') {
                    array_push($seriesexp, "$series->seriesExpression");
                    array_push($catexp, "$series->categoryExpression");
                    array_push($valueexp, "$series->valueExpression");
                    array_push($labelexp, "$series->labelExpression");
                }
            }
        }


        $bb = $data->categoryDataset->dataset->datasetRun['subDataset'];
        $sql = $this->arraysubdataset[$bb]['sql'];
        switch ($type) {
            case "barChart":
                $ylabel = $data->barPlot->valueAxisLabelExpression;
                $xlabel = $data->barPlot->categoryAxisLabelExpression;
                $maxy = $data->barPlot->rangeAxisMaxValueExpression;
                $miny = $data->barPlot->rangeAxisMinValueExpression;
                break;
            case "lineChart":
                $ylabel = $data->linePlot->valueAxisLabelExpression;
                $xlabel = $data->linePlot->categoryAxisLabelExpression;
                $maxy = $data->linePlot->rangeAxisMaxValueExpression;
                $miny = $data->linePlot->rangeAxisMinValueExpression;
                $showshape = $data->linePlot["isShowShapes"];
                break;
            case "stackedAreaChart":
                $ylabel = $data->areaPlot->valueAxisLabelExpression;
                $xlabel = $data->areaPlot->categoryAxisLabelExpression;
                $maxy = $data->areaPlot->rangeAxisMaxValueExpression;
                $miny = $data->areaPlot->rangeAxisMinValueExpression;


                break;
        }



        $param = array();
        foreach ($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag => $value) {
            $param[] = array("$value[name]" => $value->datasetParameterExpression);
        }
        if ($maxy != '' && $miny != '') {
            $scalesetting = array(0 => array("Min" => $miny, "Max" => $maxy));
        }
        else
            $scalesetting = "";

        $this->report->pointer[] = array('type' => $type, 'x' => $x, 'y' => $y, 'height' => $height, 'width' => $width, 'charttitle' => $charttitle,
            'chartsubtitle' => $chartsubtitle,
            'chartLegendPos' => $chartLegendPos, 'dataset' => $dataset, 'seriesexp' => $seriesexp,
            'catexp' => $catexp, 'valueexp' => $valueexp, 'labelexp' => $labelexp, 'param' => $param, 'sql' => $sql, 'xlabel' => $xlabel, 'showshape' => $showshape,
            'titlefontsize' => $titlefontname, 'titlefontsize' => $titlefontsize, 'scalesetting' => $scalesetting);
    }



}
