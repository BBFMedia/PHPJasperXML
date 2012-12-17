<?php
require_once(dirname(__FILE__).'/JasperExp.php');
require_once(dirname(__FILE__) . '/../j4p5/js.php');

class jasperJavascript extends JasperExp {

    static $compiled = array();

    function _setVar($name, $codename, $value) {

        js::define($codename, array(), array('value' => $value));
    }

    function run($code) {
        $code = trim($code);
        if (empty($code))
            return $code;
        $code = $this->replaceVars($code);
        $id = md5($code);
        $code = 'function func_' . $id . ' ()  {  return (' . $code . '); }';

    // -- compile script
        if (!self::$compiled[$id]) {
            js::run($code, JS_DIRECT, $id);
            self::$compiled[$id] = jsrt::$jsfunctions['func_' . $id];
         }

        $func = self::$compiled[$id];
    
            //-- Run the js code.

        $result = php_str(call_user_func($func));
        // $result = php_str(jsrt::idv('result'));

        return $result;
    }

}

