<?php

!defined("DS")                && define("DS",                 DIRECTORY_SEPARATOR);
!defined("CURRENT_DIR")       && define("CURRENT_DIR",        dirname(__DIR__, 1) . DS);

require_once("OtherCode/urlFunction.php");
require_once("OtherCode/stringFunction.php");
require_once("OtherCode/fileFunction.php");
require_once("OtherCode/shd.php");
//exit(require_once("503.php"));
ini_set('display_errors', 'On');
//$template = findfilefromdir("Assets/template");
//$template[] = $template[0];
//unset($template[0]);
$search = (isset($_GET["search"])) ? $_GET["search"] : "";

$footer = '
  <!-- Optional JavaScript; choose one of the two! -->

  <!-- Option 1: Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  <!-- Option 2: Separate Popper and Bootstrap JS -->
  <!--
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
  -->
  <script src="http://sortablejs.github.io/Sortable/Sortable.js"></script>
  <script>/*
  var sortable = Sortable.create(el);
    new Sortable(example1, {
        animation: 150,
        ghostClass: \'blue-background-class\'
    });*/
  </script>
  </body>
  </html>
';

//$servername = "localhost";
//$username = "rlight";
//$database = "animem";

require_once("../../Config/loadConfig.php");

$siteConfig = DbConfig::getSiteConfig();
if (!defined("BASEURL")) {
  define("BASEURL", "https://".$siteConfig['domain']."/admin/");
}
if (!defined("ASSETSURL")) {
  define("ASSETSURL", "https://" . $siteConfig['domain'] . "/admin/Assets/");
}

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);


$title = "DataSheet";


