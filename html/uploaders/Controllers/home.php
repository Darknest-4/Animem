<?php

set_time_limit(0);
$id = $_COOKIE["userID"];
$videoInSystem = 0;
$videoUsed = 0;
$videoUsed = 0;


$result = Select("SELECT * FROM `links` WHERE `uploaders_id` = {$id};");
$videoInSystem = (isset($result[0]["id"]) && is_numeric($result[0]["id"])) ? count($result) : 0;
$result = Select("SELECT * FROM `links` WHERE `episodelist_id`IS NOT NULL && `uploaders_id` = {$id};");
$videoUsed = (isset($result[0]["id"]) && is_numeric($result[0]["id"])) ? count($result) : 0;

$result = Select("SELECT SUM(`st`.`viewed`) AS 'viewed', SUM(`st`.`clicked`) AS 'clicked', SUM(`st`.`opened`) AS 'opened' FROM `links` l
                  INNER JOIN `statistic__links` st ON `st`.`links_id` = `l`.`id`
                  INNER JOIN `uploaders` up ON `up`.`id` = `l`.`uploaders_id`
                  WHERE `l`.`uploaders_id` = {$id};");
$videoViewed = (isset($result[0]["viewed"]) && is_numeric($result[0]["viewed"])) ? ($result[0]["viewed"]) : 0;
$videoClicked = (isset($result[0]["clicked"]) && is_numeric($result[0]["clicked"])) ? ($result[0]["clicked"]) : 0;
$videoOpened = (isset($result[0]["opened"]) && is_numeric($result[0]["opened"])) ? ($result[0]["opened"]) : 0;
$nonViewedVideo = $videoOpened - $videoViewed;


/*
$result = Select("SELECT * FROM `episodelist`;");

$videoUsed = 0;
foreach ($result as $value)
{
  $json = json_decode($value["save"], true);
  foreach ($json as $key => $value2)
  {
    if ($key != "_type") {
    
      Update("UPDATE `links3` SET `episodelist_id`={$value["id"]} WHERE `id`={$value2} LIMIT 1", true);
  }
  }
}
*/

/*

$result = Select("SELECT * FROM `links3` WHERE `uploaders_id` = {$id};");
//$result = Select("SELECT * FROM `links` WHERE `uploaders_id` = {$id} group by `link`;");
$videoInSystem = (isset($result[0]["id"]) && is_numeric($result[0]["id"])) ? count($result) : 0;
$videoUsed = 0;
foreach ($result as $key => $value)
{
  $array[] = "";
$result = Select("SELECT * FROM `episodelist` WHERE `save` LIKE '%\"".$value["id"]."\"%';");
$videoUsed = (isset($result[0]["id"])) ? $videoUsed+1 : $videoUsed;
}

*/

/*
foreach ($result as $key => $value)
{
  $array[] = "`save` LIKE '%\"".$value["id"]."\"%'";
}

$array = implode(" OR ", $array);
$result = Select("SELECT * FROM `episodelist` WHERE {$array};");
unset($array);

$videoUsed = (isset($result[0]["id"]) && is_numeric($result[0]["id"])) ? count($result) : 0;
*/
$videoNotUsed = $videoInSystem - $videoUsed;
require_once("Views" . DS . "header.phtml");
require_once("Views" . DS . "navbar.phtml");
require_once("Views" . DS . "home.phtml");
require_once("Views" . DS . "footer.phtml");
