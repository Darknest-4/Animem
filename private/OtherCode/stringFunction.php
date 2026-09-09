<?php
/*
function fing($p, $exp)
{
	foreach ($exp as $k => $v)
	{
		if (empty($v))
			if ($p = ($p->find($k)) ? $p->find($k) : $p) $p = $p;
		else
			if ($p = ($p->find($k, $v)) ? $p->find($k, $v) : $p) $p = $p;
	}
	return $p;
}
*/
function textInText($p, $p2) // $p == array || String; $p2 == String
{
	$p2 = strtolower($p2);
	if (is_array($p))
	{
		foreach ($p as $v)
			if (strlen(str_replace(strtolower($v), "", $p2)) < strlen($p2)) return TRUE;
	}
	elseif (is_string($p))	return (strlen(str_replace(strtolower($p), "", $p2)) < strlen($p2)) ? TRUE : FALSE;
}
function dateChecked($p = "") // yyyy-mm-dd
{
	if (!empty($p))
	{
		$date = explode("-", $p);
		if (count($date) == 3)
			return checkdate($date[2], $date[1], $date[0]);
		else return FALSE;
	}
	else return FALSE;
}


function TI($a, $b, $c)
{
	return TypeInspection($a, $b, $c);
}
function TypeInspection(string $varname, $var, string $inspection)
{
	$return = GetTypeInspection(str_split(gettype($var))[0], str_split($inspection));
	if (is_string($return))
		trigger_error("The variable '" . $varname . "' can only be " . $return . "!\n", E_USER_ERROR);
	else return TRUE;
}
function GetTypeInspection($var, array $inspection)
{
	$Inspect = [
		"integer"		=> ["i", "a integer"],
		"double"		=> ["d", "a double"],
		"string"		=> ["s", "a string"],
		"array"			=> ["a", "an array"],
		"NULL"			=> ["n", "a NULL"],
		"boolean"		=> ["b", "a boolean"]
	];
	$ret = "";
	foreach ($Inspect as $ins_A)
		foreach ($inspection as $ins_B)
			if ($ins_A[0] == $ins_B)
				if (empty($ret))
					$ret = $ins_A[1];
				else
					$ret .= " or " . $ins_A[1];

	foreach ($Inspect as $ins_A)
		foreach ($inspection as $ins_B)
			if ($ins_A[0] == $ins_B && $var == $ins_B)
				return true;

	return $ret;
}
function removeWhiteSpace($text)
{
	$text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
	$text = preg_replace('/([\s])\1+/', ' ', $text);
	$text = trim($text);
	return $text;
}

function clean($string)
{
	$string = str_replace(['_', ' ', 'ö', 'ő', 'ó', 'á', 'é', 'í', 'ü', 'ú', 'ű'], ['-', '-', 'o', 'o', 'o', 'a', 'e', 'i', 'u', 'u', 'u'], $string); // Replaces spaces with hyphens.
	return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}
