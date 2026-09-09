<?php

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('display_startup_errors', 1);
!defined("DS") && define("DS", DIRECTORY_SEPARATOR);
!defined("CURRENT_DIR")       && define("CURRENT_DIR",        dirname(__DIR__, 2) . DS);
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "constans.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "databaseFunction.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "allInMyFunctions.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "dencode.function.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "fileFunction.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "print.function.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "stringFunction.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "urlFunction.php";
require_once ".." . DS . ".." . DS . "private" . DS . "OtherCode" . DS . "shd.php";



$DS = Select('SELECT `id`, `title`, `save` FROM `datasheet` WHERE `title` NOT LIKE "%series%";');
if (isset($_GET["anime"]))
{
  echo "<a href=\"https://animem.org/admin/DSGenerator.php\">Vissza</a><br />";
  $anime = $_GET["anime"];
  $anime = (is_numeric($anime)) ? $anime : explode('/', str_replace(["https://", "http://"], '', $anime))[2];
  echo "Az ID: " . $anime . "<br />";

  if ($html = str_get_html(file_get_contents("https://chiaki.site/?/tools/watch_order/id/" . $anime)))
  {
    if ($table = $html->find('#wo_list', 0))
    {
      if ($subA = $table->find('a'))
      {
        foreach ($subA as $value)
          if (isset($value->href) && !empty($value->href))
            $subB[] = $value->href;
        if (isset($subA)) unset($subA);
        foreach ($subB as $value)
        {
          if (textInText("myanimelist.net/anime/", $value))
          {
            $id = explode("myanimelist.net/anime/", $value);
            $id = (isset($id[1]) && is_numeric($id[1])) ? $id[1] : ((isset($id[1])) ? explode("/", $id[1]) : "");
            $id = (isset($id[0]) && is_numeric($id[0])) ? $id : $id[0];

            $MAL[] = intval($id); // MyAnimeList ID
            $MALD = getMyanimelist(intval($id)); // MyAnimeList Data
            Save(malParser($MALD));


            if (isset($id)) unset($id);
          }
        }

        if (textInText("myanimelist.net/anime/", $subB[0]))
        {
          $id = explode("myanimelist.net/anime/", $subB[0]);
          $id = (isset($id[1]) && is_numeric($id[1])) ? $id[1] : ((isset($id[1])) ? explode("/", $id[1]) : "");
          $id = (isset($id[0]) && is_numeric($id[0])) ? $id : $id[0];
          $title = Select("SELECT `title` FROM `mal__anime` WHERE `mal_id` = {$id};");
          $title = $title[0]["title"] . " Series";
          $dats = Select("SELECT `id` FROM `datasheet` WHERE `myanimelist` IN (" . implode(", ", $MAL) . ") && `title` NOT LIKE \"%series%\";");
          foreach ($dats as $dat)
            $da[] = '"' . $dat["id"] . '"';

          $sql = "INSERT INTO `datasheet` (`title`, `link`, `myanimelist`, `save`, `series`) VALUES ('" . input_encode($title) . "','" . clean(input_encode($title)) . "'," . $id . ", " . '\'{"_type":"datasheet","fansub":[],"links":[' . implode(", ", $da) . ']}\'' . ", 1);";
          Insert($sql);
          if (isset($id)) unset($id);
        }



        if (isset($subB)) unset($subB);
      }
    }
    if (isset($table)) unset($table);
  }
  if (isset($html)) unset($html);

}
else
{
?>
  <form method="get">
    <label for="anime">MyAnimeList ID/Link:</label>
    <input type="text" id="anime" name="anime"><br><br>
    <input type="submit" value="Keresés">
  </form>
<?php
}




