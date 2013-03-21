<?php

//version 0.8d
class PHPJasperXML {
    private $adjust=1.2;
    public $version=0.8;
    private $pdflib;
    private $lang;
    private $previousarraydata;
    public $debugsql=false;
    private $myconn;
    private $con;
    public $group_name;
    public $newPageGroup = false;
    private $curgroup=0;
    private $groupno=0;
    private $footershowed=true;
    private $titleheight=0;
    private $fontdir="";
    public $bypassnofont=true;
    public $titlewithpagebreak=false;
    private $detailallowtill=0;
	private $report_count=1;		//### New declaration (variable exists in original too)
	private $group_count = array(); //### New declaration
    public function PHPJasperXML($lang="en",$pdflib="TCPDF") {
        $this->lang=$lang;
        
        error_reporting(0);
        $this->pdflib=$pdflib;
        $this->fontdir=dirname(__FILE__)."/tcpdf/fonts";
    }

    public function connect($db_host,$db_user,$db_pass,$db_or_dsn_name,$cndriver="mysql") {
    $this->db_host=$db_host;
            $this->db_user=$db_user;
       $this->db_pass=$db_pass;
    $this->db_or_dsn_name=$db_or_dsn_name;
    $this->cndriver=$cndriver;
        if($cndriver=="mysql") {

            if(!$this->con) {
                $this->myconn = @mysql_connect($db_host,$db_user,$db_pass);
                if($this->myconn) {
                    $seldb = @mysql_select_db($db_or_dsn_name,$this->myconn);
                    if($seldb) {
                        $this->con = true;
                        return true;
                    }
                    else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return true;
            }
            return true;
        }elseif($cndriver=="psql") {
            global $pgport;
            if($pgport=="" || $pgport==0)
                $pgport=5432;

            $conn_string = "host=$db_host port=$pgport dbname=$db_or_dsn_name user=$db_user password=$db_pass";
            $this->myconn = pg_connect($conn_string);


            if($this->myconn) {
                $this->con = true;

                return true;
            }else
                return false;
        }
        else {

            if(!$this->con) {
                $this->myconn = odbc_connect($db_or_dsn_name,$db_user,$db_pass);

                if( $this->myconn) {
                    $this->con = true;
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
    }

    public function disconnect($cndriver="mysql") {
        if($cndriver=="mysql") {
            if($this->con) {
                if(@mysql_close()) {
                    $this->con = false;
                    return true;
                }
                else {
                    return false;
                }
            }
        }elseif($cndriver=="psql") {
            $this->con = false;
            pg_close($this->myconn);
        }
        else {

            $this->con = false;
            odbc_close( $this->myconn);
        }
    }

    public function xml_dismantle($xml) {	
        $this->page_setting($xml);
        $i=0;
       // echo $i++."<br/>";
        foreach ($xml as $k=>$out) {
            //echo $i++."$k<br/>";
            switch($k) {
                case "parameter":
                    $this->parameter_handler($out);
                    break;
                case "queryString":
                    $this->queryString_handler($out);
                    break;
                case "field":
                    $this->field_handler($out);
                    break;
                case "variable":
                    $this->variable_handler($out);
                    break;
                case "group":
                    $this->group_handler($out);
                    break;
                case "subDataset":
                       $this->subDataset_handler($out);
                    break;
                case "background":
                    $this->pointer=&$this->arraybackground;
                    $this->pointer[]=array("height"=>$out->band["height"],"splitType"=>$out->band["splitType"]);
                    foreach ($out as $bg) {
                        $this->default_handler($bg);

                    }
                    break;
                default:
                    foreach ($out as $object) {

                        eval("\$this->pointer=&"."\$this->array$k".";");
                        $this->arrayband[]=array("name"=>$k);
                        if($k=='detail')
                        $this->detailbandheight=$object["height"]+0;
                        elseif($k=='pageHeader')
                        $this->headerbandheight=$object["height"]+0;
                         elseif($k=='title'){
                        $this->titlebandheight=$object["height"]+0;
                        $this->orititlebandheight=$object["height"]+0;
                         }
                        elseif($k=='pageFooter')
                        $this->footerbandheight=$object["height"]+0;
                        elseif($k=='lastPageFooter')
                        $this->lastfooterbandheight=$object["height"]+0;
                        elseif($k=='columnHeader')
                        $this->columnheaderbandheight=$object["height"]+0;
                        elseif($k=='columnFooter')
                        $this->columnfooterbandheight=$object["height"]+0;
                        elseif($k=='summary')
                        $this->summarybandheight=$object["height"]+0;
                        
//                       echo "Band=$k=> ".$this->detailallowtill . "=".$this->arrayPageSetting["pageHeight"]."-".$this->footerbandheight."-".$this->arrayPageSetting["bottomMargin"]."-".$this->columnfooterbandheight."<br/>";
                        
                        $this->pointer[]=array("type"=>"band","height"=>$object["height"],"splitType"=>$object["splitType"],"y_axis"=>$this->y_axis);
                        $this->default_handler($object);
                        
                    }
                    
                    $this->y_axis=$this->y_axis+$out->band["height"];	//after handle , then adjust y axis
                        $this->detailallowtill=$this->arrayPageSetting["pageHeight"]-$this->footerbandheight-$this->arrayPageSetting["bottomMargin"]-$this->columnfooterbandheight;

                          
                    break;

            }
                                  


        }
    }

    public function subDataset_handler($data){
    $this->subdataset[$data['name'].'']= $data->queryString;

    }
//read level 0,Jasperreport page setting
    public function page_setting($xml_path) {
        $this->arrayPageSetting["orientation"]="P";
        $this->arrayPageSetting["name"]=$xml_path["name"];
        $this->arrayPageSetting["language"]=$xml_path["language"];
        $this->arrayPageSetting["pageWidth"]=$xml_path["pageWidth"];
        $this->arrayPageSetting["pageHeight"]=$xml_path["pageHeight"];
        if(isset($xml_path["orientation"])) {
            $this->arrayPageSetting["orientation"]=substr($xml_path["orientation"],0,1);
        }
        $this->arrayPageSetting["columnWidth"]=$xml_path["columnWidth"];
        $this->arrayPageSetting["leftMargin"]=$xml_path["leftMargin"];
        $this->arrayPageSetting["rightMargin"]=$xml_path["rightMargin"];
        $this->arrayPageSetting["topMargin"]=$xml_path["topMargin"];
        $this->y_axis=$xml_path["topMargin"];
        $this->arrayPageSetting["bottomMargin"]=$xml_path["bottomMargin"];
    }

    public function parameter_handler($xml_path) {
        //    $defaultValueExpression=str_replace('"','',$xml_path->defaultValueExpression);
      // if($defaultValueExpression!='')
      //  $this->arrayParameter[$xml_path["name"].'']=$defaultValueExpression;
      // else
        $this->arrayParameter[$xml_path["name"].''];        

    }

    public function queryString_handler($xml_path) {
        $this->sql =$xml_path;
        if(isset($this->arrayParameter)) {
            foreach($this->arrayParameter as  $v => $a) {
                $this->sql = str_replace('$P{'.$v.'}', $a, $this->sql);
            }
        }
    }

    public function field_handler($xml_path) {
        $this->arrayfield[]=$xml_path["name"];
    }

    public function variable_handler($xml_path) {

        $this->arrayVariable["$xml_path[name]"]=array("calculation"=>$xml_path["calculation"],"target"=>substr($xml_path->variableExpression,3,-1),"class"=>$xml_path["class"] , "resetType"=>$xml_path["resetType"]);

    }

    public function group_handler($xml_path) {

//        $this->arraygroup=$xml_path;


        if($xml_path["isStartNewPage"]=="true")
            $this->newPageGroup=true;
        else
            $this->newPageGroup="";

        foreach($xml_path as $tag=>$out) {
            switch ($tag) {
                case "groupHeader":
                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupHeader"];
                    $this->pointer=&$this->arraygrouphead;
                    $this->arraygroupheadheight=$out->band["height"];
                    $this->arrayband[]=array("name"=>"group", "gname"=>$xml_path["name"],"isStartNewPage"=>$xml_path["isStartNewPage"],"groupExpression"=>substr($xml_path->groupExpression,3,-1));
                    $this->pointer[]=array("type"=>"band","height"=>$out->band["height"]+0,"y_axis"=>"","groupExpression"=>substr($xml_path->groupExpression,3,-1));
//### Modification for group count
					$gnam=$xml_path["name"];				
					$this->gnam=$xml_path["name"];
					$this->group_count["$gnam"]=1; // Count rows of groups, we're on the first row of the group.
//### End of modification
                    foreach($out as $band) {
                        $this->default_handler($band);

                    }

                    $this->y_axis=$this->y_axis+$out->band["height"];		//after handle , then adjust y axis
                    break;
                case "groupFooter":

                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupFooter"];
                    $this->pointer=&$this->arraygroupfoot;
                    $this->arraygroupfootheight=$out->band["height"];
                    $this->pointer[]=array("type"=>"band","height"=>$out->band["height"]+0,"y_axis"=>"","groupExpression"=>substr($xml_path->groupExpression,3,-1));
                    foreach($out as $b=>$band) {
                        $this->default_handler($band);

                    }
                    break;
                default:

                    break;
            }

        }
    }

  public function default_handler($xml_path) {
        foreach($xml_path as $k=>$out) {

            switch($k) {
                case "staticText":
                    $this->element_staticText($out);
                    break;
                case "image":
                    $this->element_image($out);
                    break;
                case "line":
                    $this->element_line($out);
                    break;
                case "rectangle":
                    $this->element_rectangle($out);
                    break;
            case "ellipse":
                    $this->element_ellipse($out);
                    break;
	                case "textField":
                    $this->element_textField($out);
                    break;
//                case "stackedBarChart":
//                    $this->element_barChart($out,'StackedBarChart');
//                    break;
//                case "barChart":
//                    $this->element_barChart($out,'BarChart');
//                    break;
           //     case "pieChart":
             //       $this->element_pieChart($out);
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
                    case "stackedBarChart":
                    $this->element_Chart($out,'stackedBarChart');
                    break;
                case "barChart":
                    $this->element_Chart($out,'barChart');
                    break;
                case "pieChart":
                    $this->element_Chart($out,'pieChart');
                    break;
                case "pie3DChart":
                    $this->element_pie3DChart($out,'pie3DChart');
                    break;
                case "lineChart":
                    $this->element_Chart($out,'lineChart');
                    break;
                case "stackedAreaChart":
                    $this->element_Chart($out,'stackedAreaChart');
                    break;
                case "subreport":
                    $this->element_subReport($out);
                    break;
                case "break":
                    $this->element_break($out);
                    break;
                case "componentElement":
                    $this->element_componentElement($out);
                    break;
                default:
                    break;
            }
        };		
    }

    public function element_staticText($data) {
        $align="L";
        $fill=0;
        $border=0;
        $fontsize=10;
        $font="helvetica";
        $fontstyle="";
        $textcolor = array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor = array("r"=>255,"g"=>255,"b"=>255);
        $txt="";
        $rotation="";
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $height=$data->reportElement["height"];
        $stretchoverflow="true";
        $printoverflow="false";
        $data->hyperlinkReferenceExpression=" ".$this->analyse_expression($data->hyperlinkReferenceExpression);
        if(isset($data->reportElement["forecolor"])) {
            
            $textcolor = array('forecolor'=>$data->reportElement["forecolor"],"r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor = array('backcolor'=>$data->reportElement["backcolor"],"r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));
        }
        if($data->reportElement["mode"]=="Opaque") {
            $fill=1;
        }
        if(isset($data["isStretchWithOverflow"])&&$data["isStretchWithOverflow"]=="true") {
            $stretchoverflow="true";
        }
        if(isset($data->reportElement["isPrintWhenDetailOverflows"])&&$data->reportElement["isPrintWhenDetailOverflows"]=="true") {
            $printoverflow="true";
            $stretchoverflow="false";
        }
          if(isset($data->box)) {
            $borderset="";
            if($data->box->topPen["lineWidth"]>0)
                $borderset.="T";
            if($data->box->leftPen["lineWidth"]>0)
                $borderset.="L";
            if($data->box->bottomPen["lineWidth"]>0)
                $borderset.="B";
            if($data->box->rightPen["lineWidth"]>0)
                $borderset.="R";
             if(isset($data->box->pen["lineColor"])) {
                $drawcolor=array("r"=>hexdec(substr($data->box->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->box->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->box->pen["lineColor"],5,2)));
            }
            
            if(isset($data->box->pen["lineStyle"])) {
                if($data->box->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->box->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                else
                    $dash="";
                //Dotted Dashed
            }
           
            $border=array($borderset => array('width' => $data->box->pen["lineWidth"],
                'cap' => 'butt', 
                'join' => 'miter', 
                'dash' =>$dash,
                'phase'=>0,
                'color' =>$drawcolor));
            //array($borderset=>array('width'=>$data->box->pen["lineWidth"],
                //'cap'=>'butt'(butt, round, square),'join'=>'miter' (miter, round,bevel),
                //'dash'=>2 ("2,1","2"),
              //  'colour'=>array(110,20,30)  ));
            //&&$data->box->pen["lineWidth"]>0
            //border can be array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))
            
            
           
            //elseif()
            
        }
        if(isset($data->textElement["textAlignment"])) {
            $align=$this->get_first_value($data->textElement["textAlignment"]);
        }
        if(isset($data->textElement["verticalAlignment"])) {
                        $valign="T";
            if($data->textElement["verticalAlignment"]=="Bottom")
                $valign="B";
            elseif($data->textElement["verticalAlignment"]=="Middle")
                $valign="C";
            else
                $valign="T";

        }
        if(isset($data->textElement["rotation"])) {
            $rotation=$data->textElement["rotation"];
        }
        if(isset($data->textElement->font["fontName"])) {
            $font=$data->textElement->font["fontName"];
        }
        if(isset($data->textElement->font["size"])) {
            $fontsize=$data->textElement->font["size"];
        }
        if(isset($data->textElement->font["isBold"])&&$data->textElement->font["isBold"]=="true") {
            $fontstyle=$fontstyle."B";
        }
        if(isset($data->textElement->font["isItalic"])&&$data->textElement->font["isItalic"]=="true") {
            $fontstyle=$fontstyle."I";
        }
        if(isset($data->textElement->font["isUnderline"])&&$data->textElement->font["isUnderline"]=="true") {
            $fontstyle=$fontstyle."U";
        }
        if(isset($data->reportElement["key"])) {
            $height=$fontsize*$this->adjust;
        }
        $this->pointer[]=array("type"=>"SetXY","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"hidden_type"=>"SetXY");
        $this->pointer[]=array("type"=>"SetTextColor",'forecolor'=>$data->reportElement["forecolor"].'',"r"=>$textcolor["r"],"g"=>$textcolor["g"],"b"=>$textcolor["b"],"hidden_type"=>"textcolor");
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor",'backcolor'=>$data->reportElement["backcolor"].'',"r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
        $this->pointer[]=array("type"=>"SetFont","font"=>$font,"fontstyle"=>$fontstyle,"fontsize"=>$fontsize,"hidden_type"=>"font");
        //"height"=>$data->reportElement["height"]
        
//### UTF-8 characters, a must for me.	
		$txtEnc=$data->text; 
                
		$this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$txtEnc,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"statictext","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"rotation"=>$rotation,"valign"=>$valign);
//### End of modification, below is the original line		
//        $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$data->text,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"statictext","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"rotation"=>$rotation);

    }

    public function element_image($data) {
        $imagepath=$data->imageExpression;
        //$imagepath= substr($data->imageExpression, 1, -1);
        //$imagetype= substr($imagepath,-3);
$data->hyperlinkReferenceExpression=" ".$this->analyse_expression($data->hyperlinkReferenceExpression)." ";
        switch($data[scaleImage]) {
            case "FillFrame":
                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
                break;
            default:
                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
                break;
        }
    }
    
    public function element_componentElement($data) {
//        $imagepath=$data->imageExpression;
//        //$imagepath= substr($data->imageExpression, 1, -1);
//        //$imagetype= substr($imagepath,-3);
//        $data->hyperlinkReferenceExpression=" ".$this->analyse_expression($data->hyperlinkReferenceExpression);

        $x=$data->reportElement["x"];
        $y=$data->reportElement["y"];
        $width=$data->reportElement["width"];
        $height=$data->reportElement["height"];
        
               //simplexml_tree( $data);
       // echo "<br/><br/>";
       //simplexml_tree( $data->children('jr',true));
        //echo "<br/><br/>";
//SimpleXML object (1 item) [0] // ->codeExpression[0] ->attributes('xsi', true) ->schemaLocation ->attributes('', true) ->type ->drawText ->checksumRequired barbecue: 
       foreach($data->children('jr',true) as $barcodetype =>$content){
           
           
           $barcodemethod="";
           $textposition="";
            if($barcodetype=="barbecue"){
                $barcodemethod=$data->children('jr',true)->attributes('', true) ->type;
                $textposition="";
                $checksum=$data->children('jr',true)->attributes('', true) ->checksumRequired;
                $code=$content->codeExpression;
                if($content->attributes('', true) ->drawText=='true')
                        $textposition="bottom";
                
                $modulewidth=$content->attributes('', true) ->moduleWidth;
                
            }else{
                
                 $barcodemethod=$barcodetype;
                 $textposition=$content->attributes('', true)->textPosition;
                 //$data->children('jr',true)->textPosition;
//$content['textPosition'];
                  $code=$content->codeExpression;
                $modulewidth=$content->attributes('', true)->moduleWidth;
                 

                
            }
            if($modulewidth=="")
                $modulewidth=0.4;
//                            echo "Barcode: $code,position: $textposition <br/><br/>";
            $this->pointer[]=array("type"=>"Barcode","barcodetype"=>$barcodemethod,"x"=>$x,"y"=>$y,"width"=>$width,"height"=>$height,'textposition'=>$textposition,'code'=>$code,'modulewidth'=>$modulewidth);
                            
                    /*
                     	<jr:barbecue xmlns:jr="http://jasperreports.sourceforge.net/jasperreports/components" 
                     * xsi:schemaLocation="http://jasperreports.sourceforge.net/jasperreports/components http://jasperreports.sourceforge.net/xsd/components.xsd" 
                     * type="2of7" drawText="false" checksumRequired="false">
					<jr:codeExpression><![CDATA["1234"]]></jr:codeExpression>
				</jr:barbecue>
                     * <jr:Code128 xmlns:jr="http://jasperreports.sourceforge.net/jasperreports/components" 
                     * xsi:schemaLocation="http://jasperreports.sourceforge.net/jasperreports/components http://jasperreports.sourceforge.net/xsd/components.xsd"
                     *  textPosition="bottom">
					<jr:codeExpression><![CDATA[]]></jr:codeExpression>
				</jr:Code128>
                     */

           
       }
       
       
        //if(isset(  $data->children('jr',true)->barbecue)){
           
       //}
       //elseif(isset(  $data->children('jr',true)->barbecue))
      // print_r( $data->children('jr',true));
      // type="2of7" drawText="false" checksumRequired="false"
       /*
        * 				
        * <jr:barbecue xmlns:jr="http://jasperreports.sourceforge.net/jasperreports/components" xsi:schemaLocation="http://jasperreports.sourceforge.net/jasperreports/components http://jasperreports.sourceforge.net/xsd/components.xsd" type="2of7" drawText="false" checksumRequired="false">
                    <jr:codeExpression><![CDATA["1234"]]></jr:codeExpression>
		</jr:barbecue>

        */
        //die;                
//        switch($data[scaleImage]) {
//            case "FillFrame":
//                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
//                break;
//            default:
//                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
//                break;
//        }
    }

    
    public function element_break($data) {
                $this->pointer[]=array("type"=>"break","hidden_type"=>"break");//,"path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
    }

    
    
    public function element_line($data) {	//default line width=0.567(no detect line width)
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $hidden_type="line";
         if($data->graphicElement->pen["lineWidth"]>0)
             $linewidth=$data->graphicElement->pen["lineWidth"];
         
        /*
           $borderset="";
            if($data->box->topPen["lineWidth"]>0)
                $borderset.="T";
            if($data->box->leftPen["lineWidth"]>0)
                $borderset.="L";
            if($data->box->bottomPen["lineWidth"]>0)
                $borderset.="B";
            if($data->box->rightPen["lineWidth"]>0)
                $borderset.="R";
             if(isset($data->box->pen["lineColor"])) {
                $drawcolor=array("r"=>hexdec(substr($data->box->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->box->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->box->pen["lineColor"],5,2)));
            }
              */
            if(isset($data->graphicElement->pen["lineStyle"])) {
                if($data->graphicElement->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->graphicElement->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                else
                    $dash="";
                //Dotted Dashed
            }
           
            
          
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
//        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        if(isset($data->reportElement[positionType])&&$data->reportElement[positionType]=="FixRelativeToBottom") {
            $hidden_type="relativebottomline";
        }
        
        $style=array('color'=>$drawcolor,'width'=>$linewidth,'dash'=>$dash);
//        
        
        if($data->reportElement["width"][0]+0 > $data->reportElement["height"][0]+0)	//width > height means horizontal line
        {
            $this->pointer[]=array("type"=>"Line", "x1"=>$data->reportElement["x"],"y1"=>$data->reportElement["y"],"x2"=>$data->reportElement["x"]+$data->reportElement["width"],"y2"=>$data->reportElement["y"]+$data->reportElement["height"]-1,"hidden_type"=>$hidden_type,"style"=>$style);
        }
        elseif($data->reportElement["height"][0]+0>$data->reportElement["width"][0]+0)		//vertical line
        {
            $this->pointer[]=array("type"=>"Line", "x1"=>$data->reportElement["x"],"y1"=>$data->reportElement["y"],"x2"=>$data->reportElement["x"]+$data->reportElement["width"]-1,"y2"=>$data->reportElement["y"]+$data->reportElement["height"],"hidden_type"=>$hidden_type,"style"=>$style);
        }
        
        
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor");
    }

    public function element_rectangle($data) {
		
		$radius=$data['radius'];
        $drawcolor=array("r"=>255,"g"=>255,"b"=>255);
        
        $fillcolor=array("r"=>255,"g"=>255,"b"=>255);
        $borderwidth=1;
           
           if(isset($data->graphicElement->pen["lineWidth"]))
                 $borderwidth=$data->graphicElement->pen["lineWidth"];
            
             if(isset($data->graphicElement->pen["lineColor"]))
                 $drawcolor=array("r"=>hexdec(substr($data->graphicElement->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->graphicElement->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->graphicElement->pen["lineColor"],5,2)));
            
            $dash="";
                    if($data->graphicElement->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->graphicElement->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                elseif($data->graphicElement->pen["lineStyle"]=="Solid")
                    $dash="";
//echo "$borderwidth,";
           
            $border=array("LTRB" => array('width' => $borderwidth,'color' =>$drawcolor,'cap'=>'square',
                            'join'=>'miter','dash'=>$dash));
            
            
            //array($borderset=>array('width'=>$data->box->pen["lineWidth"],
                //(butt, round, square),'join'=>'miter' (miter, round,bevel),
                //'dash'=>2 ("2,1","2"),
              //  'colour'=>array(110,20,30)  ));
            //&&$data->box->pen["lineWidth"]>0
            //border can be array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))
            
            
           
            //elseif()
            
        
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));			
        }
        
        if(isset($data->reportElement["backcolor"]) ) { 
            $fillcolor=array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));			
        }


        //$this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
       // $this->pointer[]=array("type"=>"SetFillColor","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
		
        if($radius=='')
        $this->pointer[]=array("type"=>"Rect","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"width"=>$data->reportElement["width"],"height"=>$data->reportElement["height"],"hidden_type"=>"rect","fillcolor"=>$fillcolor,"mode"=>$data->reportElement["mode"],'border'=>0);
        else
        $this->pointer[]=array("type"=>"RoundedRect","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"width"=>$data->reportElement["width"],"height"=>$data->reportElement["height"],"hidden_type"=>"roundedrect","radius"=>$radius,"fillcolor"=>$fillcolor,"mode"=>$data->reportElement["mode"],'border'=>0);
        
        
//        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor");
  //      $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor");
    }

  public function element_ellipse($data) {
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor=array("r"=>255,"g"=>255,"b"=>255);
         $width=1;
           
            
                
           if(isset($data->graphicElement->pen["lineWidth"]))
                 $borderwidth=$data->graphicElement->pen["lineWidth"];
            
             if(isset($data->graphicElement->pen["lineColor"]))
                 $drawcolor=array("r"=>hexdec(substr($data->graphicElement->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->graphicElement->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->graphicElement->pen["lineColor"],5,2)));
            
            $dash="";
                    if($data->graphicElement->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->graphicElement->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                elseif($data->graphicElement->pen["lineStyle"]=="Solid")
                    $dash="";
//echo "$borderwidth,";
           
            $border=array("LTRB" => array('width' => $borderwidth,'color' =>$drawcolor,'cap'=>'square',
                            'join'=>'miter','dash'=>$dash));
           
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));			
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor=array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));			
        }
        
