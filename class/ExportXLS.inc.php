<?php

class ExportXLS{
    public $wb;
    public $ws;
    public $arrayband;
    public $arraypageHeader;
    public $arraypageFooter;
    public $arraydetail;
    public $arraybackground;
    public $arraytitle;
    public $arraysummary;
    public $arraygroup;
    public $relativex=0;
    public $relativey=0;
    public $lastrow=0;
    public $pageHeight;
    public $pageWidth;
    public $cols=array();
    public $rows=array();
    public $vunitmultiply=1;
    public $hunitmultiply=0.15;
    public $headerbandheight;
    public $arraysqltable;
    public $global_pointer;
    public $detailrowcount;
    public $headerrowcount;
    public $arrayVariable;
    public $arrayParameter;
    public $arraygroupfoot;
    public $arraygrouphead;
    public function ExportXLS($raw,$filename, $type='Excel5'){
        
        	include dirname(__FILE__)."/PHPExcel.php";
                $this->wb  = new PHPExcel();
                $this->ws=$this->wb->getActiveSheet(0);
              //$this->ws->getStyleByColumnAndRow($pColumn, $pRow)->getFill()->getStartColor()
                
                $this->arrayband=$raw->arrayband;
                $this->arraypageHeader=$raw->arraypageHeader;
                $this->arraypageFooter=$raw->arraypageFooter;
                $this->arraydetail=$raw->arraydetail;
                $this->arraybackground=$raw->arraybackground;
                $this->arraytitle=$raw->arraytitle;
                $this->arraysummary=$raw->arraysummary;
                $this->arraygroup=$raw->arraygroup;
                $this->arraylastPageFooter=$raw->arraylastPageFooter;
                //$this->arraypageFooter=$raw->arraypageFooter;
                
                $this->headerbandheight=$raw->headerbandheight;
                $this->arraysqltable=$raw->arraysqltable; 
                $this->pageWidth=$raw->arrayPageSetting['pageWidth']; 
                $this->pageHeight=$raw->pageHeight; 
                $this->arrayVariable=$raw->arrayVariable;
                $this->arrayParameter=$raw->arrayParameter;
                
                $this->arraygroupfoot=$raw->arraygroupfoot;
                $this->arraygrouphead=$raw->arraygrouphead;

                $this->summaryexit=false;
               /*
                
                
                
                $this->xls =& $this->workbook->addWorksheet('Sheet1');
                $this->xls->setMarginLeft($raw->arrayPageSetting["leftMargin"]);
                $this->xls->setMarginRight($raw->arrayPageSetting["rightMargin"]);
                $this->xls->setMarginTop($raw->arrayPageSetting["topMargin"]);
                $this->xls->setMarginBottom($raw->arrayPageSetting["bottomMargin"]);
                */
    
        $this->global_pointer=0;
          $this->arrangeColumn();
        foreach ($raw->arrayband as $band) {
//            $this->currentband=$band["name"]; // to know current where current band in!
            switch($band["name"]) {
                case "title":
                                      
                  if($raw->arraytitle[0]["height"]>0){
                            $this->title();
                            //          echo "end title<br/><br/>";
                  }
                    break;
                case "pageHeader":
                    
                    
                  if($raw->arraypageHeader[0]["height"]>0){
                        $this->pageHeader();
                                     //   echo "end header<hr>";
                  }

                    break;
                case "detail":
                     if($raw->arraydetail[0]["height"]>0){
                        $this->detail();
                                    //    echo "end detail<br/><br/>";
                     }
                    break;
                case "pageFooter":
                     if($raw->arraylastPageFooter[0]["height"]==0 && $raw->arraypageFooter[0]["height"]>0){
                        $this->pageFooter();
                                    //    echo "end detail<br/><br/>";
                     }
                    break;
                case "lastPageFooter":
                     if($raw->arraysummary[0]["height"]>0){
                        $this->summary();
                     }
                     if($raw->arraylastPageFooter[0]["height"]>0){
                        $this->lastPageFooter();
                                    //    echo "end detail<br/><br/>";
                     }
                    break;
                case "summary":
//                     if($raw->arraysummary[0]["height"]>0){
//                        $this->summary();
//                                    //    echo "end detail<br/><br/>";
//                     }
                    break;
                case "group":
                        $this->group_pointer=$band["groupExpression"];
                         $this->group_name=$band["gname"];
                    break;

                default:
                break;

            }

        }
                           //  die;

        // Redirect output to a client’s web browser (Excel2007)
        if($filename=='')
            $filename="report.xls";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition:attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($this->wb, $type);
        $objWriter->save('php://output');


    }

    
    public function arrangeColumn(){
        
        $cols=array();
        $cx=0;
        foreach($this->arraypageHeader as $out){
          //  print_r($out);echo "<hr>";
            if($out['type']=="SetXY"){
                $cols[]=intval($out['x']);
            $cx=intval($out['x']);
            
            }
            if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $cols[]=intval($out['width'] + $cx);
               // echo $out['width']." + $cx <hr>";
            }
            
            //print_r($cols);echo "<hr>";
        }
            $i=0;
        foreach($this->arraydetail as $out){
            if($out['type']=="SetXY"){
                $cols[]=intval($out['x']);
                $cx=intval($out['x']);

            }
            
           if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $cols[]=intval($out['width'] + $cx);
                //echo $out['width']." + $cx <hr>";
            }
            // echo $i.".".$out['type']."=",$out['x'].$out['txt'].":";     print_r($cols);echo "<hr>";
             $i++;
        }
       
        
        foreach($this->arraygrouphead as $out){
          //  print_r($out);echo "<hr>";
            if($out['type']=="SetXY"){
                $cols[]=intval($out['x']);
            $cx=intval($out['x']);
            
            }
            if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $cols[]=intval($out['width'] + $cx);
               // echo $out['width']." + $cx <hr>";
            }
            
            //print_r($cols);echo "<hr>";
        }
        
        foreach($this->arraygroupfooter as $out){
          //  print_r($out);echo "<hr>";
            if($out['type']=="SetXY"){
                $cols[]=intval($out['x']);
            $cx=intval($out['x']);
            
            }
            if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $cols[]=intval($out['width'] + $cx);
               // echo $out['width']." + $cx <hr>";
            }
            
            //print_r($cols);echo "<hr>";
        }
    
            foreach($this->arraypageFooter as $out){
          //  print_r($out);echo "<hr>";
            if($out['type']=="SetXY"){
                $cols[]=intval($out['x']);
            $cx=intval($out['x']);
            
            }
            if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $cols[]=intval($out['width'] + $cx);
               // echo $out['width']." + $cx <hr>";
            }
            
            //print_r($cols);echo "<hr>";
        }
    
            foreach($this->arraylastPageFooter as $out){
          //  print_r($out);echo "<hr>";
            if($out['type']=="SetXY"){
                $cols[]=intval($out['x']);
            $cx=intval($out['x']);
            
            }
            if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $cols[]=intval($out['width'] + $cx);
               // echo $out['width']." + $cx <hr>";
            }
            
            //print_r($cols);echo "<hr>";
        }

        foreach($this->arraysummary as $out){
          //  print_r($out);echo "<hr>";
            if($out['type']=="SetXY"){
                $cols[]=intval($out['x']);
            $cx=intval($out['x']);

            }
            if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $cols[]=intval($out['width'] + $cx);
               // echo $out['width']." + $cx <hr>";
            }

            //print_r($cols);echo "<hr>";
        }
        
