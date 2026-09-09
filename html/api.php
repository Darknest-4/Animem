<?php
//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database = "animem";

require_once("../Config/loadConfig.php");

$siteConfig = DbConfig::getSiteConfig();
if (!defined("BASEURL")) {
  define("BASEURL", "https://".$siteConfig['domain']."/");
}

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

// Create connection
$conn2 = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn2->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function input_encode($str)
{
 return htmlspecialchars($str);
}
$id = (isset($_GET["id"]) && is_numeric($_GET["id"]))?$_GET["id"]:0;
if ($id != 0) {
  $sql = "
  SELECT SUM(`st`.`viewed`) AS 'videonum', `up`.`name`, `up`.`inda_link`, `up`.`fan_link`, `up`.`fb_link` FROM `links` l
INNER JOIN `statistic__links` st ON `st`.`links_id` = `l`.`id`
INNER JOIN `uploaders` up ON `up`.`id` = `l`.`uploaders_id`
WHERE `l`.`uploaders_id` = {$id}
  
  ";
  $result = $conn2->query($sql);
  if(isset($result->num_rows) AND $result->num_rows > 0)
    while($row = $result->fetch_assoc()) {
      echo json_encode($row, JSON_FORCE_OBJECT);
    }
    else echo json_encode(array(), JSON_FORCE_OBJECT);
}else
{
  $URL = (isset($_GET["URL"]) && is_string($_GET["URL"]))?$_GET["URL"]:0;
  $https = "https://";
  $http = "http://";
  $embed = "embed.indavideo.hu/player/video/";
  $inda = "indavideo.hu/video/";
  $amf = "https://amfphp.indavideo.hu/SYm0json.php/player.playerHandler.getVideoData/";
  $nxu = "https://indavideo.nxu.hu/url";
  $exit = '<iframe style="position: absolute; top: 0; left: 0; bottom: 0; border:0;right: 0; width: 100%; height: 100%;" src="'.BASEURL.'ntvep.php"></iframe>';
  
  $URL = (count(explode($embed, $URL)) == 2)?explode($embed, $URL)[1]:$URL;
  $URL = (count(explode($inda, $URL)) == 2)?explode($inda, $URL)[1]:$URL;
  
  
  $amf = json_decode(file_get_contents($amf.$URL),true);
  $username = (isset($amf["data"]["user_name"]))?$amf["data"]["user_name"]:"";
  if(!empty($username))
  {
    $result = $conn2->query("SELECT `id`, `name` FROM `uploaders` WHERE `inda_link` LIKE '%" . $username . "%' LIMIT 1");
    if(isset($result->num_rows) AND $result->num_rows > 0)
      echo json_encode($result->fetch_assoc(), JSON_FORCE_OBJECT);
  }else echo '{"id":"0","name":"Ismeretlen"}';
}



?>