		//$color=array("r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"]);
        $this->pointer[]=array("type"=>"SetFillColor","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"Ellipse","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"width"=>$data->reportElement["width"],"height"=>$data->reportElement["height"],"hidden_type"=>"ellipse","drawcolor"=>$drawcolor,"fillcolor"=>$fillcolor,'border'=>$border);
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor");
    }
    
    public function element_textField($data) {
        $align="L";
        $fill=0;
        $border=0;
        $fontsize=10;
        $font="helvetica";
        $rotation="";
        $fontstyle="";
        $textcolor = array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor = array("r"=>255,"g"=>255,"b"=>255);
        $stretchoverflow="false";
        $printoverflow="false";
        $height=$data->reportElement["height"];
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $data->hyperlinkReferenceExpression=" ".$this->analyse_expression($data->hyperlinkReferenceExpression)." ";
        if(isset($data->reportElement["forecolor"])) {
            $textcolor = array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor = array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));
        }
        if($data->reportElement["mode"]=="Opaque") {
            $fill=1;
        }
        if(isset($data["isStretchWithOverflow"])&&$data["isStretchWithOverflow"]=="true") {
            $stretchoverflow="true";
        }
        if(isset($data->reportElement["isPrintWhenDetailOverflows"])&&$data->reportElement["isPrintWhenDetailOverflows"]=="true") {
            $printoverflow="true";
        }
        if(isset($data->box)) {
            $borderset="";
            if($data->box->topPen["lineWidth"]>0)
                $borderset.="T";
            if($data->box->leftPen["lineWidth"]>0)
                $borderset.="L";
            if($data->box->bottomPen["lineWidth"]>0)
                $borderset.="B";
            if($data->box->rightPen["lineWidth"]>0)
                $borderset.="R";
             if(isset($data->box->pen["lineColor"])) {
                $drawcolor=array("r"=>hexdec(substr($data->box->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->box->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->box->pen["lineColor"],5,2)));
            }
            
            if(isset($data->box->pen["lineStyle"])) {
                if($data->box->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->box->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                else
                    $dash="";
                //Dotted Dashed
            }
           
            $border=array($borderset => array('width' => $data->box->pen["lineWidth"],
                'cap' => 'butt', 
                'join' => 'miter', 
                'dash' =>$dash,
                'phase'=>0,
                'color' =>$drawcolor));
            //array($borderset=>array('width'=>$data->box->pen["lineWidth"],
                //'cap'=>'butt'(butt, round, square),'join'=>'miter' (miter, round,bevel),
                //'dash'=>2 ("2,1","2"),
              //  'colour'=>array(110,20,30)  ));
            //&&$data->box->pen["lineWidth"]>0
            //border can be array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))
            
            
           
            //elseif()
            
        }
        if(isset($data->reportElement["key"])) {
            $height=$fontsize*$this->adjust;
        }
        if(isset($data->textElement["textAlignment"])) {
            $align=$this->get_first_value($data->textElement["textAlignment"]);
        }
        if(isset($data->textElement["verticalAlignment"])) {
            
            $valign="T";
            if($data->textElement["verticalAlignment"]=="Bottom")
                $valign="B";
            elseif($data->textElement["verticalAlignment"]=="Middle")
                $valign="C";
            else
                $valign="T";
            
            
        }
        if(isset($data->textElement["rotation"])) {
            $rotation=$data->textElement["rotation"];
        }
        if(isset($data->textElement->font["fontName"])) {
            $font=$data->textElement->font["fontName"];
        }
        if(isset($data->textElement->font["size"])) {
            $fontsize=$data->textElement->font["size"];
        }
        if(isset($data->textElement->font["isBold"])&&$data->textElement->font["isBold"]=="true") {
            $fontstyle=$fontstyle."B";
        }
        if(isset($data->textElement->font["isItalic"])&&$data->textElement->font["isItalic"]=="true") {
            $fontstyle=$fontstyle."I";
        }
        if(isset($data->textElement->font["isUnderline"])&&$data->textElement->font["isUnderline"]=="true") {
            $fontstyle=$fontstyle."U";
        }
        $this->pointer[]=array("type"=>"SetXY","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"hidden_type"=>"SetXY");
        $this->pointer[]=array("type"=>"SetTextColor","forecolor"=>$data->reportElement["forecolor"],"r"=>$textcolor["r"],"g"=>$textcolor["g"],"b"=>$textcolor["b"],"hidden_type"=>"textcolor");
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","backcolor"=>$data->reportElement["backcolor"],"r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
        $this->pointer[]=array("type"=>"SetFont","font"=>$font,"fontstyle"=>$fontstyle,"fontsize"=>$fontsize,"hidden_type"=>"font");
         //$data->hyperlinkReferenceExpression=$this->analyse_expression($data->hyperlinkReferenceExpression);
        //if( $data->hyperlinkReferenceExpression!=''){echo "$data->hyperlinkReferenceExpression";die;}


        switch ($data->textFieldExpression) {
            case 'new java.util.Date()':
//### New: =>date("Y.m.d.",....
                $this->pointer[]=array ("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>date("Y-m-d H:i:s"),"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"date","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"valign"=>$valign);
//### End of modification				
                break;
            case '"Page "+$V{PAGE_NUMBER}+" of"':
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'Page $this->PageNo() of',"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
                break;
            case '$V{PAGE_NUMBER}':
                
                // $this->pdf->getAliasNbPages();
                if(isset($data["evaluationTime"])&&$data["evaluationTime"]=="Report") {
                    $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'{:ptp:}',"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
                }
                else {
                    $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'$this->PageNo()',"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
                }
                break;
            case '" " + $V{PAGE_NUMBER}':
                echo 1;
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>' {:ptp:}',"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"nb","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
                break;
            case '$V{REPORT_COUNT}':
//###                $this->report_count=0;	
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->report_count,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"report_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
                break;
            case '$V{'.$this->gnam.'_COUNT}':
//            case '$V{'.$this->arrayband[0]["gname"].'_COUNT}':
//###                $this->group_count=0;
				$gnam=$this->arrayband[0]["gname"];																
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->group_count["$this->gnam"],"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"group_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
                break;
            default:
                $writeHTML=false;//
               
                if($data->reportElement->property["name"]=="writeHTML" || $data->textElement['markup']=='html')
                    $writeHTML=1;
                if(isset($data->reportElement["isPrintRepeatedValues"]))
                    $isPrintRepeatedValues=$data->reportElement["isPrintRepeatedValues"];


                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$data->textFieldExpression,
                        "border"=>$border,"align"=>$align,"fill"=>$fill,
                        "hidden_type"=>"field","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,
                        "printWhenExpression"=>$data->reportElement->printWhenExpression,
                        "link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],
                        "writeHTML"=>$writeHTML,"isPrintRepeatedValues"=>$isPrintRepeatedValues,"rotation"=>$rotation,"valign"=>$valign);
                break;
        }
    }

    public function element_subReport($data) {
//        $b=$data->subreportParameter;
                $srsearcharr=array('.jasper','"',"'",' ','$P{SUBREPORT_DIR}+');
                $srrepalcearr=array('.jrxml',"","",'',$this->arrayParameter['SUBREPORT_DIR']);

                if (strpos($data->subreportExpression,'$P{SUBREPORT_DIR}') === false){
                    $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                }
                else{
                    $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                }
                $b=array(); 
                foreach($data as $name=>$out){
                        if($name=='subreportParameter'){
                            $b[$out['name'].'']=$out->subreportParameterExpression;
                        }
                }//loop to let multiple parameter pass to subreport pass to subreport
                $this->pointer[]=array("type"=>"subreport", "x"=>$data->reportElement["x"], "y"=>$data->reportElement["y"],
                        "width"=>$data->reportElement["width"], "height"=>$data->reportElement["height"],
                        "subreportparameterarray"=>$b,"connectionExpression"=>$data->connectionExpression,
                        "subreportExpression"=>$subreportExpression,"hidden_type"=>"subreport");
    }

    public function transferDBtoArray($host,$user,$password,$db_or_dsn_name,$cndriver="mysql") {
        $this->m=0;

        if(!$this->connect($host,$user,$password,$db_or_dsn_name,$cndriver))	//connect database
        {
            echo "Fail to connect database";
            exit(0);
        }
        if($this->debugsql==true) {
            
            echo "<textarea cols='100' rows='40'>$this->sql</textarea>";
            die;
        }

        if($cndriver=="odbc") {

            $result=odbc_exec( $this->myconn,$this->sql);
            while ($row = odbc_fetch_array($result)) {
                foreach($this->arrayfield as $out) {
                    $this->arraysqltable[$this->m]["$out"]=$row["$out"];
                }
                $this->m++;
            }
        }elseif($cndriver=="psql") {


            pg_send_query($this->myconn,$this->sql);
            $result = pg_get_result($this->myconn);
            while ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC)) {
                foreach($this->arrayfield as $out) {
                    $this->arraysqltable[$this->m]["$out"]=$row["$out"];
                }
                $this->m++;
            }
        }
        else {
             @mysql_query("set names 'utf8'");
            $result = @mysql_query($this->sql); //query from db

            while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                foreach($this->arrayfield as $out) {
                    $this->arraysqltable[$this->m]["$out"]=$row["$out"];
                }
                $this->m++;
            }
        }
