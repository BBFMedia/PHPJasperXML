<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class bounds {
    
     public $top = 0;
     public $left = 0;
     public $right = null;
     public $bottom = null;
     public $nextPage = null;
     public function __construct($left,$top,$bottom=null,$right=null) {
         
      $this->top = $top;
      $this->left = $left;
      $this->right = $right;
      $this->bottom = $bottom;
        
     }
    
    
}


/**
 * Description of JasperElements
 *
 * @author adrian
 */
class Jasper_reportElement extends JasperObject {
   
   
    public $_type;
        protected $_height = 0;
  
    protected $_x = 0;
    protected $_y = 0;
    protected $_width = 0;
        protected $_fill = 0;
         protected $_textcolor = array("r" => 0, "g" => 0, "b" => 0);
        protected $_fillcolor = array("r" => 255, "g" => 255, "b" => 255);
        protected $_stretchoverflow = "true";
        protected $_printoverflow = "false";  
      protected $_printWhenExpression = '';
     function parse($data)
    {
      $this->loadValues($data->reportElement);      
        /** allow forground color "forecolor" */
      if ($data->reportElement->printWhenExpression)
        $this->printWhenExpression = (string)$data->reportElement->printWhenExpression;
       //reportElement
    
     

        
    }

    public $layout_top;
    public $layout_left;
    public $layout_bottom;
    public $layout_right;
    
    
    function layout($output)
    {
 
    $this->layout_top = $bounds->top + $this->y;
    $this->layout_left = $bounds->left + $this->x;
    $this->layout_right = $this->layout_left + $this->width;
    $this->layout_bottom =   $this->layout_top + $this->height;
    }
     function render($output)
     {
         $output->roster[] = array('cmd'=>'position', 'top'=> $this->layout_top,'left'=>$this->layout_left );
         
     }
}

class Jasper_pen extends JasperObject{
    protected $_lineColor = '';
    protected $_lineStyle = '';
    protected $_lineWidth = -1;
}
class Jasper_box extends Jasper_reportElement
{
      //box
protected $_pen;
protected $_topPen;
protected $_leftPen;
protected $_bottomPen;
protected $_rightPen;

protected $_padding = 0;
protected $_topPadding = 0;
protected $_leftPadding = 0;
protected $_rightPadding = 0;
protected $_bottomPadding = 0;

    function layout($output)
    { 
      parent::layout($output);
    } 
   function parse($data)
    {
     parent::parse($data);
     $this->loadValues($data);
     
     $data = $data->box;
         if ($data->getName() == 'box')
         {
// box dom   there is a lot more needed for this dom
        foreach (array('pen', 'topPen', 'leftPen', 'bottomPen', 'rightPen') as $pen) {
           $p = $data->$pen;
           $penname = $p->getName();
            if (!empty($penname)) {
                $penname = '_' . $penname;
                $this->$penname = new Jasper_pen($this);
                $this->$penname->loadValues($p);
            }
        }
    }}
          
}

abstract class Jasper_textElement extends Jasper_box
{

  //textElement       
        protected $_verticalAlignment ;
        protected $_textAlignment; 


   abstract function getText();
   function getTextSize($text)
   {
     
   }
   function parse($data)
    {
     parent::parse($data)       ;

        /// textElement dom 
     $this->loadValues($data->textElement);

    }   
    
   function layout($output,$bounds)
    {
       
        $this->_height =  $output->getTextHeight($this->text);
      parent::layout($output);
      
      // if height is stretch
      
   
    }
    
    
   function render($output)
    {
     parent::render($output);
     $output->roster[] = array('cmd'=>'textout','text'=>$this->text,'width'=>$this->_width);
    }
    
    
}
