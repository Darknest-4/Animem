<?php
$type = getRU(1); // Inda/EmbedInda/EmbedOta
$player = getRU(2); // On/Off/Null || On - plyr.io; Off - nativ browser player; Null - origin Indavideo.org player 
$link = getRU(3);
$link_A = "";
$link_B = "";
?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('display_startup_errors', 1);
error_reporting(0);
function input_encode($str)
{
  return htmlspecialchars($str);
}
defined('DS') || define('DS', DIRECTORY_SEPARATOR);


$config["Error"] = [
  "XZ100" => "Hiányzó HttpReferer. Az oldalt csak iframe tagekben lehet használni.",
  "XZ101" => "Hiányzó _GET.",
  "XZ104" => "Hiányzó HttpSecFetchDest. Az oldalt csak iframe tagekben lehet használni.",
  "XZ105" => "A HttpReferer nem szerepel a listán."
];
//$HttpReferer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : exit($config["Error"]["XZ100"]);
//if(in_array($HttpReferer, $config["HttpReferers"])){} else exit($config["Error"]["XZ105"]);
//$URL = (isset($_GET["u"])) ? input_encode($_GET["u"]) : exit($config["Error"]["XZ101"]);
if (!empty($type) && $type == "EmbedOta")
{
  $link = $link_A = "https://dash.otamoon.hu/playe.php" . DS . getRU(3) . DS . getRU(4) . DS . getRU(5);
}elseif (!empty($type) && ($type == "Inda" || $type == "EmbedInda"))
{
  if (!empty($type) && $type == "Inda")
    $link = $URL = "https://indavideo.hu/video" . DS . getRU(3);
  elseif (!empty($type) && $type == "EmbedInda")
    $link = $URL = "https://embed.indavideo.hu/player/video" . DS . getRU(3);

  $http = "https://";
  $embed = "embed.indavideo.hu/player/video/";
  $inda = "indavideo.hu/video/";
  $amf = "https://amfphp.indavideo.hu/SYm0json.php/player.playerHandler.getVideoData/";
  $nxu = "https://indavideo.nxu.hu/url";

  $URL = (count(explode($embed, $URL)) == 2) ? explode($embed, $URL)[1] : $URL;
  $URL = (count(explode($inda, $URL)) == 2) ? explode($inda, $URL)[1] : $URL;

  $amf = json_decode(file_get_contents($amf . $URL), true);

  $URL = (isset($amf["data"]) && isset($amf["data"]["hash"])) ? $http . $inda . $amf["data"]["url_title"] : exit(require_once("view/error/404v.phtml"));
  $POSTER = (isset($amf["data"]["video_img"])) ? $amf["data"]["video_img"] : exit(require_once("view/error/404v.phtml"));
  $postdata = http_build_query(
    [
      'url' => $URL
    ]
  );

  $opts = [
    'http' =>
    [
      'method'  => 'POST',
      'header'  => 'Content-Type: application/x-www-form-urlencoded',
      'content' => $postdata
    ]
  ];
  $context  = stream_context_create($opts);
  $js = (file_get_contents("https://indavideo.nxu.hu/url", false, $context)) ? json_decode(file_get_contents($nxu, false, $context), true) : json_decode(json_encode(array("error" => false)), true);
  if (isset($js['error']) || (!isset($js['resolutions']['360']) && !isset($js['url'])))
    $js['resolutions']['360'] = $amf["data"]["video_file"] . "&token=" . $amf["data"]["filesh"][360];
  // exit($exit);
  $link_A = (isset($js['resolutions']['720'])) ? $js['resolutions']['720'] : "";
  $link_B = (isset($js['resolutions']['360'])) ? $js['resolutions']['360'] : "";
}else require_once("view/error/404v.phtml");
require_once("view/centerbox/video.phtml");