//                print_r($cols);echo "<hr>";
        $cols=array_unique($cols);
             sort($cols);



             $i=0;
             
             foreach($cols as $index => $xposition){
                $nextxposition=$cols[($i+1)];
                 if($nextxposition=="")
                    $nextxposition=$this->pageWidth;
              //  echo " $index ($nextxposition-$xposition)";echo "<hr>";
                 $this->ws->getColumnDimensionByColumn($index)->setWidth($this->hunitmultiply*($nextxposition-$xposition));
                 $this->cols=array_merge($this->cols, array("c".$xposition=>$i));
                 $i++;
             }
        
    }
    
    public function arrangeRows($myband,$debug){
        $this->rows=array();
        $rows=array();
        $ch=0;
        foreach($myband as $out){
          // print_r($out);echo "<hr>";
            if($out['type']=="SetXY"){
                $rows[]=intval($out['y']);
                $ch=intval($out['y']);
            }
              if($out['type']=="Cell" ||$out['type']=="MultiCell"){
                $rows[]=intval($out['height'] + $ch);
              }
        }
    
                  $rows[]=intval($myband[0]['height']);
                    $rows=array_unique($rows);

                sort($rows);
//                   print_r($rows);echo "<hr>";

             $i=1;
             foreach($rows as $index => $yposition){
                $nextyposition=$rows[($i+1)];
                
                //if($nextyposition=="")
                  //  $nextyposition=30;
//                 $this->ws->getRowDimension($index+ $this->lastrow)->setRowHeight($this->vunitmultiply*($nextyposition-$yposition)); tmp close this for standard row height
                 $this->rows=array_merge($this->rows, array("r".$yposition=>$i));
                 $i++;
             }
//             echo "step2:";print_r($this->rows);
             $this->lastrow=$i+$this->lastrow;
             return ($i-2);
            
        
    }
    
    
    
    public function title(){
       $this->titlerowcount=$this->arrangeRows($this->arraytitle,true);
$i=0;
foreach($this->arraytitle as $out){
   
            $this->display($out,$this->maxrow,false);
     $i++;
            }
                $this->maxrow+=$this->titlerowcount;

        
    }
    
    public function pageHeader(){
              

       $this->headerrowcount= $this->arrangeRows($this->arraypageHeader);
       $this->maxrow=$this->headerrowcount;
        foreach($this->arraypageHeader as $out){
            $this->display($out,0);
            
        }
        
        //   $this->lastrow--;
      // echo "header:".$this->lastrow;echo "<hr>";
    }
    
    public function detail(){
        $this->detailrowcount=$this->arrangeRows($this->arraydetail);
        $this->groupheadrowcount=$this->arrangeRows($this->arraygrouphead);
        $this->groupfootrowcount=$this->arrangeRows($this->arraygroupfoot);

        $i=0;
        $this->showGroupHeader();
        $this->maxrow+=$this->groupheadrowcount;
        $isgroupfooterprinted=false;
        foreach($this->arraysqltable as $row){

            $this->variable_calculation($i, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
            
            if($this->global_pointer>0&&
                        ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])){	//check the group's groupExpression existed and same or not
                   
		if($isgroupfooterprinted==true)
                    $gfoot=0;
                
                /*
                 * if($i>0){
                 
                $this->showGroupFooter();
                $this->maxrow+=$this->groupfootrowcount+2;
                }
                 * 
                 */
                $this->showGroupHeader();
                $this->maxrow+=$this->groupheadrowcount;
                $isgroupfooterprinted=false;
                $this->footershowed=false;
         	$this->group_count["$this->group_name"]=1;	// We're on the first row of the group.				 
		
            }//finish check new group
            
        $this->currentband='detail';

        foreach($this->arraydetail as $out){
          
          //($this->headerrowcount+($this->detailrowcount*$i)
            $this->display($out,$this->maxrow);
                
        }
        
        
                $this->maxrow+=$this->detailrowcount;

                    if($this->global_pointer>0&&
                        ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer+1][$this->group_pointer])){	//check the group's groupExpression existed and same or not
                   
		if($isgroupfooterprinted==true)
                    $gfoot=0;
                
               if($i>0){
                 
                $this->showGroupFooter();
                $this->maxrow+=$this->groupfootrowcount+1;
                }
                $isgroupfooterprinted=true;
                $this->footershowed=true;
         	$this->group_count["$this->group_name"]=1;	// We're on the first row of the group.				 
		
            }//finish check new group

            
        foreach($this->group_count as &$cntval) {
					$cntval++;
				}
	$this->report_count++;
        $this->global_pointer++;
           $i++;
       }
          
       //$this->showGroupFooter();
      //         $this->maxrow+=$this->groupfootrowcount+2;

    }
    
     public function showGroupHeader() {
        $this->currentband='groupHeader';
        $bandheight=$this->arraygrouphead[0]['height'];
       //         $this->grouheadrowcount=$this->arrangeRows($this->arraygrouphead);
        $this->groufootrowcount=$this->arrangeRows($this->arraygroupfoot);

          //$this->arrangeRows($this->arraygroupfoot);
        foreach ($this->arraygrouphead as $out) {
            
            $this->display($out,$this->maxrow);
        }

    }
    public function showGroupFooter() {
        
        $this->currentband='groupFooter';
         $bandheight=$this->arraygroupfoot[0]['height'];
        foreach ($this->arraygroupfoot as $out) {
            $this->display($out,$this->maxrow);
        }
        $this->currentband='';

    }

    
    
    
    public function pageFooter(){
        $this->footerrowcount=$this->arrangeRows($this->arraypageFooter);
        foreach($this->arraypageFooter as $out){
            $this->display($out,$this->maxrow,false);
        }
        $this->maxrow+=$this->footerrowcount;
    }
    
    public function lastPageFooter(){
//print_r($this->arraylastPageFooter);echo "<hr>lastpage footer";


       $this->lastfooterrowcount=$this->arrangeRows($this->arraylastPageFooter,true);
$i=0;
foreach($this->arraylastPageFooter as $out){
   
            $this->display($out,$this->maxrow,false);
     $i++;
            }
                $this->maxrow+=$this->lastfooterrowcount;

    }
    public function summary(){

        $this->summaryrowcount=$this->arrangeRows($this->arraysummary);
        foreach($this->arraysummary as $out){
            $this->display($out,$this->maxrow);
        }
       $this->maxrow+=$this->summaryrowcount;
       $this->summaryexit=true;
    }
    
    public function display($arraydata,$rowpos,$debug){
    
    if($debug==true){    
    print_r($arraydata);echo "<hr>";
    }
        
        switch($arraydata['type']){
            case "MultiCell":
  //              echo 'start1';
                if($this->relativey=="")
                    $this->relativey=0;
//echo "$this->relativex,
  //                   ($this->relativey+$rowpos),".
                //$this->analyse_expression($arraydata['txt'])."<hr>";
            //    echo "$this->relativex,  
                //        ($this->relativey+$rowpos), ".
              //          $this->cols['c'.($this->mergex+$arraydata['width'])].",".  
                  //      "($this->relativey+$rowpos-1)".$this->analyse_expression($arraydata['txt'])."<hr/>";
    //                if($debug==true)
      //                  echo 'start2'.$this->relativex .",".  
        //                "($this->relativey+$rowpos), ".
          //              "(".$this->cols['c'.($this->mergex+$arraydata['width'])]."-1),".  
            //         "   ($this->relativey+$rowpos)"
                        ;
                $this->ws->mergeCellsByColumnAndRow(
                        $this->relativex,  
                        ($this->relativey+$rowpos), 
                        ($this->cols['c'.($this->mergex+$arraydata['width'])]-1),  
                        ($this->relativey+$rowpos)
                        );
              //  echo 'start3';
               $txt=$this->analyse_expression($arraydata['txt']);
               if($arraydata['pattern']!='')
                  $txt= ' '.$this->formatText ($txt, $arraydata['pattern']);
                //echo 'start4';
                $this->ws->setCellValueByColumnAndRow($this->relativex,
                       ($this->relativey+$rowpos),$txt);
                //echo 'start5';
                if($arraydata['align']=='C')
                    $this->ws->getStyleByColumnAndRow($this->relativex, ($this->relativey+$rowpos))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                elseif($arraydata['align']=='R')
                    $this->ws->getStyleByColumnAndRow($this->relativex, ($this->relativey+$rowpos))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                else
                    $this->ws->getStyleByColumnAndRow($this->relativex, ($this->relativey+$rowpos))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                //echo PHPExcel_Style_Alignment::HORIZONTAL_RIGHT ."<hr>";
                
                

                break;
            case "Cell":
                
//echo "$this->relativex,".
  //                     ($this->relativey+$this->rows['r'.$rowpos]).",".
    //                $this->analyse_expression($arraydata['txt'])."<hr>";
               $this->ws->setCellValueByColumnAndRow($this->relativex,
                       ($this->relativey+$rowpos),$this->analyse_expression($arraydata['txt']));

                break;
            case "SetXY":
                
                $this->relativex=$this->cols['c'.intval($arraydata['x'])];
                $this->relativey=$this->rows['r'.intval($arraydata['y'])];
                $this->mergex=$arraydata['x'];
                $this->mergey=$arraydata['y'];
              //  echo $this->relativey .'-'. $arraydata['y'];
                break;
            
         case "SetFont":
             $f=$this->ws->getStyleByColumnAndRow($this->relativex, ($this->relativey+$rowpos))->getFont();
             $f->setName($arraydata['font'].'');
             $f-> setSize(intVal($arraydata["fontsize"]));
                if(strpos($arraydata['fontstyle'],'B')>0)
                        $f->setBold(true);
                else
                            $f->setBold(false);

                if(strpos($arraydata['fontstyle'],'U')>0)
                        $f->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
                else
                        $f->setUnderline(PHPExcel_Style_Font::UNDERLINE_NONE);

                if(strpos($arraydata['fontstyle'],'I')>0)
                        $f->setItalic(true);
                else
                        $f->setItalic(false);
            break;
          case "SetTextColor":
              //echo PHPExcel_Style_Color::COLOR_RED;
           
            $cl= str_replace('#','',$arraydata['forecolor']);
           
              if($cl!=''){
                  

              
              $this->ws->getStyleByColumnAndRow($this->relativex, ($this->relativey+$rowpos))->getFont()->getColor()->setARGB("FF".$cl);
              }
              break;
          case "SetFillColor":
              $cl= str_replace('#','',$arraydata['backcolor']);
               if($cl!=''){
               $this->ws->getStyleByColumnAndRow($this->relativex, ($this->relativey+$rowpos))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $this->ws->getStyleByColumnAndRow($this->relativex, ($this->relativey+$rowpos))->getFill()->getStartColor()->setARGB('FF'.$cl);
               }
              break;
          case "Line":
              break;
          case "SetLineWidth":
              break;
          
          case "SetDrawColor":
              break;
          case "SetFillColor":
              
              break;
          
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
    
        public function analyse_expression($data) {
            
            if($data=='Page $this->PageNo() of' || trim($data)=='{nb}'){
                $data='';
            }
            $arrdata=explode("+",$data);

            
        $i=0;
        
        foreach($arrdata as $num=>$out) {
            $i++;
            $arrdata[$num]=str_replace('"',"",$out);
            $this->arraysqltable[$this->global_pointer][substr($out,3,-1)];

            if($out == 1){ //for report_count
               $arrdata[$num]=$this->report_count+1;
            }

            if($out == 'new java.util.Date()'){
               $arrdata[$num]=date("Y-m-d H:i:s");
            }

            if(substr($out,0,3)=='$F{') {
               $arrdata[$num]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];     
            }elseif(substr($out,0,3)=='$V{') {
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
						if ($p2!==false){ $nbr=&$this->arrayVariable[substr($out,$p1,$p2-$p1)]["ans"];
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

    
    function right($value, $count) {

        return substr($value, ($count*-1));

    }

    function left($string, $count) {
        return substr($string, 0, $count);
    }
    

      public function variable_calculation($rowno) {
//   $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
     //   print_r($this->arraysqltable);


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


    
}