//print_r(   $this->arraysqltable);die;
       	//close connection to db

    }

    public function time_to_sec($time) {
        $hours = substr($time, 0, -6);
        $minutes = substr($time, -5, 2);
        $seconds = substr($time, -2);

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function sec_to_time($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds % 3600 / 60);
        $seconds = $seconds % 60;

        return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
    }

    public function orivariable_calculation() {

        foreach($this->arrayVariable as $k=>$out) {
         //   echo $out['resetType']. "<br/><br/>";
            switch($out["calculation"]) {
                case "Sum":
                    $sum=0;
                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        foreach($this->arraysqltable as $table) {
                            $sum=$sum+$this->time_to_sec($table["$out[target]"]);
                            //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
                        }
                        //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                        //if($sum=="0:0"){$sum="00:00";}
                        $sum=$this->sec_to_time($sum);
                    }
                    else {
                        foreach($this->arraysqltable as $table) {
                            $sum=$sum+$table[$out["target"]];
                            $table[$out["target"]];
                        }
                    }

                    $this->arrayVariable[$k]["ans"]=$sum;
                    break;
                case "Average":

                    $sum=0;

                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;

                            $sum=$sum+$this->time_to_sec($table["$out[target]"]);


                        }

                        $sum=$this->sec_to_time($sum/$m);
                        $this->arrayVariable[$k]["ans"]=$sum;

                    }
                    else {
                        $this->arrayVariable[$k]["ans"]=$sum;
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;
                            $sum=$sum+$table["$out[target]"];
                        }
                        $this->arrayVariable[$k]["ans"]=$sum/$m;


                    }


                    break;
                case "DistinctCount":
                    break;
                case "Lowest":

                    foreach($this->arraysqltable as $table) {
                        $lowest=$table[$out["target"]];
                        if($table[$out["target"]]<$lowest) {
                            $lowest=$table[$out["target"]];
                        }
                        $this->arrayVariable[$k]["ans"]=$lowest;
                    }
                    break;
                case "Highest":
                    $out["ans"]=0;
                    foreach($this->arraysqltable as $table) {
                        if($table[$out["target"]]>$out["ans"]) {
                            $this->arrayVariable[$k]["ans"]=$table[$out["target"]];
                        }
                    }
                    break;