if (isset($_GET["search"]))
{
  require_once("Views/header.phtml");
  require_once("Views/admin-navbar.phtml");
  $title = str_replace(["'", "’", '"'], ["\'", "\'", '\"'], $_GET["search"]);
  $arr = Select($conn, 'SELECT * FROM `datasheet` WHERE `datasheet`.`title` LIKE "%' . $title . '%" ORDER BY `title`'); ?>
  <div class="container-fluid">
    <div class="row">
      <h3>
        Search: <?= $title; ?>
      </h3>
    </div>
    <div class="row">
      <div class="col">
        <table class="table table-striped">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Cim</th>
              <th scope="col">Link</th>
              <th scope="col">Edit</th>
            </tr>
          </thead>
          <tbody class="list">
            <?php


            foreach ($arr as $key => $value)
            {
            ?>
              <tr>
                <th scope="row"><?= $key + 1; ?></th>
                <td><?= $value["title"]; ?></td>
                <td>[<a href="<?= str_replace('admin/', '', BASEURL); ?>DataSheet/<?= $value["id"]; ?>/<?= $value["link"]; ?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL; ?>datasheet.php?edit=<?= $value["id"]; ?>" target="_blank">Edit</a>]</td>
              </tr>
            <?php
            } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php echo $footer;
}
elseif (isset($_GET["edit"]))
{
  require_once("Views/header.phtml");
  require_once("Views/admin-navbar.phtml");
  $arr = Select($conn, 'SELECT * FROM `datasheet` WHERE `id` = "' . $_GET["edit"] . '" LIMIT 1');

  if (isset($arr[0]['id']))
  {
    $arr = $arr[0];
    $id = $arr["id"];
    $title = $arr["title"];
    $link = $arr["link"];
    $myanimelist = $arr["myanimelist"];
    $description = $arr["description"];
    $datasheet = $arr["datasheet"];
    $series = $arr["series"];
    $fansub = $arr["save"];
    $links = $arr["save"];

    require_once("template/datasheet/edit.php");
    echo $footer;
  }
}
elseif (isset($_GET["new"]) && !empty($_GET["new"]))
{
  $data["fansub"] = array();
  $data["links"] = array();
  $link = str_replace(["'", " ", "?", "!", '"', "__"], "_", $_GET["new"]);
  $title = str_replace(["'", '"'], ["\'", '\"'], $_GET["new"]);
  $arr = Select($conn, 'SELECT `datasheet`.`id` FROM `datasheet` WHERE `datasheet`.`link` = "' . $link . '" LIMIT 1');
  //print_p($_GET);
  if (isset($arr[0]["id"]))
  {
    $id = $arr[0]["id"];
  }
  else
  {
    $id = Insert($conn, "INSERT INTO `datasheet` (`title`,`link`, `save`) VALUES ('" . $title . "', '" . $link . "', '" . json_encode($data) . "')", TRUE);
  }
  header("Location: " . BASEURL . "datasheet.php?edit=" . $id);
}
elseif (isset($_GET["mininews"]) && !empty($_GET["mininews"]))
{
  $_GET["link_url"] = (isset($_GET["link_url"]) && !empty($_GET["link_url"])) ? $conn->real_escape_string($_GET["link_url"]) : NULL;
  $_GET["link_description"] = (isset($_GET["link_description"]) && !empty($_GET["link_description"])) ? $conn->real_escape_string($_GET["link_description"]) : NULL;

  print_p($_GET);
  // die();

  if ($_GET["mininews"] != NULL)
  {
    Insert($conn, "INSERT INTO `wp_links`(`link_url`, `link_description`)
                    VALUES ('" . $_GET["link_url"] . "',
                            '" . $_GET["link_description"] . "')");
  }
  header("Location: " . BASEURL . "datasheet.php?edit=" . $_GET["mininews"]);
}
elseif (isset($_GET["delete"]))
{
  $select = Select($conn, 'SELECT `datasheet`.`id` FROM `datasheet` WHERE `datasheet`.`id` = "' . $_GET["delete"] . '" LIMIT 1');
  //print_p($_GET);
  // Insert($conn, "INSERT INTO `links` (`link`) VALUES ('" . $value2 . "')", TRUE);

  if (is_array($select) && isset($select[0]["id"]))
    Update($conn, "DELETE FROM `datasheet` WHERE `id` =" . $select[0]["id"] . "");
  //die;
  header("Location: " . BASEURL . "datasheet.php");
}
elseif (isset($_GET["save"]))
{


  $title = str_replace(["'", '"'], ["\'", '\"'], $_GET["title"]);
  $link = str_replace(["'", " ", "?", "!", '"', "__"], "_", $_GET["link"]);

  $myanimelist = (empty($_GET["myanimelist"]) || !isset($_GET["myanimelist"]) || !is_numeric($_GET["myanimelist"])) ? 2 : $_GET["myanimelist"];
  $datasheet = (isset($_GET["datasheet"]) && $_GET["datasheet"] == 0) ? 0 : 1;
  $series = (isset($_GET["series"]) && $_GET["series"] == 0) ? 0 : 1;
  $description = str_replace(["'", '"'], ["\'", '\"'], $_GET["description"]);



  $json["_type"] = "datasheet";
  $json["fansub"] = json_decode($_GET["fansub"]);
  $json["links"] = json_decode($_GET["episodelist"]);

  // print_p($json);

  $select = Select($conn, "SELECT `datasheet`.`id`, `datasheet`.`save` FROM `datasheet` WHERE `datasheet`.`id` = '" . $_GET["save"] . "' ORDER BY `id` ASC LIMIT 1");


  if (textInText("Series", $title))
    Update($conn, "UPDATE `datasheet` SET `series_id` = NULL WHERE `series_id`= {$_GET["save"]};");
  else
    Update($conn, "UPDATE `episodelist` SET `datasheet_id` = NULL WHERE `datasheet_id`= {$_GET["save"]};");
  
  if (is_array($select) && isset($select[0]["id"]))
  {
    if (!empty($json["links"]))
      foreach($json["links"] as $key => $EpID)
      {
        if (textInText("Series", $title))
          Update($conn, "UPDATE `datasheet` SET `series_id` = {$_GET["save"]}, `row` = {$key} WHERE `id` = {$EpID};");
        else
          Update($conn, "UPDATE `episodelist` SET `datasheet_id` = {$_GET["save"]}, `row` = {$key} WHERE `id` = {$EpID}");
      }
      $json = json_encode($json);
    Update($conn, "UPDATE `datasheet` SET
      `save2`='" . $select[0]["save"] . "',
      `save`='" . $json . "',
      `title`='" . $title . "',
      `link`='" . $link . "',
      `description`='" . $description . "',
      `myanimelist`= " . $myanimelist . ",
      `datasheet`= " . $datasheet . ",
      `series`= " . $series . "
      WHERE `id` = '" . $select[0]["id"] . "'");

    $links = json_decode($_GET["episodelist"], true);
    if (is_array($links) && !empty($links))
    {
      foreach ($links as $key => $value)
      {
        $id_list = (isset($id_list) && is_numeric($value)) ? $id_list . "," . $value : $value;
      }
      if (!empty($id_list))
        Update($conn, "UPDATE `datasheet` SET `series` = 0 WHERE `id` IN (" . $id_list . ")");
    }

    if (!empty($json))
      $save = json_decode($json, true);
    $config["anime"]["fansub"] = $save["fansub"];

    $config["anime"]["description2"] = $description;
    $config["id"] = $myanimelist;
    $config = getMyanimelist($dbConfig, $config);
    $config["anime"]["id"] = $select[0]["id"];
    //print_r($config);
    Save($dbConfig, malParser($dbConfig, $config));
  }


  header("Location: " . BASEURL . "datasheet.php?edit=" . $_GET["save"]);
}
elseif (isset($_GET["reform"]))
{

  $select = Select($conn, 'SELECT `datasheet`.`id`, `datasheet`.`save`, `datasheet`.`save2`, `datasheet`.`link`, `datasheet`.`title` FROM `datasheet` WHERE `datasheet`.`id` = "' . $_GET["reform"] . '" LIMIT 1');
  print_p($_GET);
  print_p($select);
  if (isset($select[0]["save2"]) && !empty($select[0]["save2"]))
  {
    Update($conn, "UPDATE `datasheet` SET `save`='" . $conn->real_escape_string($select[0]["save2"]) . "' WHERE `id` = '" . $select[0]["id"] . "'");
  }


  header("Location: " . BASEURL . "datasheet.php?edit=" . $_GET["reform"]);
}
else
{
  require_once("Views/header.phtml");
  require_once("Views/admin-navbar.phtml");
  $arr = Select($conn, 'SELECT * FROM `datasheet`  ORDER BY `title`');

?>
  <div class="container-fluid">
    <div class="row">
      <h3>
        All DataSheet: <?= count($arr); ?>
      </h3>
    </div>
    <div class="row">
      <div class="col">
        <table class="table table-striped">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Cim</th>
              <th scope="col">Link</th>
              <th scope="col">Edit</th>
            </tr>
          </thead>
          <tbody class="list">
            <?php


            foreach ($arr as $key => $value)
            {
            ?>
              <tr>
                <th scope="row"><?= $key + 1; ?></th>
                <td><?= $value["title"]; ?></td>
                <td>[<a href="<?= str_replace('admin/', '', BASEURL); ?>DataSheet/<?= $value["id"]; ?>/<?= $value["link"]; ?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL; ?>datasheet.php?edit=<?= $value["id"]; ?>" target="_blank">Edit</a>]</td>
              </tr>
            <?php
            }

            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php

  echo $footer;
}


function malParser($dbConfig, $config)
{
  $mal = $config["anime"];
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

  $data["id"] = $mal["id"];
  $data["myanimelist"] = $mal["mal_id"];
  $data["title"] = $mal["title"];
  $data["episodes"] = $mal["information"]["episodes"];
  $data["atitle"] = $mal["information"]["english"];
  $data["synonyms"] = $mal["information"]["synonyms"];
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


  $data["preview"] = $mal["others"]["preview"];
  $data["coverImage"] = $mal["img"];
  $data["status"] = $mal["information"]["status"];
  $data["type"] = getTypeB($dbConfig, "type", $mal["information"]["type"]);
  $data["source"] = getTypeB($dbConfig, "source", $mal["information"]["source"]);
  $data["ageRating"] = getTypeB($dbConfig, "age_rating", $mal["information"]["rating"]);
  $data["premieredYear"] = $mal["information"]["premieredYear"];
  $data["premieredSeasonal"] = $mal["information"]["premieredSeasonal"];
  $data["airedStart"] = $mal["information"]["airedStart"];
  $data["airedEnd"] = $mal["information"]["airedEnd"];
  return $data;
}

function Save($dbConfig, $config)
{

  if (isset($config["myanimelist"]) && !empty($config["myanimelist"]) && is_numeric($config["myanimelist"]))
  {
    if (!issetAnime($dbConfig, $config["myanimelist"]))
    {
      $data = [
        // Only string
        "title"               => RealEscapeString($dbConfig, ((isset($config["title"])              && !empty($config["title"])          && is_string($config["title"])) ? $config["title"] : NULL)), // Def: NULL
        "english"             => RealEscapeString($dbConfig, ((isset($config["atitle"])             && !empty($config["atitle"])         && is_string($config["atitle"])) ? $config["atitle"] : NULL)), // Def: NULL
        "synonyms"            => RealEscapeString($dbConfig, ((isset($config["synonyms"])           && !empty($config["synonyms"])       && is_string($config["synonyms"])) ? $config["synonyms"] : NULL)), // Def: NULL
        "japanese"            => RealEscapeString($dbConfig, ((isset($config["jtitle"])             && !empty($config["jtitle"])         && is_string($config["jtitle"])) ? $config["jtitle"] : NULL)), // Def: NULL
        "synopsis"            => RealEscapeString($dbConfig, ((isset($config["description"])        && !empty($config["description"])    && is_string($config["description"])) ? $config["description"] : "Nincs leírás.")), // Def: "Nincs leírás."
        "synopsis2"            => RealEscapeString($dbConfig, ((isset($config["description2"])        && !empty($config["description2"])    && is_string($config["description2"])) ? $config["description2"] : "Nincs leírás.")), // Def: "Nincs leírás."
        "preview"             => RealEscapeString($dbConfig, ((isset($config["preview"])            && !empty($config["preview"])        && is_string($config["preview"])) ? $config["preview"] : NULL)), // Def: NULL
        "img"                 => RealEscapeString($dbConfig, ((isset($config["coverImage"])         && !empty($config["coverImage"])     && is_string($config["coverImage"])) ? $config["coverImage"] : NULL)), // Def: NULL
        // "Currently Airing" || "Finished Airing" || "Not yet aired"
        "status"              => RealEscapeString($dbConfig, ((isset($config["status"])             && !empty($config["status"])         && textInText(["Currently Airing", "Finished Airing", "Not yet aired"], $config["status"])) ? $config["status"] : NULL)), // Def: NULL
        // Only numeric
        "id"             => RealEscapeString($dbConfig, ((isset($config["id"])               && !empty($config["id"])           && is_numeric($config["id"]) &&  $config["id"]) ? $config["id"] : NULL)), // Def: NULL
        "type_id"             => RealEscapeString($dbConfig, ((isset($config["type"])               && !empty($config["type"])           && is_numeric($config["type"]) &&  issetAnimeType($dbConfig, $config["type"])) ? $config["type"] : 5)), // Def: 5
        "source_id"           => RealEscapeString($dbConfig, ((isset($config["source"])             && !empty($config["source"])         && is_numeric($config["source"]) &&  issetAnimeSource($dbConfig, $config["source"])) ? $config["source"] : 6)), // Def: 6
        "age_rating_id"       => RealEscapeString($dbConfig, ((isset($config["ageRating"])          && !empty($config["ageRating"])      && is_numeric($config["ageRating"]) &&  issetAnimeAgeRating($dbConfig, $config["ageRating"])) ? $config["ageRating"] : 6)), // Def: 6 || 7
        "mal_id"              => RealEscapeString($dbConfig, ((isset($config["myanimelist"])             && !empty($config["myanimelist"])         && is_numeric($config["myanimelist"])) ? $config["myanimelist"] : "")), // Def: It cannot be empty!
        "premiered_year"      => RealEscapeString($dbConfig, ((isset($config["premieredYear"])      && !empty($config["premieredYear"])  && is_numeric($config["premieredYear"])) ? $config["premieredYear"] : NULL)), // Def: NULL
        // "Winter" || "Spring" || "Summer" || "Fall" || NULL
        "premiered_seasonal"  => RealEscapeString($dbConfig, ((isset($config["premieredSeasonal"])  && !empty($config["premieredSeasonal"]) && textInText(["Winter", "Spring", "Summer", "Fall"], $config["premieredSeasonal"])) ? $config["premieredSeasonal"] : NULL)), // Def: NULL
        // Numeric || String
        "episodes"            => RealEscapeString($dbConfig, ((isset($config["episodes"])           && !empty($config["episodes"])       && (is_string($config["episodes"]) || is_numeric($config["episodes"]))) ? $config["episodes"] : "?")), // Def: ?
        // Only date (yyyy-mm-dd)
        "aired_start"         => RealEscapeString($dbConfig, ((isset($config["airedStart"])         && !empty($config["airedStart"])     && dateChecked($config["airedStart"])) ? $config["airedStart"] : "1000-01-01")), // Def: 1000-01-01
        "aired_end"           => RealEscapeString($dbConfig, ((isset($config["airedEnd"])           && !empty($config["airedEnd"])       && dateChecked($config["airedStart"])) ? $config["airedEnd"] : "1000-01-01")), // Def: 1000-01-01
        // Only array (JSON)
        "fansub"           => RealEscapeString($dbConfig, ((isset($config["fansub"])           && !empty($config["fansub"])       && is_array($config["fansub"])) ? json_encode($config["fansub"]) : json_encode([]))) // Def: []
      ];
      //print_p($data);
      //die;
      $ID = saveAnime($dbConfig, $data);
      if (is_numeric($ID))
      {
        if (!empty($config["genres"]) && is_array($config["genres"]))
          foreach ($config["genres"] as $key => $value)
            if (!empty($key) && is_numeric($key))
              if (issetGenreID($dbConfig, $key))
                setAnimeGenre($dbConfig, $ID, $key);

        if (isset($config["studios"]["studios"]))
        {
          foreach ($config["studios"]["studios"] as $key => $value)
            if ((!empty($key) && is_numeric($key)) || $key == 0)
              if (($sid = issetStudioID($dbConfig, $key)) && ($sid != false || $sid != 0))
                setAnimeStudio($dbConfig, $ID, $sid, 1);
        }
        if (isset($config["studios"]["licensors"]))
        {
          foreach ($config["studios"]["licensors"] as $key => $value)
            if ((!empty($key) && is_numeric($key)) || $key == 0)
              if (($sid = issetStudioID($dbConfig, $key)) && ($sid != false || $sid != 0))
                setAnimeStudio($dbConfig, $ID, $sid, 2);
        }
        if (isset($config["studios"]["producers"]))
        {
          foreach ($config["studios"]["producers"] as $key => $value)
            if ((!empty($key) && is_numeric($key)) || $key == 0)
              if (($sid = issetStudioID($dbConfig, $key)) && ($sid != false || $sid != 0))
                setAnimeStudio($dbConfig, $ID, $sid, 3);
        }

        //file_get_contents("https://dash.otamoon.hu/api.php/?token=" . $token . "&newanime=". $ID);
        return array(TRUE, "" . str_replace("{{link}}", "" . "MyAnimeList/View/"  . $ID, ""), "success");
      }
    }
    else
    {

      $ID = $config["id"];


      $data = [
        // Only string
        "title"               => RealEscapeString($dbConfig, ((isset($config["title"])              && !empty($config["title"])          && is_string($config["title"])) ? $config["title"] : NULL)), // Def: NULL
        "english"             => RealEscapeString($dbConfig, ((isset($config["atitle"])             && !empty($config["atitle"])         && is_string($config["atitle"])) ? $config["atitle"] : NULL)), // Def: NULL
        "synonyms"            => RealEscapeString($dbConfig, ((isset($config["synonyms"])           && !empty($config["synonyms"])       && is_string($config["synonyms"])) ? $config["synonyms"] : NULL)), // Def: NULL
        "japanese"            => RealEscapeString($dbConfig, ((isset($config["jtitle"])             && !empty($config["jtitle"])         && is_string($config["jtitle"])) ? $config["jtitle"] : NULL)), // Def: NULL
        "synopsis"            => RealEscapeString($dbConfig, ((isset($config["description"])        && !empty($config["description"])    && is_string($config["description"])) ? $config["description"] : "Nincs leírás.")), // Def: "Nincs leírás."
        "synopsis2"            => RealEscapeString($dbConfig, ((isset($config["description2"])        && !empty($config["description2"])    && is_string($config["description2"])) ? $config["description2"] : "Nincs leírás.")), // Def: "Nincs leírás."
        "preview"             => RealEscapeString($dbConfig, ((isset($config["preview"])            && !empty($config["preview"])        && is_string($config["preview"])) ? $config["preview"] : NULL)), // Def: NULL
        "img"                 => RealEscapeString($dbConfig, ((isset($config["coverImage"])         && !empty($config["coverImage"])     && is_string($config["coverImage"])) ? $config["coverImage"] : NULL)), // Def: NULL
        // "Currently Airing" || "Finished Airing" || "Not yet aired"
        "status"              => RealEscapeString($dbConfig, ((isset($config["status"])             && !empty($config["status"])         && textInText(["Currently Airing", "Finished Airing", "Not yet aired"], $config["status"])) ? $config["status"] : NULL)), // Def: NULL
        // Only numeric
        "id"             => RealEscapeString($dbConfig, ((isset($config["id"])               && !empty($config["id"])           && is_numeric($config["id"]) &&  $config["id"]) ? $config["id"] : NULL)), // Def: NULL
        "type_id"             => RealEscapeString($dbConfig, ((isset($config["type"])               && !empty($config["type"])           && is_numeric($config["type"]) &&  issetAnimeType($dbConfig, $config["type"])) ? $config["type"] : 5)), // Def: 5
        "source_id"           => RealEscapeString($dbConfig, ((isset($config["source"])             && !empty($config["source"])         && is_numeric($config["source"]) &&  issetAnimeSource($dbConfig, $config["source"])) ? $config["source"] : 6)), // Def: 6
        "age_rating_id"       => RealEscapeString($dbConfig, ((isset($config["ageRating"])          && !empty($config["ageRating"])      && is_numeric($config["ageRating"]) &&  issetAnimeAgeRating($dbConfig, $config["ageRating"])) ? $config["ageRating"] : 6)), // Def: 6 || 7
        "mal_id"              => RealEscapeString($dbConfig, ((isset($config["myanimelist"])             && !empty($config["myanimelist"])         && is_numeric($config["myanimelist"])) ? $config["myanimelist"] : "")), // Def: It cannot be empty!
        "premiered_year"      => RealEscapeString($dbConfig, ((isset($config["premieredYear"])      && !empty($config["premieredYear"])  && is_numeric($config["premieredYear"])) ? $config["premieredYear"] : NULL)), // Def: NULL
        // "Winter" || "Spring" || "Summer" || "Fall" || NULL
        "premiered_seasonal"  => RealEscapeString($dbConfig, ((isset($config["premieredSeasonal"])  && !empty($config["premieredSeasonal"]) && textInText(["Winter", "Spring", "Summer", "Fall"], $config["premieredSeasonal"])) ? $config["premieredSeasonal"] : NULL)), // Def: NULL
        // Numeric || String
        "episodes"            => RealEscapeString($dbConfig, ((isset($config["episodes"])           && !empty($config["episodes"])       && (is_string($config["episodes"]) || is_numeric($config["episodes"]))) ? $config["episodes"] : "?")), // Def: ?
        // Only date (yyyy-mm-dd)
        "aired_start"         => RealEscapeString($dbConfig, ((isset($config["airedStart"])         && !empty($config["airedStart"])     && dateChecked($config["airedStart"])) ? $config["airedStart"] : "1000-01-01")), // Def: 1000-01-01
        "aired_end"           => RealEscapeString($dbConfig, ((isset($config["airedEnd"])           && !empty($config["airedEnd"])       && dateChecked($config["airedStart"])) ? $config["airedEnd"] : "1000-01-01")), // Def: 1000-01-01
        // Only array (JSON)
        "fansub"           => RealEscapeString($dbConfig, ((isset($config["fansub"])           && !empty($config["fansub"])       && is_array($config["fansub"])) ? json_encode($config["fansub"]) : json_encode([]))) // Def: []
      ];

      foreach ($data as $key => $value) $data[$key] = (is_string($value) && !is_numeric($value) && !is_null($value)) ? "'" . htmlspecialchars($value) . "'" : $value;


      $sql = "UPDATE `mal__anime` SET
        `title`={$data["title"]},
        `english`={$data["english"]},
        `synonyms`={$data["synonyms"]},
        `japanese`={$data["japanese"]},
        `type_id`={$data["type_id"]},
        `episodes`={$data["episodes"]},
        `premiered_year`={$data["premiered_year"]},
        `premiered_seasonal`={$data["premiered_seasonal"]},
        `source_id`={$data["source_id"]},
        `age_rating_id`={$data["age_rating_id"]},
        `preview`={$data["preview"]},
        `synopsis`={$data["synopsis"]},
        `synopsis2`={$data["synopsis2"]},
        `aired_start`={$data["aired_start"]},
        `aired_end`={$data["aired_end"]},
        `status`={$data["status"]},
        `img`={$data["img"]},
        `fansub`={$data["fansub"]},
        `mal_id`={$config["myanimelist"]}
      WHERE `id` = {$ID} LIMIT 1;";



      Update2($dbConfig, $sql);

      if (!empty($config["genres"]) && is_array($config["genres"]))
        foreach ($config["genres"] as $key => $value)
          if (!empty($key) && is_numeric($key))
            if (issetGenreID($dbConfig, $key))
              setAnimeGenre($dbConfig, $ID, $key);

      if (isset($config["studios"]["studios"]) && is_array($config["studios"]["studios"]))
      {
        foreach ($config["studios"]["studios"] as $key => $value)
          if ((!empty($key) && is_numeric($key)) || $key == 0)
            if (($sid = issetStudioID($dbConfig, $key)) && ($sid != false || $sid != 0))
              setAnimeStudio($dbConfig, $ID, $sid, 1);
      }
      if (isset($config["studios"]["licensors"]) && is_array($config["studios"]["licensors"]))
      {
        foreach ($config["studios"]["licensors"] as $key => $value)
          if ((!empty($key) && is_numeric($key)) || $key == 0)
            if (($sid = issetStudioID($dbConfig, $key)) && ($sid != false || $sid != 0))
              setAnimeStudio($dbConfig, $ID, $sid, 2);
      }
      if (isset($config["studios"]["producers"]) && is_array($config["studios"]["producers"]))
      {
        foreach ($config["studios"]["producers"] as $key => $value)
          if ((!empty($key) && is_numeric($key)) || $key == 0)
            if (($sid = issetStudioID($dbConfig, $key)) && ($sid != false || $sid != 0))
              setAnimeStudio($dbConfig, $ID, $sid, 3);
      }
    } // Már létezik az anime
  }
  else return array(FALSE, "", "danger"); // Nincs MyAnimeList ID 
}
/* ============================================================== */
/* Set/Save Functions Start */
/* ============================================================== */
function setAnimeGenre($dbConfig, $animeID, $genreID)
{

  $sql = "INSERT INTO `mal__anime__genres`(`anime_id`, `genre_id`)
  SELECT * FROM (SELECT {$animeID} as `anime_id`, {$genreID} AS `genre_id`) AS new_value
  WHERE NOT EXISTS (
   SELECT `anime_id` FROM `mal__anime__genres` WHERE `anime_id` = {$animeID} && `genre_id` = {$genreID}
  ) LIMIT 1;";
  $return =  Insert2($dbConfig, $sql);
  unset($sql);
  //echo $return;
}
function setAnimeStudio($dbConfig, $animeID, $studioID, $typeID)
{
  $sql = "INSERT INTO `mal__anime__studios`(`anime_id`, `studios_id`, `studios_type_id`)
  SELECT * FROM (SELECT {$animeID} as `anime_id`, {$studioID} AS `genre_id`, {$typeID} AS `studios_type_id`) AS new_value
  WHERE NOT EXISTS (
   SELECT `anime_id` FROM `mal__anime__studios` WHERE `anime_id` = {$animeID} && `studios_id` = {$studioID} && `studios_type_id` = {$typeID}
  ) LIMIT 1;";
  $return =  Insert2($dbConfig, $sql);
  unset($sql);
  //echo $return;
}
function saveAnime($dbConfig, $data)
{
  foreach ($data as $key => $value) $data[$key] = (is_string($value) && !is_numeric($value) && !is_null($value)) ? "'" . htmlspecialchars($value) . "'" : $value;

  $sql = "INSERT INTO `mal__anime`(`id`, `title`, `english`, `synonyms`, `japanese`, `type_id`, `episodes`, `premiered_year`,
  `premiered_seasonal`, `source_id`, `age_rating_id`, `preview`, `synopsis`, `synopsis2`, `aired_start`, `aired_end`, `status`, `mal_id`, `img`, `fansub`)
  VALUES (
    {$data["id"]},
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
  return Insert2($dbConfig, $sql, true);
}
/* ============================================================== */
/* Set/Save Functions End */
/* ============================================================== */



/* ============================================================== */
/* From here on, everything works. */
/* ============================================================== */


function getMyanimelist($dbConfig, $config)
{
  $id = (!empty($config["id"]) && is_numeric($config["id"])) ? $config["id"] : 1;

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
      if(isset($contentWrapper->find('div', 0)->find('h1', 0)->find('strong', 0)->plaintext))
        $json['anime']["title"] = $contentWrapper->find('div', 0)->find('h1', 0)->find('strong', 0)->plaintext;
      else
      if(isset($contentWrapper->find('div', 0)->find('h1', 0)->find('span', 0)->plaintext))
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

        if(isset($content->find('div.js-scrollfix-bottom-rel', 0)->find('table', 0)->find('p', 0)->plaintext))
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

    $json["newData"] = setMyanimelistData($dbConfig, $config);

    return $json;
  }
  else return ["success" => "false", "error" => "wrong id"];

  // A MAL-os 'Synonyms' rész ","-ként felbontva json-ként lesz elmentve.
}

function setMyanimelistData($dbConfig, $config)
{
  $return = FALSE;
  if (isset($config["json"]["anime"]["information"]["theme"]))
    foreach ($config["json"]["anime"]["information"]["theme"] as $link => $name)
      if (!(issetGenre($dbConfig, $name)))
        if (setGenre($dbConfig, $name, $link, "theme") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["themes"]))
    foreach ($config["json"]["anime"]["information"]["themes"] as $link => $name)
      if (!(issetGenre($dbConfig, $name)))
        if (setGenre($dbConfig, $name, $link, "theme") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["genre"]))
    foreach ($config["json"]["anime"]["information"]["genre"] as $link => $name)
      if (!(issetGenre($dbConfig, $name)))
        if (setGenre($dbConfig, $name, $link, "genre") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["genres"]))
    foreach ($config["json"]["anime"]["information"]["genres"] as $link => $name)
      if (!(issetGenre($dbConfig, $name)))
        if (setGenre($dbConfig, $name, $link, "genre") && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["demographic"]))
    foreach ($config["json"]["anime"]["information"]["demographic"] as $link => $name)
      if (!(issetGenre($dbConfig, $name)))
        if (setGenre($dbConfig, $name, $link, "demographic") && $return == FALSE)
          $return = TRUE;


  if (isset($config["json"]["anime"]["information"]["licensors"]))
    foreach ($config["json"]["anime"]["information"]["licensors"] as $link => $name)
      if (!(issetStudio($dbConfig, $name)))
        if (setStudio($dbConfig, $name, $link) && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["producers"]))
    foreach ($config["json"]["anime"]["information"]["producers"] as $link => $name)
      if (!(issetStudio($dbConfig, $name)))
        if (setStudio($dbConfig, $name, $link) && $return == FALSE)
          $return = TRUE;

  if (isset($config["json"]["anime"]["information"]["studios"]))
    foreach ($config["json"]["anime"]["information"]["studios"] as $link => $name)
      if (!(issetStudio($dbConfig, $name)))
        if (setStudio($dbConfig, $name, $link) && $return == FALSE)
          $return = TRUE;
  return $return;
}

