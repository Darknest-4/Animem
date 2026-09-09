<?php
$router = [
  "fansub" => [
    "name" => "FanSub",
    "url" => "fansub",
    "folder" => "",
    "file" => "FanSub"
  ],
  "episodelistnew" => [
    "name" => "FanSub",
    "url" => "episodelistnew",
    "folder" => "",
    "file" => "EpisodeList"
  ],
  "z" => [
    "name" => "Z",
    "url" => "z",
    "folder" => "",
    "file" => "Z"
  ]
];
if (!empty(getRU(0)) && array_key_exists(strtolower(getRU(0)), $router))
  $cfile = ((!empty($router[strtolower(getRU(0))]["folder"])) ? ((!empty($router[strtolower(getRU(0))]["file"])) ? ((file_exists(FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["folder"] . DS . $router[strtolower(getRU(0))]["file"] . ".php")) ? FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["folder"] . DS . $router[strtolower(getRU(0))]["file"] . ".php" : exit("A '" . FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["folder"] . DS . $router[strtolower(getRU(0))]["file"] . ".php" . "' fájl nem található.")) : ((file_exists(FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["folder"] . DS . "index.php")) ? FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["folder"] . DS . "index.php" : exit("A '" . FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["folder"] . DS . "index.php" . "' fájl nem található."))) : ((!empty($router[strtolower(getRU(0))]["file"])) ? ((file_exists(FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["file"] . ".php")) ? FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["file"] . ".php" : exit("A '" . FOLDER_CONTROLLER . $router[strtolower(getRU(0))]["file"] . ".php" . "' fájl nem található.")) : ((file_exists(FOLDER_CONTROLLER . "index.php")) ? FOLDER_CONTROLLER . "index.php" : exit("A '" . FOLDER_CONTROLLER . "index.php" . "' fájl nem található."))));
$method = (!empty(getRU(1)) && !is_array(getRU(1))) ? ((!is_numeric(getRU(1))) ? getRU(1) : "View") : "index";

if(isset($cfile))
require_once($cfile);
else
exit("Az oldal nem található.");
if (!empty($method) && function_exists($method)) call_user_func($method);