//### A Count for groups, as a variable. Not tested yet, but seemed to work in print_r()
				case "Count":
					$value=$this->arrayVariable[$k]["ans"];
					if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
                       $value=0;
					$value++;
                    $this->arrayVariable[$k]["ans"]=$value;
				break;	
//### End of modification				
                default:
                    $out["target"]=0;		//other cases needed, temporary leave 0 if not suitable case
                    break;

            }
        }
    }


      public function variable_calculation($rowno) {


        foreach($this->arrayVariable as $k=>$out) {
         //   echo $out['resetType']. "<br/><br/>";
            switch($out["calculation"]) {
                case "Sum":

                         $value=$this->arrayVariable[$k]["ans"];
                    if($out['resetType']==''){
                            if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                            //    foreach($this->arraysqltable as $table) {
                                    $value=$this->time_to_sec($value);

                                    $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                    //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
                               // }
                                //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                                //if($sum=="0:0"){$sum="00:00";}
                                $value=$this->sec_to_time($value);
                            }
                            else {
                               // foreach($this->arraysqltable as $table) {
                                         $value+=$this->arraysqltable[$rowno]["$out[target]"];

                              //      $table[$out["target"]];
                             //   }
                            }
                    }// finisish resettype=''
                    else //reset type='group'
                    {if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
                             $value=0;
                      //    echo $this->global_pointer.",".$this->group_pointer.",".$this->arraysqltable[$this->global_pointer][$this->group_pointer].",".$this->arraysqltable[$this->global_pointer-1][$this->group_pointer].",".$this->arraysqltable[$rowno]["$out[target]"];
                                 if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                                      $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                                //if($sum=="0:0"){$sum="00:00";}
                                $value=$this->sec_to_time($value);
                            }
                            else {
                                      $value+=$this->arraysqltable[$rowno]["$out[target]"];
                            }
                    }


                    $this->arrayVariable[$k]["ans"]=$value;
              //      echo ",$value<br/>";
                    break;
                case "Average":

                    $sum=0;

                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        $m=0;
                        //$value=$this->arrayVariable[$k]["ans"];
                        //$value=$this->time_to_sec($value);
                        //$value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);

                        foreach($this->arraysqltable as $table) {
                            $m++;

                             $sum=$sum+$this->time_to_sec($table["$out[target]"]);
                           // echo ",".$table["$out[target]"]."<br/>";

                        }


                        $sum=$this->sec_to_time($sum/$m);
                     // echo "Total:".$sum."<br/>";
                         $this->arrayVariable[$k]["ans"]=$sum;


                    }
                    else {
                        $this->arrayVariable[$k]["ans"]=$sum;
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;
                            $sum=$sum+$table["$out[target]"];
                        }
                        $this->arrayVariable[$k]["ans"]=$sum/$m;


                    }


                    break;
                case "DistinctCount":
                    break;
                case "Lowest":

                    foreach($this->arraysqltable as $table) {
                        $lowest=$table[$out["target"]];
                        if($table[$out["target"]]<$lowest) {
                            $lowest=$table[$out["target"]];
                        }
                        $this->arrayVariable[$k]["ans"]=$lowest;
                    }
                    break;
                case "Highest":
                    $out["ans"]=0;
                    foreach($this->arraysqltable as $table) {
                        if($table[$out["target"]]>$out["ans"]) {
                            $this->arrayVariable[$k]["ans"]=$table[$out["target"]];
                        }
                    }
                    break;
//### A Count for groups, as a variable. Not tested yet, but seemed to work in print_r()					
                case "Count":
					$value=$this->arrayVariable[$k]["ans"];
					if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
                       $value=0;
					$value++;
                    $this->arrayVariable[$k]["ans"]=$value;
				break;
//### End of modification
                default:
                    $out["target"]=0;		//other cases needed, temporary leave 0 if not suitable case
                    break;

            }
        }
    }


    public function outpage($out_method="I",$filename="") {

            if($this->pdflib=="TCPDF") {
                if($this->arrayPageSetting["orientation"]=="P")
                    $this->pdf=new TCPDF($this->arrayPageSetting["orientation"],'pt',array(intval($this->arrayPageSetting["pageWidth"]),intval($this->arrayPageSetting["pageHeight"])),true);
                else
                    $this->pdf=new TCPDF($this->arrayPageSetting["orientation"],'pt',array( intval($this->arrayPageSetting["pageHeight"]),intval($this->arrayPageSetting["pageWidth"])),true);
                $this->pdf->setPrintHeader(false);
                $this->pdf->setPrintFooter(false);
                
            }elseif($this->pdflib=="FPDF") {
                if($this->arrayPageSetting["orientation"]=="P")
                    $this->pdf=new FPDF($this->arrayPageSetting["orientation"],'pt',array(intval($this->arrayPageSetting["pageWidth"]),intval($this->arrayPageSetting["pageHeight"])));
                else
                    $this->pdf=new FPDF($this->arrayPageSetting["orientation"],'pt',array(intval($this->arrayPageSetting["pageHeight"]),intval($this->arrayPageSetting["pageWidth"])));
            }
            elseif($this->pdflib=="XLS"){
                

            
                 include dirname(__FILE__)."/ExportXLS.inc.php";
                $xls= new ExportXLS($this,$filename, 'Excel5');
                die;


            }elseif($this->pdflib == 'CSV'){
                
                 include dirname(__FILE__)."/ExportXLS.inc.php";
                $xls= new ExportXLS($this,$filename, 'CSV');
                die;
            }elseif($this->pdflib == 'XLST'){
                
                include dirname(__FILE__)."/ExportXLS.inc.php";
                $xls= new ExportXLS($this,$filename, 'Excel2007');
                die;
            }
     //   }
        //$this->arrayPageSetting["language"]=$xml_path["language"];
        $this->pdf->SetLeftMargin($this->arrayPageSetting["leftMargin"]);
        $this->pdf->SetRightMargin($this->arrayPageSetting["rightMargin"]);
        $this->pdf->SetTopMargin($this->arrayPageSetting["topMargin"]);
        $this->pdf->SetAutoPageBreak(true,$this->arrayPageSetting["bottomMargin"]/2);
        $this->pdf->AliasNbPages();


        $this->global_pointer=0;

        foreach ($this->arrayband as $band) {
//            $this->currentband=$band["name"]; // to know current where current band in!
            switch($band["name"]) {
                case "title":
                  if($this->arraytitle[0]["height"]>0)
                    $this->title();
                    break;
                case "pageHeader":
                    //if(!$this->newPageGroup) {
                        
                        if($this->titlewithpagebreak==false)
                        $headerY = $this->arrayPageSetting["topMargin"]+$this->titlebandheight;
                        else 
                        $headerY = $this->arrayPageSetting["topMargin"];
                    
                        $this->pageHeader($headerY);
                        $this->titlebandheight=0;
                    //}else {
                      //  $this->pageHeaderNewPage();
                   // }
                    break;
              
                case "detail":
//                    if(!$this->newPageGroup) {
                        $this->detail();
                    break;

                case "group":
                    $this->group_pointer=$band["groupExpression"];
                    $this->group_name=$band["gname"];
                    break;





                    default:
                break;

            }

        }

        if($filename=="")
            $filename=$this->arrayPageSetting["name"].".pdf";

         $this->disconnect($this->cndriver);
         $this->pdf->SetXY(10,10);
         //$this->pdf->IncludeJS($this->createJS());
         //($name, $w, $h, $caption, $action, $prop=array(), $opt=array(), $x='', $y='', $js=false)
         //$this->pdf->Button('print', 100, 10, 'Print', 'Print()',null,null,20,20,true);
        return $this->pdf->Output($filename,$out_method);	//send out the complete page

    }
public function element_pieChart($data){

          $height=$data->chart->reportElement["height"];
          $width=$data->chart->reportElement["width"];
         $x=$data->chart->reportElement["x"];
         $y=$data->chart->reportElement["y"];
          $charttitle['position']=$data->chart->chartTitle['position'];

           $charttitle['text']=$data->chart->chartTitle->titleExpression;
          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
          $chartLegendPos=$data->chart->chartLegend['position'];

          $dataset=$data->pieDataset->dataset->datasetRun['subDataset'];

          $seriesexp=$data->pieDataset->keyExpression;
          $valueexp=$data->pieDataset->valueExpression;
          $bb=$data->pieDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset["$bb"]['sql'];

         // $ylabel=$data->linePlot->valueAxisLabelExpression;


          $param=array();
          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
          }
//          print_r($param);

         $this->pointer[]=array('type'=>'PieChart','x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,
               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
            'valueexp'=>$valueexp,'param'=>$param,'sql'=>$sql,'ylabel'=>$ylabel);

    }
    public function element_pie3DChart($data){


    }

    public function element_Chart($data,$type){
   $seriesexp=array();
          $catexp=array();
          $valueexp=array();
          $labelexp=array();
          $height=$data->chart->reportElement["height"];
          $width=$data->chart->reportElement["width"];
         $x=$data->chart->reportElement["x"];
         $y=$data->chart->reportElement["y"];
          $charttitle['position']=$data->chart->chartTitle['position'];
                    $titlefontname=$data->chart->chartTitle->font['fontName'];
          $titlefontsize=$data->chart->chartTitle->font['size'];
           $charttitle['text']=$data->chart->chartTitle->titleExpression;
          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
          $chartLegendPos=$data->chart->chartLegend['position'];
          $dataset=$data->categoryDataset->dataset->datasetRun['subDataset'];
          $subcatdataset=$data->categoryDataset;
          //echo $subcatdataset;
          $i=0;
          foreach($subcatdataset as $cat => $catseries){
            foreach($catseries as $a => $series){
               if("$series->categoryExpression"!=''){
                array_push( $seriesexp,"$series->seriesExpression");
                array_push( $catexp,"$series->categoryExpression");
                array_push( $valueexp,"$series->valueExpression");
                array_push( $labelexp,"$series->labelExpression");
               }

            }

          }


          $bb=$data->categoryDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset[$bb]['sql'];
          switch($type){
            case "barChart":
                $ylabel=$data->barPlot->valueAxisLabelExpression;
                $xlabel=$data->barPlot->categoryAxisLabelExpression;
                $maxy=$data->barPlot->rangeAxisMaxValueExpression;
                $miny=$data->barPlot->rangeAxisMinValueExpression;
                break;
            case "lineChart":
                $ylabel=$data->linePlot->valueAxisLabelExpression;
                $xlabel=$data->linePlot->categoryAxisLabelExpression;
                $maxy=$data->linePlot->rangeAxisMaxValueExpression;
                $miny=$data->linePlot->rangeAxisMinValueExpression;
                $showshape=$data->linePlot["isShowShapes"];
                break;
             case "stackedAreaChart":
                      $ylabel=$data->areaPlot->valueAxisLabelExpression;
                        $xlabel=$data->areaPlot->categoryAxisLabelExpression;
                        $maxy=$data->areaPlot->rangeAxisMaxValueExpression;
                        $miny=$data->areaPlot->rangeAxisMinValueExpression;
                        
                
                 break;
          }
          


          $param=array();
          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
          }
          if($maxy!='' && $miny!=''){
              $scalesetting=array(0=>array("Min"=>$miny,"Max"=>$maxy));
          }
          else
              $scalesetting="";

         $this->pointer[]=array('type'=>$type,'x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,
               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
             'catexp'=>$catexp,'valueexp'=>$valueexp,'labelexp'=>$labelexp,'param'=>$param,'sql'=>$sql,'xlabel'=>$xlabel,'showshape'=>$showshape,
             'titlefontsize'=>$titlefontname,'titlefontsize'=>$titlefontsize,'scalesetting'=>$scalesetting);


    }



