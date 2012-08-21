<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JasperMysql
 *
 * @author adrian
 */
class JasperMysql extends JasperDatabase {

   private $_db_host;
   private $_db_user;
   private $_db_pass;
   private $_db_port = null;
   private $_db_or_dsn_name;
   private $_cndriver;
   private $_con; 
   private $_myconn;
   public function setConnection($cont)
   {
       $this->_myconn = $cont;
   }
   public function connect($db_host,$db_user,$db_pass,$db_or_dsn_name,$cndriver="mysql") {
    $this->_db_host=$db_host;
            $this->_db_user=$db_user;
       $this->_db_pass=$db_pass;
    $this->_db_or_dsn_name=$db_or_dsn_name;
    $this->_cndriver=$cndriver;
        if($this->_cndriver=="mysql") {

            if(!$this->_con) {
                $this->_myconn = @mysql_connect($db_host,$db_user,$db_pass);
                if($this->_myconn) {
                    $seldb = @mysql_select_db($db_or_dsn_name,$this->_myconn);
                    if($seldb) {
                        $this->_con = true;
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
        }elseif($this->_cndriver=="psql") {
         //   global $pgport;
           if(empty(  $this->_db_port))
                $this->_db_port =5432;

            $conn_string = "host=$db_host port=$this->_db_port dbname=$db_or_dsn_name user=$db_user password=$db_pass";
            $this->_myconn = pg_connect($conn_string);


            if($this->_myconn) {
                $this->_con = true;

                return true;
            }else
                return false;
        }
        else {

            if(!$this->_con) {
                $this->_myconn = odbc_connect($db_or_dsn_name,$db_user,$db_pass);

                if( $this->_myconn) {
                    $this->_con = true;
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
    }

    public function query($sql)
    {
       return @mysql_query($sql); 
    }
    
    public function next($queryObject)
    {
      return  mysql_fetch_array($queryObject, MYSQL_ASSOC);  
    }
}

?>
