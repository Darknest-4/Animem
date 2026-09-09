<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('display_startup_errors', 1);
!defined("DS")                && define("DS",                 DIRECTORY_SEPARATOR);
!defined("CURRENT_DIR")       && define("CURRENT_DIR",        dirname(__DIR__, 1) . DS);
require_once(CURRENT_DIR . "private" . DS . "OtherCode" . DS . "constans.php");
require_once(FOLDER_CODE . "allInMyFunctions.php");
require_once(FOLDER_CODE . "urlFunction.php");
require_once(FOLDER_CODE . "stringFunction.php");
require_once(FOLDER_CODE . "fileFunction.php");
require_once(FOLDER_CODE . "databaseFunction.php");
require_once(FOLDER_CODE . "shd.php");

if (getRU(1) == "genre")
{

  $data = Select("Select name, type from mal__genres");
  foreach ($data as $key => $value)
  {
    $data[$key]["lang"]["HU"] = "";
    $data[$key]["lang"]["EN"] = $value["name"];
  }
  header("Content-type: application/json");
  print_r(json_encode($data));
}
if (getRU(1) == "studio")
{

  $data = Select("SELECT `link`, `name` FROM `mal__studios`;");
  foreach ($data as $key => $value)
  {
  }
  header("Content-type: application/json");
  print_r(json_encode($data));
}
if (getRU(1) == "datasheet")
{

  $data = Select("SELECT `myanimelist` AS 'mal_id',`description`,`save` FROM `datasheet` WHERE `title` NOT LIKE '%series%';");
  foreach ($data as $key => $value)
  {
    if ($save = json_decode($value["save"], true))
    {
      if (isset($save["fansub"]))
        if (is_array($save["fansub"]))
          foreach ($save["fansub"] as $value2)
            $data[$key]["fansub"][] = intval($value2);
      if (isset($save["links"]))
        if (is_array($save["links"]))
          foreach ($save["links"] as $value2)
            $data[$key]["links"][] = intval($value2);
      if (isset($save)) unset($save);
    }unset($data[$key]["save"]);
  }
  header("Content-type: application/json");
  print_r(json_encode($data));
}
if (getRU(1) == "uploaders")
{

  $data = Select("SELECT `name`, `code`, `inda_link`, `fan_link`, `fb_link`, `email`, `tdes`, `pdes`, `collector`, `act_team`, `for_link_use` FROM `uploaders`;");
  foreach ($data as $key => $value)
  {
    $data[$key]["description"]["private"] = $value["tdes"];
    $data[$key]["description"]["public"] = $value["pdes"];
    $data[$key]["availability"]["facebook"] = $value["fb_link"];
    $data[$key]["availability"]["website"] = $value["fan_link"];
    $data[$key]["availability"]["indavideo"] = $value["inda_link"];
    $data[$key]["availability"]["email"] = $value["email"];
    $data[$key]["collector"] = ($value["collector"] == 1) ? true : false;
    $data[$key]["activeTeam"] = ($value["act_team"] == 1) ? true : false;
    $data[$key]["for_link_use"] = ($value["for_link_use"] == 1) ? true : false;
    unset($data[$key]["act_team"]);
    unset($data[$key]["tdes"]);
    unset($data[$key]["pdes"]);
    unset($data[$key]["fan_link"]);
    unset($data[$key]["inda_link"]);
    unset($data[$key]["fb_link"]);
    unset($data[$key]["email"]);
  }
  header("Content-type: application/json");
  print_r(json_encode($data));
}
if (getRU(1) == "anime")
{
  $data = Select("SELECT 
    `mal__anime`.`id`, 
    `mal__anime`.`title`, 
    `mal__type`.`name` AS 'type', 
    `mal__age_rating`.`code` AS 'age_rating', 
    `mal__source`.`name` AS 'source', 
    `mal__anime`.`english`, 
    `mal__anime`.`synonyms`, 
    `mal__anime`.`japanese`, 
    `mal__anime`.`episodes`, 
    `mal__anime`.`premiered_year`, 
    `mal__anime`.`premiered_seasonal`, 
    `mal__anime`.`preview`, 
    `mal__anime`.`synopsis`, 
    `mal__anime`.`synopsis2`, 
    `mal__anime`.`aired_start`, 
    `mal__anime`.`aired_end`, 
    `mal__anime`.`status`, 
    `mal__anime`.`mal_id`, 
    `mal__anime`.`img`
  FROM `mal__anime` 
    LEFT JOIN `mal__type` ON `mal__anime`.`type_id` = `mal__type`.`id`
    LEFT JOIN `mal__age_rating` ON `mal__anime`.`age_rating_id` = `mal__age_rating`.`id`
    LEFT JOIN `mal__source` ON `mal__anime`.`source_id` = `mal__source`.`id`;");
  foreach ($data as $key => $value)
  {
    $genres = Select("SELECT `link`, `name`, `type` FROM `mal__anime__genres` LEFT JOIN `mal__genres` ON `mal__genres`.`id` = `mal__anime__genres`.`genre_id` WHERE `anime_id` = " . $value["id"]);
    foreach ($genres as $genre)
    {
      $data[$key][$genre["type"]][$genre["link"]] = $genre["name"];
    }
    $studios = Select("SELECT `mal__studios`.`name`, `mal__studios`.`link`, `mal__studios_type`.`name` AS 'type' FROM `mal__anime__studios` LEFT JOIN `mal__studios` ON `mal__studios`.`id` = `mal__anime__studios`.`studios_id`  LEFT JOIN `mal__studios_type` ON `mal__studios_type`.`id` = `mal__anime__studios`.`studios_type_id` WHERE `anime_id` = " . $value["id"]);
    foreach ($studios as $studio)
    {
      $data[$key][$studio["type"]][$studio["link"]] = $studio["name"];
    }

    unset($data[$key]["id"]);
    unset($data[$key]["synopsis"]);
    unset($data[$key]["synopsis2"]);
    $data[$key]["synopsis"]["EN"] = $value["synopsis"];
    $data[$key]["synopsis"]["HU"] = $value["synopsis2"];
    /*
    $data[$key]["lang"]["HU"] = "";
    $data[$key]["lang"]["EN"] = $value["name"];*/
  }
  header("Content-type: application/json");
  print_r(json_encode($data));
}
