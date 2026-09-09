<?php

define("VERSION", getchangelog()[0]["version"]);

$p = "";
$g = (isset($_SERVER['REQUEST_URI'])) ? explode("/", substr($_SERVER['REQUEST_URI'], 1)) : array();
$g = (empty($g)) ? "" : ((isset($g[$p])) ? $g[$p] : $g);

$newSite = ["fansub", "episodelistnew", "z"];
if (isset($g[0]) && !empty($g[0]) && in_array(strtolower($g[0]), $newSite))
{

  ini_set('display_errors', 1);
  ini_set('error_reporting', E_ALL);
  ini_set('display_startup_errors', 1);
  !defined("DS")                && define("DS",                 DIRECTORY_SEPARATOR);
  !defined("CURRENT_DIR")       && define("CURRENT_DIR",        dirname(__DIR__, 1) . DS);
  //!defined("CURRENT_DIR")       && define("CURRENT_DIR",        __DIR__ . DS);
  require_once(CURRENT_DIR . "html" . DS . "Newindex.php");

  exit;
}
$stat_delay = 30; // second
require_once("../Config/loadConfig.php");
require_once("OtherCode/allInMyFunctions.php");
require_once("OtherCode/urlFunction.php");
require_once("OtherCode/stringFunction.php");
require_once("OtherCode/fileFunction.php");
require_once("OtherCode/ConnectClass.php");
require_once("OtherCode/databaseFunction.php");
require_once("OtherCode/shd.php");
require_once("other/index-header.php");

$siteConfig = DbConfig::getSiteConfig();
define("BASEURL", "https://".$siteConfig['domain']."/");
define("FS_BUTTON", '[<a href="'.BASEURL.'FanSub/{{ID}}/{{NAME}}" target="_blank" class="link-succes">{{NAME}}</a>]');

$siteConfig = DbConfig::getSiteConfig();

$g = gpi();
if (!isset($g) || !is_array($g) || empty($g))
  $g = gpi2();

function getchangelog()
{
  return [
    [
      "date" => "2023.07.01.",
      "version" => "6.1.6",
      "change" => [
        "Az adatlapokra beépítésre került a Jikan API. Mellyel pótolva lettek a hiányzó adatok/boritóképek, a hibásak pedig javítva lettek. Emelett új adatokkal is bővitve lettek az adatlapok."
      ],
    ],
    [
      "date" => "2023.03.15.",
      "version" => "6.1.5",
      "change" => [
        "Szerveres videók lejátszási hibája javitva."
      ],
    ],
    [
      "date" => "2023.03.14.",
      "version" => "6.1.4",
      "change" => [
        "Szerveres videók lejátszási hibája javitva.",
        "Az oldalon található anime kártyák részlegesen (jellenleg csak a fansuboknál) megkulonboztető körvonalat kaptak a kurzor mozgatásánál.",
        "A fansub-ok oldala újra lett írva és ki lett bővitve a fansub által forditott animékkel."
      ],
    ],
    [
      "date" => "2023.03.14.",
      "version" => "6.0.2",
      "change" => [
        "A 'Szezonális' oldal kikerült az a navigációs menüből.",
        "Kivezetésre került a téma választó, az új téma a Darkly. A téma választó helyére az oldal verzió száma került."
      ],
    ],
    [
      "date" => "2023.03.13.",
      "version" => "6.0.0",
      "change" => [
        "Az eddigi verziók számának kerekitése után, ez lett a hivatalos \"első\" verzió."
      ],
    ]
  ];
}
function siteHome($conn)
{
  // Kezdőlap
  $template = template();
  $logged = logged();
  if ($logged == TRUE)
    $ViewsFolder = "Login";
  else
    $ViewsFolder = "Logout";
  $mal = Select($conn, "SELECT `datasheet`.`id`, `datasheet`.`title`, `mal__anime`.`img`, `datasheet`.`link`, `datasheet`.`datasheet` FROM `mal__anime` INNER JOIN `datasheet` ON `datasheet`.`id` = `mal__anime`.`id` ORDER BY RAND() ASC LIMIT 12;");
  $title = "Kezdőlap";

  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
  require_once("NewViews/home.phtml");
  require_once("NewViews/footer.phtml");

  $conn->close();
  exit();
}
function siteLogin($conn)
{
  // Login
  $template = template();
  $logged = logged();
  if ($logged == TRUE)
    $ViewsFolder = "Login";
  else
    $ViewsFolder = "Logout";

  if (isset($_POST["password"]) && isset($_POST["username"]) && !empty($_POST["password"]) && !empty($_POST["username"]))
  {
    $username = strip_tags(htmlspecialchars(RealEscapeStringNew($_POST['username'])));
    $password = hash('sha256', strip_tags(htmlspecialchars(RealEscapeStringNew($_POST['password']))));


    $sql = "SELECT `id` FROM `users` WHERE `username` = '{{username}}' && `password` = '{{Password}}' LIMIT 1;";
    $id  =  SelectNew(str_replace(["{{username}}", "{{Password}}"], [$username, $password], $sql));

    if (is_array($id) && !empty($id))
    {
      $id = $id[0]["id"];
      setcookie("userID", $id, time() + (86400 * 365), "/"); // 86400 = 1 day
      header("Location: " . BASEURL . "Home");
    }
    else
    {
      $data['error'] = 'Rossz Felhasználónév vagy Jelszó';
    }
  }

  $title = "Belépés";
  require_once("NewViews/header.phtml");
  require_once("Views/Public/{$ViewsFolder}/navbar.phtml");
  require_once("Views/Public/{$ViewsFolder}/left-side.phtml");
  require_once("Views/Public/{$ViewsFolder}/login.phtml");
  require_once("Views/Public/Universal/right-side.phtml");
  require_once("Views/Public/Universal/footer.phtml");
  $conn->close();
  exit();
}
function siteLogout($conn)
{
  // Logout
  setcookie("userID", "", time() - 3600, "/");
  $_COOKIE["userID"] = "";
  siteHome($conn);
}
function siteProfil($conn)
{
  // UserProfil
  $template = template();
  $logged = logged();
  if ($logged == TRUE)
    $ViewsFolder = "Login";
  else
    $ViewsFolder = "Logout";

  if (isset($_POST["password"]) && isset($_POST["username"]) && !empty($_POST["password"]) && !empty($_POST["username"]))
  {
    $username = strip_tags(htmlspecialchars(RealEscapeStringNew($_POST['username'])));
    $password = hash('sha256', strip_tags(htmlspecialchars(RealEscapeStringNew($_POST['password']))));


    $sql = "SELECT `id` FROM `users` WHERE `username` = '{{username}}' && `password` = '{{Password}}' LIMIT 1;";
    $id  =  SelectNew(str_replace(["{{username}}", "{{Password}}"], [$username, $password], $sql));

    if (is_array($id) && !empty($id))
    {
      $id = $id[0]["id"];
      setcookie("userID", $id, time() + (86400 * 365), "/"); // 86400 = 1 day
      header("Location: " . BASEURL . "Home");
    }
    else
    {
      $data['error'] = 'Rossz Felhasználónév vagy Jelszó';
    }
  }

  $title = "Belépés";
  require_once("NewViews/header.phtml");
  require_once("Views/Public/{$ViewsFolder}/navbar.phtml");
  require_once("Views/Public/{$ViewsFolder}/left-side.phtml");
  require_once("Views/Public/{$ViewsFolder}/login.phtml");
  require_once("Views/Public/Universal/right-side.phtml");
  require_once("Views/Public/Universal/footer.phtml");
  $conn->close();
  exit();
}





$template = findfilefromdir("Assets/template");
$template[] = $template[0];
unset($template[0]);


