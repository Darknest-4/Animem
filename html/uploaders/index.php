<?php

chdir("/var/www/html/animem.org/html/uploaders");
define('BASEPATH', $_SERVER['DOCUMENT_ROOT']);
define('DS', DIRECTORY_SEPARATOR);
define('BASEDIR', __DIR__ . DS);
define('BASEURL', $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . DS);
require_once("OtherCode/allInMyFunctions.php");
require_once("OtherCode/urlFunction.php");
require_once("OtherCode/stringFunction.php");
require_once("OtherCode/fileFunction.php");
require_once("OtherCode/ConnectClass.php");
require_once("OtherCode/databaseFunction.php");
require_once("OtherCode/shd.php");


//$password = hash('sha256', strip_tags(htmlspecialchars($_POST['password'])));

if (!isset($_COOKIE["userID"]))
{
  if ((!empty(getRU()) && strtolower(getRU(0)) != "login") or empty(getRU()))
    header("Location: " . BASEURL . "uploaders/Login");
  else
  {
    require_once("Controllers" . DS . "login.php");
  }
}
else
{
  $id = Select("SELECT `id` FROM `uploaders` WHERE `id` = {$_COOKIE["userID"]} LIMIT 1;");
  if (!isset($id[0]["id"]))
  {
    setcookie("userID", "", time() - (60 * 60 * 24 * 7));
    unset($_COOKIE["userID"]);
    //die("Succesfull Logout");
    exit(header("Location: " . BASEURL . "uploaders"));
  }

  $result = Select("SELECT * FROM `uploaders` WHERE `id` = {$id[0]["id"]};");
  $fname = (isset($result[0]["name"])) ? $result[0]["name"] : "Admin";

  switch (strtolower(getRU(0))) {
    case 'home':
        require_once("Controllers" . DS . "home.php");
      break;
    case 'pgenerator':
        exit(hash('sha256', strip_tags(htmlspecialchars(getRU(1)))));
      break;
    case 'logout':

          setcookie("userID", "", time() - 3600, "/");
          $_COOKIE["userID"]="";
          
          header("Location: " . BASEURL . "uploaders" .DS. "Login");
        
      break;
    
    default:
        exit(header("Location: " . BASEURL . "uploaders" .DS. "Home"));
      break;
  }
}