public function setChartColor(){

    $k=0;
$this->chart->setColorPalette($k,0,255,88);$k++;
$this->chart->setColorPalette($k,121,88,255);$k++;
$this->chart->setColorPalette($k,255,91,99);$k++;
$this->chart->setColorPalette($k,255,0,0);$k++;
$this->chart->setColorPalette($k,0,0,100);$k++;
$this->chart->setColorPalette($k,200,0,100);$k++;
$this->chart->setColorPalette($k,0,100,0);$k++;
$this->chart->setColorPalette($k,100,0,0);$k++;
$this->chart->setColorPalette($k,200,0,0);$k++;
$this->chart->setColorPalette($k,0,0,200);$k++;
$this->chart->setColorPalette($k,50,0,0);$k++;
$this->chart->setColorPalette($k,100,0,50);$k++;
$this->chart->setColorPalette($k,0,50,0);$k++;
$this->chart->setColorPalette($k,100,50,0);$k++;
$this->chart->setColorPalette($k,50,100,50);$k++;
$this->chart->setColorPalette($k,0,255,0);$k++;
$this->chart->setColorPalette($k,100,50,0);$k++;
$this->chart->setColorPalette($k,200,100,50);$k++;
$this->chart->setColorPalette($k,100,50,200);$k++;
$this->chart->setColorPalette($k,0,200,0);$k++;
$this->chart->setColorPalette($k,200,100,0);$k++;
$this->chart->setColorPalette($k,200,50,50);$k++;
$this->chart->setColorPalette($k,50,50,50);$k++;
$this->chart->setColorPalette($k,200,100,100);$k++;
$this->chart->setColorPalette($k,50,50,100);$k++;
$this->chart->setColorPalette($k,100,0,200);$k++;
$this->chart->setColorPalette($k,200,50,100);$k++;
$this->chart->setColorPalette($k,100,100,200);$k++;
$this->chart->setColorPalette($k,0,0,50);$k++;
$this->chart->setColorPalette($k,50,250,200);$k++;
$this->chart->setColorPalette($k,100,250,200);$k++;
$this->chart->setColorPalette($k,10,10,10);$k++;
$this->chart->setColorPalette($k,20,30,50);$k++;
$this->chart->setColorPalette($k,80,150,200);$k++;
$this->chart->setColorPalette($k,30,70,20);$k++;
$this->chart->setColorPalette($k,33,60,0);$k++;
$this->chart->setColorPalette($k,150,0,200);$k++;
$this->chart->setColorPalette($k,20,60,50);$k++;
$this->chart->setColorPalette($k,50,250,250);$k++;
$this->chart->setColorPalette($k,33,250,70);$k++;

}


public function showLineChart($data,$y_axis){
    global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;

    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

    $result = @mysql_query($sql); //query from db
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


$Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));



    //if($type=='StackedBarChart')
      //  $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
        //    "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"DrawArrows"=>TRUE,"ArrowSize"=>6);
    //else
    $ScaleSpacing=5;
        $scalesetting= $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,
            "GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"Mode"=>SCALE_MODE_START0,'ScaleSpacing'=>$ScaleSpacing);

    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);


    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));

         $this->chart->drawLineChart();


   $randomchartno=rand();
	  $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }

}



public function showBarChart($data,$y_axis,$type='barChart'){
      global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;
    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

    $result = @mysql_query($sql); //query from db
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


 $Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=10;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));


if($type=='stackedBarChart')
        $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
            "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"ArrowSize"=>6);
    else
            $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
            "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_START0,"ArrowSize"=>6);
    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);

    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);
//print_r($textsetting);die;
    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));


    if($type=='stackedBarChart')
        $this->chart->drawStackedBarChart();
    else
        $this->chart->drawBarChart();


   $randomchartno=rand();
	  $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }


}



public function showPieChart($data,$y_axis){
     


}




public function showAreaChart($data,$y_axis,$type){
    global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;

    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

    $result = @mysql_query($sql); //query from db
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


$Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));



    //if($type=='StackedBarChart')
      //  $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
        //    "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"DrawArrows"=>TRUE,"ArrowSize"=>6);
    //else
    $ScaleSpacing=5;
        $scalesetting= $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,
            "GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,'ScaleSpacing'=>$ScaleSpacing);

    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);


    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));

$this->chart->drawStackedAreaChart(array("Surrounding"=>60));


   $randomchartno=rand();
	  $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }

}




