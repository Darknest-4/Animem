<?php
    require_once("view/header.phtml");
  $select = Select('SELECT * FROM `datasheet` WHERE `datasheet`.`link` = "' . RealEscapeString($RU1) . '" LIMIT 1');
  if (isset($select[0]) && !empty($select[0]))
  {
    $select = $select[0];

    $title = $select["title"];
    $select["anime"] = myanimelist($select["myanimelist"]);


    $uploaders = Select('SELECT `fansub1`, `fansub2`, `fansub3` FROM `datasheet_uploaders` WHERE `datasheet_id` = ' . $select["id"] . '');

    $uploaders = json_decode($select["save"], true);
    $uploaders = $uploaders["fansub"];
    //  print_p($uploaders);
    if (is_array($uploaders) && !empty($uploaders))
    {
      if (isset($uploaders[0]) && !is_array($uploaders[0]))
      {
        foreach ($uploaders as $k1 => $v1)
        {
          $uploaders2[$k1][] = $v1;
        }
        $uploaders = $uploaders2;
      }
      foreach ($uploaders as $k => $v)
      {
        $key = 0;
        if (isset($uploaders[$k][$key]))
        {
          $uploaders[$k]["fansub1"] = $uploaders[$k][$key];
          unset($uploaders[$k][$key]);
        }
        else
          $uploaders[$k]["fansub1"] = "";
        $key = 1;
        if (isset($uploaders[$k][$key]))
        {
          $uploaders[$k]["fansub2"] = $uploaders[$k][$key];
          unset($uploaders[$k][$key]);
        }
        else
          $uploaders[$k]["fansub2"] = "";
        $key = 2;
        if (isset($uploaders[$k][$key]))
        {
          $uploaders[$k]["fansub3"] = $uploaders[$k][$key];
          unset($uploaders[$k][$key]);
        }
        else
          $uploaders[$k]["fansub3"] = "";
      }
      $select["uploaders"] = getUploaders($uploaders, $config);
    }
    else
      $select["uploaders"] = "";

    // 	print_p($uploaders);
    if ($select["datasheet"] == "0")
      $arr2 = NULL;
    else
      $arr2 = Select('SELECT `datasheet`.`link` FROM `datasheet` WHERE `datasheet`.`save` LIKE "%' . $select["id"] . '%" && datasheet = 0 LIMIT 1');

    $episodelist = Select('SELECT `episodelist_id` FROM `datasheet_episodelist` WHERE `datasheet_id` = ' . $select["id"] . '');
    $select["episodelist"] = getEpisodelist2($select["save"], $select["datasheet"], $config);
    //$select["episodelist"] = getEpisodelist($episodelist);
    // print_p($select);
    // $sql = "UPDATE `statistic_meta` SET `open_datasheet`= `open_datasheet`+1, `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
    // Update(str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
    require_once("view/centerbox/datasheet.phtml");
  }else require_once("view/error/404.phtml");
  require_once("view/footer.phtml");



  
function myanimelist($q)
{
  $data["anime"] =
    Select(
      "SELECT
  `mal__anime`.`id`,
  `mal__anime`.`title`,
  `mal__anime`.`english`,
  `mal__anime`.`synonyms`,
  `mal__anime`.`japanese`,
  `mal__anime`.`episodes`,
  `mal__anime`.`score`,
  `mal__anime`.`members`,
  `mal__anime`.`ranked`,
  `mal__anime`.`favorites`,
  `mal__anime`.`preview`,
  `mal__anime`.`synopsis`,
  `mal__anime`.`img`,
  `mal__anime`.`popularity`,
  `mal__anime`.`aired`,
  `mal__anime`.`status`,
  `mal__anime`.`description`,
  `mal__anime`.`create_date`,
  `mal__age_rating`.`name` AS `age_rating`,
  `mal__age_rating`.`name` AS `age_rating`,
  CONCAT(
      'https://myanimelist.net/anime/',
      `mal__anime`.`mal_id`,
      '/',
          REPLACE
              (
      `mal__anime`.`title`,' ','_')
  ) AS link,
  RIGHT(
      `mal__anime`.`premiered`,
      LOCATE(
          ' ',
          REVERSE(`mal__anime`.`premiered`)
      ) - 1
  ) AS 'year',
  REPLACE
  (
  REPLACE
      (
      REPLACE
          (
          REPLACE
              (
                  LEFT(
                      `mal__anime`.`premiered`,
                      LOCATE(' ', `mal__anime`.`premiered`) - 1
                  ),
                  'Winter',
                  'Tél'
              ),
              'Spring',
              'Tavasz'
      ),
      'Summer',
      'Nyár'
  ),
  'Fall',
  'Ősz'
  ) AS 'season',
  `mal__type`.`name` AS type,
  `mal__source`.`name` AS source
  FROM
  mal__anime
  INNER JOIN `mal__age_rating` ON `mal__age_rating`.`id` = `mal__anime`.`age_rating_id`
  INNER JOIN `mal__source` ON `mal__source`.`id` = `mal__anime`.`source_id`
  INNER JOIN `mal__type` ON `mal__type`.`id` = `mal__anime`.`type_id`
  WHERE
  `mal__anime`.`mal_id` = " . $q . " LIMIT 1"
    );

  return $data["anime"][0];
}

function getUploaders($q, $config)
{
  $uploadersList = "";
  foreach ($q as $key => $value)
  {
    if (!empty($value["fansub1"]))
      $uploader2[] = Select('SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub1"] . ' LIMIT 1');
    if (!empty($value["fansub2"]))
      $uploader2[] = Select('SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub2"] . ' LIMIT 1');
    if (!empty($value["fansub3"]))
      $uploader2[] = Select('SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub3"] . ' LIMIT 1');

    if (count($uploader2) == 1)
    {
      if (empty($uploadersList))
      {
        $uploadersList = '[<a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a>]';
      }
      else
      {
        if (isset($uploader2[0][0]["id"]))
          $uploadersList .= ', [<a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a>]';
      }
    }
    elseif (count($uploader2) == 2)
    {
      if (empty($uploadersList))
      {
        $uploadersList = '[<a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a>]';
      }
      else
      {
        $uploadersList .= ', [<a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a>]';
      }
    }
    elseif (count($uploader2) == 3)
    {
      if (empty($uploadersList))
      {
        $uploadersList = '[<a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a> & <a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[2][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[2][0]["name"] . '</a>]';
      }
      else
      {
        $uploadersList .= ', [<a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a> & <a href="'. $config["url"]["base"] .'fansub/?' . $uploader2[2][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[2][0]["name"] . '</a>]';
      }
    }
    unset($uploader2);
  }
  return $uploadersList;
}
function getEpisodelist2($q, $w, $config)
{
  $Episodelist = "";
  $q = json_decode($q, true);
  if (is_array($q["links"]))
    foreach ($q["links"] as $id)
    {
      if ($w == true)
        $uploader2 = Select('SELECT `title`, `link` FROM `episodelist` WHERE `id` = ' . $id . ' LIMIT 1');
      else
        $uploader2 = Select('SELECT `title`, `link` FROM `datasheet` WHERE `id` = ' . $id . ' LIMIT 1');
      if (!empty($uploader2[0]["title"]))
      {
        $Episodelist .= '<div><h2 class="bdp-post-title">';
        $Episodelist .= '<a href="'. $config["url"]["base"] .'Episode/' . $uploader2[0]["link"] . '">' . $uploader2[0]["title"] . '</a>';
        $Episodelist .= '</h2></div>';
      }
      unset($uploader2);
    }
  return $Episodelist;
}