function malParser($mal)
{
  echo "Parse data:" . $mal["anime"]["mal_id"] . "<br />";
  $mal = $mal["anime"];
  if (!isset($mal["information"]["demographic"])) $mal["information"]["demographic"] = array();
  if (!isset($mal["information"]["themes"])) $mal["information"]["themes"] = array();
  if (!isset($mal["information"]["genres"])) $mal["information"]["genres"] = array();
  if (!isset($mal["information"]["synonyms"])) $mal["information"]["synonyms"] = "";
  if (!isset($mal["information"]["english"])) $mal["information"]["english"] = "";
  if (!isset($mal["information"]["synonyms"])) $mal["information"]["synonyms"] = "";
  if (!isset($mal["information"]["premieredYear"])) $mal["information"]["premieredYear"] = "";
  if (!isset($mal["information"]["premieredSeasonal"])) $mal["information"]["premieredSeasonal"] = "";
  if (!isset($mal["information"]["airedStart"])) $mal["information"]["airedStart"] = "";
  if (!isset($mal["information"]["airedEnd"])) $mal["information"]["airedEnd"] = "";
  if (!isset($mal["title"])) $mal["title"] = "";
  if (!isset($mal["information"]["episodes"])) $mal["information"]["episodes"] = "";

  if (!isset($mal["information"]["studios"])) $mal["information"]["studios"] = "";
  if (!isset($mal["information"]["licensors"])) $mal["information"]["licensors"] = "";
  if (!isset($mal["information"]["producers"])) $mal["information"]["producers"] = "";

  if (!isset($mal["information"]["type"])) $mal["information"]["type"] = "";
  if (!isset($mal["information"]["source"])) $mal["information"]["source"] = "";
  if (!isset($mal["information"]["rating"])) $mal["information"]["rating"] = "";
  if (!isset($mal["others"]["synopsis"])) $mal["others"]["synopsis"] = "";
  if (!isset($mal["fansub"])) $mal["fansub"] = "";
  if (!isset($mal["description2"])) $mal["description2"] = "";

  if (!isset($mal["mal_id"])) $mal["mal_id"] = NULL;
  $data["myanimelist"] = $mal["mal_id"];
  $data["title"] = $mal["title"];
  $data["episodes"] = $mal["information"]["episodes"];
  $data["atitle"] = $mal["information"]["english"];
  $data["synonyms"] = $mal["information"]["synonyms"];
  if (!isset($mal["information"]["japanese"])) $mal["information"]["japanese"] = "";
  $data["jtitle"] = $mal["information"]["japanese"];
  $data["studios"]["studios"] = $mal["information"]["studios"];
  $data["studios"]["licensors"] = $mal["information"]["licensors"];
  $data["studios"]["producers"] = $mal["information"]["producers"];

  if (isset($mal["information"]["genres"]))
    foreach ($mal["information"]["genres"] as $key => $value) $data["genres"][$key] = $value;
  if (isset($mal["information"]["genre"]))
    foreach ($mal["information"]["genre"] as $key => $value) $data["genres"][$key] = $value;
  if (isset($mal["information"]["themes"]))
    foreach ($mal["information"]["themes"] as $key => $value) $data["genres"][$key] = $value;
  if (isset($mal["information"]["theme"]))
    foreach ($mal["information"]["theme"] as $key => $value) $data["genres"][$key] = $value;
  if (isset($mal["information"]["demographic"]))
    foreach ($mal["information"]["demographic"] as $key => $value) $data["genres"][$key] = $value;

  $data["fansub"] = $mal["fansub"];
  $data["description"] = $mal["others"]["synopsis"];
  $data["description2"] = $mal["description2"];


  if (!isset($mal["description2"])) $mal["description2"] = "";
  $data["preview"] = $mal["others"]["preview"];
  if (!isset($mal["img"])) $mal["img"] = "";
  $data["coverImage"] = $mal["img"];
  if (!isset($mal["information"]["status"])) $mal["information"]["status"] = "";
  $data["status"] = $mal["information"]["status"];
  $data["type"] = getTypeB("type", $mal["information"]["type"]);
  $data["source"] = getTypeB("source", $mal["information"]["source"]);
  $data["ageRating"] = getTypeB("age_rating", $mal["information"]["rating"]);
  $data["premieredYear"] = $mal["information"]["premieredYear"];
  $data["premieredSeasonal"] = $mal["information"]["premieredSeasonal"];
  $data["airedStart"] = $mal["information"]["airedStart"];
  $data["airedEnd"] = $mal["information"]["airedEnd"];
  return $data;
}
function Save($config)
{
  echo "Save data:" . $config["myanimelist"] . "<br />";
  if (isset($config["myanimelist"]) && !empty($config["myanimelist"]) && !issetAnime($config["myanimelist"]))
  {
    $data = [
      // Only string
      "title"               => input_encode(((isset($config["title"])              && !empty($config["title"])          && is_string($config["title"])) ? $config["title"] : NULL)), // Def: NULL
      "english"             => input_encode(((isset($config["atitle"])             && !empty($config["atitle"])         && is_string($config["atitle"])) ? $config["atitle"] : NULL)), // Def: NULL
      "synonyms"            => input_encode(((isset($config["synonyms"])           && !empty($config["synonyms"])       && is_string($config["synonyms"])) ? $config["synonyms"] : NULL)), // Def: NULL
      "japanese"            => input_encode(((isset($config["jtitle"])             && !empty($config["jtitle"])         && is_string($config["jtitle"])) ? $config["jtitle"] : NULL)), // Def: NULL
      "synopsis"            => input_encode(((isset($config["description"])        && !empty($config["description"])    && is_string($config["description"])) ? $config["description"] : "Nincs leírás.")), // Def: "Nincs leírás."
      "synopsis2"           => input_encode(((isset($config["description2"])        && !empty($config["description2"])    && is_string($config["description2"])) ? $config["description2"] : "Nincs leírás.")), // Def: "Nincs leírás."
      "preview"             => (((isset($config["preview"])            && !empty($config["preview"])        && is_string($config["preview"])) ? $config["preview"] : NULL)), // Def: NULL
      "img"                 => (((isset($config["coverImage"])         && !empty($config["coverImage"])     && is_string($config["coverImage"])) ? $config["coverImage"] : NULL)), // Def: NULL
      // "Currently Airing" || "Finished Airing" || "Not yet aired"
      "status"              => (((isset($config["status"])             && !empty($config["status"])         && textInText(["Currently Airing", "Finished Airing", "Not yet aired"], $config["status"])) ? $config["status"] : NULL)), // Def: NULL
      // Only numeric
      "type_id"             => (((isset($config["type"])               && !empty($config["type"])           && is_numeric($config["type"]) &&  issetAnimeType($config["type"])) ? $config["type"] : 5)), // Def: 5
      "source_id"           => (((isset($config["source"])             && !empty($config["source"])         && is_numeric($config["source"]) &&  issetAnimeSource($config["source"])) ? $config["source"] : 6)), // Def: 6
      "age_rating_id"       => (((isset($config["ageRating"])          && !empty($config["ageRating"])      && is_numeric($config["ageRating"]) &&  issetAnimeAgeRating($config["ageRating"])) ? $config["ageRating"] : 6)), // Def: 6 || 7
      "mal_id"              => (((isset($config["myanimelist"])             && !empty($config["myanimelist"])         && is_numeric($config["myanimelist"])) ? $config["myanimelist"] : "")), // Def: It cannot be empty!
      "premiered_year"      => (((isset($config["premieredYear"])      && !empty($config["premieredYear"])  && is_numeric($config["premieredYear"])) ? $config["premieredYear"] : NULL)), // Def: NULL
      // "Winter" || "Spring" || "Summer" || "Fall" || NULL
      "premiered_seasonal"  => (((isset($config["premieredSeasonal"])  && !empty($config["premieredSeasonal"]) && textInText(["Winter", "Spring", "Summer", "Fall"], $config["premieredSeasonal"])) ? $config["premieredSeasonal"] : NULL)), // Def: NULL
      // Numeric || String
      "episodes"            => (((isset($config["episodes"])           && !empty($config["episodes"])       && (is_string($config["episodes"]) || is_numeric($config["episodes"]))) ? $config["episodes"] : "?")), // Def: ?
      // Only date (yyyy-mm-dd)
      "aired_start"         => (((isset($config["airedStart"])         && !empty($config["airedStart"])     && dateChecked($config["airedStart"])) ? $config["airedStart"] : "1000-01-01")), // Def: 1000-01-01
      "aired_end"           => (((isset($config["airedEnd"])           && !empty($config["airedEnd"])       && dateChecked($config["airedStart"])) ? $config["airedEnd"] : "1000-01-01")), // Def: 1000-01-01
      // Only array (JSON)
      "fansub"           => (((isset($config["fansub"])           && !empty($config["fansub"])       && is_array($config["fansub"])) ? json_encode($config["fansub"]) : json_encode([]))) // Def: []
    ];
    // print_p($data);
    //die;
    $ID = saveAnime($data);
    if (is_numeric($ID))
    {
      if (!empty($config["genres"]) && is_array($config["genres"]))
        foreach ($config["genres"] as $key => $value)
          if (!empty($key) && is_numeric($key))
            if (issetGenreID($key))
              setAnimeGenre($ID, $key);

      if (isset($config["studios"]["studios"]))
      {
        foreach ($config["studios"]["studios"] as $key => $value)
          if ((!empty($key) && is_numeric($key)) || $key == 0)
            if (($sid = issetStudioID($key)) && ($sid != false || $sid != 0))
              setAnimeStudio($ID, $sid, 1);
      }
      if (isset($config["studios"]["licensors"]))
      {
        foreach ($config["studios"]["licensors"] as $key => $value)
          if ((!empty($key) && is_numeric($key)) || $key == 0)
            if (($sid = issetStudioID($key)) && ($sid != false || $sid != 0))
              setAnimeStudio($ID, $sid, 2);
      }
      if (isset($config["studios"]["producers"]))
      {
        foreach ($config["studios"]["producers"] as $key => $value)
          if ((!empty($key) && is_numeric($key)) || $key == 0)
            if (($sid = issetStudioID($key)) && ($sid != false || $sid != 0))
              setAnimeStudio($ID, $sid, 3);
      }

      //file_get_contents("https://dash.otamoon.hu/api.php/?token=" . $token . "&newanime=". $ID);
      return array(TRUE, "" . str_replace("{{link}}", "" . "MyAnimeList/View/"  . $ID, ""), "success");
    }
  }
}
/* ============================================================== */
/* Set/Save Functions Start */
/* ============================================================== */
function setAnimeGenre($animeID, $genreID)
{

  $sql = "INSERT INTO `mal__anime__genres`(`anime_id`, `genre_id`)
  SELECT * FROM (SELECT {$animeID} as `anime_id`, {$genreID} AS `genre_id`) AS new_value
  WHERE NOT EXISTS (
   SELECT `anime_id` FROM `mal__anime__genres` WHERE `anime_id` = {$animeID} && `genre_id` = {$genreID}
  ) LIMIT 1;";
  $return =  Insert($sql);
  unset($sql);
  //echo $return;
}
function setAnimeStudio($animeID, $studioID, $typeID)
{
  $sql = "INSERT INTO `mal__anime__studios`(`anime_id`, `studios_id`, `studios_type_id`)
  SELECT * FROM (SELECT {$animeID} as `anime_id`, {$studioID} AS `genre_id`, {$typeID} AS `studios_type_id`) AS new_value
  WHERE NOT EXISTS (
   SELECT `anime_id` FROM `mal__anime__studios` WHERE `anime_id` = {$animeID} && `studios_id` = {$studioID} && `studios_type_id` = {$typeID}
  ) LIMIT 1;";
  $return =  Insert($sql);
  unset($sql);
  //echo $return;
}
function saveAnime($data)
{
  $sql = "INSERT INTO `episodelist`(`title`, `link`) VALUES ('" . htmlspecialchars($data["title"]) . " részek','" . clean(htmlspecialchars($data["title"])) . "-részek');";
  foreach ($data as $key => $value) $data[$key] = (is_string($value) && !is_numeric($value) && !is_null($value)) ? "'" . htmlspecialchars($value) . "'" : $value;


   $ID = Insert($sql, "default", true);
   $sql = "INSERT INTO `datasheet`(`title`, `link`, `myanimelist`, `save`) VALUES (" .$data["title"] . "," . ($data["title"]) . ",{$data["mal_id"]}, '{\"_type\":\"datasheet\",\"fansub\":[],\"links\":[\"{$ID}\"]}');";
  
  $ID = Insert($sql, "default", true);
  if (is_numeric($ID))
  {
     $sql = "INSERT INTO `mal__anime`(`title`, `english`, `synonyms`, `japanese`, `type_id`, `episodes`, `premiered_year`,
  `premiered_seasonal`, `source_id`, `age_rating_id`, `preview`, `synopsis`, `synopsis2`, `aired_start`, `aired_end`, `status`, `mal_id`, `img`, `fansub`)
  VALUES (
    {$data["title"]},
    {$data["english"]},
    {$data["synonyms"]},
    {$data["japanese"]},
    {$data["type_id"]},
    {$data["episodes"]},
    {$data["premiered_year"]},
    {$data["premiered_seasonal"]},
    {$data["source_id"]},
    {$data["age_rating_id"]},
    {$data["preview"]},
    {$data["synopsis"]},
    {$data["synopsis2"]},
    {$data["aired_start"]},
    {$data["aired_end"]},
    {$data["status"]},
    {$data["mal_id"]},
    {$data["img"]},
    {$data["fansub"]}
  );";
    return Insert($sql, "default", true);
  }
}
/* ============================================================== */
/* Set/Save Functions End */
/* ============================================================== */