function issetGenre($dbConfig, $genre = "")
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__genres` WHERE `name` = '{$genre}' LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function setGenre($dbConfig, $name, $link, $type)
{
  $link = intval($link);
  $sql = "INSERT INTO `mal__genres`(`name`, `link`, `type`) VALUES ('{$name}','{$link}','{$type}');";
  $return =  Insert2($dbConfig, $sql);
  unset($sql);
  return $return;
}

function issetStudio($dbConfig, $studio = "")
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__studios` WHERE `name` = '{$studio}' LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function setStudio($dbConfig, $name, $link)
{
  $link = intval($link);
  $sql = "INSERT INTO `mal__studios`(`name`, `link`) VALUES ('{$name}','{$link}');";
  $return =  Insert2($dbConfig, $sql);
  unset($sql);
  return $return;
}
/* ============================================================== */
/* Isset Functions Start */
/* ============================================================== */
function issetAnime($dbConfig, $myanimelist)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__anime` WHERE `mal_id` = {$myanimelist} LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetAnimeType($dbConfig, $id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__type` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetAnimeSource($dbConfig, $id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__source` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetAnimeAgeRating($dbConfig, $id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__age_rating` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetGenreID($dbConfig, $link = 0)
{
  $link = intval($link);
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__genres` WHERE `link` = {$link} LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
function issetStudioID($dbConfig, $link = 0)
{
  $link = intval($link);
  $sql = "SELECT `id` FROM `mal__studios` WHERE `link` = {$link} LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["id"]) ? $return[0]["id"] : false;
}
function issetStudioTypeID($dbConfig, $id = 0)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__studios_type` WHERE `id` = {$id} LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["count"]) ? true : false;
}
/* ============================================================== */
/* Isset Functions End */
/* ============================================================== */
function getTypeB($dbConfig, $table, $name) // type; age_rating; source
{
  $sql = "SELECT `id` FROM `mal__{$table}` WHERE `name` = '{$name}' LIMIT 1;";
  $return =  Select2($dbConfig, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && 0 < $return[0]["id"]) ? $return[0]["id"] : false;
}