private function changeSubDataSetSql($sql){

foreach($this->currentrow as $name =>$value)
        $sql=str_replace('$F{'.$name.'}',$value,$sql);

foreach($this->arrayParameter as $name=>$value)
    $sql=str_replace('$P{'.$name.'}',$value,$sql);

foreach($this->arrayVariable as $name=>$value){
    $sql=str_replace('$V{'.$value['target'].'}',$value['ans'],$sql);


}


//print_r($this->arrayparameter);


//variable not yet implemented
     return $sql;


}
    public function background() {
        foreach ($this->arraybackground as $out) {
            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arrayPageSetting["topMargin"],true);
                    break;
                default:
                    $this->display($out,$this->arrayPageSetting["topMargin"],false);
                    break;
            }

        }
    }

    public function pageHeader($headerY,$newpage=false) {
        
        $this->currentband='pageHeader';// to know current where current band in!
                    
        if(($headerY==""||$this->titleheight==0) || $newpage==true){

        //if($this->titlebandheight==0 || $this->titlebandheight=="" ){
            $this->pdf->AddPage();
            $this->background();
                $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"];      
                $headerY=$this->arrayPageSetting["topMargin"];      
                
        }
        else{
                    
                    $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
        }
        
        
        
          
        
            
        foreach ($this->arraypageHeader as $out) {
            
            switch($out["hidden_type"]) {
                case "field":
                    
                    $this->display($out,$headerY,true);
                    
                    break;
                default:
                    
                    $this->display($out,$headerY,false);
                    
                    break;
            }
        }
        
        $this->currentband='';
    }


    
    
    public function pageHeaderNewPage() {
        $this->currentband='pageHeader';
        $this->pdf->AddPage();
        $this->background();
        if(isset($this->arraypageHeader)) {
               //$headerY = $this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"];
            $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->titlebandheight;
        }
        foreach ($this->arraypageHeader as $out) {
            switch($out["hidden_type"]) {
                case "textfield":
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
                default:
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
            }
        }
        $this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]);
    }


      public function columnHeader($y) {
        //$this->pdf->AddPage();
        //$this->background();
            $this->currentband='columnHeader';
            //$this->titlesummary=$this->arraycolumnHeader[0]["height"];
            if($this->titlewithpagebreak==false && $this->pdf->getPage() ==1)
                $y=$this->titleheight+$this->headerbandheight+$this->arrayPageSetting["topMargin"];
                else
            $y=$this->arrayPageSetting["topMargin"]+$this->headerbandheight;
            //print_r($this->arraytitle);die;

        foreach ($this->arraycolumnHeader as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$y,true);
                    break;
                default:
                    $this->display($out,$y,false);
                    break;
            }
        }

                $this->currentband='';
    }
  public function columnFooter() {
        //$this->pdf->AddPage();
        //$this->background();
            $this->currentband='columnFooter';
            //$this->titlesummary=$this->arraycolumnHeader[0]["height"];

            //print_r($this->arraytitle);die;
        $y= $this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->columnfooterbandheight;
       foreach ($this->arraycolumnFooter as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$y,true);
                    break;
                default:
                    $this->display($out,$y,false);
                    break;
            }
        }

                $this->currentband='';
    }

    
    public function title() {
          $this->currentband='title';

            
        if(isset($this->arraytitle)) {
            
            $this->pdf->AddPage();
            $this->background();
            $this->titleheight=$this->arraytitle[0]["height"];
            $this->arraytitle[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
            
        foreach ($this->arraytitle as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arraytitle[0]["y_axis"],true);
                    break;
                case "break":
               
                     $this->pdf->AddPage();
                    //  $this->background();                  
                      $this->titlewithpagebreak=true;
                    break;
                default:
                    $this->display($out,$this->arraytitle[0]["y_axis"],false);
                    break;
            }
        }
        
        }else{
             $this->titleheight=0;
            
        }


        $this->currentband='';
    }

      public function summary($y) {
            $this->currentband='summary';
            $this->titlesummary=$this->arraysummary[0]["height"];
            $currentPage=$this->pdf->GetPage();
             if($this->detailallowtill < ($y+$this->summarybandheight) ){
                 $this->pdf->AddPage();
                 $y=$this->arrayPageSetting["topMargin"];
            }
                  
        foreach ($this->arraysummary as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$y,true);
                    break;
                default:
                    $this->display($out,$y,false);
                    break;
            }
        }
        
        
       
            $this->pdf->SetPage($currentPage);
         
                
        if(isset($this->arraylastPageFooter)){
            $this->columnFooter();
            $this->lastPageFooter();
        }
        else{
            $this->columnFooter();
             $this->pageFooter();
        }
       
        $this->currentband='';
        
        
    }

    
    
    public function group($headerY) {


        $gname=$this->arrayband[0]["gname"]."";
        if(isset($this->arraypageHeader)) {
            $this->arraygroup[$gname]["groupHeader"][0]["y_axis"]=$headerY;
        }
        if(isset($this->arraypageFooter)) {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }
        else {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }

        if(isset($this->arraygroup)) {

            foreach($this->arraygroup[$gname] as $name=>$out) {


                switch($name) {
                    case "groupHeader":
//###                        $this->group_count=0;
                        foreach($out as $path) { 
                            switch($path["hidden_type"]) {
                                case "field":

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],true);
                                    break;
                                default:

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    case "groupFooter":
                        foreach($out as $path) {
                            switch($path["hidden_type"]) {
                                case "field":
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],true);
                                    break;
                                default:
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }
//
//
//    public function groupNewPage() {
//        $gname=$this->arrayband[0]["gname"]."";
//
//        if(isset($this->arraypageHeader)) {
//            $this->arraygroup[$gname]["groupHeader"][0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
//        }
//        if(isset($this->arraypageFooter)) {
//            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
//        }
//        else {
//            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
//        }
//
//        if(isset($this->arraygroup)) {
//            foreach($this->arraygroup[$gname] as $name=>$out) {
//                switch($name) {
//                    case "groupHeader":
//                        foreach($out as $path) {
//                            switch($path["hidden_type"]) {
//                                case "field":
//                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],true);
//                                    break;
//                                default:
//
//                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],false);
//                                    break;
//                            }
//                        }
//                        break;
//                    case "groupFooter":
//                        foreach($out as $path) {
//                            switch($path["hidden_type"]) {
//                                case "field":
//                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],true);
//                                    break;
//                                default:
//                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],false);
//                                    break;
//                            }
//                        }
//                        break;
//                    default:
//                        break;
//                }
//            }
//        }
//    }

    public function pageFooter() {
        $this->currentband='pageFooter';
        if(isset($this->arraypageFooter)) {
            foreach ($this->arraypageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "field":
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],true);
                        break;
                    default:
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],false);
                        break;
                }
            }
        }
        else {
            $this->lastPageFooter();
        }
        $this->currentband='';
    }

    public function lastPageFooter() {
        $this->currentband='lastPageFooter';
        if(isset($this->arraylastPageFooter)) {
            foreach ($this->arraylastPageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "field":
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],true);
                        break;
                    default:
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],false);
                        break;
                }
            }
        }
        $this->currentband='';
    }

    public function NbLines($w,$txt) {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->pdf->CurrentFont['cw'];
        if($w==0)
            $w=$this->pdf->w-$this->pdf->rMargin-$this->pdf->x;
        $wmax=($w-2*$this->pdf->cMargin)*1000/$this->pdf->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb) {
            $c=$s[$i];
            if($c=="\n") {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
    

	public function printlongtext($fontfamily,$fontstyle,$fontsize){
					//$this->gotTextOverPage=false;
						$this->columnFooter();
						$this->pageFooter();
						$this->pageHeader();
                                                $this->columnHeader();
					$this->hideheader==true;
					
					$this->currentband='detail';  
          
                                        $fontfile=$this->fontdir.'/'.$fontfamily.'.php';
                              if(file_exists($fontfile) || $this->bypassnofont==false)
                $this->pdf->SetFont($fontfamily,$arraydata["fontstyle"],$arraydata["fontsize"],$fontfile);
            else
                $this->pdf->SetFont('helvetica',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/helvetica.php');
                                        
					//$this->pdf->SetFont($fontfamily,$fontstyle,$fontsize,$this->fontdir.'/'.$fontfamily.'php');
                                        
					$this->pdf->SetTextColor($this->forcetextcolor_r,$this->forcetextcolor_g,$this->forcetextcolor_b);
					//$this->pdf->SetTextColor(44,123,4);
					$this->pdf->SetFillColor($this->forcefillcolor_r,$this->forcefillcolor_g,$this->forcefillcolor_b);

					$bltxt=$this->continuenextpageText; 
					$this->pdf->SetY($this->arraypageHeader[0]["height"]+$this->columnheaderbandheight+$this->arrayPageSetting["topMargin"]);
					$this->pdf->SetX($bltxt['x']);
					$maxheight=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()-$bltxt['height'];

					$this->pdf->MultiCell($bltxt['width'],$bltxt['height'],$bltxt['txt'],
								$bltxt['border'],
								$bltxt['align'],$bltxt['fill'],$bltxt['ln'],'','',$bltxt['reset'],
								$bltxt['streth'],$bltxt['ishtml'],$bltxt['autopadding'],$maxheight-$bltxt['height'],$bltxt['valign']);
							
					   if($this->pdf->balancetext!=''){
							$this->continuenextpageText=array('width'=>$bltxt["width"], 'height'=>$bltxt["height"], 
								'txt'=>$this->pdf->balancetext,	'border'=>$bltxt["border"] ,'align'=>$bltxt["align"], 'fill'=>$bltxt["fill"],'ln'=>1,
										'x'=>$bltxt['x'],'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true,'valign'=>$bltxt['valign']);
								$this->pdf->balancetext='';
								$this->printlongtext($fontfamily,$fontstyle,$fontsize);
					  }
					//echo $this->currentband;  
				if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
								$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
					
				}
		}
		
		
    public function detail() {
		$currentpage= $this->pdf->getNumPages();
		$this->maxpagey=array();
        $this->currentband='detail';
   
        $this->arraydetail[0]["y_axis"]=$this->arraydetail[0]["y_axis"];//- $this->titleheight;
        $field_pos_y=$this->arraydetail[0]["y_axis"];
        $biggestY=0;
        $tempY=$this->arraydetail[0]["y_axis"];
        
       if(isset($this->SubReportCheckPoint))
		$checkpoint=$this->SubReportCheckPoint;


             $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
	    //if($this->pdf->getPage()>1)
                if($this->pdf->getPage()>1)
                     $checkpoint= $this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight,false);
                else
                     $checkpoint=$this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->orititlebandheight+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight,false);
                
            
            if($this->pdf->getPage()>1)
              $this->titlebandheight=0;
            
		$isgroupfooterprinted=false;
            if($this->titlewithpagebreak==false)
		$this->maxpagey=array('page_0'=>$checkpoint);
            else
                $this->maxpagey=array('page_1'=>$checkpoint);
        $rownum=0; 
        
        if($this->arraysqltable) {
		$n=0;
            foreach($this->arraysqltable as $row) {
	
	
   			$n++;
				$currentpage= $this->pdf->getNumPages();
				
				$this->pdf->lastPage();
				$this->hideheader==false;
					
				if($n>1)
					$checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];
      //                   echo $checkpoint."<br/>";

		$pageheight=$this->arrayPageSetting["pageHeight"];
		$footerheight=$this->footerbandheight;
		$headerheight=$this->headerbandheight;
		$bottommargin=$this->arrayPageSetting["bottomMargin"];
		$detailheight=$this->detailbandheight;
		
         
                 
               if(isset($this->arraygroup)&&($this->global_pointer>0)&&
                            ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])){	//check the group's groupExpression existed and same or not
				
                                $checkpoint=$this->showGroupHeader($checkpoint,true);
                        	$currentpage= $this->pdf->getNumPages();
				$this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
                              
                    $this->pdf->SetY($checkpoint); 
                    $this->group_count["$this->group_name"]=1;	// We're on the first row of the group.				 
		
                }

       //check detail band will over current page footer
                if(($checkpoint +$detailheight >$this->detailallowtill) && ($this->pdf->getPage()>1) ||
                        ($checkpoint +$detailheight >$this->detailallowtill-$this->orititleheight) && ($this->pdf->getNumPages()==1)){
                    
                                                $this->columnFooter();
						$this->pageFooter();
						$this->pageHeader();
                                                $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
						$currentpage= $this->pdf->getNumPages();
						$checkpoint=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->titlebandheight+$this->columnheaderbandheight;//$this->arraydetail[0]["y_axis"]- $this->titleheight;
						$this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
		}
		if(isset($this->arrayVariable))	//if self define variable existing, go to do the calculation
                                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
                
		$this->currentband='detail';
	
/* begin page handling*/


//begin page handling
                foreach ($this->arraydetail as $out) {
//						echo $out["hidden_type"]."<br/>";
                    switch ($out["hidden_type"]) {
                        case "field":
                     //        $txt=$this->analyse_expression($compare["txt"]);

		         $maxheight=$this->detailallowtill-$checkpoint;//$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()+2-$this->columnheaderbandheight-$this->columnfooterbandheight;
                            $this->prepare_print_array=array("type"=>"MultiCell","width"=>$out["width"],"height"=>$out["height"],"txt"=>$out["txt"],
									"border"=>$out["border"],"align"=>$out["align"],"fill"=>$out["fill"],"hidden_type"=>$out["hidden_type"],
									"printWhenExpression"=>$out["printWhenExpression"],"soverflow"=>$out["soverflow"],"poverflow"=>$out["poverflow"],"link"=>$out["link"],
									"pattern"=>$out["pattern"],"writeHTML"=>$out["writeHTML"],"isPrintRepeatedValues"=>$out["isPrintRepeatedValues"],"valign"=>$out["valign"]);
                            $this->display($this->prepare_print_array,0,true,$maxheight);
              //                                  $checkpoint=$this->arraydetail[0]["y_axis"];

					        break;
                        case "relativebottomline":
                        //$this->relativebottomline($out,$tempY);
                            $this->relativebottomline($out,$biggestY);
                            break;
                          case "subreport":
                            $checkpoint=$this->display($out,$checkpoint);
                              //$this->arraydetail[0]["y_axis"]=$checkpoint;
                              //$biggestY=$checkpoint;
                              if($this->maxpagey['page_'.($this->pdf->getNumPages()-1)]<$checkpoint)
                              $this->maxpagey['page_'.($this->pdf->getNumPages()-1)]=$checkpoint;
						 break;
                        default:
							//echo $out["hidden_type"]."=".print_r($out,true)."<br/><br/>";
                            $this->display($out,$checkpoint);
			   $maxheight=$this->detailallowtill-$checkpoint;

                            //$checkpoint=$this->pdf->GetY();
                            break;
                    }
                    
                    if($this->pdf->getNumPages()>1){
                       
                    $this->pdf->setPage($currentpage);
                    
                    }

                }

				$this->pdf->lastPage();
								
//                if($this->SubReportCheckPoint>0)
	//				$biggestY=$this->SubReportCheckPoint;
		//			$this->SubReportCheckPoint=0; //if subreport return position

        if(isset($this->arraygroup)&&
           ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer+1][$this->group_pointer])){




		$pageheight=$this->arrayPageSetting["pageHeight"];
		$footerheight=$this->footerbandheight;
		$headerheight=$this->headerbandheight;
		$topmargin=$this->arrayPageSetting["topMargin"];
		$bottommargin=$this->arrayPageSetting["bottomMargin"];
		$detailheight=$this->detailbandheight;
		$gfootheight=$this->arraygroupfoot[0]['height'];
		$currentY=$this->maxpagey['page_'.($this->pdf->getPage()-1)];
		
                        $this->currentband='detail';
   			            }

				foreach($this->group_count as &$cntval) {
					$cntval++;
				}
				$this->report_count++;
				
                $this->global_pointer++;
                   $rownum++;			
                                // $this->columnFooter();
				//  $this->pdf->lastpage();
				  $headerY=$checkpoint;            
            
            }
        
        
					$this->global_pointer--;
        }else {
            echo "No data found";
            exit(0);
        }
 
 
 			

  if(isset($this->arraygroup)){
      $checkpoint=$this->showGroupFooter($this->maxpagey['page_'.($this->pdf->getNumPages()-1)]);
  }
                  $this->summary($checkpoint);
             

 
   			
    }


    public function showGroupHeader($y,$printgroupfooter=false) {
        if($printgroupfooter==true){
            $y=$this->showGroupFooter($y);
            
            if($this->newPageGroup==true){
                $this->columnFooter();
		$this->pageFooter();
                $this->pageHeader();
                $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
                $y=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight;
            }
        }
             $bandheight=$this->arraygrouphead[0]['height'];
             $yplusbandheight=$y+$bandheight;
            
        
        $this->currentband='groupHeader';
      if($yplusbandheight>$this->detailallowtill){
            
                $this->columnFooter();
		$this->pageFooter();
		$this->pageHeader();
                $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
		$y=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight;
	
            
        }
        
        foreach ($this->arraygrouphead as $out) {
            $this->display($out,$y,true);
        }
        $this->currentband='';
        
        return $y+$bandheight;
    }
    public function showGroupFooter($y) {
        $this->currentband='groupFooter';
        $bandheight=$this->arraygroupfoot[0]['height'];
        $yplusbandheight=$y+$bandheight;
        
                
                
        if($yplusbandheight>$this->detailallowtill){
            
                                                $this->columnFooter();
						$this->pageFooter();
						$this->pageHeader();
                                                $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
				$y=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight;
            
        }

        foreach ($this->arraygroupfoot as $out) {
            $this->display($out,$y,true);
        }
     
        $this->currentband='';
        return $y+$bandheight;

     

    }


    public function display($arraydata,$y_axis=0,$fielddata=false,$maxheight=0) {
  
    $this->Rotate($arraydata["rotation"]);
    
    if($arraydata["rotation"]!=""){
            if($arraydata["rotation"]=="Left"){
                 $w=$arraydata["width"];
                $arraydata["width"]=$arraydata["height"];
                $arraydata["height"]=$w;
                    $this->pdf->SetXY($this->pdf->GetX()-$arraydata["width"],$this->pdf->GetY());
            }
            elseif($arraydata["rotation"]=="Right"){
                 $w=$arraydata["width"];
                $arraydata["width"]=$arraydata["height"];
                $arraydata["height"]=$w;
                    $this->pdf->SetXY($this->pdf->GetX(),$this->pdf->GetY()-$arraydata["height"]);
            }
            elseif($arraydata["rotation"]=="UpsideDown"){
                //soverflow"=>$stretchoverflow,"poverflow"
                $arraydata["soverflow"]=true;
                $arraydata["poverflow"]=true;
               //   $w=$arraydata["width"];
               // $arraydata["width"]=$arraydata["height"];
                //$arraydata["height"]=$w;
                $this->pdf->SetXY($this->pdf->GetX()- $arraydata["width"],$this->pdf->GetY()-$arraydata["height"]);
            }
    }
    if($arraydata["type"]=="SetFont") {
            $arraydata["font"]=  strtolower($arraydata["font"]);
             
            $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
           if(file_exists($fontfile) || $this->bypassnofont==false)
                $this->pdf->SetFont($arraydata["font"],$arraydata["fontstyle"],$arraydata["fontsize"],$fontfile);
            else
                $this->pdf->SetFont('helvetica',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/helvetica.php');

        }
        elseif($arraydata["type"]=="subreport") {	
        

            return $this->runSubReport($arraydata,$y_axis);

        }
        elseif($arraydata["type"]=="MultiCell") {
           
            if($fielddata==false) {
                $this->checkoverflow($arraydata,$this->updatePageNo($arraydata["txt"]),'',$maxheight);
            }
            elseif($fielddata==true) {
                $this->checkoverflow($arraydata,$this->updatePageNo($this->analyse_expression($arraydata["txt"],$arraydata["isPrintRepeatedValues"] )),$maxheight);
            }
        }
        elseif($arraydata["type"]=="SetXY") {
            $this->pdf->SetXY($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis);
        }
        elseif($arraydata["type"]=="Cell") {


            $this->pdf->Cell($arraydata["width"],$arraydata["height"],$this->updatePageNo($arraydata["txt"]),$arraydata["border"],$arraydata["ln"],
                       $arraydata["align"],$arraydata["fill"],$arraydata["link"],0,true,"T",$arraydata["valign"]);


        }
        elseif($arraydata["type"]=="Rect"){
		if($arraydata['mode']=='Transparent')
		$style='';
		else
		$style='FD';
          //      $this->pdf->SetLineStyle($arraydata['border']);
			$this->pdf->Rect($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],$arraydata["height"],
			$style,$arraydata['border'],$arraydata['fillcolor']);
                }
        elseif($arraydata["type"]=="RoundedRect"){
			if($arraydata['mode']=='Transparent')
				$style='';
			else
			$style='FD';
			//
                //        $this->pdf->SetLineStyle($arraydata['border']);
			 $this->pdf->RoundedRect($arraydata["x"]+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis, $arraydata["width"],$arraydata["height"], $arraydata["radius"], '1111', 
			$style,$arraydata['border'],$arraydata['fillcolor']);
			}
        elseif($arraydata["type"]=="Ellipse"){
            //$this->pdf->SetLineStyle($arraydata['border']);
			 $this->pdf->Ellipse($arraydata["x"]+$arraydata["width"]/2+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis+$arraydata["height"]/2, $arraydata["width"]/2,$arraydata["height"]/2,
				0,0,360,'FD',$arraydata['border'],$arraydata['fillcolor']);
			}
        elseif($arraydata["type"]=="Image") {
            $path=$this->analyse_expression($arraydata["path"]);
            $imgtype=substr($path,-3);
            
            if($imgtype=='jpg' || right($path,3)=='jpg' || right($path,4)=='jpeg')
		$imgtype="JPEG";
            elseif($imgtype=='png'|| $imgtype=='PNG')
                  $imgtype="PNG";
          
        if(file_exists($path) || left($path,4)=='http' ){            
            $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                    $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"]); 
        }
        elseif(left($path,22)==  "data:image/jpeg;base64"){
            $imgtype="JPEG";
            $img=  str_replace('data:image/jpeg;base64,', '', $path);
            $imgdata = base64_decode($img);
            $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],
                    $arraydata["height"]);//,$imgtype,$arraydata["link"]); 
            
        }
        elseif(left($path,22)==  "data:image/png;base64,"){
                  $imgtype="PNG";
                 // $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

                 $img= str_replace('data:image/png;base64,', '', $path);
                             $imgdata = base64_decode($img);

           
            $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                    $arraydata["width"],$arraydata["height"]);//,$imgtype,$arraydata["link"]); 
    
            
        }

        }

        elseif($arraydata["type"]=="SetTextColor") {
			$this->textcolor_r=$arraydata['r'];
			$this->textcolor_g=$arraydata['g'];
			$this->textcolor_b=$arraydata['b'];
			
			if($this->hideheader==true && $this->currentband=='pageHeader')
				$this->pdf->SetTextColor(100,33,30);
			else
				$this->pdf->SetTextColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
        elseif($arraydata["type"]=="SetDrawColor") {
			$this->drawcolor_r=$arraydata['r'];
			$this->drawcolor_g=$arraydata['g'];
			$this->drawcolor_b=$arraydata['b'];
            $this->pdf->SetDrawColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
        elseif($arraydata["type"]=="SetLineWidth") {
            $this->pdf->SetLineWidth($arraydata["width"]);
        }
        elseif($arraydata["type"]=="break"){
      
          
        }
        elseif($arraydata["type"]=="Line") {
            $this->pdf->Line($arraydata["x1"]+$this->arrayPageSetting["leftMargin"],$arraydata["y1"]+$y_axis,$arraydata["x2"]+$this->arrayPageSetting["leftMargin"],$arraydata["y2"]+$y_axis,$arraydata["style"]);
        }
        elseif($arraydata["type"]=="SetFillColor") {
			$this->fillcolor_r=$arraydata['r'];
			$this->fillcolor_g=$arraydata['g'];
			$this->fillcolor_b=$arraydata['b'];
            $this->pdf->SetFillColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
      elseif($arraydata["type"]=="lineChart") {

            $this->showLineChart($arraydata, $y_axis);
        }
      elseif($arraydata["type"]=="barChart") {

            $this->showBarChart($arraydata, $y_axis,'barChart');
        }
      elseif($arraydata["type"]=="pieChart") {

            $this->showPieChart($arraydata, $y_axis);
        }
      elseif($arraydata["type"]=="stackedBarChart") {

            $this->showBarChart($arraydata, $y_axis,'stackedBarChart');
        }
      elseif($arraydata["type"]=="stackedAreaChart") {

            $this->showAreaChart($arraydata, $y_axis,$arraydata["type"]);
        }
        elseif($arraydata["type"]=="Barcode"){
            
            $this->showBarcode($arraydata, $y_axis);
        }

    }

    
    public function showBarcode($data,$y){
        
        $type=  strtoupper($data['barcodetype']);
        $height=$data['height'];
        $width=$data['width'];
        $x=$data['x'];
        $y=$data['y']+$y;
        $textposition=$data['textposition'];
        $code=$data['code'];
        $code=$this->analyse_expression($code);
        $modulewidth=$data['modulewidth'];
        if($textposition=="" || $textposition=="none")
         $withtext = false;
        else
            $withtext = true;
        
     $style = array(
    'border' => false,
    'vpadding' => 'auto',
    'hpadding' => 'auto',
         'text'=>$withtext,
    'fgcolor' => array(0,0,0),
    'bgcolor' => false, //array(255,255,255)
    'module_width' => 1, // width of a single module in points
    'module_height' => 1 // height of a single module in points
);

        
//[2D barcode section]        
//DATAMATRIX
//QRCODE,H or Q or M or L (H=high level correction, L=low level correction)
// -------------------------------------------------------------------
// PDF417 (ISO/IEC 15438:2006)

/*

 The $type parameter can be simple 'PDF417' or 'PDF417' followed by a
 number of comma-separated options:

 'PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6'

 Possible options are:

     a  = aspect ratio (width/height);
     e  = error correction level (0-8);

     Macro Control Block options:

     t  = total number of macro segments;
     s  = macro segment index (0-99998);
     f  = file ID;
     o0 = File Name (text);
     o1 = Segment Count (numeric);
     o2 = Time Stamp (numeric);
     o3 = Sender (text);
     o4 = Addressee (text);
     o5 = File Size (numeric);
     o6 = Checksum (numeric).

 Parameters t, s and f are required for a Macro Control Block, all other parametrs are optional.
 To use a comma character ',' on text options, replace it with the character 255: "\xff".

*/ 
        switch($type){
          case "PDF417":
               $this->pdf->write2DBarcode($code, 'PDF417', $x, $y, $width, $height, $style, 'N');
              break;
          case "DATAMATRIX":
              
              //$this->pdf->Cell( $width,10,$code);
              //echo $this->left($code,3);
              if($this->left($code,3)=="QR:"){
                  
              $code=  $this->right($code,strlen($code)-3);
              
              $this->pdf->write2DBarcode($code, 'QRCODE', $x, $y, $width, $height, $style, 'N');
              }
              else
                  $this->pdf->write2DBarcode($code, 'DATAMATRIX', $x, $y, $width, $height, $style, 'N');
              break;
            case "CODE128":
                $this->pdf->write1DBarcode($code, 'C128',  $x, $y, $width, $height, $modulewidth, $style, 'N');

              // $this->pdf->write1DBarcode($code, 'C128', $x, $y, $width, $height,"", $style, 'N');
              break;
          case  "EAN8":
                 $this->pdf->write1DBarcode($code, 'EAN8', $x, $y, $width, $height, $modulewidth,$style, 'N');
              break;
          case  "EAN13":
                 $this->pdf->write1DBarcode($code, 'EAN13', $x, $y, $width, $height, $modulewidth,$style, 'N');
              break;
          case  "CODE39":
                 $this->pdf->write1DBarcode($code, 'C39', $x, $y, $width, $height, $modulewidth,$style, 'N');
              break;
           case  "CODE93":
                 $this->pdf->write1DBarcode($code, 'C93', $x, $y, $width, $height, $modulewidth,$style, 'N');
              break;
        }
        

    }
    public function relativebottomline($path,$y) {
        $extra=$y-$path["y1"];
        $this->display($path,$extra);
    }

    public function updatePageNo($s) {
        return str_replace('$this->PageNo()', $this->pdf->PageNo(),$s);
    }

    public function staticText($xml_path) {
//$this->pointer[]=array("type"=>"SetXY","x"=>$xml_path->reportElement["x"],"y"=>$xml_path->reportElement["y"]);
    }
    


    public function checkoverflow($arraydata,$txt="",$maxheight=0) {

        $this->print_expression($arraydata);

        if($this->print_expression_result==true) {

            if($arraydata["link"]) {
                $arraydata["link"]=$this->analyse_expression($arraydata["link"],"");

            }

            if($arraydata["writeHTML"]==1 && $this->pdflib=="TCPDF") {
                $this->pdf->writeHTML($txt);
			$this->pdf->Ln();
					if($this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
            
            }
            
            elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="false") {
                            if($arraydata["valign"]=="M")
                                    $arraydata["valign"]="C";
                                if($arraydata["valign"]=="")
                                    $arraydata["valign"]="T";                
                                
                while($this->pdf->GetStringWidth($txt) > $arraydata["width"]) {
                    if($txt!=$this->pdf->getAliasNbPages() && $txt!=' '.$this->pdf->getAliasNbPages())
                    $txt=substr_replace($txt,"",-1);
                }
                            
                $this->pdf->Cell($arraydata["width"], $arraydata["height"],$this->formatText($txt, $arraydata["pattern"]),
						$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"],0,true,"T",$arraydata["valign"]);
				$this->pdf->Ln();
					if($this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
		
            }
             elseif($arraydata["soverflow"]=="true") {
				if($arraydata["valign"]=="C")
                                    $arraydata["valign"]="M";
                                if($arraydata["valign"]=="")
                                    $arraydata["valign"]="T";
                                
				$x=$this->pdf->GetX();
		        $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"] 
       							,$arraydata["align"], $arraydata["fill"],1,'','',true,0,false,true,$maxheight);//,$arraydata["valign"]);
		
				if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
				
			//$this->pageFooter();
            if($this->pdf->balancetext!='' ){
				$this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
						'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 'fill'=>$arraydata["fill"],'ln'=>1,
							'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
					$this->pdf->balancetext='';
					$this->forcetextcolor_b=$this->textcolor_b;
					$this->forcetextcolor_g=$this->textcolor_g;
					$this->forcetextcolor_r=$this->textcolor_r;
					$this->forcefillcolor_b=$this->fillcolor_b;
					$this->forcefillcolor_g=$this->fillcolor_g;
					$this->forcefillcolor_r=$this->fillcolor_r;
					if($this->continuenextpageText)
						$this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
					
					}          
				
					
         

            }
            elseif($arraydata["poverflow"]=="true") {
           
                            if($arraydata["valign"]=="M")
                                    $arraydata["valign"]="C";
                                if($arraydata["valign"]=="")
                                    $arraydata["valign"]="T"; 
                                
                $this->pdf->Cell($arraydata["width"], $arraydata["height"],  $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"],0,true,"T",
                                $arraydata["valign"]);
				$this->pdf->Ln();
					if($this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
            
            }
           
            else {
				//MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0) {	
                $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), $arraydata["border"], 
							$arraydata["align"], $arraydata["fill"],1,'','',true,0,true,true,$maxheight);
				if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
            if($this->pdf->balancetext!=''){
				$this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
						'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 'fill'=>$arraydata["fill"],'ln'=>1,
							'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
					$this->pdf->balancetext='';
					$this->forcetextcolor_b=$this->textcolor_b;
					$this->forcetextcolor_g=$this->textcolor_g;
					$this->forcetextcolor_r=$this->textcolor_r;
					$this->forcefillcolor_b=$this->fillcolor_b;
					$this->forcefillcolor_g=$this->fillcolor_g;
					$this->forcefillcolor_r=$this->fillcolor_r;
					$this->gotTextOverPage=true;
					if($this->continuenextpageText)
						$this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
					
					}          



            }
        }
        $this->print_expression_result=false;
        


    }

    public function hex_code_color($value) {
        $r=hexdec(substr($value,1,2));
        $g=hexdec(substr($value,3,2));
        $b=hexdec(substr($value,5,2));
        return array("r"=>$r,"g"=>$g,"b"=>$b);
    }

    public function get_first_value($value) {
        return (substr($value,0,1));
    }

    function right($value, $count) {

        return substr($value, ($count*-1));

    }

    function left($string, $count) {
        return substr($string, 0, $count);
    }

    public function analyse_expression($data,$isPrintRepeatedValue="true") {
        $arrdata=explode("+",$data);

        $i=0;
        
        foreach($arrdata as $num=>$out) {
            $i++;
            $arrdata[$num]=str_replace('"',"",$out);
            $this->arraysqltable[$this->global_pointer][substr($out,3,-1)];

            if(substr($out,0,3)=='$F{') {
                
                if($isPrintRepeatedValue=="true" ||$isPrintRepeatedValue=="") {
                    $arrdata[$num]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];
                    
                }
                else {

                    if($this->previousarraydata[$arrdata[$num]]==$this->arraysqltable[$this->global_pointer][substr($out,3,-1)]) {

                        $arrdata[$num]="";
                    }
                    else {
                        $arrdata[$num]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];
                        $this->previousarraydata[$out]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];
                    }
                }
              //  echo $arrdata[$num]."==";
            }
            elseif(substr($out,0,3)=='$V{') {
//###	A new function to handle iReport's "+-/*" expressions.
// It works like a cheap calculator, without precedences, so 1+2*3 will be 9, NOT 7.
			
				$p1=3;
				$p2=strpos($out,"}");
				if ($p2!==false){ 
					$total=&$this->arrayVariable[substr($out,$p1,$p2-$p1)]["ans"];
					$p1=$p2+1;
					while ($p1<strlen($out)){
						if (strpos("+-/*",substr($out,$p1,1))!==false) $opr=substr($out,$p1,1);
						else $opr="";
						$p1=strpos($out,'$V{',$p1)+3;
						$p2=strpos($out,"}",$p1);
						if ($p2!==false){
                                                        
                                                        $nbr=&$this->arrayVariable[substr($out,$p1,$p2-$p1)]["ans"];
							switch ($opr){
								case "+": $total+=$nbr;
										  break;
								case "-": $total-=$nbr;
										  break;
								case "*": $total*=$nbr;
										  break;
								case "/": $total/=$nbr;
										  break;
							}
						}
						$p1=$p2+1;
					}
				}
				$arrdata[$num]=$total;
//### End of modifications, below is the original line.				
//                $arrdata[$num]=&$this->arrayVariable[substr($out,3,-1)]["ans"];
            }
            elseif(substr($out,0,3)=='$P{') {
                $arrdata[$num]=$this->arrayParameter[substr($out,3,-1)];
            }
          //  echo "<br/>";
        }

        if($this->left($data,3)=='"("' && $this->right($data,3)=='")"') {
            $total=0;

            foreach($arrdata as $num=>$out) {
                if($num>0 && $num<$i)
                    $total+=$out;

            }
            return $total;

        }
        else {

            return implode($arrdata);
        }
    }

    public function formatText($txt,$pattern) {
        if($pattern=="###0")
            return number_format($txt,0,"","");
        elseif($pattern=="#,##0")
            return number_format($txt,0,".",",");
        elseif($pattern=="###0.0")
            return number_format($txt,1,".","");
        elseif($pattern=="#,##0.0")
            return number_format($txt,1,".",",");
        elseif($pattern=="###0.00")
            return number_format($txt,2,".","");
        elseif($pattern=="#,##0.00")
            return number_format($txt,2,".",",");
        elseif($pattern=="###0.000")
            return number_format($txt,3,".","");
        elseif($pattern=="#,##0.000")
            return number_format($txt,3,".",",");
        elseif($pattern=="#,##0.0000")
            return number_format($txt,4,".",",");
        elseif($pattern=="###0.0000")
            return number_format($txt,4,".","");
        elseif($pattern=="dd/MM/yyyy" && $txt !="")
            return date("d/m/Y",strtotime($txt));
        elseif($pattern=="MM/dd/yyyy" && $txt !="")
            return date("m/d/Y",strtotime($txt));
        elseif($pattern=="yyyy/MM/dd" && $txt !="")
            return date("Y/m/d",strtotime($txt));
        elseif($pattern=="dd-MMM-yy" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="dd-MMM-yy" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="dd/MM/yyyy h.mm a" && $txt !="")
            return date("d/m/Y h:i a",strtotime($txt));
        elseif($pattern=="dd/MM/yyyy HH.mm.ss" && $txt !="")
            return date("d-m-Y H:i:s",strtotime($txt));
        else
            return $txt;


    }

    public function print_expression($data) {
        $expression=$data["printWhenExpression"];
        $expression=str_replace('$F{','$this->arraysqltable[$this->global_pointer][',$expression);
        $expression=str_replace('$P{','$this->arraysqltable[$this->global_pointer][',$expression);
        $expression=str_replace('$V{','$this->arraysqltable[$this->global_pointer][',$expression);
        $expression=str_replace('}',']',$expression);
        $this->print_expression_result=false;
        if($expression!="") {
            eval('if('.$expression.'){$this->print_expression_result=true;}');
        }
        elseif($expression=="") {
            $this->print_expression_result=true;
        }

    }

    public function runSubReport($d,$current_y) {
            $this->insubReport=1;
        foreach($d["subreportparameterarray"] as $name=>$b) {
            $t = $b->subreportParameterExpression;
            $arrdata=explode("+",$t);
            $i=0;
            foreach($arrdata as $num=>$out) {
                $i++;
//                $arrdata[$num]=str_replace('"',"",$out);
                if(substr($b,0,3)=='$F{') {
                    $arrdata2[$name.'']=$this->arraysqltable[$this->global_pointer][substr($b,3,-1)];
                }
                elseif(substr($b,0,3)=='$V{') {
                    $arrdata2[$name.'']=&$this->arrayVariable[substr($b,3,-1)]["ans"];
                }
                elseif(substr($b,0,3)=='$P{') {
                    $arrdata2[$name.'']=$this->arrayParameter[substr($b,3,-1)];
                }
            }
            $t=implode($arrdata);
        }
           
             $a= $this->includeSubReport($d,$arrdata2,$current_y);
            $this->insubReport=0;
            return $a;
    }
    
    public function transferXMLtoArray($fileName) {
        if(!file_exists($fileName))
            echo "File - $fileName does not exist";
        else {

            $xmlAry = $this->xmlobj2arr(simplexml_load_file($fileName));
			
            foreach($xmlAry[header] as $key => $value)
                $this->arraysqltable["$this->m"]["$key"]=$value;

            foreach($xmlAry[detail][record]["$this->m"] as $key2 => $value2)
                $this->arraysqltable["$this->m"]["$key2"]=$value2;
        }

      //  if(isset($this->arrayVariable))	//if self define variable existing, go to do the calculation
       //     $this->variable_calculation();

    }

    public function includeSubReport($d,$arrdata,$current_y){ 
               include_once ("PHPJasperXMLSubReport.inc.php");
               $srxml=  simplexml_load_file($d['subreportExpression']);
               $PHPJasperXMLSubReport= new PHPJasperXMLSubReport($this->lang,$this->pdflib,$d['x']);
               $PHPJasperXMLSubReport->arrayParameter=$arrdata;
               $PHPJasperXMLSubReport->debugsql=$this->debugsql;
               $PHPJasperXMLSubReport->xml_dismantle($srxml);
               
               
              $this->passAllArrayDatatoSubReport($PHPJasperXMLSubReport,$d,$current_y,$arrdata);
               
               $PHPJasperXMLSubReport->transferDBtoArray($this->db_host,$this->db_user,$this->db_pass,$this->db_or_dsn_name);
               $PHPJasperXMLSubReport->pdf=$this->pdf;
               $PHPJasperXMLSubReport->outpage();    //page output method I:standard output  D:Download file
  
               $this->SubReportCheckPoint=$PHPJasperXMLSubReport->SubReportCheckPoint;
               //echo $this->SubReportCheckPoint."<br/>";
               $PHPJasperXMLSubReport->MainPageCurrentY=0;
               return $PHPJasperXMLSubReport->maxy;
    }

    public function passAllArrayDatatoSubReport($PHPJasperXMLSubReport,$d,$current_y,$data){
        
                $PHPJasperXMLSubReport->arrayMainPageSetting=$this->arrayPageSetting;
                if(isset($this->arraypageHeader)) {
                    $PHPJasperXMLSubReport->arrayPageSetting["subreportpageHeight"]=$PHPJasperXMLSubReport->arrayPageSetting["pageHeight"];
                    $PHPJasperXMLSubReport->arrayMainpageHeader=$this->arraypageHeader;
                    $PHPJasperXMLSubReport->arrayMainpageFooter=$this->arraypageFooter;

                    if($this->currentband=='pageHeader'){ ///here need to add more conditions to fulfill different band subreport
                        $PHPJasperXMLSubReport->TopHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]+$d['y'];
                    }
                    else{      
                        $PHPJasperXMLSubReport->TopHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]
                                                                                                +$PHPJasperXMLSubReport->arrayMainpageHeader[0]["height"]+$d['y'];
                    }
