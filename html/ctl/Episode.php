<?php
require_once("view/header.phtml");

$select = Select('SELECT * FROM `episodelist` WHERE `episodelist`.`link` = "' . RealEscapeString($RU1) . '" LIMIT 1');
$episode_lang = Select('SELECT * FROM `episode_lang` ORDER BY `text`');
foreach ($episode_lang as  $value)
  $episode_langs[$value["id"]] = $value["text"];

if (isset($select[0]) && !empty($select[0]))
{
  $arr2 = Select('SELECT `datasheet`.`link` FROM `datasheet` WHERE `datasheet`.`save` LIKE "%' . $select[0]["id"] . '%" && datasheet = 1 LIMIT 1');
  $select = $select[0];

  $title = $select["title"];

  $json = json_decode($select["save"], true);
  if (isset($json["_type"]))
  {
    $json2 = array();
    if (isset($json["_type"])) unset($json["_type"]);
    foreach ($json as $key => $value)
    {
      $link = Select("SELECT
        CONCAT(
            'https://',
            `links_type`.`link`,
            `links`.`link`
        ) AS link FROM `links`
        INNER JOIN `links_type` ON `links_type`.`id` = `links`.`links_type` WHERE `links`.`id` = " . $value . " LIMIT 1;");
      $json2[preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $key) . " " . $episode_langs[$select["episode_lang"]]] = $link[0]["link"];
      $json3[preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $key) . " " . $episode_langs[$select["episode_lang"]]] = $value;
    }

    $link = Select("SELECT `datasheet_id`  FROM `datasheet_episodelist` WHERE `episodelist_id` =" . $select["id"] . " LIMIT 1;");
    if (isset($link[0]["datasheet_id"]))
      $link = Select("SELECT `myanimelist`  FROM `datasheet` WHERE `id` =" . $link[0]["datasheet_id"] . " LIMIT 1;");
    if (isset($link[0]["myanimelist"]))
      $link = Select($conn3, "SELECT `age_rating_id`  FROM `mal__anime` WHERE `mal_id` = " . $link[0]["myanimelist"] . " LIMIT 1;");
    if (isset($link[0]["age_rating_id"]))
      $link = Select($conn3, "SELECT `code`  FROM `mal__age_rating` WHERE `id` = " . $link[0]["age_rating_id"] . " LIMIT 1;");
  }

  $datasheet = $config["url"]["base"] . "DataSheet/" . $arr2[0]["link"];
  $title = $select["title"];
  $content = "";

  if (isset($json2) && isset($json3))
  {
    if (isset($link[0]["code"]))
      $content .= '<div id="div' . $link[0]["code"] . '"></div>';
    $content .= ''
      . '<script>var jsonzx125 = ' . json_encode($json2) . ';</script>'
      . '<script>var tikitaki = ' . json_encode($json3) . ';</script>'
      . '<div id="jsonzx124"></div>';
  }
  else $content .= str_replace("https://animem.org/wp-content/", "https://animem.org/Assets/", $select["save"]);
  echo str_replace(["{{DATASHEET}}","{{TITLE}}","{{CONTENT}}"], [$datasheet, $title, $content], fGetCon("view/centerbox/episode.phtml"));
}else require_once("view/error/404.phtml");
require_once("view/footer.phtml");
