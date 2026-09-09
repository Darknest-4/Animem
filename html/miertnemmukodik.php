<?php
//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database = "animem";

require_once("../Config/loadConfig.php");

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

$conn = new mysqli($servername, $username, $password, $database);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$changeAnime = file_get_contents("changeAnime.txt");
/**
 * régi embed type id: 6
 * uj embed type id: 9
 * régi link embed.animem.org/Embed/
 * uj link: embed.animem.org/
 * 
  */
echo "\n";
$pattern = "UPDATE `links` SET `link`='{{newlink}}', `links_type`='9' WHERE `links_type`='6' && `link`='{{oldlink}}';";
$changeAnime = str_replace(["https://embed.animem.org/Embed/", "https://embed.animem.org/"], "", $changeAnime);
$changeAnimeArray = explode("\n", $changeAnime);
$changeAnimeArrayCount = count($changeAnimeArray)-1;
foreach ($changeAnimeArray as $key => $value) {
  $changeAnimeArray[$key] = explode(":", $value);
  $conn->query(str_replace(["{{newlink}}", "{{oldlink}}"], [$changeAnimeArray[$key][1], $changeAnimeArray[$key][0]], $pattern));
  sleep(0.5);
  echo "\n";
  echo $key . "/" . $changeAnimeArrayCount . " -> " . str_replace(["{{newlink}}", "{{oldlink}}"], [$changeAnimeArray[$key][1], $changeAnimeArray[$key][0]], $pattern);
}






echo "\n";










die;
die;

$bigSave = json_decode(file_get_contents("bigSave.json"), true);
foreach ($bigSave as $key => $value) {
  $id = (!isset($id))?$value["id2"]:$id . ", " . $value["id2"];
}
//die("\n" . $id . "\n");
require_once('shd.php');
//  animem_db
$other="";
$conn = new mysqli("localhost", "animem", "OV3SY1WCZyew6sZEDFu4D5ClzHAeHj8U0O4X2SPwwmy4KdnPn1Z81PLzesUhu4Ud", "animem");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$select = Select($conn, "SELECT `id` as 'id2', `jp_title` as 'url' FROM `anime` WHERE `id` NOT IN ({$id}) ORDER BY `anime`.`id` ASC ");
if (is_array($select))
{
  foreach ($select as $json)
  {
    
    $other .= "https://www.google.com/search?q=" . $json["url"] . "+site%3Ahttps%3A%2F%2Fmyanimelist.net&tbs=li%3A1";
    $other .= "\n";
    //echo $json["url"] = strtolower(str_replace(['_&_','_&amp;_','(',')','?: ','?',':','!!','!','/',',',"'",'__'], ["___", "___", "", "", "__", "_", "_","_", "_", "_", "_", "_", "_"], $json["url"]));

    
    /*
    if(substr($json["url"], -1) == "_")
      echo $json["url"] = substr($json["url"], 0, -1);
    //if ($html = file_get_contents("https://www.google.com/search?q=" . $json["url"] . "+site%3Ahttps%3A%2F%2Fmyanimelist.net&tbs=li%3A1"))
    if ($html = file_get_contents("https://search.yahoo.com/search?p=" . $json["url"] . "+site%3Ahttps%3A%2F%2Fmyanimelist.net&fr=yfp-t&ei=UTF-8&fp=1"))
      {
        if ($html != FALSE && $html = str_get_html($html))
        {
          //sleep(3);
          //echo $html;
          if ($contentWrapper = $html->find('div#main', 0)->find('a'))
          {
            unset($html);
            if (!empty($contentWrapper))
              foreach ($contentWrapper as $key => $value)
              {
                if (isset($value->href))
                {
                  // print_p($value->href);
                  $link = explode("myanimelist.net/anime/", $value->href);
                  if (isset($link[1]))
                  {
                    // print_p($value->href);
                    $link = explode("&", $link[1]);
                    if (isset($link[0]) && !empty($link[0]))
                    {
                      /*
                      echo $link2[0];
                      echo "\n";
                      echo $value->href;
                      echo "\n";
                      echo $json["url"];
                      echo "\n";
                      echo "\n";
                      */
                      /*
                      $link = explode("/", $link[0]);
                      if (isset($link[1]) && !empty($link[1]) && strtolower($link[1]) == $json["url"])
                      {
                        $json["MyAnimeList"] = $link[0];
                        print_p($json);
                        break;
                      }
                    }
                  }
                }
              }
          }
        }else die();
      }
    if (isset($json["MyAnimeList"]))
    {
      $bigSave[] = $json;
      unset($json);
    }*/
  }
}

      file_put_contents("other2.html", $other);
echo "\n";
// print_p($bigSave);
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

function print_p($p = '')
{
  $type = gettype($p);
  echo "\n<pre>\n";
  if ($type == "integer" || $type == "double" || $type == "string")
    echo ($p);
  elseif ($type == "array" || $type == "object")
    print_r($p);
  elseif ($type == NULL || $type == "boolean")
    var_dump($p);
  echo "\n</pre>\n";
}
/*
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
*/
$conn->close();
