<?php

if(!function_exists('input_encode'))
{
    function input_encode($p = '')
    {
        return (!empty($p)) ? htmlspecialchars($p) : "";
    }
}
if(!function_exists('removeWhiteSpace'))
{
    function removeWhiteSpace($text)
    {
        $text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
        $text = preg_replace('/([\s])\1+/', ' ', $text);
        $text = trim($text);
        return $text;
    }
}
if(!function_exists('encodeLongString'))
{
    function encodeLongString($input)
    {
        return rtrim(strtr(base64_encode(gzdeflate($input, 9)), '+/', '-_'), '=');
    }
}
if(!function_exists('decodeLongString'))
{
    function decodeLongString($input)
    {
        return gzinflate(base64_decode(strtr($input, '-_', '+/')));
    }
}

?>