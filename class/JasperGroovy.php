<?php


/**
 * Description of JasperGroovy
 *
 * @author adrian
 */
               
class JasperGroovy extends JasperExp
{

 function _setVar($name,$codename,$value) {
           
		   
 
}


    function right($value, $count) {

        return substr($value, ($count * -1));
    }

    function left($string, $count) {
        return substr($string, 0, $count);
    }
    
public $arraysqltable;
public $previousarraydata ;
public $arrayVariable;
public $global_pointer;
public $arrayParameter;

function run($code)
{

//$code = $this->replaceVars($code); 

	$arrdata=explode("+",$code);

        $i=0;
        
        foreach($arrdata as $num=>$out) {
            $i++;
			$out = trim($out);
			if (substr($out,0,1) == '"')
			   $out = stripcslashes($out);
			  
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

        if($this->left($code,3)=='"("' && $this->right($code,3)=='")"') {
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
return false ;

 }
 
 }

 