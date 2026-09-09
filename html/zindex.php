<?php
require_once("lib/allInMyFunctions.php");
require_once("lib/urlFunction.php");
require_once("lib/stringFunction.php");
require_once("lib/fileFunction.php");
require_once("lib/ConnectClass.php");
require_once("lib/databaseFunction.php");
$config["url"]["base"] = "https://animem.org/zindex.php/";

$RU0 = getRU(0);
$RU1 = getRU(1);
switch ($RU0)
{
  case 'Anime':
    require_once("ctl/");
    break;

  case 'DataSheet':
    require_once("ctl/DataSheet.php");
    break;

  case 'DataSheetEditor':
    require_once("ctl/");
    break;

  case 'Episode':
    require_once("ctl/Episode.php");
    break;

  case 'EpisodeEditor':
    require_once("ctl/");
    break;

  case 'EpisodeViewer':
    require_once("ctl/");
    break;

  case 'Guide':
    require_once("ctl/");
    break;

  case 'Login':
    require_once("ctl/");
    break;

  case 'Logout':
    require_once("ctl/");
    break;

  case 'Search':
    require_once("ctl/");
    break;

  case 'Statistic':
    require_once("ctl/");
    break;

  case 'Uploaders':
    require_once("ctl/");
    break;

  case 'UploadersEditor':
    require_once("ctl/");
    break;

  case 'UploadersViewer':
    require_once("ctl/");
    break;

  case 'User':
    require_once("ctl/");
    break;

  case 'UserDelete':
    require_once("ctl/");
    break;

  case 'UserNew':
    require_once("ctl/");
    break;

  case 'Redirect':
    require_once("ctl/");
    break;

  case 'Discord':
    require_once("ctl/");
    break;

  case 'Video':
    require_once("ctl/Video.php");
    break;

  case 'Home':
    require_once("ctl/Home.php");
    break;
  default:
    require_once("ctl/Home.php");
    break;
}