###set different initial Y for subreport of each detail loop of main report
                if($current_y>$PHPJasperXMLSubReport->TopHeightFromMainPage)
                    {$PHPJasperXMLSubReport->TopHeightFromMainPage=$current_y+$d['y'];}
###
                $PHPJasperXMLSubReport->BottomHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["bottomMargin"]
                                                                                                +$PHPJasperXMLSubReport->arrayMainpageFooter[0]["height"];
                $PHPJasperXMLSubReport->arrayPageSetting["leftMargin"]=$PHPJasperXMLSubReport->arrayPageSetting["leftMargin"]+$this->arrayPageSetting["leftMargin"];
###Set fixed pageHeight constant despite the changes of $PHPJasperXMLSubReport->TopHeightFromMainPage due to subreport in Detail band
                $PHPJasperXMLSubReport->arrayPageSetting["pageHeight"]=$this->arrayPageSetting["pageHeight"]
                              -($PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]
                                +$PHPJasperXMLSubReport->arrayMainpageHeader[0]["height"]+$d['y'])
                                  -$this->arraypageFooter[0]["height"]
                                 -$PHPJasperXMLSubReport->arrayMainPageSetting["bottomMargin"]-$d['y'];
                }
                if(isset($this->arraypageFooter)) {
                    $PHPJasperXMLSubReport->arrayMainpageFooter=$this->arraypageFooter;
                }
                if(isset($this->arraygroup)) {
                    $PHPJasperXMLSubReport->arrayMaingroup=$this->arraygroup;
                }
                if(isset($this->arraylastPageFooter)) {
                    $PHPJasperXMLSubReport->arrayMainlastPageFooter=$this->arraylastPageFooter;
                }
                if(isset($this->arraytitle)) {
                    $PHPJasperXMLSubReport->arrayMaintitle=$this->arraytitle;
                }
               $PHPJasperXMLSubReport->parentcurrentband=$this->currentband;
                switch($this->currentband){
                    case "detail":
                         $PHPJasperXMLSubReport->allowprintuntill=$this->detailallowtill;
                        break;
                    default:
                        $PHPJasperXMLSubReport->allowprintuntill=$current_y+$d['height'];
                   //         echo print_r($d,true)."<br/>";
//
                        break;
                    
                }

    }
