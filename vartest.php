<?php
if (!function_exists("getallheaders")) {

    function getallheaders()
    {
        foreach ($_SERVER as $K => $V) {
            $a = explode('_', $K);
            if (array_shift($a) == 'HTTP') {
                array_walk($a, function(&$v) {
                    $v = ucfirst(strtolower($v));
                });
                $retval[join('-', $a)] = $V;
            }
        } return $retval;
    }

}

$varname = $_REQUEST["varname"];

//echo 'phpvar="Keeps also content and a time stamp ' . date("H:i:s") . ' and request <pre>' . print_r(getallheaders(), true) . '</pre>";';
//echo 'phpvar="Keeps also content and a time stamp ' . date("H:i:s") . ' and request <pre>' . print_r($_SERVER, true) . '</pre>";';
//echo 'phpvar="Keeps also content and a time stamp ' . date("H:i:s") . ' and request <pre>' . print_r($_REQUEST, true) . '</pre>";';
//echo 'phpvar="Keeps also content and a time stamp '.date("H:i:s").' and request <pre>'.  print_r($_SERVER,true).'</pre>";';
echo 'Requested: "'.$varname.'" Keeps also content and a time stamp ' . date("H:i:s") . ' and request <pre>' . print_r($_REQUEST, true) . '</pre>';
