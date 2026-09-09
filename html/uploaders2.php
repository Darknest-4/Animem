<?php

chdir("/var/www/html/animem.org/html");
echo shell_exec("clear");
define("DS", DIRECTORY_SEPARATOR);
require_once("OtherCode/urlFunction.php");
require_once("OtherCode/stringFunction.php");
require_once("OtherCode/fileFunction.php");
require_once("OtherCode/ConnectClass.php");
require_once("OtherCode/databaseFunction.php");
require_once("OtherCode/shd.php");


//$password = hash('sha256', strip_tags(htmlspecialchars($_POST['password'])));