/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && isset($g[1]) && is_numeric($g[1]) && !empty($g[1]) && strtolower($g[0]) == "datasheet2")
{ // DataSheet New

  $DSBlock = '<span>[<a class="link-succes">{{TEXT}}</a>]</span>';

  $select = Select($conn, 'SELECT * FROM `datasheet` WHERE `datasheet`.`id` = "' . $g[1] . '" LIMIT 1');
  if (isset($select[0]) && !empty($select[0]))
  {
    $select = $select[0];

    $title = $select["title"];
    $select["anime"] = Select($conn, "SELECT `mal__anime`.`id`, `mal__anime`.`title`, `mal__anime`.`english`, `mal__anime`.`synonyms`, `mal__anime`.`japanese`, `mal__anime`.`episodes`, `mal__anime`.`score`, `mal__anime`.`members`, `mal__anime`.`ranked`, `mal__anime`.`favorites`, `mal__anime`.`preview`, `mal__anime`.`synopsis`, `mal__anime`.`synopsis2`, `mal__anime`.`img`, `mal__anime`.`popularity`, CONCAT(`mal__anime`.`aired_start`, ' - ', `mal__anime`.`aired_end`) AS 'aired', `mal__anime`.`status`, `mal__anime`.`description`, `mal__anime`.`create_date`, `mal__age_rating`.`name` AS `age_rating`, CONCAT( 'https://myanimelist.net/anime/', `mal__anime`.`mal_id`, '/', REPLACE ( `mal__anime`.`title`,' ','_') ) AS 'link', `mal__anime`.`premiered_year` AS 'year', REPLACE ( REPLACE ( REPLACE ( REPLACE (`mal__anime`.`premiered_seasonal`, 'Winter', 'Tél' ), 'Spring', 'Tavasz' ), 'Summer', 'Nyár' ), 'Fall', 'Ősz' ) AS 'season', `mal__type`.`name` AS 'type', `mal__source`.`name` AS 'source' FROM `mal__anime` INNER JOIN `mal__age_rating` ON `mal__age_rating`.`id` = `mal__anime`.`age_rating_id` INNER JOIN `mal__source` ON `mal__source`.`id` = `mal__anime`.`source_id` INNER JOIN `mal__type` ON `mal__type`.`id` = `mal__anime`.`type_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]} LIMIT 1;");
    if (isset($select["anime"][0]))
    {
      $GS = Select($conn, "SELECT `mal__anime`.`id`, `mal__genres`.`name` AS 'Genre', `mal__genres`.`type` AS 'GType' FROM `mal__anime` INNER JOIN `mal__anime__genres` ON `mal__anime__genres`.`anime_id` = `mal__anime`.`id` INNER JOIN `mal__genres` ON `mal__genres`.`link` = `mal__anime__genres`.`genre_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]};");
      if (isset($GS[0]))
        foreach ($GS as $value)
        {
          $value["Genre"] = str_replace("{{TEXT}}", $value["Genre"], $DSBlock);
          /*****************************************************************************************************/
          if ($value["GType"] == "theme")
            if (!isset($select["anime"][0]["theme"]) || !in_array($value["Genre"], $select["anime"][0]["theme"]))
              $select["anime"][0]["theme"][] = $value["Genre"];
          /*****************************************************************************************************/
          if ($value["GType"] == "genre")
            if (!isset($select["anime"][0]["genre"]) || !in_array($value["Genre"], $select["anime"][0]["genre"]))
              $select["anime"][0]["genre"][] = $value["Genre"];
          /*****************************************************************************************************/
          if ($value["GType"] == "demographic")
            if (!isset($select["anime"][0]["demographic"]) || !in_array($value["Genre"], $select["anime"][0]["demographic"]))
              $select["anime"][0]["demographic"][] = $value["Genre"];
          /*****************************************************************************************************/
        }
      $GS = Select($conn, "SELECT `mal__anime`.`id`, `mal__studios`.`name` AS 'Studio', `mal__studios_type`.`name` AS 'SType' FROM `mal__anime` INNER JOIN `mal__anime__studios` ON `mal__anime__studios`.`anime_id` = `mal__anime`.`id` INNER JOIN `mal__studios` ON `mal__studios`.`link` = `mal__anime__studios`.`studios_id` INNER JOIN `mal__studios_type` ON `mal__studios_type`.`id` = `mal__anime__studios`.`studios_type_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]};");
      if (isset($GS[0]))
        foreach ($GS as $value)
        {
          $value["Studio"] = str_replace("{{TEXT}}", $value["Studio"], $DSBlock);
          /*****************************************************************************************************/
          if ($value["SType"] == "Studios")
            if (!isset($select["anime"][0]["Studios"]) || !in_array($value["Studio"], $select["anime"][0]["Studios"]))
              $select["anime"][0]["Studios"][] = $value["Studio"];
          /*****************************************************************************************************/
          if ($value["SType"] == "Licensors")
            if (!isset($select["anime"][0]["Licensors"]) || !in_array($value["Studio"], $select["anime"][0]["Licensors"]))
              $select["anime"][0]["Licensors"][] = $value["Studio"];
          /*****************************************************************************************************/
          if ($value["SType"] == "Producers")
            if (!isset($select["anime"][0]["Producers"]) || !in_array($value["Studio"], $select["anime"][0]["Producers"]))
              $select["anime"][0]["Producers"][] = $value["Studio"];
          /*****************************************************************************************************/
        }
    }

    $select["anime"] = $select["anime"][0];

    //die(print_p(Select($conn, "SELECT `mal__anime`.`id`, `mal__anime`.`title`, `mal__anime`.`english`, `mal__anime`.`synonyms`, `mal__anime`.`japanese`, `mal__anime`.`episodes`, `mal__anime`.`score`, `mal__anime`.`members`, `mal__anime`.`ranked`, `mal__anime`.`favorites`, `mal__anime`.`preview`, `mal__anime`.`synopsis`, `mal__anime`.`synopsis2`, `mal__anime`.`img`, `mal__anime`.`popularity`, CONCAT(`mal__anime`.`aired_start`, ' - ', `mal__anime`.`aired_end`) AS 'aired', `mal__anime`.`status`, `mal__anime`.`description`, `mal__anime`.`create_date`, `mal__age_rating`.`name` AS `age_rating`, `mal__age_rating`.`name` AS `age_rating`, CONCAT( 'https://myanimelist.net/anime/', `mal__anime`.`mal_id`, '/', REPLACE ( `mal__anime`.`title`,' ','_') ) AS 'link', `mal__anime`.`premiered_year` AS 'year', REPLACE ( REPLACE ( REPLACE ( REPLACE (`mal__anime`.`premiered_seasonal`, 'Winter', 'Tél' ), 'Spring', 'Tavasz' ), 'Summer', 'Nyár' ), 'Fall', 'Ősz' ) AS 'season', `mal__type`.`name` AS 'type', `mal__source`.`name` AS 'source' FROM `mal__anime` INNER JOIN `mal__age_rating` ON `mal__age_rating`.`id` = `mal__anime`.`age_rating_id` INNER JOIN `mal__source` ON `mal__source`.`id` = `mal__anime`.`source_id` INNER JOIN `mal__type` ON `mal__type`.`id` = `mal__anime`.`type_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]} LIMIT 1;")));
    //die($select["myanimelist"]);
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
      $select["uploaders"] = getUploaders($conn, $uploaders);
    }
    else
      $select["uploaders"] = "";

    // 	print_p($uploaders);
    if ($select["datasheet"] == "0")
      $arr2 = NULL;
    else
      $arr2 = Select($conn, 'SELECT `datasheet`.`link` FROM `datasheet` WHERE `datasheet`.`save` LIKE "%' . $select["id"] . '%" && datasheet = 0 LIMIT 1');

    $select["episodelist"] = getEpisodelist2($conn, $select["save"], $select["datasheet"]);
    // print_p($select);
    require_once("NewViews/header.phtml");
    require_once("NewViews/navbar.phtml");
    require_once("NewViews/datasheet.phtml");
    require_once("NewViews/footer.phtml");
    // $sql = "UPDATE `statistic_meta` SET `open_datasheet`= `open_datasheet`+1, `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
    // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
    $conn->close();


    exit();
  }
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && isset($g[1]) && is_numeric($g[1]) && !empty($g[1]) && strtolower($g[0]) == "datasheet")
{ // DataSheet New

  error_reporting(E_ALL); // Error/Exception engine, always use E_ALL

  ini_set('display_errors', false); 
  $DSBlock = '<span>[<a class="link-succes">{{TEXT}}</a>]</span>';

  $select = Select($conn, 'SELECT * FROM `datasheet` WHERE `datasheet`.`id` = "' . $g[1] . '" LIMIT 1');
  if (isset($select[0]) && !empty($select[0]))
  {
    $select = $select[0];


    $title = $select["title"];
    $arrContextOptions = array(
      "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
      ),
    );

    //$response = file_get_contents("https://xani.me/jikan-rest/anime/{$select["myanimelist"]}/full?sfw=false", false, stream_context_create($arrContextOptions));
//    $response = file_get_contents("https://xani.me/jikan-rest/anime/{$select["myanimelist"]}/full?sfw=false", false, stream_context_create($arrContextOptions));

//    $response = (json_decode($response, true));
//    if (!isset($response["data"]))
//    {
      $response = file_get_contents("https://api.jikan.moe/v4/anime/{$select["myanimelist"]}/full?sfw=false", false, stream_context_create($arrContextOptions));
      $response = json_decode($response, true);
//    }
    



      $title = $response["data"]["title"];
      ini_set('display_errors', 1);
      ini_set('error_reporting', E_ALL);
      ini_set('display_startup_errors', 1);
      foreach (["producers", "licensors", "studios", "genres", "explicit_genres", "themes", "demographics"] as $x) {
        if (isset($response["data"][$x])) {
          foreach ($response["data"][$x] as $k => $v) {
            $xx[$x][] = '[<a class="link-succes">' . $v["name"] . '</a>]';
          }
        }
      }


      $info = [
        "Cím:"              => (!empty($response["data"]["title"]))? $response["data"]["title"]:"-",
        "Angol cím:"        => (!empty($response["data"]["title_english"]))? $response["data"]["title_english"]:"-",
        "Japán cím:"        => (!empty($response["data"]["title_japanese"]))? $response["data"]["title_japanese"]:"-",
        "Egyéb cím(ek):"    => (!empty($response["data"]["title_synonyms"]))? implode(", ", $response["data"]["title_synonyms"]):"-",
        "MyAnimeList:"      => (!empty($response["data"]["url"]))? '[<a href="' . $response["data"]["url"] . '" target="_blank" class="link-succes">link</a>]':'[<a class="link-danger">link</a>]',
        "Trailer:"          => (!empty($response["data"]["trailer"]["url"]))? '[<a href="' . $response["data"]["trailer"]["url"] . '" target="_blank" class="link-succes">link</a>]':'[<a class="link-danger">link</a>]',
        "Részek összesen:"  => (!empty($response["data"]["episodes"]))? $response["data"]["episodes"]:"?",
        "Szezon:"           => (!empty($response["data"]["year"]))? $response["data"]["year"] . " - " . str_replace(["winter", "spring", "summer", "fall"], ["Tél", "Tavasz", "Nyár", "Ősz"],$response["data"]["season"]):"-",
        "Típus:"            => (!empty($response["data"]["type"]))? $response["data"]["type"]:"-",
        "Besorolás:"        => (!empty($response["data"]["rating"]))? $response["data"]["rating"]:"-",
        "Forrás:"           => (!empty($response["data"]["source"]))? $response["data"]["source"]:"-",
        "Értékelés:"        => (!empty($response["data"]["score"]))? "10/" . $response["data"]["score"]:"10/?",
        //"Members:"        => (!empty($response["data"]["episodes"]))? number_format($response["data"]["members"],0,',',' '):0,
        "Studió:"           => (isset($xx["studios"]))                ?implode(", ", $xx["studios"]):"-",
        "Producer:"         => (isset($xx["producers"]))              ?implode(", ", $xx["producers"]):"-",
        "Kiadó:"            => (isset($xx["licensors"]))              ?implode(", ", $xx["licensors"]):"-",
        "Téma:"             => (isset($xx["themes"]))                 ?implode(", ", $xx["themes"]):"-",
        "Műfaj:"            => (isset($xx["genres"]))                 ?implode(", ", $xx["genres"]):"-",
        "Egyéb Műfaj:"      => (isset($xx["demographics"]))           ?implode(", ", $xx["demographics"]):"-",
        "Explicit Műfaj:"   => (isset($xx["explicit_genres"]))        ?implode(", ", $xx["explicit_genres"]):"-"
      ];
      foreach ($info as $k => $v)
        if (empty($v))
          unset($info[$k]);

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
        $select["uploaders"] = getUploaders($conn, $uploaders);
      }
      else
        $select["uploaders"] = "";

      // 	print_p($uploaders);
      if ($select["datasheet"] == "0")
        $arr2 = NULL;
      else
        $arr2 = Select($conn, 'SELECT `datasheet`.`link` FROM `datasheet` WHERE `datasheet`.`save` LIKE "%' . $select["id"] . '%" && datasheet = 0 LIMIT 1');

      $select["episodelist"] = getEpisodelist2($conn, $select["save"], $select["datasheet"]);
      // print_p($select);
      require_once("NewViews/header.phtml");
      require_once("NewViews/navbar.phtml");
      require_once("NewViews/datasheet.phtml");
      require_once("NewViews/footer.phtml");
    
    // $sql = "UPDATE `statistic_meta` SET `open_datasheet`= `open_datasheet`+1, `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
    // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
    $conn->close();


    exit();
  }
}
/****************************************************************** */
/*if (isset($g[0]) && !empty($g[0]) &&  strtolower($g[0]) == "nrss")
{
  header("Content-type: text/xml");
  echo trim(file_get_contents("https://nanashi.hu/rss/"));
  die;
}*/
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) &&  strtolower($g[0]) == "mrss")
{
  header("Content-type: text/xml");
  echo trim(file_get_contents("https://mahoufansub.hu/c/news/rss/"));
  die;
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) &&  strtolower($g[0]) == "newrss")
{
  header("Content-type: application/json");

  // Load xml data into xml data object
  $xmldata = simplexml_load_string(file_get_contents("https://mahoufansub.hu/c/news/rss/"));
  $xmldata = simplexml_load_string(file_get_contents("https://nanashi.hu/category/hirek/rss"));

  // Encode this xml data into json
  // using json_encode function
  $jsondata = json_encode($xmldata);

  // Display json data
  print_r($jsondata);




  die;
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) &&  strtolower($g[0]) == "arss")
{
  header("Content-type: text/xml");
  echo file_get_contents("https://astarosub.hu/rss");
  die;
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && isset($g[1]) && !empty($g[1]) && strtolower($g[0]) == "indaembed")
{

  $http = "https://";
  $embed = "embed.indavideo.hu/player/video/";
  $amf = "https://amfphp.indavideo.hu/SYm0json.php/player.playerHandler.getVideoData/";
  $amf = json_decode(file_get_contents($amf . $g[1]), true);

  //$g[1] = (isset($amf["data"]) && isset($amf["data"]["hash"])) ? $http . $embed . $amf["data"]["hash"] . "?autostart=0&hide=titleshare&hq=1&indavideo=none" : $g[1];
  if (isset($amf["data"]) && isset($amf["data"]["hash"])) {
  	$g[1] = $http . $embed . $amf["data"]["hash"] . "?autostart=0&hide=titleshare&hq=1&indavideo=none";
  } else {
  	$select = Select($conn, 'SELECT embed FROM links WHERE link = "' . $g[1] . '" LIMIT 1');
  	if (isset($select[0]) && !empty($select[0]) && isset($select[0]["embed"])) {
  		if (null !== $select[0]["embed"]) {
				$g[1] = $http . $embed . $select[0]["embed"] . "?autostart=0&hide=titleshare&hq=1&indavideo=none";
  		}
  	}
  }
  unset($amf);
?>
  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="UTF-8">
    <meta name="description" content="Free Web tutorials">
    <meta name="keywords" content="HTML, CSS, JavaScript">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Animem Video Player</title>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

      * {
        margin: 0;
        padding: 0;
        border: 0;
        box-sizing: border-box;
        outline: none;
      }

      html {
        font-family: 'Poppins', sans-serif;
      }

      body {
        background-color: #23272a;
      }
    </style>
  </head>

  <body>






    <div class="video" id="video">
      <div class="plyr">
        <div class="plyr__video-wrapper">
          <iframe id="player" src="<?= $g[1]; ?>" frameborder="0" allowfullscreen="" scrolling="no" style="width: 100%;display:block;"></iframe>

        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>




    <script>
      window.onload = jsonzx100();

      function jsonzx100() {
        GEBI('player').style.height = ((GEBI('player').offsetWidth / 16) * 9) + "px";
        GEBI('video').style.height = ((GEBI('video').offsetWidth / 16) * 9) + "px";
      }
      jQuery(window).resize(function() {
        GEBI('video').style.height = ((GEBI('video').offsetWidth / 16) * 9) + "px";
        GEBI('player').style.height = ((GEBI('player').offsetWidth / 16) * 9) + "px";
      });

      function GEBI(id) {
        if (typeof document.getElementById(id) === 'undefined' || document.getElementById(id) === null) return NULL;
        else return document.getElementById(id);
      }
    </script>
  </body>

  </html>
  <?php exit();
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && isset($g[1]) && is_numeric($g[1]) && !empty($g[1]) && strtolower($g[0]) == "episodelist")
{ // EpisodeList New+
  header("Content-Range: bytes */11111");
  ini_set('display_errors', 1);
  ini_set('error_reporting', E_ALL);
  ini_set('display_startup_errors', 1);

  $select = Select($conn, "SELECT 
        `datasheet`.`id`,
        `datasheet`.`myanimelist` AS 'mal_id',
        `mal__type`.`text`,
        `mal__age_rating`.`code`,
        `episodelist`.`title`,
        `episodelist`.`save`,
        `episodelist`.`episode_lang`
      FROM `episodelist`
      LEFT JOIN `datasheet` ON (`datasheet`.`id` = `episodelist`.`datasheet_id` && `datasheet`.`title` NOT LIKE '%Series%')
      LEFT JOIN `mal__anime` ON (`mal__anime`.`id` = `datasheet`.`id`)
      LEFT JOIN `mal__type` ON ( `mal__type`.`id` =`mal__anime`.`type_id`)
      LEFT JOIN `mal__age_rating` ON (`mal__age_rating`.`id` = `mal__anime`.`age_rating_id`)
      WHERE `episodelist`.`id` = {$g[1]} LIMIT 1;");
  //  print_p($select);
  $episode_lang = Select($conn, 'SELECT * FROM `episode_lang` ORDER BY `text`');
  foreach ($episode_lang as  $value)
    $episode_langs[$value["id"]] = $value["text"];

  if (isset($select[0]) && !empty($select[0]))
  {
    $select = $select[0];
    $title = $select["title"];

    $json = json_decode($select["save"], true);
    if (isset($json["_type"]))
    {
      $json2 = array();
      if (isset($json["_type"])) unset($json["_type"]);
      foreach ($json as $key => $value)
      {
        $link = Select($conn, "SELECT
              `uploaders`.`id`,
              `uploaders`.`name`,
              `links`.`links_type`,
        SUM(`statistic__links`.`viewed`) AS 'viewed',
          CONCAT(
              'https://',
              `links_type`.`link`,
              `links`.`link`
          ) AS 'link' FROM `links`
          LEFT JOIN `links_type` ON `links_type`.`id` = `links`.`links_type` 
          LEFT JOIN `statistic__links` ON `statistic__links`.`links_id` = `links`.`id`
          LEFT JOIN `uploaders` ON `uploaders`.`id` = `links`.`uploaders_id`
          WHERE `links`.`id` = " . $value . " LIMIT 1;");

        $replace = [
          "https://" => "//",
          "http://" => "//",
          "//mega.nz/file" => "//mega.nz/embed",
          "//embed.animem.org/Video/EmbedOta/On" => "//embed.animem.org/Video/EmbedOta",
          "//embed.animem.org/Video/EmbedOta" => "//embed.animem.org/Video/EmbedOta/",
          "//indavideo.hu/video/" => "//animem.org/IndaEmbed/",
          "//embed.indavideo.hu/video/" => "//animem.org/IndaEmbed/",
          "//Semmi" => "",
          "//redirect_48h" => "",
          "//redirect" => "",
        ];


        $link[0]["link"] = str_replace(
          array_keys(
            $replace
          ),
          $replace,
          $link[0]["link"]
        );
        if (textInText("xani.me", $link[0]["link"]))
          $link[0]["link"] = BASEURL."503.php";



        $episodeslist[] = [
          "title" => ((textInText(["rész", "ona", "ova", "movie", "special",], str_replace("ru00e9sz",  "rész", $key))) ? str_replace("ru00e9sz",  "rész", $key) : $key . $select["text"]) . " " . $episode_langs[$select["episode_lang"]],
          "link" => $link[0]["link"],
          "link_type" => $link[0]["links_type"],
          "viewed" => $link[0]["viewed"],
          "name" => ($link[0]["name"] == NULL) ? "Ideiglenesen Ismeretlen Forrás" : $link[0]["name"],
          "id" => ($link[0]["id"] == NULL) ? 369 : $link[0]["id"],
        ];
      }
    }

    //"https://x-anime.hu/api?get=fileList&mal=" . $select["mal_id"] . "&type=" . (($select["episode_lang"] == 1) ? "sync" : "def");
    //$tracks = (file_get_contents("https://x-anime.hu/api?get=fileList&mal=" . $select["mal_id"] . "&type=" . (($select["episode_lang"] == 1) ? "sync" : "def")));
    $tracks = (file_get_contents("https://".$siteConfig['videoDomain']."/api?get=fileList&mal=" . $select["mal_id"] . "&type=" . (($select["episode_lang"] == 1) ? "sync" : "def")));

    $new = true;
    require_once("NewViews/header.phtml");
    require_once("NewViews/navbar.phtml");
    require_once("NewViews/episode.phtml");
    require_once("NewViews/footer.phtml");

    $conn->close();
    exit();
  }
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && isset($g[1]) && is_numeric($g[1]) && !empty($g[1]) && strtolower($g[0]) == "video")
{ // Video New
  $sql = "SELECT 
      SUM(`statistic__links`.`viewed`) AS 'viewed', `links`.* FROM `links`
      LEFT JOIN `statistic__links` ON `statistic__links`.`links_id` = `links`.`id`
      WHERE (`links_type` = 1 || `links_type` = 2) &&  `links`.`id` = " . $g[1] . " LIMIT 1";

  $link = Select($conn, $sql);
  if (is_array($link) && !empty($link) && isset($link[0]))
  {
    $link = $link[0];
    $d =  Select($conn, "SELECT * FROM `uploaders` WHERE id = " . $link["uploaders_id"] . " LIMIT 1");
    $link["fname"] = $d[0]["name"];
    $link["color"] = "ckhaki";
    $title = $link["title"];
    require_once("NewViews/header.phtml");
    require_once("NewViews/navbar.phtml");
    require_once("Views/video.phtml");
  }
  else
  {
    $title = "404";
    require_once("NewViews/header.phtml");
    require_once("NewViews/navbar.phtml");
    require_once("view/error/404.phtml");
  }



  require_once("NewViews/footer.phtml");

  // $sql = "UPDATE `statistic_meta` SET `open_episodelist`= `open_episodelist`+1, `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && strtolower($g[0]) == "login")
{ // Login
  siteLogin($conn);
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && strtolower($g[0]) == "logout")
{ // Logout
  siteLogout($conn);
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && strtolower($g[0]) == "profil")
{ // profil
  siteProfil($conn);
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && strtolower($g[0]) == "seasonal")
{ // Seasonal New
  /****************************************************************** */ /****************************************************************** */
  if ((isset($g[1]) && $g[1] != "rss") || !isset($g[1]))
  {
    if (isset($g[1]) && !empty($g[1]))
    {
      $getday = $g[1];
      if (str_split($getday)[0] == "N")
      {
        $getday = str_replace("N", "", $getday);
        $getday = (int)$getday;
        if ($getday != 0)
          $getday = "-" . $getday;
        else
          $getday = $getday;
      }
      if (str_split($getday)[0] == "P")
      {
        $getday = str_replace("P", "", $getday);
        $getday = (int)$getday;
        $getday = $getday;
      }
    }
    else
      $getday = 0;
    for ($i = $getday - 3; $i <= $getday + 3; $i++)
    {
      $days[$i] = [
        "i" =>  $i,
        "name" => weekday($i),
        "min" => weekdaytime($i) + 1,
        "max" => weekdaytime($i + 1) - 1
      ];
      $buttonlist[$i] = weekday($i);
    }



    $day = Select($conn, "SELECT (SELECT SUM(`statistic__links`.`viewed`) AS 'viewed' FROM `statistic__links` WHERE `statistic__links`.`links_id` = `links`.`id`) AS 'viewed', `links`.*, `uploaders`.`name` AS 'fname', `uploaders`.`for_link_use`, `uploaders`.`collector` FROM `links`
    LEFT JOIN `uploaders` ON `uploaders`.`id` = `links`.`uploaders_id`
      WHERE
      (
        `links`.`links_type` = 1 ||
        `links`.`links_type` = 2
      ) &&
      (
        `links`.`create_date`
        BETWEEN '" . date("Y-m-d H:i:s", $days[$getday]["min"] - 7200) . "'
        AND '" . date("Y-m-d H:i:s", $days[$getday]["max"] - 7200) . "'
      ) &&
      (
        `links`.`title` IS NOT NULL &&
        `links`.`embed` IS NOT NULL &&
        `links`.`create_date` IS NOT NULL &&
        `links`.`uploaders_id` IS NOT NULL
      )
    ORDER BY `links`.`create_date`;");
    // print_p($days);

    if (is_array($day))
      foreach ($day as $k => $v)
      {
        if (empty($day[$k]["name"]))
          $day[$k]["name"] = $day[$k]["link"];
        else
          $day[$k]["name"] = myUrlDecode($day[$k]["name"]);
        $day[$k]["my_link"] = BASEURL."redirect2/https://indavideo.hu/video/" . $day[$k]["link"];

        $day[$k]["color"] = "caqua";
        if ($day[$k]["for_link_use"] == 1)
        {
          $day[$k]["my_link"] = BASEURL."redirect2/".BASEURL."video/" . $day[$k]["id"];
          $day[$k]["color"] = "ckhaki";
        }
      }


    $title = "Szezonális";
    require_once("NewViews/header.phtml");
    require_once("NewViews/navbar.phtml");
    require_once("Views/seasonal.phtml");
    require_once("NewViews/footer.phtml");
  }
  else
  {
    $sql = "SELECT * FROM `links` WHERE (`links_type` = 1 || `links_type` = 2) && (`title` IS NOT NULL && `embed` IS NOT NULL &&`create_date` IS NOT NULL && `create_date` != '0000-00-00 00:00:00'&& `uploaders_id` IS NOT NULL) ORDER BY `create_date` DESC LIMIT 50";
    $day = Select($conn, $sql);
    // print_p($days);

    if (is_array($day))
      foreach ($day as $k => $v)
      {
        if (empty($day[$k]["name"]))
          $day[$k]["name"] = $day[$k]["link"];
        else
          $day[$k]["name"] = myUrlDecode($day[$k]["name"]);
        $day[$k]["my_link"] = "https://indavideo.hu/video/" . $day[$k]["link"];
        $d  =  Select($conn, "SELECT * FROM `uploaders` WHERE id = " . $v["uploaders_id"] . " LIMIT 1");

        $day[$k]["color"] = "aqua";
        $day[$k]["fname"] = $d[0]["name"];
        if ($d[0]["for_link_use"] == 1)
        {
          $day[$k]["my_link"] = BASEURL."video/" . $day[$k]["id"];
          $day[$k]["color"] = "khaki";
        }
        if ($d[0]["for_link_use"] == 2 || $d[0]["collector"] == 1)
          unset($day[$k]);
      }
    header("Content-type: text/xml");
    echo "<?xml version='1.0' encoding='UTF-8' ?>"; ?>
    <rss version='2.0'>
      <channel>

        <title>Animem.org - Szezonális</title>
        <link><?= BASEURL ?>seasonal/rss/</link>
        <description>Animem.org az animék országa. Az animék országában szinte bármilyen animét megtalálhatsz. Legyen az szinkronos vagy feliratos. Mindezt a lehető legjobb minőségben.</description>
        <language>hu</language>
        <image>
          <url><?= BASEURL ?>seasonal/rss/themes/images/info/rss-icon.png</url>
          <title>Animem.org - Szezonális</title>
          <link><?= BASEURL ?>seasonal/rss/</link>
        </image>


        <?php
        // <pubDate><?= $value["create_date"]; ? ></pubDate>
        if (is_array($day))
          foreach ($day as $key => $value)
          {
        ?>

          <item>
            <title><?= str_replace("-", " ", $value["title"]); ?></title>
            <link><?= $value["my_link"]; ?></link>
            <guid isPermaLink='true'><?= $value["my_link"]; ?></guid>
            <description>
              Készítette a(z) <?= $value["fname"]; ?>.
            </description>
            <pubDate><?= $value["create_date"]; ?></pubDate>

          </item>
        <?php
          }



        ?>

      </channel>
    </rss>
  <?php



  }
  $conn->close();


  exit();
  /****************************************************************** */ /****************************************************************** */
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && strtolower($g[0]) == "anime")
{ // Anime New


  $title = "Animék 'A'-tól 'Z'-ig";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");

  $ch = [1 => "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "w", "v", "x", "y", "z"];
  $ch[] = 1;
  $ch[] = "Filmek";


  if (isset($g[1]) && !empty($g[1]) && is_numeric($g[1]))
  {
    $CharList = explode(", ", "A, B, C, D, E, F, G, H, I, J, K, L, M, N, O, P, Q, R, S, T, U, V, W, X, Y, Z");
    $PInfo = strtoupper($ch[$g[1]]);
    $Where = (in_array($PInfo, $CharList)
      ? "`datasheet`.`series` = 1 && `mal__anime`.title LIKE '" . $PInfo . "%' && `mal__anime`.title != ''"
      : ($PInfo == "ALL"
        ? "`datasheet`.`series` = 1 && `mal__anime`.title != ''"
        : ($PInfo == "FILMEK"
          ? "`mal__anime`.`type_id` = 2 && `mal__anime`.title != ''"
          : "`datasheet`.`series` = 1 && Upper(substr(`mal__anime`.title,1,1)) NOT in ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z') && `mal__anime`.title != ''"
        )
      )
    );


    $mal = Select($conn, "SELECT
      `datasheet`.`id`,
      `datasheet`.`link`,
      `datasheet`.`title`,
      `mal__anime`.`img`,
      `datasheet`.`series`,
      `datasheet`.`datasheet`
    FROM
        `mal__anime`
    INNER JOIN `datasheet` ON
        `datasheet`.`myanimelist` = `mal__anime`.`mal_id`
    WHERE
        " . $Where . "
    GROUP BY
        `datasheet`.`id`, `datasheet`.`title`, `mal__anime`.`img`
    ORDER BY
        `datasheet`.`title`;");
  }
  else
  {
    $mal = Select($conn, "SELECT `datasheet`.`id`, `datasheet`.`title`, `mal__anime`.`img`, `datasheet`.`link`, `datasheet`.`datasheet` FROM `mal__anime` INNER JOIN `datasheet` ON `datasheet`.`id` = `mal__anime`.`id` ORDER BY RAND() ASC LIMIT 12;");
  }

  require_once("Views/anime.phtml");
  require_once("NewViews/footer.phtml");
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "statistic")
{ // Statisztika New
  $statistic = "";
  $epc = Select($conn, "SELECT COUNT(*) as 'count' FROM `links`");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">' . $value["count"]  . ' videó található az oldalon.</h6>
        </div>';
  }
  $epc = Select($conn, "SELECT COUNT(*) as 'count' FROM `datasheet`");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">Kb. ' . $value["count"] * 2  . '. Anime található az oldalon.</h6>
        </div>';
  }
  $epc = Select($conn, "SELECT SUM(`viewed`) as 'count' FROM `statistic__links`; ");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">' . $value["count"] . ' db megtekintett anime.</h6>
        </div>';
  }
  $epc = Select($conn, "SELECT `other_user` as 'count1', `logged_user` as 'count2' FROM `statistic__visits_all` ORDER BY `date` DESC LIMIT 7;");
  $c = 0;
  foreach ($epc as $key => $value)
  {
    $c += $value["count1"] + $value["count2"];
  }
  $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">Elmult 7 nap oldal látogatottsága: ' . $c . ' fő</h6>
        </div>';
  $statistic = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $statistic;
  $statistic .= '</section>';



  $title = "Statisztika";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
  require_once("template/statistic.phtml");
  require_once("NewViews/footer.phtml");
  //  date("Y-m-d h:i:s", time()-$stat_delay)
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]))
{ // DataSheet

  $DSBlock = '<span>[<a class="link-succes">{{TEXT}}</a>]</span>';

  $select = Select($conn, 'SELECT * FROM `datasheet` WHERE `datasheet`.`link` = "' . RealEscapeString($g[0]) . '" LIMIT 1');
  if (isset($select[0]) && !empty($select[0]))
  {
    $select = $select[0];

    $title = $select["title"];
    $select["anime"] = Select($conn, "SELECT `mal__anime`.`id`, `mal__anime`.`title`, `mal__anime`.`english`, `mal__anime`.`synonyms`, `mal__anime`.`japanese`, `mal__anime`.`episodes`, `mal__anime`.`score`, `mal__anime`.`members`, `mal__anime`.`ranked`, `mal__anime`.`favorites`, `mal__anime`.`preview`, `mal__anime`.`synopsis`, `mal__anime`.`synopsis2`, `mal__anime`.`img`, `mal__anime`.`popularity`, CONCAT(`mal__anime`.`aired_start`, ' - ', `mal__anime`.`aired_end`) AS 'aired', `mal__anime`.`status`, `mal__anime`.`description`, `mal__anime`.`create_date`, `mal__age_rating`.`name` AS `age_rating`, CONCAT( 'https://myanimelist.net/anime/', `mal__anime`.`mal_id`, '/', REPLACE ( `mal__anime`.`title`,' ','_') ) AS 'link', `mal__anime`.`premiered_year` AS 'year', REPLACE ( REPLACE ( REPLACE ( REPLACE (`mal__anime`.`premiered_seasonal`, 'Winter', 'Tél' ), 'Spring', 'Tavasz' ), 'Summer', 'Nyár' ), 'Fall', 'Ősz' ) AS 'season', `mal__type`.`name` AS 'type', `mal__source`.`name` AS 'source' FROM `mal__anime` INNER JOIN `mal__age_rating` ON `mal__age_rating`.`id` = `mal__anime`.`age_rating_id` INNER JOIN `mal__source` ON `mal__source`.`id` = `mal__anime`.`source_id` INNER JOIN `mal__type` ON `mal__type`.`id` = `mal__anime`.`type_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]} LIMIT 1;");
    if (isset($select["anime"][0]))
    {
      $GS = Select($conn, "SELECT `mal__anime`.`id`, `mal__genres`.`name` AS 'Genre', `mal__genres`.`type` AS 'GType' FROM `mal__anime` INNER JOIN `mal__anime__genres` ON `mal__anime__genres`.`anime_id` = `mal__anime`.`id` INNER JOIN `mal__genres` ON `mal__genres`.`link` = `mal__anime__genres`.`genre_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]};");
      if (isset($GS[0]))
        foreach ($GS as $value)
        {
          $value["Genre"] = str_replace("{{TEXT}}", $value["Genre"], $DSBlock);
          /*****************************************************************************************************/
          if ($value["GType"] == "theme")
            if (!isset($select["anime"][0]["theme"]) || !in_array($value["Genre"], $select["anime"][0]["theme"]))
              $select["anime"][0]["theme"][] = $value["Genre"];
          /*****************************************************************************************************/
          if ($value["GType"] == "genre")
            if (!isset($select["anime"][0]["genre"]) || !in_array($value["Genre"], $select["anime"][0]["genre"]))
              $select["anime"][0]["genre"][] = $value["Genre"];
          /*****************************************************************************************************/
          if ($value["GType"] == "demographic")
            if (!isset($select["anime"][0]["demographic"]) || !in_array($value["Genre"], $select["anime"][0]["demographic"]))
              $select["anime"][0]["demographic"][] = $value["Genre"];
          /*****************************************************************************************************/
        }
      $GS = Select($conn, "SELECT `mal__anime`.`id`, `mal__studios`.`name` AS 'Studio', `mal__studios_type`.`name` AS 'SType' FROM `mal__anime` INNER JOIN `mal__anime__studios` ON `mal__anime__studios`.`anime_id` = `mal__anime`.`id` INNER JOIN `mal__studios` ON `mal__studios`.`link` = `mal__anime__studios`.`studios_id` INNER JOIN `mal__studios_type` ON `mal__studios_type`.`id` = `mal__anime__studios`.`studios_type_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]};");
      if (isset($GS[0]))
        foreach ($GS as $value)
        {
          $value["Studio"] = str_replace("{{TEXT}}", $value["Studio"], $DSBlock);
          /*****************************************************************************************************/
          if ($value["SType"] == "Studios")
            if (!isset($select["anime"][0]["Studios"]) || !in_array($value["Studio"], $select["anime"][0]["Studios"]))
              $select["anime"][0]["Studios"][] = $value["Studio"];
          /*****************************************************************************************************/
          if ($value["SType"] == "Licensors")
            if (!isset($select["anime"][0]["Licensors"]) || !in_array($value["Studio"], $select["anime"][0]["Licensors"]))
              $select["anime"][0]["Licensors"][] = $value["Studio"];
          /*****************************************************************************************************/
          if ($value["SType"] == "Producers")
            if (!isset($select["anime"][0]["Producers"]) || !in_array($value["Studio"], $select["anime"][0]["Producers"]))
              $select["anime"][0]["Producers"][] = $value["Studio"];
          /*****************************************************************************************************/
        }
    }

    $select["anime"] = $select["anime"][0];

    //die(print_p(Select($conn, "SELECT `mal__anime`.`id`, `mal__anime`.`title`, `mal__anime`.`english`, `mal__anime`.`synonyms`, `mal__anime`.`japanese`, `mal__anime`.`episodes`, `mal__anime`.`score`, `mal__anime`.`members`, `mal__anime`.`ranked`, `mal__anime`.`favorites`, `mal__anime`.`preview`, `mal__anime`.`synopsis`, `mal__anime`.`synopsis2`, `mal__anime`.`img`, `mal__anime`.`popularity`, CONCAT(`mal__anime`.`aired_start`, ' - ', `mal__anime`.`aired_end`) AS 'aired', `mal__anime`.`status`, `mal__anime`.`description`, `mal__anime`.`create_date`, `mal__age_rating`.`name` AS `age_rating`, `mal__age_rating`.`name` AS `age_rating`, CONCAT( 'https://myanimelist.net/anime/', `mal__anime`.`mal_id`, '/', REPLACE ( `mal__anime`.`title`,' ','_') ) AS 'link', `mal__anime`.`premiered_year` AS 'year', REPLACE ( REPLACE ( REPLACE ( REPLACE (`mal__anime`.`premiered_seasonal`, 'Winter', 'Tél' ), 'Spring', 'Tavasz' ), 'Summer', 'Nyár' ), 'Fall', 'Ősz' ) AS 'season', `mal__type`.`name` AS 'type', `mal__source`.`name` AS 'source' FROM `mal__anime` INNER JOIN `mal__age_rating` ON `mal__age_rating`.`id` = `mal__anime`.`age_rating_id` INNER JOIN `mal__source` ON `mal__source`.`id` = `mal__anime`.`source_id` INNER JOIN `mal__type` ON `mal__type`.`id` = `mal__anime`.`type_id` WHERE `mal__anime`.`mal_id` = {$select["myanimelist"]} LIMIT 1;")));
    //die($select["myanimelist"]);
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
      $select["uploaders"] = getUploaders($conn, $uploaders);
    }
    else
      $select["uploaders"] = "";

    // 	print_p($uploaders);
    if ($select["datasheet"] == "0")
      $arr2 = NULL;
    else
      $arr2 = Select($conn, 'SELECT `datasheet`.`link` FROM `datasheet` WHERE `datasheet`.`save` LIKE "%' . $select["id"] . '%" && datasheet = 0 LIMIT 1');

    $select["episodelist"] = getEpisodelist2($conn, $select["save"], $select["datasheet"]);
    // print_p($select);
    require_once("NewViews/header.phtml");
    require_once("NewViews/navbar.phtml");
    require_once("Views/datasheet.phtml");
    require_once("NewViews/footer.phtml");
    // $sql = "UPDATE `statistic_meta` SET `open_datasheet`= `open_datasheet`+1, `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
    // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
    $conn->close();


    exit();
  }
}
/****************************************************************** */
if (isset($g[0]) && strtolower($g[0]) == "discord")
{ // Discord
  $conn->close();


  header("Location: https://discord.gg/UDCszyA");
  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "redirect2")
{ // Redirect2
  $redirect = explode("redirect2/", $_SERVER["REQUEST_URI"]);
  $link = str_replace(BASEURL."video/", "", $redirect[1]);
  if (strlen($link) < strlen($redirect[1]))
  {
    /************************************************************************* */

    $result = $conn->query("SELECT `id` FROM `statistic__links` WHERE `links_id` = " . $link . " && (`date` BETWEEN '" . date("Y-m-d 00:00:00", time() - (60 * 60 * 24)) . "' AND '" . date("Y-m-d 00:00:00", time() + (60 * 60 * 24)) . "') LIMIT 1");
    if (isset($result->num_rows) && $result->num_rows > 0)
    {
      $row = $result->fetch_assoc();
      $stat_id = (isset($row["id"])) ? $row["id"] : 0;
    }
    else
    {
      $result = $conn->query("INSERT INTO `statistic__links`(`links_id`, `clicked`) VALUES ({$link},1)");
      $stat_id = 0;
    }
    /************************************************************************* */
    if ($stat_id != 0)
    {
      $conn->query("UPDATE `statistic__links` SET `clicked` = `clicked`+1 WHERE `id` = " . $stat_id . " LIMIT 1;");
    }

    /************************************************************************* */
  }
  else
  {
    $link = str_replace("https://indavideo.hu/video/", "", $redirect[1]);

    $result = $conn->query("SELECT `id` FROM `links` WHERE `link` = '" . $link .  "' LIMIT 1");
    /************************************************************************* */

    if (isset($result->num_rows) && $result->num_rows > 0)
    {
      $row = $result->fetch_assoc();
      $id = (isset($row["id"])) ? $row["id"] : 0;
      $result = $conn->query("SELECT `id` FROM `statistic__links` WHERE `links_id` = " . $id . " && (`date` BETWEEN '" . date("Y-m-d 00:00:00", time() - (60 * 60 * 24)) . "' AND '" . date("Y-m-d 00:00:00", time() + (60 * 60 * 24)) . "') LIMIT 1");
      if (isset($result->num_rows) && $result->num_rows > 0)
      {
        $row = $result->fetch_assoc();
        $stat_id = (isset($row["id"])) ? $row["id"] : 0;
      }
      else
      {
        $conn->query("INSERT INTO `statistic__links`(`links_id`, `clicked`) VALUES ({$id},1)");
        $stat_id = 0;
      }
      /************************************************************************* */
      if ($stat_id != 0)
      {
        $conn->query("UPDATE `statistic__links` SET `clicked` = `clicked`+1 WHERE `id` = " . $stat_id . " LIMIT 1;");
      }

      /************************************************************************* */
    }
  }

  $conn->close();


  if (isset($redirect[1]));
  exit(header("Location: " . $redirect[1]));
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "monogatari-szeria-utmutato")
{ // monogatari-szeria-utmutato
  $title = "Monogatari szeria útmutató";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
  ?>
  <div class="card bg-dark">
    <div class="card-header border-bottom">
      <h4 class="text-center text-white">Monogatari útmutató</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <img src="<?= BASEURL ?>Assets/uploads/fontos_kepek/monogatari_series_chronological_order.jpg" />
        <img src="<?= BASEURL ?>Assets/uploads/fontos_kepek/monogatari_series_watch_order.jpg" />
      </div>
    </div>
  </div>
<?php require_once("NewViews/footer.phtml");
  $conn->close();
  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "Mahou-Sensei-Negima-utmutato")
{ // Mahou-Sensei-Negima-utmutato
  $title = "Mahou-Sensei-Negima útmutató";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
?>
  <div class="card bg-dark">
    <div class="card-header border-bottom">
      <h4 class="text-center text-white">Mahou Sensei Negima útmutató</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <img src="<?= BASEURL ?>Assets/uploads/fontos_kepek/UQ_Holder_univerzum.jpg" />
      </div>
    </div>
  </div>

<?php require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "Fate-stay-night-Series-utmutato")
{ // Fate-stay-night-Series-utmutato
  $title = "Fate/stay night Series útmutató";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
?>
  <div class="card bg-dark">
    <div class="card-header border-bottom">
      <h4 class="text-center text-white">Fate/stay night Series útmutató</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <h4><a href="https://biblioteca.riczroninfactories.eu/?p=5977" class="bluuuu" target="_blank">Fate/Stay night Széria útmutató</a></h4>
        <img src="<?= BASEURL ?>Assets/uploads/fontos_kepek/fate_universe1.jpg" />
        <img src="<?= BASEURL ?>Assets/uploads/fontos_kepek/fate_universe2.jpg" />
      </div>
    </div>
  </div>

<?php require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && !empty($g[0]) && isset($g[1]) && is_numeric($g[1]) && !empty($g[1]) && strtolower($g[0]) == "template")
{
  if (isset($template[$g[1]]))
    setcookie("template", $g[1], time() + (86400 * 365), "/"); // 86400 = 1 day

  $conn->close();
  exit(header("Location: ".BASEURL));
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "toplista")
{ // Toplista
  $statistic = "";
  $epc = Select($conn, "SELECT COUNT(*) as 'count' FROM `links`");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">' . $value["count"]  . ' videó található az oldalon.</h6>
        </div>';
  }
  $epc = Select($conn, "SELECT COUNT(*) as 'count' FROM `datasheet`");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">Kb. ' . $value["count"] * 2  . '. Anime található az oldalon.</h6>
        </div>';
  }
  $epc = Select($conn, "SELECT SUM(`viewed`) as 'count' FROM `statistic__links`; ");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">' . $value["count"] . ' db megtekintett anime.</h6>
        </div>';
  }
  $epc = Select($conn, "SELECT SUM(`other_user`) as 'count1' , SUM(`logged_user`) as 'count2' FROM `statistic__visits_all` ORDER BY `date` DESC LIMIT 7;");
  foreach ($epc as $key => $value)
  {
    $c = $value["count1"] + $value["count2"];
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">Elmult 7 nap oldal látogatottsága: ' . $c . ' fő</h6>
        </div>';
  }
  $statistic = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $statistic;
  $statistic .= '</section>';



  $title = "Statisztika";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
  require_once("template/toplista.phtml");
  require_once("NewViews/footer.phtml");
  //  date("Y-m-d h:i:s", time()-$stat_delay)
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "allofnotliketheresz")
{ // SS
  $title = "allofnotliketheresz";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");


  $DS = Select($conn, "SELECT `id`, `title` FROM `episodelist` WHERE `save` NOT LIKE '%ru00e9sz%' && `save` NOT LIKE '%rész%';");
?>
  <div class="col-12">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card bg-dark">
          <div class="card-header border-bottom">
            <h4 class="text-center text-white">Lista azokról az animékről melyekben nem szerepel, hogy "rész".</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-6">
                <?php
                foreach ($DS as $key => $value)
                { ?>
                  <div class="row px-3">
                    <div class="col-12 mb-3">
                      <div class="row"><?php ?>
                        <div class="border col-2"><?= $value["id"]; ?>
                        </div>
                        <div class="border col-10"><a href="<?= BASEURL ?>admin/episode.php/?edit=<?= $value["id"]; ?>"><?= $value["title"]; ?></a>
                        </div>
                      </div>
                    </div>
                  </div>
              </div>
              <div class="col-6">
              <?php

                }
              ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php

  require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && strtolower($g[0]) == "changelog")
{ // SS
  $title = "ChangeLog";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");

  $changelog = getchangelog();
?>
  <div class="col-12">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card bg-dark">
          <div class="card-header border-bottom">
            <h4 class="text-center text-white">Animem changelog:</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-12">
                <ul class="">
                  <?php foreach ($changelog as $key => $value)
                  {
                  ?><li class="<?= ($key < count($changelog) - 1) ? "border-bottom" : ""; ?> mb-2">
                      <h5><?= $value["date"]; ?> - <?= $value["version"]; ?></h5>
                      <ul class=""><?php

                                    foreach ($value["change"] as $key2 => $value2)
                                    {
                                    ?><li class="<?= ($key2 = count($value["change"])) ? "mb-2" : ""; ?>">
                            <span><?= $value2; ?></span>
                          </li><?php
                                    } ?>
                      </ul>
                    </li><?php
                        } ?>




                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php

  require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "allofliketheshit")
{ // SS
  $title = "allofliketheshit";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");


  $DS = Select($conn, "SELECT `id`, `title` FROM `episodelist` WHERE `save` LIKE '%ru00e9sz%' || `save` LIKE '%rész%' || `save` LIKE '%ova%' || `save` LIKE '%ona%' || `save` LIKE '%movie%' || `save` LIKE '%special%';");
?>
  <div class="col-12">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card bg-dark">
          <div class="card-header border-bottom">
            <h4 class="text-center text-white">Lista azokról az animékről melyekben minden szutyok van.</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-6">
                <?php
                foreach ($DS as $key => $value)
                { ?>
                  <div class="row px-3">
                    <div class="col-12 mb-3">
                      <div class="row"><?php ?>
                        <div class="border col-2"><?= $value["id"]; ?>
                        </div>
                        <div class="border col-10"><a href="<?= BASEURL ?>admin/episode.php/?edit=<?= $value["id"]; ?>"><?= $value["title"]; ?></a>
                        </div>
                      </div>
                    </div>
                  </div>
              </div>
              <div class="col-6">
              <?php

                }
              ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php

  require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "allofliketheshit2")
{ // SS
  $title = "allofliketheshit";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");


  $DS = Select($conn, 'SELECT `id`, `title`, `save` FROM `datasheet` WHERE `title` NOT LIKE "%series%";');
  $EP = Select($conn, "SELECT `id`, `title` FROM `episodelist` WHERE `save` LIKE '%ru00e9sz%' || `save` LIKE '%rész%' || `save` LIKE '%ova%' || `save` LIKE '%ona%' || `save` LIKE '%movie%' || `save` LIKE '%special%';");
?>
  <div class="col-12">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card bg-dark">
          <?php
          $array = [];
          foreach ($DS as $key => $value)
          {
            $json = json_decode($value["save"], true);
            if (1 < count($json["links"]))
              echo '<a href="' . BASEURL."DataSheet/" . $value["id"] . '">' . $value["title"] . '</a><br />';


            unset($json);
          }

          ?>
        </div>
      </div>
    </div>
  </div>
<?php

  require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "allvideofromserver")
{ // SS


  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ini_set('error_reporting', E_ALL);
  ini_set('display_startup_errors', 1);
  error_reporting(1);


  $title = "allofliketheshit";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
  $array = [];
?>

  <div class="col-12">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card bg-dark">
          <div class="card-header border-bottom">
            <h4 class="text-center text-white">Lista azokról az animékről melyek teljes mértékben a szerveren találhatóak.</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-6">
                <?php
                $DS = Select($conn, "SELECT `id`, `title`, `save` FROM `episodelist`;");
                foreach ($DS as $key => $DSvalue)
                {
                  $save = json_decode($DSvalue["save"], true);
                  $links = [];
                  unset($save["_type"]);
                  foreach ($save as $value)
                  {
                    $links[] = $value;
                  }
                  //echo "SELECT `links_type` FROM `links` WHERE `id` IN (" . implode(", ", $links) . ");";
                  if (!empty($links))
                  {
                    $DS2 = Select($conn, "SELECT `links_type` FROM `links` WHERE `id` IN (" . implode(", ", $links) . ");");
                    $bool = true;
                    foreach ($DS2 as $DS2value)
                    {
                      $bool = ($bool != false && ($DS2value["links_type"] == 11 || $DS2value["links_type"] == 9)) ? true : false;
                    }
                    if ($bool == true)
                    {
                      $array[] = $DSvalue["id"];

                ?>

                      <div class="row px-3">
                        <div class="col-12 mb-3">
                          <div class="row"><?php ?>
                            <div class="border col-2"><?= $DSvalue["id"]; ?>
                            </div>
                            <div class="border col-10"><a href="<?= BASEURL ?>admin/episode.php/?edit=<?= $DSvalue["id"]; ?>"><?= $DSvalue["title"]; ?></a>
                            </div>
                          </div>
                        </div>
                      </div>
              </div>
              <div class="col-6">
          <?php
                    }
                  }
                }

                // echo implode(", ",$array);

          ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php

  require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "ss")
{ // SS
  $title = "ss";
  // https://chiaki.site/?/tools/watch_order/id/7472

  $array = [
    "07-Ghost" => 5525,
    "100-man no Inochi no Ue ni Ore wa Tatteiru" => 41380,
    "100-man no Inochi no Ue ni Ore wa Tatteiru 2nd Season" => 44881,
    "11eyes" => 6682,
    "180 Byou de Kimi no Mimi wo Shiawase ni Dekiru ka?" => 48858,
    "18if" => 35248,
    "3-gatsu no Lion" => 31646,
    "30-sai no Hoken Taiiku" => 9624,
    "3D Kanojo: Real Girl" => 36793,
    "3D Kanojo: Real Girl 2nd Season" => 37956,
    "4-nin wa Sorezore Uso wo Tsuku" => 51464,
    "5-toubun no Hanayome" => 38101,
    "5-toubun no Hanayome Movie" => 48548,
    "5-toubun no Hanayome ∬" => 39783,
    "86" => 41457,
    "86 Part 2" => 48569,
    "86 Special Edition: Senya ni Akaku Hinageshi no Saku" => 49235,
    "A.D. Police (TV)" => 1349,
    "Aa! Megami-sama!" => 49,
    "Aa! Megami-sama! (2011)" => 9611,
    "Aa! Megami-sama! (TV)" => 50,
    "Aa! Megami-sama! (TV) Specials" => 1003,
    "Aa! Megami-sama! Movie" => 304,
    "Aa! Megami-sama!: Sorezore no Tsubasa" => 880,
    "Aa! Megami-sama!: Sorezore no Tsubasa Specials" => 2198,
    "Aa! Megami-sama!: Tatakau Tsubasa" => 3090,
    "Absolute Duo" => 25397,
    "Accel World" => 11759,
    "Accel World: Acchel World." => 13859,
    "Acchi Kocchi" => 12291,
    "AD Police" => 1346,
    "Adachi to Shimamura" => 39790,
    "Afro Samurai" => 1292,
    "Afro Samurai Movie" => 13709,
    "Afro Samurai: Resurrection" => 4970,
    "Aharen-san wa Hakarenai" => 49520,
    "Ahiru no Sora" => 37403,
    "Aho Girl" => 34881,
    "Ai no Utagoe wo Kikasete" => 42847,
    "Ai to Yuuto: Hajimete no Otsukai" => 50711,
    "Air Gear" => 857,
    "Air Gear: Kuro no Hane to Nemuri no Mori - Break on the Sky" => 9201,
    "Aishen Qiaokeli-ing..." => 32323,
    "Aishen Qiaokeli-ing...II" => 36848,
    "Ajin" => 31580,
    "Ajin OVA" => 32015,
    "Ajin Part 1: Shoudou" => 30868,
    "Ajin Part 2" => 33253,
    "Ajin Part 2 OVA" => 36625,
    "Akagami no Shirayuki-hime" => 30123,
    "Akagami no Shirayuki-hime 2nd Season" => 31173,
    "Akagami no Shirayuki-hime: Nandemonai Takaramono, Kono Page" => 31483,
    "AkaKill! Gekijou" => 25241,
    "Akame ga Kill!" => 22199,
    "Akanesasu Shoujo" => 37561,
    "Akatsuki no Yona" => 25013,
    "Akatsuki no Yona OVA" => 30370,
    "Akebi-chan no Sailor-fuku" => 48553,
    "Aki-Sora" => 6987,
    "Akiba Maid Sensou" => 52193,
    "Akiba's Trip The Animation" => 34051,
    "Akikan no Tuna" => 52076,
    "Akudama Drive" => 41433,
    "Akuma no Riddle" => 19429,
    "Akuyaku Reijou nanode Last Boss wo Kattemimashita" => 49979,
    "Aldnoah.Zero" => 22729,
    "Aldnoah.Zero Part 2" => 27655,
    "Amaama to Inazuma" => 32828,
    "Amagami SS" => 8676,
    "Amagi Brilliant Park" => 22147,
    "Amagi Brilliant Park: Wakuwaku Mini Theater - Rakugaki Backstage" => 30056,
    "Ange Vierge" => 32171,
    "Angel Beats!" => 6547,
    "Ani x Para: Anata no Hero wa Dare desu ka" => 37290,
    "Ano Hi Mita Hana no Namae wo Bokutachi wa Mada Shiranai." => 9989,
    "Ano Hi Mita Hana no Namae wo Bokutachi wa Mada Shiranai. Movie" => 15039,
    "Another" => 11111,
    "Ansatsu Kyoushitsu" => 24833,
    "Ansatsu Kyoushitsu 2nd Season" => 30654,
    "Ansatsu Kyoushitsu: Deai no Jikan" => 28405,
    "Ansatsu Kyoushitsu: Jump Festa 2013 Special" => 19759,
    "Ao Ashi" => 49052,
    "Ao Haru Ride" => 21995,
    "Ao no Exorcist" => 9919,
    "Ao no Exorcist Movie" => 11737,
    "Ao no Exorcist: Kuro no Iede" => 11266,
    "Ao no Exorcist: Kyoto Fujouou-hen" => 33506,
    "Ao no Exorcist: Kyoto Fujouou-hen OVA" => 34465,
    "Ao no Exorcist: Ura Ex" => 10647,
    "Aoharu Snatch" => 52752,
    "Appare-Ranman!" => 40532,
    "Arad Senki: Slap Up Party" => 4657,
    "Arad: Suming Zhi Men" => 38413,
    "Arc the Lad" => 55,
    "Arifureta Itsuka" => 50091,
    "Arifureta Shokugyou de Sekai Saikyou" => 36882,
    "Arifureta Shokugyou de Sekai Saikyou 2nd Season" => 40507,
    "Arifureta Shokugyou de Sekai Saikyou 2nd Season Special" => 50916,
    "Arifureta Shokugyou de Sekai Saikyou Recap" => 40239,
    "Arifureta Shokugyou de Sekai Saikyou Specials" => 40083,
    "Arifureta Shokugyou de Sekai Saikyou: Prologue" => 46971,
    "Arknights: Reimei Zensou" => 50205,
    "Arte" => 40128,
    "Aru Asa Dummy Head Mic ni Natteita Ore-kun no Jinsei" => 52381,
    "Aru Zombie Shoujo no Sainan (ONA)" => 37735,
    "Asa made Jugyou Chu!" => 12581,
    "Asobi ni Iku yo!" => 6166,
    "Asobi ni Iku yo!: Asobi ni Oide" => 9618,
    "Asobi ni Iku yo!: Asobi ni Oide - Owari" => 29998,
    "Assassins Pride" => 38572,
    "Assault Lily: Bouquet" => 40550,
    "Asura Cryin'" => 5342,
    "Asura Cryin' 2" => 6676,
    "Atasha Kawashiri Kodama Da yo: Dangerous Lifehacker no Tadareta Seikatsu" => 49780,
    "Aura: Maryuuin Kouga Saigo no Tatakai" => 14669,
    "Ayane-chan High Kick!" => 2183,
    "Azumanga Daioh" => 66,
    "Azur Lane" => 38328,
    "Azur Lane: Bisoku Zenshin!" => 40930,
    "B-gata H-kei" => 7817,
    "B-Legend! Battle B-Daman" => 2789,
    "B: The Beginning" => 32827,
    "B: The Beginning Succession" => 37994,
    "Babylon" => 37525,
    "Baccano!" => 2251,
    "Back Arrow" => 40964,
    "Baka to Test to Shoukanjuu" => 6347,
    "Baka to Test to Shoukanjuu Ni!" => 8516,
    "Baka to Test to Shoukanjuu Specials" => 7870,
    "Baka to Test to Shoukanjuu: Matsuri" => 9471,
    "Bakemono no Ko" => 28805,
    "Bakemonogatari" => 5081,
    "Bakumatsu" => 37584,
    "Bakuon!!" => 30795,
    "Bakuon!! no Kobeya" => 34623,
    "Bakuon!! OVA" => 31883,
    "Bakuretsu Tenshi" => 109,
    "Bakuretsu Tenshi: Infinity" => 2205,
    "Bakuten!!" => 43756,
    "Ballroom e Youkoso" => 34636,
    "Banana Fish" => 36649,
    "BanG Dream!" => 33573,
    "BanG Dream! 2nd Season" => 37869,
    "BanG Dream! 3rd Season" => 37870,
    "BanG Dream! 5th Anniversary Animation: CiRCLE Thanks Party!" => 51218,
    "BanG Dream! Film Live" => 39619,
    "BanG Dream! Garupa☆Pico" => 37873,
    "BanG Dream! Morfonication" => 51989,
    "BanG Dream! Movie: Episode of Roselia - I: Yakusoku" => 41780,
    "BanG Dream! Movie: Episode of Roselia - II: Song I Am." => 41781,
    "BanG Dream!: Asonjatta!" => 34870,
    "Baraou no Souretsu" => 42892,
    "Basilisk: Kouga Ninpou Chou" => 67,
    "Beastars" => 39195,
    "Beastars 2nd Season" => 40935,
    "Beatless" => 36516,
    "Beelzebub" => 9513,
    "Beelzebub: Hirotta Akachan wa Daimaou!?" => 9120,
    "Bem" => 39221,
    "Berserk " => 2,
    "Berserk: Ougon Jidai-hen - Memorial Edition" => 52976,
    "Big Order (TV)" => 31904,
    "Bikini Warriors" => 30782,
    "Birdie Wing: Golf Girls&#039; Story" => 50248,
    "Bishoujo Senshi Sailor Moon" => 530,
    "Bishoujo Senshi Sailor Moon R" => 740,
    "Bishoujo Senshi Sailor Moon: Sailor Stars" => 996,
    "Bishounen Tanteidan" => 40752,
    "Black Blood Brothers" => 1498,
    "Black Bullet" => 20787,
    "Black Clover" => 34572,
    "Black Fox" => 37498,
    "Black Lagoon" => 889,
    "Black Lagoon Omake" => 8440,
    "Black Lagoon: Roberta's Blood Trail" => 4901,
    "Black Lagoon: The Second Barrage" => 1519,
    "Black★Rock Shooter (OVA)" => 7059,
    "Black★Rock Shooter (TV)" => 11285,
    "Black★★Rock Shooter: Dawn Fall" => 49831,
    "Blade" => 6920,
    "Blade & Soul" => 22547,
    "Blade & Soul Specials" => 23781,
    "Blade of the Immortal" => 4151,
    "Blade Runner: Black Lotus" => 38749,
    "Blade Runner: Black Out 2022" => 36308,
    "Blassreiter" => 3407,
    "Bleach" => 269,
    "Bleach Movie 1: Memories of Nobody" => 1686,
    "Bleach Movie 2: The DiamondDust Rebellion - Mou Hitotsu no Hyourinmaru" => 2889,
    "Bleach Movie 3: Fade to Black - Kimi no Na wo Yobu" => 4835,
    "Bleach Movie 4: Jigoku-hen" => 8247,
    "Bleach: Memories in the Rain" => 762,
    "Bleach: Sennen Kessen-hen" => 41467,
    "Bleach: The Sealed Sword Frenzy" => 834,
    "Blend S" => 34618,
    "Blood Lad" => 11633,
    "Blood+" => 150,
    "Blood-C" => 10490,
    "Bloodivores" => 33985,
    "Blue Dragon" => 2142,
    "Blue Gender" => 58,
    "Blue Lock" => 49596,
    "Blue Period" => 46352,
    "Blue Reflection Ray" => 47639,
    "BNA" => 40060,
    "Bocchi the Rock!" => 47917,
    "Boku dake ga Inai Machi" => 31043,
    "Boku ga Aishita Subete no Kimi e" => 49834,
    "Boku ni Sexfriend ga Dekita Riyuu" => 52402,
    "Boku no Hero Academia" => 31964,
    "Boku no Hero Academia (ONA)" => 51781,
    "Boku no Hero Academia 2nd Season" => 33486,
    "Boku no Hero Academia 3rd Season" => 36456,
    "Boku no Hero Academia 4th Season" => 38408,
    "Boku no Hero Academia 5th Season" => 41587,
    "Boku no Hero Academia 6th Season" => 49918,
    "Boku no Hero Academia the Movie 1: Futari no Hero" => 36896,
    "Boku no Hero Academia the Movie 2: Heroes:Rising" => 39565,
    "Boku no Hero Academia the Movie 3: World Heroes&#039; Mission" => 44200,
    "Boku no Hero Academia the Movie 3: World Heroes&#039; Mission - Tabidachi" => 50532,
    "Boku no Hero Academia the Movie: Futari no Hero Specials" => 38699,
    "Boku no Hero Academia: Ikinokore! Kesshi no Survival Kunren" => 42603,
    "Boku no Hero Academia: Sukue! Kyuujo Kunren!" => 33929,
    "Boku no Hero Academia: Training of the Dead" => 35459,
    "Boku no Kanojo ga Majimesugiru Sho-bitch na Ken" => 35712,
    "Boku wa Tomodachi ga Sukunai" => 10719,
    "Boku wa Tomodachi ga Sukunai Next" => 14967,
    "Boku wa Tomodachi ga Sukunai: Relay Shousetsu wa Ketsumatsu ga Hanpanai" => 14027,
    "Boku wa Tomodachi ga Sukunai: Yaminabe wa Bishoujo ga Zannen na Nioi" => 10897,
    "Bokura no Live Kimi to no Life" => 9907,
    "Bokura no Yoake" => 51307,
    "Bokutachi no Remake" => 40904,
    "Bokutachi wa Benkyou ga Dekinai" => 38186,
    "Bokutachi wa Benkyou ga Dekinai!" => 40004,
    "Bokutachi wa Benkyou ga Dekinai!: Chapel no Kane wa [X] wo Shukufuku Suru" => 42519,
    "Bokutachi wa Benkyou ga Dekinai: Nagisa ni Usemono Arite Senjin wa Enzen to [X] Suru" => 39819,
    "Bonobono (TV 2016)" => 32353,
    "Boogiepop wa Warawanai (2019)" => 37451,
    "Boruto: Jump Festa 2016 Special" => 35072,
    "Boruto: Naruto Next Generations" => 34566,
    "Boruto: Naruto the Movie - Naruto ga Hokage ni Natta Hi" => 32365,
    "Brave 10" => 11241,
    "Brave Witches" => 32866,
    "Brave Witches: Petersburg Daisenryaku" => 34644,
    "Breakers" => 40850,
    "Btooom!" => 14345,
    "Bubblegum Crisis" => 1347,
    "Bubuki Buranki" => 32023,
    "Bubuki Buranki: Hoshi no Kyojin" => 33041,
    "Bucchigire!" => 51371,
    "Bungou Stray Dogs" => 31478,
    "Bungou Stray Dogs 2nd Season" => 32867,
    "Bungou Stray Dogs 3rd Season" => 38003,
    "Busou Shoujo Machiavellianism" => 33475,
    "C Danchi" => 51306,
    "Caligula (TV)" => 36828,
    "Campione!: Matsurowanu Kamigami to Kamigoroshi no Maou" => 12293,
    "Cap Kakumei Bottleman DX" => 51155,
    "Captain Tsubasa" => 2116,
    "Captain Tsubasa (2018)" => 36934,
    "Captain Tsubasa: Road to 2002" => 1614,
    "Cardfight!! Vanguard" => 9539,
    "Cardfight!! Vanguard (2018)" => 37476,
    "Cardfight!! Vanguard G" => 27815,
    "Cardfight!! Vanguard G: GIRS Crisis-hen" => 31196,
    "Cardfight!! Vanguard G: Next" => 34077,
    "Cardfight!! Vanguard G: Stride Gate-hen" => 32802,
    "Cardfight!! Vanguard G: Z" => 36022,
    "Cardfight!! Vanguard Movie: Neon Messiah" => 23991,
    "Cardfight!! Vanguard: Asia Circuit-hen" => 13145,
    "Cardfight!! Vanguard: Legion Mate-hen" => 21729,
    "Cardfight!! Vanguard: Link Joker-hen" => 15611,
    "Cardfight!! Vanguard: will+Dress" => 49819,
    "Cardfight!! Vanguard: Zoku Koukousei-hen" => 39244,
    "Carnival Phantasm" => 10012,
    "Carnival Phantasm EX Season" => 12187,
    "Carnival Phantasm: HibiChika Special" => 15927,
    "Carole & Tuesday" => 37435,
    "Cello Hiki no Gauche (1982)" => 1049,
    "Cestvs: The Roman Fighter" => 43763,
    "Chain Chronicle: Haecceitas no Hikari" => 28833,
    "Chainsaw Man" => 44511,
    "Changye Kaita Zhe" => 50446,
    "Charlotte" => 28999,
    "Cheat Kusushi no Slow Life: Isekai ni Tsukurou Drugstore" => 40960,
    "Cheating Craft" => 33771,
    "Chibi Maruko-chan (1995)" => 6149,
    "Chihayafuru" => 10800,
    "Chihayafuru 2" => 14397,
    "Chihayafuru 2: Waga Miyo ni Furu Nagame Shima ni" => 18745,
    "Chihayafuru 3" => 37379,
    "Chihayafuru 3: Ima Hitotabi no" => 41105,
    "Chiikawa" => 50250,
    "Chio-chan no Tsuugakuro" => 35821,
    "Chobits" => 59,
    "Chobits Recap" => 1311,
    "Chobits: Chibits" => 596,
    "Chocotan!" => 18599,
    "Chou Yuu Sekai: Being the Reality" => 34675,
    "Choujin Koukousei-tachi wa Isekai demo Yoyuu de Ikinuku you desu!" => 39523,
    "Chuan Shu Zijiu Zhinan" => 38990,
    "Chuunibyou demo Koi ga Shitai!" => 14741,
    "Chuunibyou demo Koi ga Shitai! Lite" => 15687,
    "Chuunibyou demo Koi ga Shitai! Movie: Take On Me" => 35608,
    "Chuunibyou demo Koi ga Shitai! Ren" => 18671,
    "Chuunibyou demo Koi ga Shitai! Ren Lite" => 21797,
    "Chuunibyou demo Koi ga Shitai! Ren Specials" => 23237,
    "Chuunibyou demo Koi ga Shitai! Ren: The Rikka Wars" => 27601,
    "Chuunibyou demo Koi ga Shitai!: Depth of Field - Ai to Nikushimi Gekijou" => 15879,
    "Chuunibyou demo Koi ga Shitai!: Kirameki no... Slapstick Noel" => 16934,
    "Cike Wu Liuqi" => 38409,
    "Circlet Princess" => 38157,
    "Citrus" => 34382,
    "Clannad" => 2167,
    "Clannad Movie" => 1723,
    "Clannad: After Story" => 4181,
    "Clannad: After Story - Mou Hitotsu no Sekai, Kyou-hen" => 6351,
    "Clannad: Mou Hitotsu no Sekai, Tomoyo-hen" => 4059,
    "Claymore" => 1818,
    "Clockwork Planet" => 32407,
    "Code Geass: Boukoku no Akito 1 - Yokuryuu wa Maiorita" => 8888,
    "Code Geass: Boukoku no Akito 2 - Hikisakareshi Yokuryuu" => 15197,
    "Code Geass: Boukoku no Akito 3 - Kagayaku Mono Ten yori Otsu" => 15199,
    "Code Geass: Boukoku no Akito 4 - Nikushimi no Kioku kara" => 15201,
    "Code Geass: Boukoku no Akito 5 - Itoshiki Mono-tachi e" => 30711,
    "Code Geass: Fukkatsu no Lelouch" => 34437,
    "Code Geass: Hangyaku no Lelouch" => 1575,
    "Code Geass: Hangyaku no Lelouch - Nunnally in Wonderland" => 12685,
    "Code Geass: Hangyaku no Lelouch I - Koudou" => 34438,
    "Code Geass: Hangyaku no Lelouch II - Handou" => 34439,
    "Code Geass: Hangyaku no Lelouch III - Oudou" => 34440,
    "Code Geass: Hangyaku no Lelouch R2" => 2904,
    "Code Geass: Soubou no Oz Picture Drama" => 17277,
    "Cool Doji Danshi" => 51680,
    "Cop Craft" => 38940,
    "Corpse Party: Tortured Souls - Bougyakusareta Tamashii no Jukyou" => 15037,
    "Cowboy Bebop" => 1,
    "Cowboy Bebop: Tengoku no Tobira" => 5,
    "Crayon Shin-chan" => 966,
    "Cross Ange: Tenshi to Ryuu no Rondo" => 25731,
    "Cyclops Shoujo Saipuu" => 17397,
    "D.Gray-man" => 1482,
    "D.Gray-man Hallow" => 32370,
    "D.I.C.E." => 2285,
    "D.N.Angel" => 61,
    "D4DJ: First Mix" => 39681,
    "Da Wang Rao Ming" => 44406,
    "Dagashi Kashi" => 31636,
    "Dagashi Kashi 2" => 36049,
    "Dakara Boku wa, H ga Dekinai." => 12549,
    "Dakaretai Otoko 1-i ni Odosarete Imasu." => 37597,
    "Danball Senki" => 7081,
    "Danball Senki W" => 12651,
    "Dance Dance Danseur" => 48702,
    "Dance in the Vampire Bund" => 6747,
    "Danganronpa: Kibou no Gakuen to Zetsubou no Koukousei The Animation" => 16592,
    "Danna ga Nani wo Itteiru ka Wakaranai Ken" => 26349,
    "Danshi Koukousei no Nichijou" => 11843,
    "Dantalian no Shoka" => 8915,
    "Darker than Black: Kuro no Keiyakusha" => 2025,
    "Darling in the FranXX" => 35849,
    "Darwin's Game" => 38656,
    "Date A Bullet: Dead or Bullet" => 40416,
    "Date A Bullet: Nightmare or Queen" => 42423,
    "Date A Live" => 15583,
    "Date A Live II" => 19163,
    "Date A Live III" => 36633,
    "Date A Live IV" => 41461,
    "Date A Live Movie: Mayuri Judgment" => 24655,
    "Date A Live: Date to Date" => 17641,
    "Days (TV)" => 32494,
    "Deadman Wonderland" => 6880,
    "Deaimon" => 48779,
    "Death Billiards" => 14353,
    "Death March kara Hajimaru Isekai Kyousoukyoku" => 34497,
    "Death Note" => 1535,
    "Death Parade" => 28223,
    "Deatte 5-byou de Battle" => 43814,
    "Deca-Dence" => 40056,
    "Deep Insanity: The Lost Child" => 49292,
    "Deji Meets Girl" => 49505,
    "Delicious Party♡Precure" => 50281,
    "Deltora Quest" => 1826,
    "Densetsu no Yuusha no Densetsu" => 8086,
    "Detective Conan" => 235,
    "Detective Conan Movie 01: The Timed Skyscraper" => 779,
    "Detective Conan Movie 02: The Fourteenth Target" => 780,
    "Detective Conan Movie 03: The Last Wizard of the Century" => 781,
    "Detective Conan Movie 04: Captured in Her Eyes" => 1363,
    "Detective Conan Movie 05: Countdown to Heaven" => 1364,
    "Detective Conan Movie 06: The Phantom of Baker Street" => 1365,
    "Detective Conan Movie 07: Crossroad in the Ancient Capital" => 1366,
    "Detective Conan Movie 08: Magician of the Silver Sky" => 1367,
    "Detective Conan Movie 09: Strategy Above the Depths" => 1505,
    "Detective Conan Movie 10: Requiem of the Detectives" => 1506,
    "Detective Conan Movie 11: Jolly Roger in the Deep Azure" => 2171,
    "Detective Conan Movie 12: Full Score of Fear" => 4447,
    "Detective Conan OVA 01: Conan vs. Kid vs. Yaiba" => 1369,
    "Detective Conan OVA 02: 16 Suspects" => 2512,
    "Detective Conan OVA 03: Conan and Heiji and the Vanished Boy" => 2513,
    "Detective Conan OVA 04: Conan and Kid and Crystal Mother" => 2514,
    "Detective Conan OVA 05: The Target is Kogoro! The Detective Boys&#039; Secret Investigation" => 2515,
    "Detective Conan OVA 06: Follow the Vanished Diamond! Conan &amp; Heiji vs. Kid!" => 1368,
    "Detective Conan OVA 07: A Challenge from Agasa! Agasa vs. Conan and the Detective Boys" => 2597,
    "Detective Conan OVA 08: High School Girl Detective Sonoko Suzuki&#039;s Case Files" => 6198,
    "Detective Conan OVA 09: The Stranger in 10 Years..." => 6438,
    "Detective Conan OVA 10: Kid in Trap Island" => 8609,
    "Detective Conan OVA 11: A Secret Order from London" => 10703,
    "Detective Conan OVA 12: The Miracle of Excalibur" => 13839,
    "Detroit Metal City" => 3702,
    "Devil May Cry" => 1726,
    "Devil Survivor 2 The Animation" => 16512,
    "Devilman: Crybaby" => 35120,
    "Devils Line" => 35928,
    "Di Wang Gong Lue" => 37563,
    "Dies Irae" => 32271,
    "Digimon Adventure" => 552,
    "Digimon Ghost Game" => 49515,
    "Dimension W" => 31163,
    "Dinghai Fusheng Lu" => 44065,
    "Divine Gate" => 31710,
    "Do It Yourself!!" => 48542,
    "Dogeza de Tanondemita" => 42571,
    "Dokyuu Hentai HxEros" => 40623,
    "Dokyuu Hentai HxEros OVA" => 42803,
    "Dolls' Frontline" => 46604,
    "Domestic na Kanojo" => 37982,
    "Doraemon (2005)" => 8687,
    "Dorohedoro" => 38668,
    "Dororo" => 37520,
    "Dororo Pilot" => 11471,
    "Doukyonin wa Hiza, Tokidoki, Atama no Ue." => 38145,
    "Douluo Dalu" => 37150,
    "Dr. Stone" => 38691,
    "Dr. Stone: Stone Wars" => 40852,
    "Dragon Ball" => 223,
    "Dragon Crisis!" => 9330,
    "Dragon Quest: Dai no Daibouken (2020)" => 40906,
    "Dragon, Ie wo Kau." => 40526,
    "Drifters" => 31339,
    "Duel Masters" => 1685,
    "Duel Masters Win" => 52460,
    "Dumbbell Nan Kilo Moteru?" => 39026,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka" => 28121,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka Gaiden: Sword Oratoria" => 32887,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka II" => 37347,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka II OVA" => 40453,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka III" => 40454,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka III OVA" => 44983,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka IV: Shin Shou - Meikyuu-hen" => 47164,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka Movie: Orion no Ya" => 37348,
    "Dungeon ni Deai wo Motomeru no wa Machigatteiru Darou ka OVA" => 32801,
    "Durarara!!" => 6746,
    "D_Cide Traumerei the Animation" => 48470,
    "Ebiten: Kouritsu Ebisugawa Koukou Tenmonbu" => 14073,
    "Edens Zero" => 42192,
    "Egao no Daika" => 38544,
    "Eien no 831" => 49532,
    "Eiken: Eikenbu yori Ai wo Komete" => 788,
    "Elfen Lied" => 226,
    "Emiya-san Chi no Kyou no Gohan" => 37033,
    "Endride" => 32608,
    "Endro~!" => 38062,
    "Enen no Shouboutai" => 38671,
    "Engage Kiss" => 51417,
    "Entotsu Machi no Poupelle" => 35609,
    "Eoneu Nal Jameseo Kkaeeoboni Bagelyeoga Doeeo Isseotda" => 43779,
    "Eromanga-sensei" => 32901,
    "Eroriman 2" => 53070,
    "Eternal Boys" => 51293,
    "Ex-Arm" => 38853,
    "eX-Driver" => 377,
    "Exception" => 49163,
    "Fairy Gone" => 39063,
    "Fairy Gone Part 2" => 39811,
    "Fairy Tail" => 6702,
    "Fairy Tail (2014)" => 22043,
    "Fairy Tail Movie 1: Houou no Miko" => 12049,
    "Fairy Tail Movie 1: Houou no Miko - Hajimari no Asa" => 17535,
    "Fairy Tail Movie 2: Dragon Cry" => 30778,
    "Fairy Tail OVA" => 9982,
    "Fairy Tail OVA (2016)" => 32930,
    "Fairy Tail x Rave" => 18393,
    "Fairy Tail: Final Series" => 35972,
    "Fanren Xiu Xian Chuan" => 41219,
    "Fanren Xiu Xian Chuan 2nd Season" => 50207,
    "Fanren Xiu Xian Chuan: Yan Jia Bao Dazhan" => 45558,
    "Fantasy Bishoujo Juniku Ojisan to" => 48997,
    "Fate/Apocrypha" => 34662,
    "Fate/Extra: Last Encore" => 33047,
    "Fate/Extra: Last Encore - Illustrias Tendousetsu" => 37651,
    "Fate/Grand Carnival" => 44248,
    "Fate/Grand Order: First Order" => 34321,
    "Fate/Grand Order: Himuro no Tenchi - 7-nin no Saikyou Ijin-hen" => 36914,
    "Fate/Grand Order: Moonlight/Lostroom" => 36915,
    "Fate/Grand Order: Shinsei Entaku Ryouiki Camelot 1 - Wandering; Agateram" => 38085,
    "Fate/Grand Order: Shinsei Entaku Ryouiki Camelot 2 - Paladin; Agateram" => 38086,
    "Fate/Grand Order: Shuukyoku Tokuiten - Kani Jikan Shinden Solomon" => 41497,
    "Fate/Grand Order: Zettai Majuu Sensen Babylonia" => 38084,
    "Fate/Grand Order: Zettai Majuu Sensen Babylonia - Initium Iter" => 40206,
    "Fate/kaleid liner Prisma☆Illya" => 14829,
    "Fate/kaleid liner Prisma☆Illya 2wei Herz!" => 27525,
    "Fate/kaleid liner Prisma☆Illya 2wei Herz! Specials" => 31056,
    "Fate/kaleid liner Prisma☆Illya 2wei!" => 20509,
    "Fate/kaleid liner Prisma☆Illya 2wei! Specials" => 25011,
    "Fate/kaleid liner Prisma☆Illya 2wei!: Mahou Shoujo in Onsen Ryokou" => 26057,
    "Fate/kaleid liner Prisma☆Illya 3rei!!" => 31706,
    "Fate/kaleid liner Prisma☆Illya 3rei!! Specials" => 33456,
    "Fate/kaleid liner Prisma☆Illya Movie: Licht - Namae no Nai Shoujo" => 42030,
    "Fate/kaleid liner Prisma☆Illya Movie: Sekka no Chikai" => 34100,
    "Fate/kaleid liner Prisma☆Illya Movie: Sekka no Chikai - Kuro Sakura no Heya" => 36833,
    "Fate/kaleid liner Prisma☆Illya Specials" => 19109,
    "Fate/kaleid liner Prisma☆Illya: Prisma☆Phantasm" => 38897,
    "Fate/kaleid liner Prisma☆Illya: Undoukai de Dance!" => 18851,
    "Fate/Prototype" => 12565,
    "Fate/stay night" => 356,
    "Fate/stay night Movie: Heaven's Feel - I. Presage Flower" => 25537,
    "Fate/stay night Movie: Heaven's Feel - II. Lost Butterfly" => 33049,
    "Fate/stay night Movie: Heaven's Feel - III. Spring Song" => 33050,
    "Fate/stay night Movie: Unlimited Blade Works" => 6922,
    "Fate/stay night TV Reproduction" => 7559,
    "Fate/stay night: Unlimited Blade Works" => 22297,
    "Fate/stay night: Unlimited Blade Works 2nd Season" => 28701,
    "Fate/stay night: Unlimited Blade Works 2nd Season - Sunny Day" => 31389,
    "Fate/stay night: Unlimited Blade Works Prologue" => 27821,
    "Fate/strange Fake" => 40982,
    "Fate/strange Fake: Whispers of Dawn" => 53127,
    "Fate/Zero" => 10087,
    "Fate/Zero 2nd Season" => 11741,
    "Flip Flappers" => 32979,
    "Fortune Arterial: Akai Yakusoku" => 8536,
    "Free!" => 18507,
    "Free! Dive to the Future" => 36704,
    "Free! Eternal Summer" => 22265,
    "Free! Eternal Summer: Kindan no All Hard!" => 26213,
    "Free! Movie 3: Road to the World - Yume" => 39014,
    "Free! Movie 4: The Final Stroke - Zenpen" => 38400,
    "Free! Movie 5: The Final Stroke - Kouhen" => 48830,
    "Free! Take Your Marks" => 35198,
    "Free!: Dive to the Future - Soushun no Build-up!" => 38027,
    "Free!: FrFr - Short Movie" => 19671,
    "Fresh Precure!" => 5684,
    "Fruits Basket" => 120,
    "Fugou Keiji: Balance:Unlimited" => 41120,
    "Full Metal Panic!" => 71,
    "Fullmetal Alchemist" => 121,
    "Fullmetal Alchemist: Brotherhood" => 5114,
    "Fullmetal Alchemist: Brotherhood Specials" => 6421,
    "Fullmetal Alchemist: The Conqueror of Shamballa" => 430,
    "Fullmetal Alchemist: The Sacred Star of Milos" => 9135,
    "Fumetsu no Anata e" => 41025,
    "Fumetsu no Anata e 2nd Season" => 49709,
    "Fushigi Dagashiya: Zenitendou" => 42295,
    "Futari wa Precure" => 603,
    "Futoku no Guild" => 51212,
    "Futsal Boys!!!!!" => 40488,
    "Fuufu Ijou, Koibito Miman." => 50425,
    "Ga-Rei: Zero" => 4725,
    "Gaiken Shijou Shugi" => 53149,
    "Gaikotsu Kishi-sama, Tadaima Isekai e Odekakechuu" => 48760,
    "Gake no Ue no Ponyo" => 2890,
    "Gakkou no Kaidan" => 1281,
    "Gakusen Toshi Asterisk" => 30544,
    "Gallery Fake" => 364,
    "Ganbare Douki-chan" => 49605,
    "Ganbare! Choushuu-kun" => 52815,
    "Gangsta." => 25183,
    "Gankutsuou" => 239,
    "Gantz" => 384,
    "Garden of Remembrance" => 52085,
    "Garo: Honoo no Kokuin" => 23311,
    "Gate: Jieitai Kanochi nite, Kaku Tatakaeri" => 28907,
    "Ged Senki" => 1829,
    "Gekkan Shoujo Nozaki-kun" => 23289,
    "Genjitsu Shugi Yuusha no Oukoku Saikenki" => 41710,
    "Gensou Sangokushi: Tengen Reishinki" => 49514,
    "Gensoumaden Saiyuuki" => 129,
    "Gensoumaden Saiyuuki Movie: Requiem - Erabarezaru Mono e no Chinkonka" => 484,
    "Gensoumaden Saiyuuki OVA" => 3218,
    "Gensoumaden Saiyuuki: Kibou no Zaika" => 4476,
    "Getsuyoubi no Tawawa" => 34213,
    "Ghost Hunt" => 1571,
    "Gibiate" => 40074,
    "Gin no Guardian" => 32936,
    "Gin no Guardian II" => 35757,
    "Gin no Saji" => 16918,
    "Ginga Eiyuu Densetsu" => 820,
    "Ginga Eiyuu Densetsu Gaiden: Ougon no Tsubasa" => 3015,
    "Ginga Eiyuu Densetsu: Arata Naru Tatakai no Overture" => 3016,
    "Ginga Eiyuu Densetsu: Die Neue These - Gekitotsu" => 42886,
    "Ginga Eiyuu Densetsu: Die Neue These - Kaikou" => 31433,
    "Ginga Eiyuu Densetsu: Die Neue These - Sakubou" => 51805,
    "Ginga Eiyuu Densetsu: Die Neue These - Seiran 1" => 36369,
    "Ginga Eiyuu Densetsu: Die Neue These - Seiran 2" => 36370,
    "Ginga Eiyuu Densetsu: Die Neue These - Seiran 3" => 36371,
    "Ginga Eiyuu Densetsu: Waga Yuku wa Hoshi no Taikai" => 3014,
    "Ginga Kikoutai Majestic Prince" => 15863,
    "Ginga Nagareboshi Gin" => 589,
    "Ginga Tetsudou no Yoru" => 1441,
    "Gintama" => 918,
    "Girls & Panzer" => 14131,
    "Girls & Panzer Movie" => 18617,
    "Girls & Panzer Movie Specials" => 32740,
    "Girls & Panzer Specials" => 15811,
    "Girls & Panzer: Kore ga Hontou no Anzio-sen Desu!" => 18619,
    "Girls & Panzer: Saishuushou Part 1" => 33970,
    "Girls & Panzer: Saishuushou Part 2" => 38081,
    "Girls & Panzer: Saishuushou Part 2 Specials" => 40656,
    "Girls & Panzer: Saishuushou Part 3" => 39166,
    "Girls & Panzer: Shoukai Shimasu!" => 16199,
    "Given" => 39533,
    "Given Movie" => 40421,
    "Given: Uragawa no Sonzai" => 49053,
    "Gleipnir" => 39463,
    "Goblin Slayer" => 37349,
    "Gochuumon wa Usagi Desu ka?" => 21273,
    "God Eater" => 27631,
    "Godzilla: S.P" => 43229,
    "Gohan Kaijuu Pap" => 22669,
    "Gokukoku no Brynhildr" => 21431,
    "Golden Kamuy" => 36028,
    "Golden Kamuy 4th Season" => 50528,
    "Golden Time" => 17895,
    "Gosho Aoyama's Collection of Short Stories" => 5578,
    "Granblue Fantasy The Animation" => 31629,
    "Granblue Fantasy The Animation Season 2" => 36587,
    "Granblue Fantasy The Animation Season 2 Extras" => 40873,
    "Granblue Fantasy The Animation: Kabocha no Lantern" => 35814,
    "Grancrest Senki" => 34279,
    "Grand Blue" => 37105,
    "Grappler Baki (TV)" => 287,
    "Great Pretender" => 40052,
    "Grisaia no Kajitsu" => 17729,
    "Groove Adventure Rave" => 246,
    "Gudetama" => 23539,
    "Guilty Crown" => 10793,
    "Gundam Breaker: Battlogue" => 49211,
    "Gunjou no Fanfare" => 49691,
    "Gunjou no Magmell" => 37806,
    "Guraburu!" => 41574,
    "Gyakusatsu Kikan" => 23279,
    "Gyakuten Sekai no Denchi Shoujo" => 48644,
    "Hachi-nan tte, Sore wa Nai deshou!" => 38830,
    "Hachimitsu to Clover" => 16,
    "Hachimitsu to Clover II" => 1142,
    "Hagure Yuusha no Aesthetica" => 13161,
    "Hagure Yuusha no Aesthetica: Hajirai Ippai" => 15729,
    "Hai to Gensou no Grimgar" => 31859,
    "Haikara-san ga Tooru" => 3335,
    "Haikyuu!!" => 20583,
    "Haiyore! Nyaruko-san" => 11785,
    "Hajimete no Gal" => 34403,
    "Hajimete no Gal: Hajimete no Bunkasai" => 35237,
    "Hakaima Sadamitsu" => 1465,
    "Hakozume: Kouban Joshi no Gyakushuu" => 49519,
    "Hakuouki" => 6895,
    "Hakuouki Movie 1: Kyoto Ranbu" => 13117,
    "Hakuouki Movie 2: Shikon Soukyuu" => 13119,
    "Hakuouki OVA" => 11663,
    "Hakuouki OVA (2021)" => 49338,
    "Hakuouki: Hekketsuroku" => 9065,
    "Hakuouki: Hekketsuroku - Kyoto Kaisouroku" => 9723,
    "Hakuouki: Otogisoushi" => 32011,
    "Hakuouki: Otogisoushi Special" => 34948,
    "Hakuouki: Reimeiroku" => 13115,
    "Hakuouki: Sekkaroku" => 10350,
    "Hamatora The Animation" => 20689,
    "Hanakappa" => 8336,
    "Hanako" => 50740,
    "Hand Shakers" => 32981,
    "Hanebado!" => 37259,
    "Happy Sugar Life" => 37517,
    "Happy Summer Dream " => 5459,
    "Harem Camp!" => 52717,
    "Harukana Receive" => 35983,
    "Hataage! Kemono Michi" => 39030,
    "Hataraku Maou-sama!" => 15809,
    "Hataraku Maou-sama!!" => 48413,
    "Hataraku Saibou (TV)" => 37141,
    "Hatsukoi Limited." => 5150,
    "Hatsukoi Limited.: Gentei Shoujo" => 6882,
    "Hazamatsuri" => 50697,
    "Healer Girl" => 48857,
    "Healin' Good♡Precure" => 40610,
    "Healin' Good♡Precure Movie: Yume no Machi de Kyun! Tto GoGo! Dai Henshin!!" => 42422,
    "Heike Monogatari" => 49738,
    "Heion Sedai no Idaten-tachi" => 42625,
    "Hellsing" => 270,
    "Hellsing Ultimate" => 777,
    "Hellsing: The Dawn" => 11077,
    "Here U Are " => 119072,
    "Heroine Tarumono! Kiraware Heroine to Naisho no Oshigoto" => 49692,
    "Hibike! Euphonium" => 27989,
    "Hibike! Euphonium 2" => 31988,
    "Hibike! Euphonium 2 Specials" => 34204,
    "Hibike! Euphonium Movie 1: Kitauji Koukou Suisougaku-bu e Youkoso" => 31989,
    "Hibike! Euphonium Movie 2: Todoketai Melody" => 35082,
    "Hibike! Euphonium Movie 3: Chikai no Finale" => 35678,
    "Hibike! Euphonium: Kakedasu Monaka" => 31665,
    "Hibike! Euphonium: Suisougaku-bu no Nichijou" => 31096,
    "Hidan no Aria" => 8630,
    "Hidan no Aria AA" => 28883,
    "Hidan no Aria: Butei ga Kitarite Onsen Kenshuu" => 10604,
    "Higashi no Eden" => 5630,
    "Hige wo Soru. Soshite Joshikousei wo Hirou." => 40938,
    "High School DxD" => 11617,
    "High School DxD BorN" => 24703,
    "High School DxD BorN: Ishibumi Ichiei Kanzen Kanshuu! Mousou Bakuyou Kaijo Original Video" => 31326,
    "High School DxD BorN: Yomigaeranai Fushichou" => 32215,
    "High School DxD Hero" => 34281,
    "High School DxD Hero: Taiikukan-ura no Holy" => 37719,
    "High School DxD New" => 15451,
    "High School DxD New: Oppai, Tsutsumimasu!" => 30300,
    "High School DxD OVA" => 12729,
    "High School DxD Specials" => 13357,
    "High School Fleet" => 31500,
    "High Score Girl" => 21877,
    "Highschool of the Dead" => 8074,
    "High☆Speed! Movie: Free! Starting Days" => 30415,
    "Higurashi no Naku Koro ni" => 934,
    "Hiiro no Kakera" => 12461,
    "Himegoto" => 22835,
    "Himouto! Umaru-chan" => 28825,
    "Hinamatsuri (TV)" => 36296,
    "Hinomaruzumou" => 37007,
    "Hitori no Shita: The Outcast" => 33421,
    "Hitsugi no Chaika" => 20853,
    "Hokuto no Ken" => 967,
    "Home!" => 48577,
    "Honey Tokyo" => 8526,
    "Honzuki no Gekokujou: Shisho ni Naru Tame ni wa Shudan wo Erandeiraremasen" => 39468,
    "Honzuki no Gekokujou: Shisho ni Naru Tame ni wa Shudan wo Erandeiraremasen 2nd Season" => 40815,
    "Honzuki no Gekokujou: Shisho ni Naru Tame ni wa Shudan wo Erandeiraremasen 3rd Season" => 42429,
    "Honzuki no Gekokujou: Shisho ni Naru Tame ni wa Shudan wo Erandeiraremasen OVA" => 40841,
    "Hori-san to Miyamura-kun" => 14753,
    "Horimiya" => 42897,
    "Hortensia Saga (TV)" => 40961,
    "Hoshi no Samidare" => 50891,
    "Hoshi no Samidare: Zenhan-sen" => 53131,
    "Hotaru no Haka" => 578,
    "Hotarubi no Mori e" => 10408,
    "Howl no Ugoku Shiro" => 431,
    "Human Bug Daigaku" => 53012,
    "Hundred" => 31338,
    "Hunter x Hunter (2011)" => 11061,
    "Hunter x Hunter Movie 1: Phantom Rouge" => 13271,
    "Hunter x Hunter Movie 2: The Last Mission" => 19951,
    "Hunter x Hunter: Greed Island" => 138,
    "Hunter x Hunter: Greed Island Final" => 139,
    "Hunter x Hunter: Original Video Animation" => 137,
    "Hyakka Ryouran: Samurai Girls" => 8277,
    "Hyakuren no Haou to Seiyaku no Valkyria" => 37446,
    "Hyouka" => 12189,
    "Ichiban Chikakute Tooi Hoshi" => 48177,
    "Ichiban Ushiro no Daimaou" => 7088,
    "Ichiban Ushiro no Daimaou Specials" => 8465,
    "Id:Invaded" => 40046,
    "IDOLiSH7" => 33899,
    "IDOLiSH7: Third Beat! Part 2" => 46654,
    "iDOLM@STER Xenoglossia" => 1694,
    "Idoly Pride" => 40842,
    "IGPX: Immortal Grand Prix (2005)" => 3270,
    "IGPX: Immortal Grand Prix (2005) 2nd Season" => 1410,
    "Ijiranaide, Nagatoro-san" => 42361,
    "Ikebukuro West Gate Park" => 40359,
    "Ikkitousen" => 257,
    "Ikkitousen: Dragon Destiny" => 1956,
    "Ikkitousen: Dragon Destiny Specials" => 2905,
    "Ikkitousen: Extravaganza Epoch" => 26183,
    "Ikkitousen: Great Guardians" => 4196,
    "Ikkitousen: Great Guardians Specials" => 5333,
    "Ikkitousen: Shuugaku Toushi Keppuuroku" => 11255,
    "Ikkitousen: Western Wolves" => 38425,
    "Ikkitousen: Xtreme Xecutor" => 7580,
    "Ikkitousen: Xtreme Xecutor Specials" => 8507,
    "Imawa no Kuni no Alice (OVA)" => 24781,
    "Imouto sae Ireba Ii." => 35413,
    "Imouto sae Ireba Ii. (ONA)" => 37045,
    "Inazuma Eleven" => 5231,
    "Inferno Cop" => 16774,
    "Infinite Dendrogram" => 38909,
    "Initial D First Stage" => 185,
    "Inu to Hasami wa Tsukaiyou" => 17831,
    "Inu x Boku SS" => 11013,
    "InuYasha" => 249,
    "Inuyashiki" => 34542,
    "Irodorimidori" => 50267,
    "IS: Infinite Stratos" => 9041,
    "IS: Infinite Stratos 2" => 18247,
    "IS: Infinite Stratos 2 - Hitonatsu no Omoide" => 20045,
    "IS: Infinite Stratos 2 - World Purge-hen" => 21653,
    "IS: Infinite Stratos Encore - Koi ni Kogareru Rokujuusou" => 10794,
    "Isekai Cheat Magician" => 37744,
    "Isekai Izakaya: Koto Aitheria no Izakaya Nobu" => 34420,
    "Isekai Maou to Shoukan Shoujo no Dorei Majutsu" => 37210,
    "Isekai Meikyuu de Harem wo" => 44524,
    "Isekai Meikyuu de Harem wo Specials" => 52281,
    "Isekai Ojisan" => 49220,
    "Isekai Quartet" => 38472,
    "Isekai Quartet 2" => 39988,
    "Isekai Shokudou" => 34012,
    "Isekai wa Smartphone to Tomo ni." => 35203,
    "Isekai Yakkyoku" => 49438,
    "Ishuzoku Reviewers" => 40010,
    "Island" => 33012,
    "Isuca" => 25429,
    "Itai no wa Iya nano de Bougyoryoku ni Kyokufuri Shitai to Omoimasu." => 38790,
    "Itazura na Kiss" => 3731,
    "Itou Junji: Collection" => 36124,
    "Itsudatte Bokura no Koi wa 10 cm Datta." => 36220,
    "Iwa Kakeru!: Sport Climbing Girls" => 41783,
    "Ixion Saga DT" => 14765,
    "Iya na Kao sare nagara Opantsu Misete Moraitai" => 37021,
    "Jahy-sama wa Kujikenai!" => 48753,
    "Jaku-Chara Tomozaki-kun" => 40530,
    "Jantama Pong☆" => 50789,
    "Jashin-chan Dropkick" => 36906,
    "Jashin-chan Dropkick Episode 12" => 38383,
    "Jashin-chan Dropkick'" => 39049,
    "Jashin-chan Dropkick': Chitose-hen" => 40661,
    "Jian Wangchao" => 36996,
    "Jibaku Shounen Hanako-kun" => 39534,
    "Jie Mo Ren" => 33309,
    "Jigoku Shoujo" => 228,
    "Jikan no Shihaisha" => 34565,
    "Jinzou Ningen Kikaider The Animation" => 598,
    "Jitsu wa Watashi wa" => 29785,
    "JoJo no Kimyou na Bouken" => 666,
    "JoJo no Kimyou na Bouken (TV)" => 14719,
    "JoJo no Kimyou na Bouken Part 3: Stardust Crusaders" => 20899,
    "JoJo no Kimyou na Bouken Part 3: Stardust Crusaders 2nd Season" => 26055,
    "JoJo no Kimyou na Bouken Part 4: Diamond wa Kudakenai" => 31933,
    "JoJo no Kimyou na Bouken Part 5: Ougon no Kaze" => 37991,
    "JoJo no Kimyou na Bouken Part 6: Stone Ocean" => 48661,
    "JoJo no Kimyou na Bouken: Adventure" => 665,
    "Jormungand" => 12413,
    "Josee to Tora to Sakana-tachi" => 40787,
    "Joshikousei no Mudazukai" => 38619,
    "Joukamachi no Dandelion" => 28387,
    "Jouran: The Princess of Snow and Blood" => 47250,
    "Journey: Taiko Arabia Hantou de no Kiseki to Tatakai no Monogatari" => 48612,
    "Juan Siliang" => 41918,
    "Juedai Shuang Jiao" => 49750,
    "Jujutsu Kaisen (TV)" => 40748,
    "Jun You Yun" => 50433,
    "Juuni Taisen" => 35076,
    "Juuou Mujin no Fafnir" => 24873,
    "K" => 14467,
    "K-On!" => 5680,
    "K: Missing Kings" => 16904,
    "K: Return of Kings" => 27991,
    "Kaeru San Yuushi" => 28051,
    "Kagami no Kojou" => 51116,
    "Kage no Jitsuryokusha ni Naritakute!" => 48316,
    "Kageki Shoujo!!" => 43691,
    "Kaginado" => 48775,
    "Kaginado Season 2" => 50685,
    "Kaguya-hime no Monogatari" => 16664,
    "Kaguya-sama wa Kokurasetai: First Kiss wa Owaranai" => 52198,
    "Kaguya-sama wa Kokurasetai: Tensai-tachi no Renai Zunousen" => 37999,
    "Kaguya-sama wa Kokurasetai: Tensai-tachi no Renai Zunousen OVA" => 43609,
    "Kaguya-sama wa Kokurasetai: Ultra Romantic" => 43608,
    "Kaguya-sama wa Kokurasetai: Ultra Romantic Teaser PV - Ishigami Yuu wa Kataritai" => 50325,
    "Kaguya-sama wa Kokurasetai? Tensai-tachi no Renai Zunousen" => 40591,
    "Kai Byoui Ramune" => 42822,
    "Kaichou wa Maid-sama!" => 7054,
    "Kaifuku Jutsushi no Yarinaoshi" => 40750,
    "Kaijin Kaihatsu-bu no Kuroitsu-san" => 49385,
    "Kaizoku Oujo" => 42544,
    "Kakegurui" => 34933,
    "Kakkou no Iinazuke" => 48675,
    "Kakuriyo no Yadomeshi" => 36754,
    "Kaleido Star" => 427,
    "Kame no Koura wa Abarabone" => 52980,
    "Kami Kuzu☆Idol" => 50470,
    "Kami no Tou" => 40221,
    "Kami-tachi ni Hirowareta Otoko" => 41312,
    "Kamisama Hajimemashita" => 14713,
    "Kamisama ni Natta Hi" => 41930,
    "Kamisama no Inai Nichiyoubi" => 16009,
    "Kamisama no Memochou" => 10568,
    "Kamiusagi Rope: Warau Asa ni wa Fukuraitaru tte Maji ssuka!?" => 30151,
    "Kämpfer" => 6205,
    "Kanata no Astra" => 39198,
    "KanColle: Itsuka Ano Umi de" => 30455,
    "Kanojo ga Flag wo Oraretara" => 19685,
    "Kanojo mo Kanojo" => 43969,
    "Kanojo to Kanojo no Neko: Everything Flows" => 32491,
    "Kanojo, Okarishimasu" => 40839,
    "Kanojo, Okarishimasu 2nd Season" => 42963,
    "Kanojo, Okarishimasu 2nd Season Mini Anime" => 52289,
    "Kanokon" => 3503,
    "Kao Ni La Zhanshen Xitong" => 52178,
    "Karakai Jouzu no Takagi-san" => 35860,
    "Karakai Jouzu no Takagi-san 2" => 38993,
    "Karakai Jouzu no Takagi-san 3" => 49721,
    "Karakai Jouzu no Takagi-san Movie" => 49722,
    "Karakai Jouzu no Takagi-san: Water Slide" => 37621,
    "Karakuri Circus" => 37447,
    "Katanagatari" => 6594,
    "Katekyo Hitman Reborn!" => 1604,
    "Katsugeki/Touken Ranbu" => 33018,
    "Katsute Kami Datta Kemono-tachi e" => 39199,
    "Kawaii dake ja Nai Shikimori-san" => 45613,
    "Kawaikereba Hentai demo Suki ni Natte Kuremasu ka?" => 39326,
    "Keijo!!!!!!!!" => 32686,
    "Keishichou Tokumubu Tokushu Kyouakuhan Taisakushitsu Dainanaka: Tokunana" => 39567,
    "Keito no Yousei: Knit to Wool" => 35695,
    "Kekkai Sensen" => 24439,
    "Kemono Jihen" => 40908,
    "Ken En Ken: Aoki Kagayaki" => 38083,
    "Kenja no Deshi wo Nanoru Kenja" => 42072,
    "Kenja no Mago" => 36407,
    "Kenpuu Denki Berserk" => 33,
    "Keppeki Danshi! Aoyama-kun" => 34825,
    "Key the Metal Idol" => 1457,
    "Kiba" => 845,
    "Kill la Kill" => 18679,
    "Killing Bites" => 34964,
    "Kimetsu no Yaiba" => 38000,
    "Kimi ga Nozomu Eien" => 147,
    "Kimi ga Nozomu Eien: Next Season" => 3318,
    "Kimi ni Todoke" => 6045,
    "Kimi no Iru Machi" => 17741,
    "Kimi no Na wa." => 32281,
    "Kimi no Suizou wo Tabetai" => 36098,
    "Kimi to Boku no Saigo no Senjou, Aruiwa Sekai ga Hajimaru Seisen" => 40595,
    "Kimi wa Kanata" => 41379,
    "Kimi wo Aishita Hitori no Boku e" => 49835,
    "Kin no Kuni Mizu no Kuni" => 52186,
    "King's Raid: Ishi wo Tsugumono-tachi" => 41834,
    "Kingdom" => 12031,
    "Kingdom 2nd Season" => 17389,
    "Kingdom 3rd Season" => 40682,
    "Kingdom 4th Season" => 50160,
    "Kiniro Mosaic" => 16732,
    "Kino no Tabi: The Beautiful World" => 486,
    "Kirin the Noop" => 35694,
    "Kiseijuu: Sei no Kakuritsu" => 22535,
    "Kishin Douji Zenki" => 1573,
    "Kishin Douji Zenki Gaiden: Anki Kitan" => 5828,
    "Kishuku Gakkou no Juliet" => 37475,
    "Kiss x Sis" => 5042,
    "Kiss x Sis (TV)" => 7593,
    "Kiznaiver" => 31798,
    "KJ File" => 51773,
    "Knight's & Magic" => 34104,
    "Knyacki!" => 7505,
    "Kobayashi-san Chi no Maid Dragon" => 33206,
    "Kobayashi-san Chi no Maid Dragon S" => 39247,
    "Kobayashi-san Chi no Maid Dragon S: Nippon no Omotenashi - Attend wa Dragon Desu" => 49893,
    "Kobayashi-san Chi no Maid Dragon: Valentine, Soshite Onsen! - Amari Kitai Shinaide Kudasai" => 35363,
    "Kobayashi-san Chi no OO Dragon" => 35145,
    "Koe no Katachi" => 28851,
    "Koi to Producer: EVOL×LOVE" => 40075,
    "Koi to Uso" => 34934,
    "Koi to Yobu ni wa Kimochi Warui" => 41103,
    "Koi wa Sekai Seifuku no Ato de" => 48643,
    "Kokkoku" => 36548,
    "Kokoro ga Sakebitagatterunda." => 28725,
    "Komi-san wa, Comyushou desu." => 48926,
    "Komi-san wa, Comyushou desu. 2nd Season" => 50631,
    "Koneko no Rakugaki" => 6993,
    "Koneko no Studio" => 6994,
    "Konigiri-kun" => 35696,
    "Kono Bijutsubu ni wa Mondai ga Aru!" => 31952,
    "Kono Healer, Mendokusai" => 48742,
    "Kono Oto Tomare!" => 38080,
    "Kono Sekai no Tanoshimikata: Secret Story Film" => 42750,
    "Kono Subarashii Sekai ni Shukufuku wo!" => 30831,
    "Kono Yo no Hate de Koi wo Utau Shoujo YU-NO" => 34620,
    "Konohana Kitan" => 35241,
    "Kore wa Zombie Desu ka?" => 8841,
    "Koroshi Ai" => 44516,
    "Kotonoha no Niwa" => 16782,
    "Koukaku Kidoutai: Stand Alone Complex" => 467,
    "Koukyoushihen Eureka Seven" => 237,
    "Koukyuu no Karasu" => 50590,
    "Koutetsujou no Kabaneri" => 28623,
    "Kuma Kuma Kuma Bear" => 40974,
    "Kumichou Musume to Sewagakari" => 49776,
    "Kumo Desu ga, Nani ka?" => 37984,
    "Kunoichi Tsubaki no Mune no Uchi" => 50338,
    "Kuro no Shoukanshi" => 51064,
    "Kuroko no Basket" => 11771,
    "Kuroshitsuji" => 4898,
    "Kurozuka" => 5039,
    "Kuusen Madoushi Kouhosei no Kyoukan" => 25283,
    "Kuzu no Honkai" => 32949,
    "Kyokou Suiri" => 39017,
    "Kyoro-chan" => 4375,
    "Kyou kara Maou!" => 251,
    "Kyoukai no Kanata" => 18153,
    "Kyoukai Senki" => 48466,
    "Kyoukai Senki Part 2" => 50678,
    "Kyoukaisenjou no Horizon" => 10456,
    "Kyuuketsuki Sugu Shinu" => 41833,
    "Kyuukyoku Shinka shita Full Dive RPG ga Genjitsu yori mo Kusoge Dattara" => 44276,
    "Lapis Re:LiGHTs" => 37587,
    "Leadale no Daichi nite" => 48239,
    "Liang Bu Yi 2nd Season" => 50403,
    "Lianqi Lianle 3000 Nian" => 50538,
    "Lie Huo Jiao Chou" => 44064,
    "Ling Yu" => 36291,
    "Listeners" => 40165,
    "Little Witch Academia (TV)" => 33489,
    "Liv &amp; Bell" => 29421,
    "Liz to Aoi Tori" => 35677,
    "Log Horizon" => 17265,
    "Log Horizon 2nd Season" => 23321,
    "Log Horizon: Entaku Houkai" => 41109,
    "Long Zu" => 44408,
    "Long Zu Episode 0" => 52890,
    "Lord El-Melloi II Sei no Jikenbo: Rail Zeppelin Grace Note" => 38959,
    "Lord El-Melloi II Sei no Jikenbo: Rail Zeppelin Grace Note - Hakamori to Neko to Majutsushi" => 38936,
    "Lord El-Melloi II Sei no Jikenbo: Rail Zeppelin Grace Note Special" => 49361,
    "Love All Play" => 49556,
    "Love Hina" => 189,
    "Love Lab" => 16353,
    "Love Live! Nijigasaki Gakuen School Idol Doukoukai" => 40879,
    "Love Live! Nijigasaki Gakuen School Idol Doukoukai 2nd Season" => 48916,
    "Love Live! School Idol Project" => 15051,
    "Love Live! School Idol Project 2nd Season" => 19111,
    "Love Live! School Idol Project OVA" => 20745,
    "Love Live! School Idol Project: μ's →NEXT LoveLive! 2014 - Endless Parade Makuai Drama" => 25897,
    "Love Live! Sunshine!!" => 32526,
    "Love Live! Sunshine!! 2nd Season" => 34973,
    "Love Live! Sunshine!! The School Idol Movie: Over the Rainbow" => 37027,
    "Love Live! Superstar!!" => 41169,
    "Love Live! Superstar!! 2nd Season" => 50203,
    "Love Live! The School Idol Movie" => 24997,
    "Lovely★Complex" => 2034,
    "Luo Xiao Hei Zhan Ji" => 33443,
    "Lupin III" => 1412,
    "Lycoris Recoil" => 50709,
    "Machikado Mazoku" => 39071,
    "Machikado Mazoku: 2-choume" => 42745,
    "Machine-Doll wa Kizutsukanai" => 17247,
    "Madan no Ou to Vanadis" => 24455,
    "Made in Abyss" => 34599,
    "Made in Abyss Movie 3: Fukaki Tamashii no Reimei" => 36862,
    "Made in Abyss: Retsujitsu no Ougonkyou" => 41084,
    "Made in Abyss: Retsujitsu no Ougonkyou Mini Anime" => 52149,
    "Madtoy Chatty" => 52463,
    "Magatsu Wahrheit: Zuerst" => 37599,
    "Magi: Sinbad no Bouken (TV)" => 31741,
    "Magia Record: Mahou Shoujo Madoka☆Magica Gaiden" => 38256,
    "Magia Record: Mahou Shoujo Madoka☆Magica Gaiden 2nd Season - Kakusei Zenya" => 41530,
    "Magia Record: Mahou Shoujo Madoka☆Magica Gaiden Final Season - Asaki Yume no Akatsuki" => 49291,
    "Mahou no Princess Minky Momo vs. Mahou no Tenshi Creamy Mami" => 8972,
    "Mahou Sensou" => 19769,
    "Mahou Shoujo Madoka★Magica" => 9756,
    "Mahou Shoujo Madoka★Magica Movie 1: Hajimari no Monogatari" => 11977,
    "Mahou Shoujo Madoka★Magica Movie 2: Eien no Monogatari" => 11979,
    "Mahou Shoujo Madoka★Magica Movie 3: Hangyaku no Monogatari" => 11981,
    "Mahou Shoujo Site" => 36266,
    "Mahou Shoujo Tokushusen Asuka" => 37979,
    "Mahouka Koukou no Rettousei" => 20785,
    "Mahouka Koukou no Rettousei: Raihousha-hen" => 40497,
    "Mahouka Koukou no Rettousei: Tsuioku-hen" => 48375,
    "Mahouka Koukou no Yuutousei" => 45572,
    "Mahoutsukai no Yome" => 35062,
    "Mahoutsukai Precure!" => 31884,
    "Mahoutsukai Reimeiki" => 48842,
    "Mairimashita! Iruma-kun" => 39196,
    "Mairimashita! Iruma-kun 3rd Season" => 49784,
    "Maji de Watashi ni Koi Shinasai!" => 10213,
    "Majo no Tabitabi" => 40571,
    "Majo no Takkyuubin" => 512,
    "Majutsushi Orphen Hagure Tabi" => 37576,
    "Maken-Ki!" => 9936,
    "Mamahaha no Tsurego ga Motokano datta" => 49470,
    "Mamekichi Mameko NEET no Nichijou" => 53011,
    "Manga de Wakaru! Fate/Grand Order" => 38958,
    "Manul no Yuube" => 38776,
    "Maou Gakuin no Futekigousha: Shijou Saikyou no Maou no Shiso, Tensei shite Shison-tachi no Gakkou e Kayou" => 40496,
    "Maou-sama, Retry!" => 38297,
    "Maoujou de Oyasumi" => 40397,
    "Mars Red" => 41265,
    "Marulk-chan no Nichijou" => 40897,
    "Masamune-kun no Revenge" => 33487,
    "Mashiro no Oto" => 42590,
    "Mashiro-iro Symphony: The Color of Lovers" => 10397,
    "Masou Gakuen HxH" => 31845,
    "Matsugae wo Musubi" => 38130,
    "Mayo Chiki!" => 10110,
    "Megalo Box" => 36563,
    "Megami-ryou no Ryoubo-kun." => 41812,
    "Megaton-kyuu Musashi" => 33737,
    "Megaton-kyuu Musashi 2nd Season" => 50559,
    "Meikyuu Black Company" => 42340,
    "Meitantei Conan: Hannin no Hanzawa-san" => 50010,
    "Meitantei Conan: Zero no Tea Time" => 50012,
    "Mesudachi The Animation" => 52904,
    "Mi Yu Xing Zhe" => 37175,
    "Midara na Ao-chan wa Benkyou ga Dekinai" => 38778,
    "Mieruko-chan" => 48483,
    "Miira no Kaikata" => 35828,
    "Mimi wo Sumaseba" => 585,
    "Mini Dragon" => 48590,
    "Mini Dragon Specials" => 49483,
    "Mirai Nikki (TV)" => 10620,
    "Miru Tights" => 38935,
    "Mitsuami no Kamisama" => 32461,
    "Mob Psycho 100" => 32182,
    "Mob Psycho 100 II" => 37510,
    "Mob Psycho 100 III" => 50172,
    "Mob Psycho 100: Dai Ikkai Rei toka Soudansho Ian Ryokou - Kokoro Mitasu Iyashi no Tabi" => 39651,
    "Mobile Suit Gundam: The Witch from Mercury" => 49828,
    "Mogyutto \"Love\" de Sekkinchuu!" => 12637,
    "Momokuri" => 30014,
    "Mondaiji-tachi ga Isekai kara Kuru Sou Desu yo?" => 15315,
    "Mondaiji-tachi ga Isekai kara Kuru Sou Desu yo?: Onsen Manyuuki" => 16444,
    "Mononoke" => 2246,
    "Mononoke Hime" => 164,
    "Monster" => 19,
    "Monster Musume no Iru Nichijou" => 30307,
    "Monster Musume no Iru Nichijou: Hobo Mainichi ◯◯! Namappoi Douga" => 31121,
    "Monster Musume no Oishasan" => 40708,
    "Mori no Ratio" => 29427,
    "Motto To LOVE-Ru" => 9181,
    "Mouretsu Pirates" => 8917,
    "Mousou Dairinin" => 323,
    "Mugen no Juunin: Immortal" => 39806,
    "Munou na Nana" => 41619,
    "Musaigen no Phantom World" => 31442,
    "Musashino!" => 35335,
    "Mushikaburi-hime" => 50923,
    "Mushishi" => 457,
    "Mushoku Tensei: Isekai Ittara Honki Dasu" => 39535,
    "Muv-Luv Alternative 2nd Season" => 50638,
    "Muv-Luv Alternative: Total Eclipse" => 11021,
    "Nagato Yuki-chan no Shoushitsu" => 26351,
    "Nagi no Asu kara" => 16067,
    "Nakitai Watashi wa Neko wo Kaburu" => 41168,
    "Namiuchigiwa no Muromi-san" => 16910,
    "Nana" => 877,
    "Nana Recaps" => 1869,
    "Nanatsu no Taizai" => 23755,
    "Nanatsu no Taizai Movie 1: Tenkuu no Torawarebito" => 35946,
    "Nanatsu no Taizai Movie 2: Hikari ni Norowareshi Mono-tachi" => 46420,
    "Nanatsu no Taizai OVA" => 30347,
    "Nanatsu no Taizai: Eiyuu-tachi wa Hashagu" => 38198,
    "Nanatsu no Taizai: Ensa no Edinburgh" => 50315,
    "Nanatsu no Taizai: Fundo no Shinpan" => 41491,
    "Nanatsu no Taizai: Imashime no Fukkatsu" => 34577,
    "Nanatsu no Taizai: Kamigami no Gekirin" => 39701,
    "Nanatsu no Taizai: Seisen no Shirushi" => 31722,
    "Nanbaka" => 30016,
    "Nande Koko ni Sensei ga!?" => 38397,
    "Nande Koko ni Sensei ga!? Special" => 39689,
    "Naruto" => 20,
    "Naruto Movie 1: Dai Katsugeki!! Yuki Hime Shinobu Houjou Dattebayo!" => 442,
    "Naruto Movie 2: Dai Gekitotsu! Maboroshi no Chiteiiseki Dattebayo!" => 936,
    "Naruto Movie 3: Dai Koufun! Mikazuki Jima no Animaru Panikku Dattebayo!" => 2144,
    "Naruto: Shippuuden" => 1735,
    "Naruto: Shippuuden Movie 1" => 2472,
    "Naruto: Shippuuden Movie 2 - Kizuna" => 4437,
    "Naruto: Shippuuden Movie 3 - Hi no Ishi wo Tsugu Mono" => 6325,
    "Naruto: Shippuuden Movie 4 - The Lost Tower" => 8246,
    "Naruto: Shippuuden Movie 5 - Blood Prison" => 10589,
    "Naruto: Shippuuden Movie 6 - Road to Ninja" => 13667,
    "Natsu-iro Egao de 1, 2, Jump!" => 11033,
    "Nazo no Kanojo X" => 12467,
    "Nejimaki Seirei Senki: Tenkyou no Alderamin" => 31764,
    "Neko Neko Fantasia" => 8496,
    "Nekopara" => 38924,
    "Nekopara OVA" => 34658,
    "Nekopara: Koneko no Hi no Yakusoku" => 37983,
    "Neon Genesis Evangelion" => 30,
    "Nerima Daikon Brothers" => 1655,
    "Nichijou" => 10165,
    "Night Head Genesis" => 1243,
    "Nihon Chinbotsu 2020" => 40515,
    "Ninja Collection" => 42260,
    "Ninjala (TV)" => 50418,
    "Nintama Rantarou" => 1199,
    "Nisekoi" => 18897,
    "No Game No Life" => 19815,
    "No Guns Life" => 39539,
    "Noblesse" => 41345,
    "Nobunaga the Fool" => 21177,
    "Nodame Cantabile" => 1698,
    "Nodame Cantabile: Nodame to Chiaki no Umi Monogatari" => 3965,
    "Nomad: Megalo Box 2" => 40729,
    "Non Non Biyori" => 17549,
    "Noragami" => 20507,
    "Noumin Kanren no Skill bakka Agetetara Nazeka Tsuyoku Natta." => 51128,
    "Nozo x Kimi" => 24175,
    "Nu Wushen de Canzhuo" => 40100,
    "Nurarihyon no Mago" => 7592,
    "Nyan Koi!" => 6512,
    "Nyanko Days" => 34148,
    "Obake Zukan!" => 52319,
    "Ochikobore Fruit Tart" => 39609,
    "Oda Nobuna no Yabou" => 11933,
    "Odd Taxi" => 46102,
    "Odd Taxi Movie: In the Woods" => 50653,
    "Oidon to" => 35698,
    "Ojarumaru" => 4459,
    "Okusama ga Seitokaichou!" => 28819,
    "Omamori Himari" => 6324,
    "Omoi, Omoware, Furi, Furare" => 39753,
    "Omoide no Marnie" => 21557,
    "One Off" => 13283,
    "One Piece" => 21,
    "One Punch Man" => 30276,
    "Onipan!" => 50955,
    "Onna no Sono no Hoshi" => 52601,
    "Onsen Yousei Hakone-chan" => 31143,
    "Ookami Kodomo no Ame to Yuki" => 12355,
    "Ookami Shoujo to Kuro Ouji" => 23673,
    "Orange" => 32729,
    "Ore dake Haireru Kakushi Dungeon" => 41899,
    "Ore Monogatari!!" => 28297,
    "Ore no Imouto ga Konnani Kawaii Wake ga Nai" => 8769,
    "Ore no Kanojo to Osananajimi ga Shuraba Sugiru" => 14749,
    "Ore, Tsushima" => 48252,
    "Orient" => 45560,
    "Orient: Awajishima Gekitou-hen" => 51368,
    "Origami Ninja Koyankinte" => 41458,
    "Osake wa Fuufu ni Natte kara" => 35484,
    "Osake wa Fuufu ni Natte kara: Yuzu Atsukan" => 37826,
    "Osananajimi ga Zettai ni Makenai Love Comedy" => 43007,
    "Oshiete! Galko-chan" => 32013,
    "Otome Game no Hametsu Flag shika Nai Akuyaku Reijou ni Tensei shiteshimatta..." => 38555,
    "Otome Game Sekai wa Mob ni Kibishii Sekai desu" => 50461,
    "Otome wa Boku ni Koishiteru" => 1569,
    "Otoppe" => 35372,
    "Ousama Game The Animation" => 36027,
    "Ousama Ranking" => 40834,
    "Overlord" => 29803,
    "Overlord II" => 35073,
    "Overlord III" => 37675,
    "Overlord IV" => 48895,
    "Overlord Movie 1: Fushisha no Ou" => 34161,
    "Overlord: Ple Ple Pleiades" => 31138,
    "Overlord: Ple Ple Pleiades 2" => 37087,
    "Overlord: Ple Ple Pleiades 3" => 37781,
    "Owari no Seraph" => 26243,
    "Pakkororin" => 38099,
    "Pandora Hearts" => 5530,
    "Panty & Stocking with Garterbelt" => 8795,
    "Paradise Kiss" => 322,
    "Paripi Koumei" => 50380,
    "Peach Boy Riverside" => 42627,
    "Penguin Highway" => 37407,
    "Perfect Blue" => 437,
    "Persona: Trinity Soul" => 3366,
    "Peter Grill to Kenja no Jikan" => 40436,
    "Peter Grill to Kenja no Jikan: Super Extra" => 50348,
    "Phantom in the Twilight" => 37598,
    "Phantom: Requiem for the Phantom" => 5682,
    "Plastic Memories" => 27775,
    "Platinum End" => 44961,
    "Plunderer" => 37345,
    "Pokemon (2019)" => 40351,
    "Poputepipikku 2nd Season" => 50663,
    "Pororo Donghwanala" => 48250,
    "Precure Miracle Leap Movie: Minna to no Fushigi na Ichinichi" => 40587,
    "Prima Doll" => 50917,
    "Princess Connect! Re:Dive" => 39292,
    "Princess Connect! Re:Dive Season 2" => 42670,
    "Princess Nine: Kisaragi Joshikou Yakyuubu" => 1846,
    "Princess Principal" => 35240,
    "Prison School" => 30240,
    "Promare" => 35848,
    "Psycho-Pass" => 13601,
    "Puchimas!!: Petit Petit iDOLM@STER" => 21073,
    "Puchimas!: Petit iDOLM@STER" => 15649,
    "Puchimas!: Petit iDOLM@STER - Takatsuki Gold Densetsu Special!! Haruka-san Matsuri" => 18781,
    "Pui Pui Molcar: Driving School" => 51986,
    "Pumpkin Scissors" => 1538,
    "Punirunes" => 52935,
    "Puraore! Pride of Orange" => 44274,
    "PuriGorota: Uchuu no Yuujou Daibouken" => 5656,
    "Puzzle &amp; Dragon" => 37096,
    "Qi Jie Diyi Xian" => 51131,
    "Qualidea Code" => 32360,
    "Quanzhi Fashi" => 34300,
    "Quanzhi Gaoshou" => 33926,
    "R.O.D: The TV" => 209,
    "Radiant" => 37202,
    "Raikou Shinki Aigis Magia: Pandra Saga 3rd Ignition - The Animation" => 52561,
    "Rail Romanesque" => 40958,
    "Rail Wars!" => 23309,
    "Rainbow: Nisha Rokubou no Shichinin" => 6114,
    "Rakudai Kishi no Cavalry" => 30296,
    "Re-Main" => 48406,
    "Re:Creators" => 34561,
    "Re:Zero kara Hajimeru Isekai Seikatsu" => 31240,
    "Re:␣Hamatora" => 23421,
    "Rec" => 710,
    "Recola" => 52066,
    "Redline" => 6675,
    "Reiwa no Di Gi Charat" => 49179,
    "Reizouko no Tsukenosuke!" => 38451,
    "ReLIFE" => 30015,
    "Renai Boukun" => 32262,
    "Renai Flops" => 51403,
    "Renmei Kuugun Koukuu Mahou Ongakutai Luminous Witches" => 38006,
    "RErideD: Tokigoe no Derrida" => 35835,
    "Rewrite" => 31716,
    "Rikei ga Koi ni Ochita no de Shoumei shitemita." => 38992,
    "Rikei ga Koi ni Ochita no de Shoumei shitemita. Heart" => 43470,
    "Robot Pulta" => 29375,
    "Rokka no Yuusha" => 28497,
    "Rokudenashi Majutsu Koushi to Akashic Records" => 32951,
    "Romantic Killer" => 52865,
    "Romeo x Juliet" => 1699,
    "Rosario to Vampire" => 2993,
    "RPG Fudousan" => 48363,
    "Rurouni Kenshin Special" => 12067,
    "Rurouni Kenshin: Meiji Kenkaku Romantan" => 45,
    "Rurouni Kenshin: Meiji Kenkaku Romantan - Seisou-hen" => 401,
    "Rurouni Kenshin: Meiji Kenkaku Romantan - Tsuioku-hen" => 44,
    "RWBY: Hyousetsu Teikoku" => 51381,
    "Ryman&#039;s Club" => 50185,
    "Ryuu to Sobakasu no Hime" => 44807,
    "Ryuugajou Nanana no Maizoukin (TV)" => 21561,
    "Sabiiro no Armor: Reimei" => 39917,
    "Sabikui Bisco" => 48414,
    "Saenai Heroine no Sodatekata" => 23277,
    "Saenai Heroine no Sodatekata Fine" => 36885,
    "Saenai Heroine no Sodatekata ♭: Koi to Junjou no Service-kai" => 35338,
    "Saihate no Paladin" => 48761,
    "Saijaku Muhai no Bahamut" => 30749,
    "Saiki Kusuo no Ψ-nan" => 33255,
    "Saikin Yatotta Maid ga Ayashii" => 51837,
    "Saikin, Imouto no Yousu ga Chotto Okashiinda ga." => 17777,
    "Saiyuuki Gaiden" => 9088,
    "Saiyuuki Gaiden: Kouga no Shou" => 17137,
    "Saiyuuki Reload" => 130,
    "Saiyuuki Reload Blast" => 33726,
    "Saiyuuki Reload Gunlock" => 131,
    "Saiyuuki Reload: Burial" => 2143,
    "Saiyuuki Reload: Zeroin" => 45783,
    "Sakamoto Desu ga?" => 32542,
    "Sakasama no Patema" => 12477,
    "Sakugan" => 38192,
    "Sakura Trick" => 20047,
    "Sakura-sou no Pet na Kanojo" => 13759,
    "Sakurako-san no Ashimoto ni wa Shitai ga Umatteiru" => 30187,
    "Samurai Champloo" => 205,
    "Sango Shou Densetsu: Aoi Umi no Elfie" => 5014,
    "Sankarea" => 11499,
    "Sasaki to Miyano" => 44055,
    "Satsuriku no Tenshi" => 35994,
    "Sayonara no Asa ni Yakusoku no Hana wo Kazarou" => 35851,
    "Sayonara Watashi no Cramer" => 42774,
    "Sazae-san" => 2406,
    "Scarlet Nexus" => 48492,
    "Schoolgirl Strikers: Animation Channel" => 34289,
    "Seijo no Maryoku wa Bannou Desu" => 42826,
    "Seiken Densetsu: Legend of Mana - The Teardrop Crystal" => 49304,
    "Seiken no Blacksmith" => 5940,
    "Seikoku no Dragonar" => 21033,
    "Seikon no Qwaser" => 6500,
    "Seikon no Qwaser II" => 10073,
    "Seikon no Qwaser II Picture Drama" => 10920,
    "Seikon no Qwaser Picture Drama" => 8668,
    "Seikon no Qwaser: Jotei no Shouzou" => 9202,
    "Seirei Gensouki" => 44203,
    "Seirei no Moribito" => 1827,
    "Seireitsukai no Blade Dance" => 22877,
    "Seiren" => 33836,
    "Seisen Cerberus: Ryuukoku no Fatalités" => 32595,
    "Seishun Buta Yarou wa Bunny Girl Senpai no Yume wo Minai" => 37450,
    "Seishun Buta Yarou wa Yumemiru Shoujo no Yume wo Minai" => 38329,
    "Seitokai no Ichizon" => 5909,
    "Seitokai Yakuindomo" => 8675,
    "Sekai Saikou no Ansatsusha, Isekai Kizoku ni Tensei suru" => 47790,
    "Sekirei" => 4063,
    "Selection Project" => 44275,
    "Sen to Chihiro no Kamikakushi" => 199,
    "Sengoku Basara" => 5355,
    "Senpai ga Uzai Kouhai no Hanashi" => 42351,
    "Senran Kagura" => 15119,
    "Sensou no Tsukurikata" => 32728,
    "Sentouin, Hakenshimasu!" => 41456,
    "Servamp" => 31229,
    "Seven Knights Revolution: Eiyuu no Keishousha" => 47391,
    "Sewayaki Kitsune no Senko-san" => 38759,
    "Shachiku-san wa Youjo Yuurei ni Iyasaretai." => 49160,
    "Shachou, Battle no Jikan Desu!" => 40783,
    "Shadows House" => 43439,
    "Shadows House 2nd Season" => 49782,
    "Shadowverse" => 40506,
    "Shadowverse Flame" => 50060,
    "Shakugan no Shana" => 355,
    "Shakugan no Shana II (Second)" => 2787,
    "Shakugan no Shana III (Final)" => 6773,
    "Shakugan no Shana Movie" => 1815,
    "Shakugan no Shana S" => 6572,
    "Shakunetsu Kabaddi" => 42395,
    "Shaman King" => 154,
    "Shaman King (2021)" => 42205,
    "Shanhe Jian Xin" => 44196,
    "Shaonu Qianxian: Renxing Xiao Juchang" => 40151,
    "Shen Lan Qi Yu Wushuang Zhu" => 48965,
    "Shen Yin Wangzuo" => 51335,
    "Shi Huang Zhi Shen" => 39298,
    "Shiawase Haitatsu Taneko" => 10506,
    "Shichisei no Subaru" => 36316,
    "Shigatsu wa Kimi no Uso" => 23273,
    "Shiguang Dailiren" => 44074,
    "Shijou Saikyou no Daimaou, Murabito A ni Tensei suru" => 48415,
    "Shijou Saikyou no Deshi Kenichi" => 1559,
    "Shikaru Neko" => 48442,
    "Shikioriori" => 37396,
    "Shikizakura" => 38440,
    "Shikkakumon no Saikyou Kenja" => 47161,
    "Shikong Zhi Xi" => 50443,
    "Shimajirou no Wow!" => 18941,
    "Shimoneta to Iu Gainen ga Sonzai Shinai Taikutsu na Sekai" => 29786,
    "Shin Angyo Onshi" => 884,
    "Shin Ikkitousen" => 49342,
    "Shin no Nakama ja Nai to Yuusha no Party wo Oidasareta node, Henkyou de Slow Life suru Koto ni Shimashita" => 44037,
    "Shinchou Yuusha: Kono Yuusha ga Ore Tueee Kuse ni Shinchou Sugiru" => 38659,
    "Shine Post" => 50221,
    "Shingeki no Bahamut: Genesis" => 21843,
    "Shingeki no Kyojin" => 16498,
    "Shingeki no Kyojin OVA" => 18397,
    "Shingeki no Kyojin Picture Drama" => 19391,
    "Shingeki no Kyojin Season 2" => 25777,
    "Shingeki no Kyojin Season 3" => 35760,
    "Shingeki no Kyojin Season 3 Part 2" => 38524,
    "Shingeki no Kyojin: Ano Hi Kara" => 19285,
    "Shingeki no Kyojin: Kuinaki Sentaku" => 25781,
    "Shingeki no Kyojin: Lost Girls" => 36106,
    "Shingeki no Kyojin: The Final Season" => 40028,
    "Shingeki no Kyojin: The Final Season Part 2" => 48583,
    "Shingeki! Kyojin Chuugakkou" => 31374,
    "Shinigami Bocchan to Kuro Maid" => 47257,
    "Shinka no Mi: Shiranai Uchi ni Kachigumi Jinsei" => 46985,
    "Shinmai Maou no Testament" => 23233,
    "Shinmai Maou no Testament Burst" => 30363,
    "Shinmai Maou no Testament Burst Specials" => 32409,
    "Shinmai Maou no Testament Burst: Toujou Basara no Shigoku Heiwa na Nichijou" => 30365,
    "Shinmai Maou no Testament Departures" => 36688,
    "Shinmai Maou no Testament Departures: Maria no Hizou Eizou" => 39480,
    "Shinmai Maou no Testament Specials" => 30464,
    "Shinmai Maou no Testament: Toujou Basara no Hard Sweet na Nichijou" => 29027,
    "Shinmai Renkinjutsushi no Tenpo Keiei" => 49849,
    "Shinobi no Ittoki" => 51098,
    "Shirobako" => 25835,
    "Shiroi Suna no Aquatope" => 46093,
    "Shironeko Project: Zero Chronicle" => 38843,
    "Shokei Shoujo no Virgin Road" => 47162,
    "Shokugeki no Souma" => 28171,
    "Shoujo Kakumei Utena" => 440,
    "Shoukoku no Altair" => 34547,
    "Show By Rock!!" => 27441,
    "Shuffle!" => 79,
    "Shuumatsu no Harem" => 41946,
    "Shuumatsu no Walküre" => 44942,
    "Sin: Nanatsu no Taizai" => 33834,
    "Sin: Nanatsu no Taizai - Masani Akuma no Shogyou..." => 35559,
    "Sirius" => 37569,
    "SK∞" => 42923,
    "Slayers" => 534,
    "Slime Taoshite 300-nen, Shiranai Uchi ni Level Max ni Nattemashita" => 40586,
    "Slow Loop" => 45425,
    "Snow Halation" => 9930,
    "Somali to Mori no Kamisama" => 39575,
    "SoniAni: Super Sonico The Animation" => 20555,
    "Sonny Boy" => 48849,
    "Sono Bisque Doll wa Koi wo Suru" => 48736,
    "Sora no Aosa wo Shiru Hito yo" => 39569,
    "Sora no Otoshimono" => 5958,
    "Sorairo Utility" => 50209,
    "Sore Ike! Anpanman" => 1960,
    "Soredemo Ayumu wa Yosetekuru" => 45653,
    "Soul Eater" => 3588,
    "Sounan Desu ka?" => 39456,
    "Sousei no Onmyouji" => 32105,
    "Spiral: Suiri no Kizuna" => 341,
    "Spy x Family" => 50265,
    "Spy x Family Part 2" => 50602,
    "SSSS.Gridman" => 35847,
    "Star Wars: Visions" => 49357,
    "Star☆Twinkle Precure" => 38578,
    "Steins;Gate" => 9253,
    "Steins;Gate 0" => 30484,
    "Steins;Gate 0: Kesshou Takei no Valentine - Bittersweet Intermedio" => 37492,
    "Steins;Gate Movie: Fuka Ryouiki no Déjà vu" => 11577,
    "Steins;Gate: Kyoukaimenjou no Missing Link - Divide By Zero" => 32188,
    "Steins;Gate: Oukoubakko no Poriomania" => 10863,
    "Steins;Gate: Soumei Eichi no Cognitive Computing" => 27957,
    "Stranger: Mukou Hadan" => 2418,
    "Strike the Blood" => 18277,
    "Strike the Blood Final" => 49316,
    "Strike the Blood II" => 33286,
    "Strike the Blood III" => 37449,
    "Strike the Blood IV" => 40485,
    "Strike the Blood: Kieta Seisou-hen" => 40486,
    "Strike the Blood: Valkyria no Oukoku-hen" => 30321,
    "Strike Witches" => 3667,
    "Strike Witches 2" => 6381,
    "Strike Witches Movie" => 9751,
    "Strike Witches OVA" => 1862,
    "Strike Witches: 501 Butai Hasshin Shimasu!" => 38004,
    "Strike Witches: 501 Butai Hasshin Shimasu! Movie" => 39987,
    "Strike Witches: Road to Berlin" => 38005,
    "Subarashiki Kono Sekai The Animation" => 42307,
    "Subete ga F ni Naru" => 28621,
    "Succubus Yondara Haha ga Kita!?" => 52617,
    "Suisei no Gargantia" => 16524,
    "Suki ni Naru Sono Shunkan wo.: Kokuhaku Jikkou Iinkai" => 33036,
    "Sukitte Ii na yo." => 14289,
    "Summer Ghost" => 48171,
    "Summer Wars" => 5681,
    "Summertime Render" => 47194,
    "Sunohara-sou no Kanrinin-san" => 36817,
    "Super Cub" => 40685,
    "Suteki na Okurimono" => 50640,
    "Suzume no Tojimari" => 50594,
    "Suzumiya Haruhi no Shoushitsu" => 7311,
    "Suzumiya Haruhi no Yuuutsu" => 849,
    "Suzumiya Haruhi no Yuuutsu (2009)" => 4382,
    "Sword Art Online" => 11757,
    "Sword Art Online Alternative: Gun Gale Online" => 36475,
    "Sword Art Online Alternative: Gun Gale Online - Refrain" => 37831,
    "Sword Art Online II" => 21881,
    "Sword Art Online II: Debriefing" => 27891,
    "Sword Art Online Movie: Ordinal Scale" => 31765,
    "Sword Art Online: Alicization" => 36474,
    "Sword Art Online: Alicization - War of Underworld" => 39597,
    "Sword Art Online: Alicization - War of Underworld 2nd Season" => 40540,
    "Sword Art Online: Extra Edition" => 20021,
    "Sword Art Online: Progressive Movie - Hoshi Naki Yoru no Aria" => 42916,
    "Sword Art Online: Progressive Movie - Kuraki Yuuyami no Scherzo" => 50275,
    "Sylvanian Families: Freya no Happy Diary" => 52964,
    "Taboo Tattoo" => 29758,
    "Tada-kun wa Koi wo Shinai" => 36470,
    "Taimadou Gakuen 35 Shiken Shoutai" => 24133,
    "Taishou Otome Otogibanashi" => 45055,
    "Takanashi Rikka Kai: Chuunibyou demo Koi ga Shitai! Movie" => 19021,
    "Takanashi Rikka Kai: Chuunibyou demo Koi ga Shitai! Movie Lite" => 22859,
    "Takano Kousaten" => 50206,
    "Takt Op. Destiny" => 48556,
    "Tales of Luminaria: The Fateful Crossroad" => 49942,
    "Tales of Zestiria the Cross" => 30911,
    "Tamako Market" => 16417,
    "Tantei wa Mou, Shindeiru." => 46471,
    "Tari Tari" => 13333,
    "Tatakau Shisho: The Book of Bantorra" => 6758,
    "Tate no Yuusha no Nariagari" => 35790,
    "Tate no Yuusha no Nariagari Season 2" => 40356,
    "Tatoeba Last Dungeon Mae no Mura no Shounen ga Joban no Machi de Kurasu Youna Monogatari" => 40594,
    "Tears to Tiara" => 3594,
    "Tegamibachi" => 6444,
    "Teikou Penguin" => 50522,
    "Tejina-senpai" => 38610,
    "Tenchi Souzou Design-bu" => 41762,
    "Tengen Toppa Gurren Lagann" => 2001,
    "Tenki no Ko" => 38826,
    "Tenkuu Shinpan" => 43690,
    "Tensai Ouji no Akaji Kokka Saisei Jutsu" => 47159,
    "Tensei shitara Ken Deshita" => 49891,
    "Tensei shitara Slime Datta Ken" => 37430,
    "Tensei shitara Slime Datta Ken Movie: Guren no Kizuna-hen" => 49877,
    "Terra Formars" => 22687,
    "Tesla Note" => 48680,
    "The Cockpit" => 2500,
    "The First Slam Dunk" => 45649,
    "The God of High School" => 41353,
    "The iDOLM@STER" => 10278,
    "The iDOLM@STER Shiny Festa" => 14835,
    "The iDOLM@STER: 765 Pro to Iu Monogatari" => 11889,
    "The Last: Naruto the Movie" => 16870,
    "The Sky Crawlers" => 3089,
    "Tian Bao Fuyao Lu" => 40735,
    "Tian Bao Fuyao Lu 2nd Season" => 44068,
    "Tian Mei De Yao Hen" => 44390,
    "Tiger &amp; Bunny 2 Part 2" => 52291,
    "To LOVE-Ru" => 3455,
    "To LOVE-Ru Darkness" => 13663,
    "To LOVE-Ru Darkness 2nd" => 28979,
    "To LOVE-Ru Darkness 2nd OVA" => 31380,
    "To LOVE-Ru Darkness 2nd Specials" => 31711,
    "To LOVE-Ru Darkness OVA" => 13851,
    "To LOVE-Ru OVA" => 5667,
    "To LOVE-Ru: Multiplication - Mae kara Ushiro kara" => 35000,
    "Toaru Kagaku no Railgun" => 6213,
    "Toaru Majutsu no Index" => 4654,
    "Tobiuo no Boy wa Byouki Desu" => 26149,
    "Toji no Miko" => 35589,
    "Toki wo Kakeru Shoujo" => 2236,
    "Tokyo 24-ku" => 50204,
    "Tokyo 7th Sisters: Bokura wa Aozora ni Naru" => 41307,
    "Tokyo Ghoul" => 22319,
    "Tokyo Mew Mew" => 687,
    "Tokyo Mew Mew New ♡" => 41589,
    "Tokyo Ravens" => 16011,
    "Tokyo Revengers" => 42249,
    "Tomodachi Game" => 50273,
    "Tonari no Kaibutsu-kun" => 14227,
    "Tonari no Totoro" => 523,
    "Tonikaku Kawaii" => 41389,
    "Tonikaku Kawaii: Seifuku" => 51533,
    "Toradora!" => 4224,
    "Toriko" => 10033,
    "Totsukuni no Shoujo" => 39495,
    "Totsukuni no Shoujo (2022)" => 48405,
    "Touken Ranbu: Hanamaru" => 33023,
    "Tribe Nine" => 49969,
    "Trigun" => 6,
    "Trinity Seven" => 25157,
    "Tropical-Rouge! Precure" => 44191,
    "Tropical-Rouge! Precure Movie: Yuki no Princess to Kiseki no Yubiwa!" => 49426,
    "True Tears" => 2129,
    "Tsugumomo" => 34019,
    "Tsuki ga Kirei" => 34822,
    "Tsuki ga Michibiku Isekai Douchuu" => 43523,
    "Tsuki to Laika to Nosferatu" => 48471,
    "Tsurezure Children" => 34902,
    "Tsuritama" => 12883,
    "Tsurune: Kazemai Koukou Kyuudoubu" => 36653,
    "Tsurune: Kazemai Koukou Kyuudoubu - Yabai" => 38921,
    "Tunshi Xingkong" => 44218,
    "Tunshi Xingkong 2nd Season" => 49571,
    "Uchi no Ko no Tame naraba, Ore wa Moshikashitara Maou mo Taoseru kamo Shirenai." => 39324,
    "Uchi no Shishou wa Shippo ga Nai" => 49533,
    "Uchuu Nanchara Kotetsu-kun" => 42870,
    "Udon no Kuni no Kiniro Kemari" => 32673,
    "Ueno-san wa Bukiyou" => 37920,
    "Ultraman" => 36871,
    "Ultraman Season 2" => 39935,
    "Ulysses: Jehanne Darc to Renkin no Kishi" => 36510,
    "Uma Musume: Pretty Derby (TV)" => 35249,
    "Umayuru" => 51772,
    "Umibe no Étranger" => 40615,
    "Under the Dog" => 27387,
    "Unicorn no Kyupi" => 34990,
    "UQ Holder!: Mahou Sensei Negima! 2" => 33478,
    "Uragiri wa Boku no Namae wo Shitteiru" => 7058,
    "Uramichi Oniisan" => 40620,
    "Urasekai Picnic" => 41392,
    "Urawa no Usagi-chan" => 27927,
    "Urawa no Usagi-chan Special" => 31426,
    "Urusei Yatsura (2022)" => 50710,
    "Usagi Drop" => 10162,
    "Ushio to Tora" => 842,
    "Ushio to Tora" => 842,
    "Ushio to Tora (TV)" => 29854,
    "Ushio to Tora (TV) 2nd Season" => 31098,
    "Ushio to Tora: Comical Deformer Gekijou" => 5308,
    "Utawarerumono" => 856,
    "Utawarerumono OVA" => 3593,
    "Utawarerumono OVA Picture Drama" => 6743,
    "Utawarerumono Specials" => 1830,
    "Utawarerumono: Futari no Hakuoro" => 40590,
    "Utawarerumono: Itsuwari no Kamen" => 30901,
    "Utawarerumono: Itsuwari no Kamen Specials" => 33208,
    "Utawarerumono: Tusukuru-koujo no Karei Naru Hibi" => 36777,
    "Uzaki-chan wa Asobitai!" => 41226,
    "Uzaki-chan wa Asobitai! Double" => 42962,
    "Val x Love" => 39799,
    "Valkyrie Drive: Mermaid" => 30385,
    "Valkyrie Drive: Mermaid Specials" => 31736,
    "Vampire Hunter" => 1952,
    "Vampire Hunter D" => 732,
    "Vampire Hunter D (2000)" => 543,
    "Vampire Knight" => 3457,
    "Vanitas no Karte" => 48580,
    "Vanitas no Karte Part 2" => 49114,
    "Vazzrock The Animation" => 43771,
    "Vinland Saga" => 37521,
    "Violet Evergarden" => 33352,
    "Visual Prison" => 48567,
    "ViVid Strike!" => 33589,
    "Vivy: Fluorite Eye's Song" => 46095,
    "W'z" => 37509,
    "Walkure Romanze" => 19151,
    "Wangan Midnight" => 2608,
    "Watashi ga Motenai no wa Dou Kangaetemo Omaera ga Warui!" => 16742,
    "Watashi ni Tenshi ga Maiorita!" => 37993,
    "Watashi ni Tenshi ga Maiorita! Precious Friends" => 44141,
    "White Album" => 4720,
    "Witch Craft Works" => 21085,
    "Wo de Ni Tian Shen Qi" => 37564,
    "Wolf&#039;s Rain" => 202,
    "Wonder Egg Priority" => 43299,
    "Wonderful Rush" => 14951,
    "Working!!" => 6956,
    "World Trigger" => 24405,
    "World Witches Hasshin Shimasu!" => 42506,
    "Wotaku ni Koi wa Muzukashii" => 35968,
    "X" => 156,
    "Xi Yangyang Yu Hui Tailang: Juezhan Ci Shidai" => 49079,
    "Xian Wang de Richang Shenghuo" => 41094,
    "Xin Weiqi Shaonian" => 51862,
    "Xing Yu Siwan Nian" => 51390,
    "Yagate Kimi ni Naru" => 37786,
    "Yahari Ore no Seishun Love Comedy wa Machigatteiru." => 14813,
    "Yahari Ore no Seishun Love Comedy wa Machigatteiru. Kan" => 39547,
    "Yahari Ore no Seishun Love Comedy wa Machigatteiru. OVA" => 18753,
    "Yahari Ore no Seishun Love Comedy wa Machigatteiru. Zoku" => 23847,
    "Yahari Ore no Seishun Love Comedy wa Machigatteiru. Zoku OVA" => 33161,
    "Yaku nara Mug Cup mo" => 42568,
    "Yaku nara Mug Cup mo: Niban Gama" => 49263,
    "Yakusoku no Neverland" => 37779,
    "Yama no Susume: Next Summit" => 48491,
    "Yamada-kun to 7-nin no Majo (TV)" => 28677,
    "Yamada-kun to 7-nin no Majo: Mou Hitotsu no Suzaku-sai" => 24627,
    "Yami Shibai" => 19383,
    "YanYan Machiko" => 8631,
    "Yao Jing Zhong Zhi Shou Ce" => 41093,
    "Yasuke" => 43697,
    "Yatogame-chan Kansatsu Nikki" => 37940,
    "Yatogame-chan Kansatsu Nikki Nisatsume" => 39960,
    "Yatogame-chan Kansatsu Nikki Sansatsume" => 42959,
    "Yatogame-chan Kansatsu Nikki Yonsatsume" => 50438,
    "Yes ka No ka Hanbun ka" => 40646,
    "Yes! Precure 5" => 1932,
    "Yinhe Zhi Xin" => 44233,
    "Yofukashi no Uta" => 50346,
    "Yojouhan Time Machine Blues" => 49590,
    "Yondemasu yo, Azazel-san. (TV)" => 10216,
    "Yosuga no Sora: In Solitude, Where We Are Least Alone." => 8861,
    "Youjo Senki" => 32615,
    "Youkai Watch ♪" => 48365,
    "Youkoso Jitsuryoku Shijou Shugi no Kyoushitsu e (TV)" => 35507,
    "Youkoso Jitsuryoku Shijou Shugi no Kyoushitsu e 2nd Season" => 51096,
    "Young Black Jack" => 30740,
    "Yowamushi Monsters" => 30119,
    "Yowamushi Pedal" => 18179,
    "Yowamushi Pedal Movie" => 30413,
    "Yowamushi Pedal: Glory Line" => 35789,
    "Yowamushi Pedal: Grande Road" => 24277,
    "Yowamushi Pedal: Limit Break" => 50552,
    "Yowamushi Pedal: New Generation" => 31783,
    "Yowamushi Pedal: Re:RIDE" => 25755,
    "Yowamushi Pedal: Re:ROAD" => 30790,
    "Yowamushi Pedal: Special Ride" => 18177,
    "Yozakura Quartet" => 4548,
    "Yuan Long" => 42284,
    "Yume Oukoku to Nemureru 100-nin no Ouji-sama" => 33966,
    "Yumeria" => 204,
    "Yuragi-sou no Yuuna-san" => 36726,
    "Yuri!!! on Ice" => 32995,
    "Yuru Camp△" => 34798,
    "Yuuki Yuuna wa Yuusha de Aru" => 25519,
    "Yuukoku no Moriarty" => 40911,
    "Yuukoku no Moriarty OVA" => 49733,
    "Yuukoku no Moriarty Part 2" => 43325,
    "Yuusha Party wo Tsuihou sareta Beast Tamer, Saikyoushu no Nekomimi Shoujo to Deau" => 52046,
    "Yuusha, Yamemasu" => 50175,
    "Yuusha, Yamemasu: Kenshuu Ryokou wa Mokuteki wo Miushinau na" => 51562,
    "Yuu☆Yuu☆Hakusho" => 392,
    "Yuyushiki" => 15911,
    "Yu☆Gi☆Oh!: Go Rush!!" => 50607,
    "Zankyou no Terror" => 23283,
    "Zero kara Hajimeru Mahou no Sho" => 34176,
    "Zero no Tsukaima" => 1195,
    "Zetman" => 11837,
    "Zetsuen no Tempest" => 14075,
    "Zettai Junpaku♡Mahou Shoujo" => 16656,
    "Zhen Hun Jie" => 33350,
    "Zhihao Beipan Diqiule" => 49118,
    "Zhu Tian Ji" => 50925,
    "Zi Chuan" => 44389,
    "Zoku Touken Ranbu: Hanamaru" => 34863,
    "Zombie-Loan" => 2404,
    "Zombieland Saga" => 37976,
    "Zuihou de Zhaohuan Shi" => 41915,
    "Zutto Mae kara Suki deshita.: Kokuhaku Jikkou Iinkai" => 31245,
    "Zutto Mae kara Suki deshita.: Kokuhaku Jikkou Iinkai - Kinyoubi no Ohayou" => 36305
  ];


  foreach ($array as $key => $value)
  {
    if ($html = str_get_html(file_get_contents("https://chiaki.site/?/tools/watch_order/id/" . $value[0])))
    {
      if ($table = $html->find('#wo_list', 0))
      {
        if ($subA = $table->find('a'))
        {
          foreach ($subA as $value)
            if (isset($value->href) && !empty($value->href))
              $subB[] = $value->href . "\n";
          if (isset($subA)) unset($subA);
          foreach ($subB as $value)
          {
            if (textInText("myanimelist.net/anime/", $value))
            {
              $id = explode("myanimelist.net/anime/", $value);
              $id = (isset($id[1]) && is_numeric($id[1])) ? $id[1] : ((isset($id[1])) ? explode("/", $id[1]) : "");
              $id = (isset($id[0]) && is_numeric($id[0])) ? $id : $id[0];

              $MALD[$key . " Series"][] = "https://myanimelist.net/anime/" . intval($id); // MyAnimeList Data
              if (isset($id)) unset($id);
            }
          }
          if (isset($subB)) unset($subB);
        }
      }
      if (isset($table)) unset($table);
    }
    if (isset($html)) unset($html);
  }

  print_p(json_encode($MALD));
  $conn->close();
  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "sa")
{ // SA
  $title = "sa";
  // https://chiaki.site/?/tools/watch_order/id/7472

  $DS = Select($conn, "SELECT * FROM `mal__anime` ORDER BY `title` ASC;");
  echo '$array = [<br />';
  foreach ($DS as $key => $value)
  {
    echo '"' . $value["title"] . '" => ' . $value["mal_id"] . ',<br />';
  }


  //print_r($chiaki);
  $conn->close();
  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "multipleepList")
{ // SS
  /*
  
  ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

  $title = "multipleEpList";
  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");

  $isOK = [82, 150, 205, 245, 266, 566, 701, 707, 941, 990, 1184, 1447, 1728];
  $multipleEpList = [132, 133, 147, 156, 180, 202, 219, 232, 311, 320, 347, 366, 368, 381, 382, 395, 440, 441, 486, 491, 494, 496, 498, 511, 512, 540, 548, 551, 553, 579, 588, 591, 642, 656, 657, 680, 698, 702, 752, 785];
  $unSetDataSheet = [135, 139, 140, 141, 143, 151, 162, 163, 183, 190, 191, 193, 194, 199, 201, 206, 210, 211, 212, 215, 217, 220, 222, 226, 227, 231, 234, 236, 238, 240, 249, 250, 255, 262, 263, 271, 273, 274, 275, 276, 279, 280, 281, 291, 302, 305, 308, 312, 313, 314, 330, 335, 341, 350, 351, 355, 365, 374, 379, 384, 385, 393, 408, 409, 410, 412, 413, 414, 415, 418, 419, 424, 425, 427, 433, 435, 437, 442, 443, 447, 449, 452, 454, 458, 459, 467, 473, 474, 481, 485, 487, 489, 490, 503, 505, 506, 507, 508, 509, 513, 515, 521, 524, 525, 532, 535, 539, 545, 557, 560, 561, 562, 563, 570, 573, 574, 578, 583, 606, 607, 609, 612, 614, 620, 625, 632, 637, 640, 643, 647, 653, 654, 658, 665, 677, 678, 686, 689, 690, 696, 697, 700, 704, 708, 709, 710, 713, 723, 727, 738, 754, 755, 758, 759, 763, 765, 769, 773, 775, 777, 778, 779, 783, 787, 790, 810, 815, 817, 829, 834, 837, 844, 849, 876, 879, 882, 963, 989];
  $WrongEpList = 0;
  $DS = Select($conn, "SELECT * FROM `datasheet` WHERE `title` NOT LIKE '%Series%';");

  $ccc = 0;
  foreach ($DS as $key => $value)
  {
    $json = json_decode($value["save"], true);
    if (!empty($json) && !empty($json["links"]) && is_array($json["links"]) && 1 < count($json["links"]))
    {
      $ccc++;
    }
  }

?>
  <div class="col-12">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card bg-dark">
          <div class="card-header border-bottom">
            <h4 class="text-center text-white">Anime lista azokról az animékről melyeknek az EpList-jeit egybe kell rakni.</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-6">
                <?php
                foreach ($DS as $key => $value)
                {
                  $json = json_decode($value["save"], true);
                  if (!empty($json) && !empty($json["links"]) && is_array($json["links"]) && 1 < count($json["links"]))
                  {
                    unset($value["series_id"]);
                    unset($value["link"]);
                    unset($value["myanimelist"]);
                    unset($value["description"]);
                    unset($value["save2"]);
                    unset($value["last_update"]);
                    $value["save"] = array();
                    if (!(in_array($value["id"], $isOK) || !in_array($value["id"], $multipleEpList) || in_array($value["id"], $unSetDataSheet)))
                    { ?>
                      <div class="row px-3">
                        <div class="col-12 mb-3">
                          <div class="row"><?php ?>
                            <div class="border col-2"><?= $value["id"]; ?>
                            </div>
                            <div class="border col-10"><a href="<?= BASEURL ?>DataSheet/<?= $value["id"]; ?>"><?= $value["title"]; ?></a>
                            </div>
                          </div>
                          <?php }
                        $DS2 = Select($conn, "SELECT * FROM `episodelist` WHERE `id` IN (" . implode(", ", $json["links"]) . ");");
                        foreach ($DS2 as $key2 => $value2)
                        {
                          $save = json_decode($value2["save"], true);
                          unset($save["_type"]);
                          $save = (empty($save)) ? ["", ""] : $save;
                          $value["save"][$key2] = ["id" => $value2["id"], "title" => $value2["title"], "link_count" => count($save)];
                          if (!(in_array($value["id"], $isOK) || !in_array($value["id"], $multipleEpList) || in_array($value["id"], $unSetDataSheet)))
                          { ?>
                            <div class="row">
                              <div class="border col-1">-
                              </div>
                              <div class="border col-2"><?= $value2["id"]; ?>
                              </div>
                              <div class="border col-9"><a href="<?= BASEURL ?>EpisodeList/<?= $value2["id"]; ?>"><?= $value2["title"]; ?></a>
                              </div>
                            </div>
                          <?php
                          }
                        }
                        if (!(in_array($value["id"], $isOK) || !in_array($value["id"], $multipleEpList) || in_array($value["id"], $unSetDataSheet)))
                        {
                          $WrongEpList++;
                          ?>
                        </div>
                      </div>
                    <?php
                        }
                      }
                      if (count($multipleEpList) / 2 <= $WrongEpList && !isset($ttDSDASDASDASD))
                      {
                        $ttDSDASDASDASD = "";
                    ?>
              </div>
              <div class="col-6">
            <?php
                      }
                    }
                    //echo count($DS) . " / " . $WrongEpList;
            ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
  ?>
  <div class="col-12">
    <div class="row mb-3">
      <div class="col-12">
        <div class="card bg-dark">
          <div class="card-header border-bottom">
            <h4 class="text-center text-white">Anime lista azokról az animékről melyeknek az DS-eit egybe kell rakni.</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-6">
                <?php
                foreach ($DS as $key => $value)
                {
                  $json = json_decode($value["save"], true);
                  if (!empty($json) && !empty($json["links"]) && is_array($json["links"])  && 1 < count($json["links"]))
                  {
                    unset($value["series_id"]);
                    unset($value["link"]);
                    unset($value["myanimelist"]);
                    unset($value["description"]);
                    unset($value["save2"]);
                    unset($value["last_update"]);
                    $value["save"] = array();
                    if (!(in_array($value["id"], $isOK) || in_array($value["id"], $multipleEpList) || !in_array($value["id"], $unSetDataSheet)))
                    { ?>
                      <div class="row px-3">
                        <div class="col-12 mb-3">
                          <div class="row"><?php ?>
                            <div class="border col-2"><?= $value["id"]; ?>
                            </div>
                            <div class="border col-10"><a href="<?= BASEURL ?>DataSheet/<?= $value["id"]; ?>"><?= $value["title"]; ?></a>
                            </div>
                            <?php $ll = (!isset($ll) || empty($ll)) ? 1 : $ll + 1; ?>
                          </div>
                        <?php }

                      if (!(in_array($value["id"], $isOK) || in_array($value["id"], $multipleEpList) || !in_array($value["id"], $unSetDataSheet)))
                      {
                        $WrongEpList++;
                        ?>
                        </div>
                      </div>
                    <?php
                      }
                    }
                    if (count($unSetDataSheet) / 2 <= $WrongEpList && !isset($ttDSDASDASDASD))
                    {
                      $ttDSDASDASDASD = "";
                    ?>
              </div>
              <div class="col-6">
            <?php
                    }
                  }
                  //echo count($DS) . " / " . $WrongEpList;
            ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?= $ll; ?>
  </div>
<?php

  require_once("NewViews/footer.phtml");
  $conn->close();


  exit();
}
/****************************************************************** */
if (isset($_GET["s"]))
{ // Keresés

  $title = "Keresés: " . $_GET["s"];
  $_GET["s"] = RealEscapeString($_GET["s"]);


  if (!empty($_GET["s"]))
  {
    foreach (explode(" ", $_GET["s"]) as $word)
    {
      if (!empty($word))
        if (!empty($where))
        {
          $where .= " && (`mal__anime`.`title` LIKE '%" . $word . "%'";
          $where .= " || `datasheet`.`title` LIKE '%" . $word . "%'";
          $where .= " || `mal__anime`.`english` LIKE '%" . $word . "%'";
          $where .= " || `mal__anime`.`synonyms` LIKE '%" . $word . "%'";
          $where .= " || `mal__anime`.`japanese` LIKE '%" . $word . "%')";
        }
        else
        {
          $where = "(`mal__anime`.`title` LIKE '%" . $word . "%'";
          $where .= " || `datasheet`.`title` LIKE '%" . $word . "%'";
          $where .= " || `mal__anime`.`english` LIKE '%" . $word . "%'";
          $where .= " || `mal__anime`.`synonyms` LIKE '%" . $word . "%'";
          $where .= " || `mal__anime`.`japanese` LIKE '%" . $word . "%')";
        }
    }
  }

  $where = (empty($where)) ? 1 : $where;
  $goupBy = 'GROUP BY datasheet.id, mal__anime.title, mal__anime.img';
  $orderBy = 'ORDER BY datasheet.series_id ASC, datasheet.title ASC';
  $mal = Select($conn, "SELECT `datasheet`.`id`, `datasheet`.`title`, `mal__anime`.`img`, `datasheet`.`link`, `datasheet`.`datasheet` FROM `mal__anime` INNER JOIN `datasheet` ON (`datasheet`.`myanimelist` = `mal__anime`.`mal_id`) WHERE (" . $where . ") ".$goupBy." ".$orderBy." LIMIT 120;");
  if (!isset($mal[0]))
  {
    $mal = Select($conn, "SELECT `datasheet`.`id`, `datasheet`.`title`, `mal__anime`.`img`, `datasheet`.`link`, `datasheet`.`datasheet` FROM `mal__anime` INNER JOIN `datasheet` ON `datasheet`.`id` = `mal__anime`.`id` ".$goupBy." ORDER BY RAND() ASC LIMIT 12;");
    $error = "Mivel nincs találat, így mutatunk néhány random kiválasztott animét.";
  }

  require_once("NewViews/header.phtml");
  require_once("NewViews/navbar.phtml");
  require_once("Views/search.phtml");
  require_once("NewViews/footer.phtml");
  $conn->close();
  exit();
}
/****************************************************************** */
if (!isset($_GET["s"]) || (isset($g[0]) && strtolower($g[0]) == "home"))
{ // Kezdőlap
  siteHome($conn);
}
/****************************************************************** */

if (isset($g[0]) && !empty($g[0]))
{
  if (isset($g[1]) && !empty($g[1]) && !is_numeric($g[1]))
    switch ($g[0])
    {
      case '':
        # code...
        break;

      default:
        siteHome($conn);
        break;
    }
  else
    switch ($g[0])
    {
      case '':
        # code...
        break;

      default:
        siteHome($conn);
        break;
    }
}
elseif (isset($_GET["s"]) && !empty($_GET["s"]))
{
}
function getEpisodelist2($conn, $q, $w)
{
  $Episodelist = array();
  $q = json_decode($q, true);
  if (is_array($q["links"]))
    foreach ($q["links"] as $id)
    {
      if ($w == true)
        $uploader2 = Select($conn, 'SELECT `id`, `title`, `link` FROM `episodelist` WHERE `id` = ' . $id . ' LIMIT 1');
      else
        $uploader2 = Select($conn, 'SELECT `id`, `title`, `link` FROM `datasheet` WHERE `id` = ' . $id . ' LIMIT 1');
      if (!empty($uploader2[0]["title"]))
      {
        $Episodelist[] = [
          "id" => $uploader2[0]["id"],
          "link" => $uploader2[0]["link"],
          "title" => $uploader2[0]["title"],
          "type" => ($w == true) ? "EpisodeList" : "DataSheet"
        ];
      }
      unset($uploader2);
    }
  return $Episodelist;
}


function getUploaders($conn, $q)
{
  $uploadersList = array();
  foreach ($q as $key => $value)
  {
    if (!empty($value["fansub1"]))
      $uploader2[] = Select($conn, 'SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub1"] . ' LIMIT 1');
    if (!empty($value["fansub2"]))
      $uploader2[] = Select($conn, 'SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub2"] . ' LIMIT 1');
    if (!empty($value["fansub3"]))
      $uploader2[] = Select($conn, 'SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub3"] . ' LIMIT 1');


    switch (count($uploader2))
    {
      case 1:
        if (isset($uploader2[0][0]["id"]))
          $uploadersList[] = str_replace(["{{ID}}", "{{NAME}}"], [$uploader2[0][0]["id"], $uploader2[0][0]["name"]], FS_BUTTON);
        break;
      case 2:
        if (isset($uploader2[0][0]["id"]) && isset($uploader2[1][0]["id"]))
          $uploadersList[] = str_replace(["{{ID}}", "{{NAME}}"], [$uploader2[0][0]["id"], $uploader2[0][0]["name"]], FS_BUTTON)
            . ' & ' . str_replace(["{{ID}}", "{{NAME}}"], [$uploader2[1][0]["id"], $uploader2[1][0]["name"]], FS_BUTTON);
        break;
      case 3:
        if (isset($uploader2[0][0]["id"]) && isset($uploader2[1][0]["id"]) && isset($uploader2[2][0]["id"]))
          $uploadersList[] = str_replace(["{{ID}}", "{{NAME}}"], [$uploader2[0][0]["id"], $uploader2[0][0]["name"]], FS_BUTTON)
            . ' & ' . str_replace(["{{ID}}", "{{NAME}}"], [$uploader2[1][0]["id"], $uploader2[1][0]["name"]], FS_BUTTON)
            . ' & ' . str_replace(["{{ID}}", "{{NAME}}"], [$uploader2[2][0]["id"], $uploader2[2][0]["name"]], FS_BUTTON);
        break;

      default:
        $uploadersList[] = str_replace(["{{ID}}", "{{NAME}}"], [369, "Ismeretlen Forrás"], FS_BUTTON);
        break;
    }
    unset($uploader2);
  }
  return implode(", ", $uploadersList);
}
function myanimelist($conn, $q)
{
  $data["anime"] =
    Select(
      $conn,
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

function weekday($offset)
{
  $day = 24 * 60 * 60;
  $today = time() - (time() % $day);
  $num = str_replace("-", "", str_replace("+", "", $offset));
  if (str_split($offset)[0] == "-")
    $ret = $today - ($num * $day);
  else
    $ret = $today + ($num * $day);

  if (3 < $num)
    $ret = date("m. d.", $ret);
  else
    $ret = changedays(date("l", $ret));


  return $ret;
}
function weekdaytime($offset)
{
  $day = 24 * 60 * 60;
  $today = time() - (time() % $day);
  $num = str_replace("-", "", str_replace("+", "", $offset));
  if (str_split($offset)[0] == "-")
    $ret = $today - ($num * $day);
  else
    $ret = $today + ($num * $day);


  return $ret;
}
function changedays($day)
{
  $dayNames = [
    'Monday' => 'Hétfő',
    'Tuesday' => 'Kedd',
    'Wednesday' => 'Szerda',
    'Thursday' => 'Csütörtök',
    'Friday' => 'Péntek',
    'Saturday' => 'Szombat',
    'Sunday' => 'Vasárnap'
  ];
  return $dayNames[$day];
}