function Select($conn, $sql = "")
{
  $result = $conn->query($sql);
  if (isset($result->num_rows))
    if ($result->num_rows > 0)
      while ($row = $result->fetch_assoc())
      {
        $data[] = $row;
      }
    else $data = array();
  else exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre>" . "<br />");

  return $data;
}

function Update($conn, $sql = "")
{
  if ($conn->query($sql) === TRUE)
  {
    return true;
  }
  else
  {
    print_p("Error: " . $sql . "<br>" . $conn->error);
  }
}
function print_p($p = '')
{
  $type = gettype($p);
  echo '<pre>';
  if ($type == "integer" || $type == "double" || $type == "string")
    echo ($p);
  elseif ($type == "array" || $type == "object")
    print_r($p);
  elseif ($type == NULL || $type == "boolean")
    var_dump($p);
  echo '</pre>';
}
function RealEscapeString($dbConfig, $string = "")
{
  $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
  if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

  $result = $conn->real_escape_string($string);
  return $result;
}
function Update2($dbConfig, $sql = "")
{
  $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
  if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
  if ($conn->query($sql) === TRUE)
  {
    return true;
  }
  else
  {
    print_p("Error: " . $sql . "<br>" . $conn->error);
  }
}
function Select2($dbConfig, $sql = "")
{
  $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
  if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

  $result = $conn->query($sql);
  if (isset($result->num_rows))
    if ($result->num_rows > 0)
      while ($row = $result->fetch_assoc())
      {
        $data[] = $row;
      }
    else
    {
      $data = array();
    }
  else
  {
    exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre>" . "<br />");
  }

  return $data;
}
function Insert2($dbConfig, $sql = "", $id = FALSE)
{
  $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
  if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
  if ($conn->query($sql) === TRUE)
  {
    return ($id == FALSE) ? TRUE : $conn->insert_id;
  }
  else
  {
    exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre><br />" . $conn->error . "<br />");
  }
}
function Insert($conn, $sql = "", $id = FALSE)
{
  if ($conn->query($sql) === TRUE)
  {
    return ($id == FALSE) ? TRUE : $conn->insert_id;
  }
  else
  {
    exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre><br />" . $conn->error . "<br />");
  }
}
function fgc($p = '')
{
  return (!empty($p)) ? file_get_contents($p) : FALSE;
}
function gpi($p = NULL)
{
  $explode = (isset($_SERVER['REDIRECT_URL'])) ? explode("/", substr($_SERVER['REDIRECT_URL'], 1)) : array();
  return (isset($explode[$p])) ? $explode[$p] : $explode;
}
function getip()
{
  if (!empty($_SERVER['HTTP_CLIENT_IP']))
  {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  }
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  elseif (!empty($_SERVER['REMOTE_ADDR']))
  {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  else
  {
    return False;
  }
  if (20 < strlen($ip))
    return false;
  else
    return $ip;
}

$conn->close();
?>