/* ============================================================== */
/* From here on, everything works. */
/* ============================================================== */


function getMyanimelist($id)
{
  echo "Get data:" . $id . "<br />";
  $types = ["English:", "Synonyms:", "Japanese:", "Type:", "Episodes:", "Status:", "Aired:", "Premiered:", "Broadcast:", "Producers:", "Licensors:", "Studios:", "Source:", "Genre:", "Genres:", "Themes:", "Theme:", "Demographic:", "Duration:", "Rating:", "Score:", "Popularity:", "Members:", "Favorites:"];
  $trash = [
    "https://myanimelist.net/anime/season/",
    "/anime/producer/",
    "/anime/genre/",
    "https://myanimelist.net/topanime.php?type=",
    "https://myanimelist.net/dbchanges.php?aid=" . $id . "&amp;t=producers"
  ];

  if (fGetCon("https://myanimelist.net/anime/" . $id, FALSE))
    $html = fGetCon("https://myanimelist.net/anime/" . $id, FALSE);
  elseif (fGetCon("https://myanimelist.net/manga/" . $id, FALSE))
    $html = fGetCon("https://myanimelist.net/manga/" . $id, FALSE);

  if ($html = str_get_html($html))
  {
    $json['success'] = TRUE;
    $json['anime']["mal_id"] = $id;
    if ($contentWrapper = $html->find('div#contentWrapper', 0))
    {
      if (isset($contentWrapper->find('div', 0)->find('h1', 0)->find('strong', 0)->plaintext))
        $json['anime']["title"] = $contentWrapper->find('div', 0)->find('h1', 0)->find('strong', 0)->plaintext;
      else
      if (isset($contentWrapper->find('div', 0)->find('h1', 0)->find('span', 0)->plaintext))
        $json['anime']["title"] = $contentWrapper->find('div', 0)->find('h1', 0)->find('span', 0)->plaintext;
      $content = $contentWrapper->find('div#content', 0);
      $json['anime']["img"] = $content->find('img', 0)->getAttribute('data-src');
      $json['anime']["information"] = $content->find('div.spaceit_pad');
      foreach ($json['anime']["information"] as $k => $v)
      {
        if (isset($v->find('span', 0)->innertext))
          $type = $v->find('span', 0)->innertext;
        $type = (!empty($type)) ? $type : $k;
        if (in_array($type, $types))
        {
          $type = strtolower(str_replace(":", "", $type));
          if ($v->find('a'))
            foreach ($v->find('a') as $k2 => $v2)
              $b[$type][str_replace("/" . str_replace(array(" ", "."), array("_", ""), $v2->plaintext), "", str_replace($trash, "", $v2->href))] = removeWhiteSpace(str_replace($types, "", $v2->plaintext));
          else
            $b[$type] = removeWhiteSpace(str_replace($types, "", $v->plaintext));
          if ($v->find('span.score-label', 0))
            $b[$type] = removeWhiteSpace(str_replace($types, "", $v->find('span.score-label', 0)->plaintext));
        }
        // Hiányzo kulcsok esetén a kovetkezo sort ki kell venni a megjegyzésbol. Ekkor minden érték belekerul. 
        //$b[$type] = removeWhiteSpace(htmlspecialchars($v->plaintext));
        //$b[$type] = "";
      }
      $json['anime']["information"] = $b;
      $json['anime']["information"]["ranked"] = $content->find('span.ranked', 0)->find('strong', 0)->plaintext;
      if ($pp = $contentWrapper->find('div.anime-detail-header-video', 0))
        $json['anime']["others"]["preview"] = explode("?", $content->find('div.video-promotion', 0)->find('a', 0)->href)[0];
      else
        $json['anime']["others"]["preview"] = "";

      if (isset($content->find('div.js-scrollfix-bottom-rel', 0)->find('table', 0)->find('p', 0)->plaintext))
        $json['anime']["others"]["synopsis"] =  removeWhiteSpace($content->find('div.js-scrollfix-bottom-rel', 0)->find('table', 0)->find('p', 0)->plaintext);

      if ($related =  $content->find('table.anime_detail_related_anime', 0))
        if ($related =  $related->find('a'))
          if (is_array($related) && !empty($related))
            foreach ($related as $key => $value)
            {
              if (textInText("anime", $value->href))
                $json['anime']["others"]["related"][explode("/", str_replace("/anime/", "", $value->href))[0]] = explode("/", str_replace("/anime/", "", $value->href))[1];
              if (textInText("manga", $value->href))
                $json['anime']["others"]["related"][explode("/", str_replace("/manga/", "", $value->href))[0]] = explode("/", str_replace("/manga/", "", $value->href))[1];
            }
    }
    if (isset($json['anime']["others"]["related"])) unset($json['anime']["others"]["related"]);
    if (is_array($json['anime']["information"]["type"])) $json['anime']["information"]["type"] = reset($json['anime']["information"]["type"]);

    if (isset($json['anime']["information"]["aired"]))
    {
      $aired = explode(" to ", $json['anime']["information"]["aired"]);
      if (isset($aired[0]))
        $json['anime']["information"]["airedStart"] = date("Y-m-d", strtotime($aired[0]));
      if (isset($aired[1]))
        $json['anime']["information"]["airedEnd"] = date("Y-m-d", strtotime($aired[1]));
    }
    if (isset($json['anime']["information"]["licensors"]))
    {
      $key = array_search('add some', $json['anime']["information"]["licensors"]);
      $json['anime']["information"]["licensors"][0] = "add some";
      unset($json['anime']["information"]["licensors"][$key]);
    }
    if (isset($json['anime']["information"]["studios"]))
    {
      $key = array_search('add some', $json['anime']["information"]["studios"]);
      $json['anime']["information"]["studios"][0] = "add some";
      unset($json['anime']["information"]["studios"][$key]);
    }
    if (isset($json['anime']["information"]["producers"]))
    {
      $key = array_search('add some', $json['anime']["information"]["producers"]);
      $json['anime']["information"]["producers"][0] = "add some";
      unset($json['anime']["information"]["producers"][$key]);
    }
    if (isset($json['anime']["information"]["premiered"]))
    {
      foreach ($json['anime']["information"]["premiered"] as $val)
        $aired = explode(" ", $val);

      $json['anime']["information"]["premieredSeasonal"] = $aired[0];
      $json['anime']["information"]["premieredYear"] = $aired[1];
    }
    $config["json"] = $json;

    $json["newData"] = setMyanimelistData($config);

    return $json;
  }
  else return ["success" => "false", "error" => "wrong id"];

  // A MAL-os 'Synonyms' rész ","-ként felbontva json-ként lesz elmentve.

}
function setMyanimelistData($config)
{
  $return = FALSE;
  if (isset($config["json"]["anime"]["information"]["theme"]))
    foreach ($config["json"]["anime"]["information"]["theme"] as $link => $name)
      if (!(issetGenre($name)))
        if (setGenre($name, $link, "theme") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["themes"]))
    foreach ($config["json"]["anime"]["information"]["themes"] as $link => $name)
      if (!(issetGenre($name)))
        if (setGenre($name, $link, "theme") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["genre"]))
    foreach ($config["json"]["anime"]["information"]["genre"] as $link => $name)
      if (!(issetGenre($name)))
        if (setGenre($name, $link, "genre") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["genres"]))
    foreach ($config["json"]["anime"]["information"]["genres"] as $link => $name)
      if (!(issetGenre($name)))
        if (setGenre($name, $link, "genre") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["demographic"]))
    foreach ($config["json"]["anime"]["information"]["demographic"] as $link => $name)
      if (!(issetGenre($name)))
        if (setGenre($name, $link, "demographic") && $return == FALSE)
          $return = TRUE;


  if (isset($config["json"]["anime"]["information"]["licensors"]))
    foreach ($config["json"]["anime"]["information"]["licensors"] as $link => $name)
      if (!(issetStudio($name)))
        if (setStudio($name, $link) && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["producers"]))
    foreach ($config["json"]["anime"]["information"]["producers"] as $link => $name)
      if (!(issetStudio($name)))
        if (setStudio($name, $link) && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["studios"]))
    foreach ($config["json"]["anime"]["information"]["studios"] as $link => $name)
      if (!(issetStudio($name)))
        if (setStudio($name, $link) && $return == FALSE)
          $return = TRUE;
  return $return;
}

