<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperPdfType
 *
 * @author adrian
 */
class JasperPdfType extends JasperOutputType{
    
    /**
     *
     * @var TCPDF 
     */
    private $_pdf;
    
  
    
    /*
     * Passes on any calls to this class onto the tcpdf instance
     * @param $chrMethod, $arrArguments
     * @return $mix
     */
    public $orientation = 'P';
    public $pageLayout = 'A4';
    private function _getPdf()
    {
        if (empty($this->_pdf))
        {
            $this->_pdf = new TCPDF($this->orientation,'pt',$this->pageLayout);
            $this->applyFonts();
        }
       return  $this->_pdf;
}

private function applyFonts()
{
    foreach($this->_fonts as $key => &$fonts)
    {
     if ($font['truetype']) 
     {
       $fonts['outputName'] = $this->_pdf->addTTFfont($fonts['fontfile'], '',  '', $fonts['flags']?$fonts['flags']:32, '', 3 , 1) ;
       }
       else
       {
        
         $this->_pdf->SetFont($fonts['outputName'], $fonts['style']?$fonts['style']:'', $fonts['size']?$fonts['size']:null, $fonts['fontfile']?$fonts['fontfile']:'', $fonts['subset']?$fonts['subset']:'default', $fonts['out']?$fonts['out']:true);        
  
     } 
    }
}

 public  function __call( $chrMethod, $arrArguments ) {



        $objInstance = $this->_getPdf();

        return call_user_func_array(array($objInstance, $chrMethod), $arrArguments);

    }    
public function textLineCount($txt,$w)
{
            $cw=&$this->_pdf->CurrentFont['cw'];
        if($w==0)
            $w=$this->_pdf->w-$this->_pdf->rMargin-$this->_pdf->x;
        $wmax=($w-2*$this->_pdf->cMargin)*1000/$this->_pdf->FontSize;
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

public function balancetext()
{
    return $this->_pdf->balancetext;
}
public function clearBalancetext()
{
  $this->_pdf->balancetext = '';  
}

}
