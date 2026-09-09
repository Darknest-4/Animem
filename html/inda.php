<?php
header('Content-Type: application/json; charset=utf-8');
$pathinfo = explode("/", $_SERVER["PATH_INFO"]);
$link = (isset($pathinfo[1]))?$pathinfo[1]:exit('{"success":"0","errorMessage":null}');
$json = file_get_contents("https://amfphp.indavideo.hu/SYm0json.php/player.playerHandler.getVideoData/" . $link);
echo (!empty($json))?$json:exit('{"success":"0","errorMessage":null}');