function issetGenre($genre = "")
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__genres` WHERE `name` = '{$genre}' LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function setGenre($name, $link, $type)
{
  $link = intval($link);
  $sql = "INSERT INTO `mal__genres`(`name`, `link`, `type`) VALUES ('{$name}','{$link}','{$type}');";
  $return =  Insert($sql);
  unset($sql);
  return $return;
}

function issetStudio($studio = "")
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__studios` WHERE `name` = '{$studio}' LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function setStudio($name, $link)
{
  $link = intval($link);
  $sql = "INSERT INTO `mal__studios`(`name`, `link`) VALUES ('{$name}','{$link}');";
  $return =  Insert($sql);
  unset($sql);
  return $return;
}
/* ============================================================== */
/* Isset Functions Start */
/* ============================================================== */
function issetAnime($myanimelist)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__anime` WHERE `mal_id` = {$myanimelist} LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetAnimeType($id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__type` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetAnimeSource($id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__source` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetAnimeAgeRating($id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__age_rating` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetGenreID($link = 0)
{
  $link = intval($link);
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__genres` WHERE `link` = {$link} LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetStudioID($link = 0)
{
  $link = intval($link);
  $sql = "SELECT `id` FROM `mal__studios` WHERE `link` = {$link} LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["id"]) ? $return[0]["id"] : false;
}
function issetStudioTypeID($id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__studios_type` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
/* ============================================================== */
/* Isset Functions End */
/* ============================================================== */
function getTypeB($table, $name) // type; age_rating; source
{
  $sql = "SELECT `id` FROM `mal__{$table}` WHERE `name` = '{$name}' LIMIT 1;";
  $return =  Select($sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["id"]) ? $return[0]["id"] : false;
}
