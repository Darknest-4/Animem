<?php

if(!function_exists('print_p'))
{
    function print_p($p = '')
    {
        $type = gettype($p);
        echo '<pre>';
        if($type == "integer" || $type == "double" || $type == "string")
            echo($p);
        elseif($type == "array" || $type == "object")
            print_r($p);
        elseif($type == NULL || $type == "boolean")
            var_dump($p);
        echo '</pre>';
    }
}

if(!function_exists('vpp'))
{
    function vpp($p = '')
    {
        echo '<pre>';
        var_dump($p);
        echo '</pre>';
    }
}


?>