//wrote by huzursuz at mailinator dot com on 02-Feb-2009 04:44
//http://hk.php.net/manual/en/function.get-object-vars.php
    public function xmlobj2arr($Data) {
        if (is_object($Data)) {
            foreach (get_object_vars($Data) as $key => $val)
                $ret[$key] = $this->xmlobj2arr($val);
            return $ret;
        }
        elseif (is_array($Data)) {
            foreach ($Data as $key => $val)
                $ret[$key] = $this->xmlobj2arr($val);
            return $ret;
        }
        else
            return $Data;
    }


private function Rotate($type, $x=-1, $y=-1)
{
    if($type=="")
    $angle=0;
    elseif($type=="Left")
    $angle=90;
    elseif($type=="Right")
    $angle=270;
    elseif($type=="UpsideDown")
    $angle=180;

    if($x==-1)
        $x=$this->pdf->getX();
    if($y==-1)
        $y=$this->pdf->getY();
    if($this->angle!=0)
        $this->pdf->_out('Q');
    $this->angle=$angle;
    if($angle!=0)
    {
        $angle*=M_PI/180;
        $c=cos($angle);
        $s=sin($angle);
        $cx=$x*$this->pdf->k;
        $cy=($this->pdf->h-$y)*$this->pdf->k;
        $this->pdf->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
    }
}

}
