<?php

session_start();
$_SESSION['session'] = session_id();

require_once(CURRENT_DIR . "private" . DS . "OtherCode" . DS . "constans.php");

  //exit($ip);
require_once(FOLDER_CODE . "allInMyFunctions.php");
require_once(FOLDER_CODE . "urlFunction.php");
require_once(FOLDER_CODE . "stringFunction.php");
require_once(FOLDER_CODE . "fileFunction.php");
require_once(FOLDER_CODE . "databaseFunction.php");
require_once(FOLDER_CODE . "shd.php");

if (!empty(getRU(0)) && strtolower(getRU(0)) == "fansub2"){
if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
elseif (!empty($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
else $ip = false;
if (20 < strlen($ip) || $ip == false) $ip = false;
if ($ip == false || $ip != "84.0.6.47")
  exit(require_once(CURRENT_DIR . "html/NewViews/503.phtml"));
}
require_once(FOLDER_CODE . "router.php");