<?php

$servername = "localhost";
$username = "animem";
$password = "OV3SY1WCZyew6sZEDFu4D5ClzHAeHj8U0O4X2SPwwmy4KdnPn1Z81PLzesUhu4Ud";
$database = "animem";
$username3 = "xanimem";
$password3 = "ZfsCfn8gx9jMhxfcJcMRrpLiZm6wKjGwAqY2rKKoJ0YOKjnAly56RzUU8U6qSXGV";
$database3 = "xanimem";



$conn = new mysqli($servername, $username, $password, $database);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn3 = new mysqli($servername, $username3, $password3, $database3);
if($conn3->connect_error) die("Connection failed: " . $conn3->connect_error);
 
$arr = Select($conn, 'SELECT post_content, post_title, post_name FROM 
`wp_posts`
WHERE `post_content` LIKE "%datasheet%"
ORDER BY post_name');
$Insert = "";
foreach ($arr as $key => $value)
{
  $json = json_decode($value["post_content"], true);
  unset($json["_type"]);

  if(substr($json["myanimelist"], -1) == "/")
    $json["myanimelist"] = substr($json["myanimelist"], 0, -1);
  $json["myanimelist"] = explode("anime/", $json["myanimelist"])[1];


  if(strlen(str_replace("/", "", $json["myanimelist"])) < strlen($json["myanimelist"]))
    $json["myanimelist"] = explode("/", $json["myanimelist"])[0];

  $id = Select($conn, 'SELECT id FROM `datasheet` 
    WHERE `link` = "' . $conn -> real_escape_string($value["post_name"]) . '" LIMIT 1');
  if(!isset($id[0]["id"]))
  {
    $id = Insert($conn, "INSERT INTO `datasheet`(`title`, `link`, `myanimelist`, `description`) VALUES 
        ('".$conn -> real_escape_string($value["post_title"])."',
        '".$conn -> real_escape_string($value["post_name"])."',
        ".$json["myanimelist"].",
        '".$conn -> real_escape_string($json["description"])."');", true);

  }else
  {
    $id = $id[0]["id"];
  }
    foreach ($json["fansub"] as $text => $fansub_id)
    {
      $fansub_id = (!empty($fansub_id))?$fansub_id:369;
      $arr2 = Select($conn, 'SELECT id FROM `uploaders` 
        WHERE `id` = ' . $fansub_id . ' LIMIT 1');
      if(isset($arr2[0]["id"]))
      {
        $arr2 = Select($conn, 'SELECT datasheet_id FROM 
          `datasheet_uploaders`
          WHERE `datasheet_id` = ' . $id . ' && `fansub1` = ' . $fansub_id . '
          LIMIT 1');
        if(!isset($arr2[0]["datasheet_id"]))
        {
          Insert($conn, "INSERT INTO `datasheet_uploaders`(`datasheet_id`, `fansub1`) VALUES 
            (".$id.", ".$fansub_id.");");
        }
      }
    }
    foreach ($json["links"] as $text => $link)
    {

      if(substr($link, -1) == "/")
        $link = substr($link, 0, -1);
      $link = explode("animem.org/", $link);
      if(isset($link[1]))
        $link2 =$link = $link[1];
      else
      {
        echo "<br/>". '<a href="https://animem.org/'. $value["post_name"] . '" target="_blank">'. $value["post_name"] . '</a>';
        echo "<br/>". '<a href="https://animem.org/'. "redirect/". $link[0] . '" target="_blank">'. "redirect/". $link[0] . '</a>';

        $link = "redirect/". $link[0];
        $link2 = $link[0];

      }
      $arr2 = Select($conn, 'SELECT id FROM `episodelist` 
        WHERE `link` = "' . $link . '" LIMIT 1');
      if(isset($arr2[0]["id"]))
      {
        $link_id = $arr2[0]["id"];
      }else
      {
        $arrs = Select($conn, 'SELECT * FROM 
        `wp_posts`
        WHERE `post_name` = "'.$link2.'"
        ORDER BY post_name LIMIT 1');
        if(!isset($arrs[0]["post_content"]))
        {
          
        $arrs2 = Select($conn, 'SELECT * FROM 
        `wp_posts`
        WHERE `post_name` = "'.$value["post_name"].'"
        ORDER BY post_name LIMIT 1');
        $arrs2[0]["post_content"] = json_decode($arrs2[0]["post_content"]);
        print_p($arrs2);
        echo "<br/>". '<a href="https://animem.org/'. $value["post_name"] . '" target="_blank">'. $value["post_name"] . '</a>';
        echo "<br/>". '<a href="https://animem.org/'. $link2 . '" target="_blank">'. "". $link2 . '</a>';
        echo "<br/>";

        }else
        {

          $Insert = "INSERT INTO `episodelist` (`title`, `link`, `save`) VALUE
            (
              '" . $text . "',
              '" . $link . "',
              '" . str_replace("'", "\'", $arrs[0]["post_content"]) . "'
            )";
          // $link_id = Insert($conn, $Insert, true);

        }
      }
        if(isset($link_id))
        {
          $arr2 = Select($conn, 'SELECT datasheet_id FROM 
            `datasheet_episodelist`
            WHERE `datasheet_id` = ' . $id . ' && `episodelist_id` = ' . $link_id . '
            LIMIT 1');
          if(!isset($arr2[0]["datasheet_id"]))
          {
            Insert($conn, "INSERT INTO `datasheet_episodelist`(`datasheet_id`, `episodelist_id`) VALUES 
              (".$id.", ".$link_id.");");
          }
        }
    
  }
  
  
}
print_p($Insert);


die;
/***************  link episodelist converter start   ************************ */

/*
$arr = Select($conn, 'SELECT * FROM 
`wp_posts`
WHERE `post_content` LIKE "%episodelist%"
ORDER BY post_name');
echo count($arr);
die();
$Insert = "";
foreach ($arr as $key => $value)
{
  $arr = Select($conn, 'SELECT `link` FROM `episodelist` WHERE `link` = "' . $value["post_name"] . '" LIMIT 1');
  if(!isset($arr[0]["link"]))
  {
    if(empty($Insert))
      $Insert .= "\n" . "INSERT INTO `episodelist` (`title`, `link`, `save`) VALUE
      (
        '" . $conn -> real_escape_string($value["post_title"]) . "',
        '" . $conn -> real_escape_string($value["post_name"]) . "',
        '" . str_replace("'", "\'", $value["post_content"]) . "'
      )";
    else
      $Insert .= "," . "
      (
        '" . $conn -> real_escape_string($value["post_title"]) . "',
        '" . $conn -> real_escape_string($value["post_name"]) . "',
        '" . str_replace("'", "\'", $value["post_content"]) . "'
      )";
  }
}
print_p($Insert);
*/
die;
$arr = Select($conn, 'SELECT * FROM 
`episodelist`
WHERE `save` != ""
ORDER BY title');

$Insert = "";
foreach ($arr as $key => $value)
{
  $json = json_decode($value["save"], true);
  unset($json["_type"]);
  foreach ($json as $secret_message => $links_id)
  {
    if(!empty($links_id))
    {
      
      $arr2 = Select($conn, 'SELECT `links_id` FROM `episodelist_links` WHERE `links_id` = ' . $links_id . ' LIMIT 1');
      if(!isset($arr2[0]["links_id"]))
      {
        $arr2 = Select($conn, 'SELECT * FROM `links` WHERE `id` = ' . $links_id . ' LIMIT 1');
        if(isset($arr2[0]["id"]))
        {
          $Insert = "INSERT INTO `episodelist_links` (`episode_id`, `links_id`, `secret_message`) VALUE"
            ."\n("
              . "'" . $value["id"] . "', "
              . "'". $links_id . "', "
              . "'" . str_replace(["u00e9","'"], ["é", "\'"], $secret_message)
            . "');";
          Insert($conn, $Insert);
        }else 
        {
          $Insert = "INSERT INTO `episodelist_links` (`episode_id`, `secret_message`) VALUE"
            ."\n("
              . "'" . $value["id"] . "', "
              . "'" . str_replace(["u00e9","'"], ["é", "\'"], $secret_message)
            . "');";
          Insert($conn, $Insert);
          
        }
      }
      $arr2 = Select($conn, 'SELECT `links_id` FROM `episodelist_links` 
      WHERE `links_id` = ' . $links_id . ' && 
      `secret_message` != "' . str_replace(["u00e9","'"], ["é", "\'"], $secret_message) . '" LIMIT 1');
      if(isset($arr2[0]["links_id"]))
      {
        $Insert = "INSERT INTO `episodelist_links` (`episode_id`, `secret_message`) VALUE"
          ."\n("
            . "'" . $value["id"] . "', "
            . "'" . str_replace(["u00e9","'"], ["é", "\'"], $secret_message)
          . "');";
        Insert($conn, $Insert);
      }
      unset($arr2);
    }
  }
}
die;
//print_p($arr );





/***************  link episodelist converter end   ************************** */
/***************  link parser start   ************************ */
/*

$Update = "";
$arr = Select($conn, 'SELECT * FROM 
`links`
WHERE `links_type` = 0 && 
      `link` LIKE "%drive.google.com/file/d/%"
ORDER BY link');
foreach ($arr as $key => $value)
{

  //$link = $value["link"];
  //if(substr($value["link"], -1) == "/")
  //  $link = substr($value["link"], 0, -1);
    
    
  $link = explode("drive.google.com/file/d/", $value["link"])[1];
 $link = str_replace("/view", "/preview", $link);
// $Update .= "\n" . "UPDATE `links` SET `link`='" . $link . "' WHERE `id` = '" . $value["id"] . "';";
   $Update .= "\n" . "UPDATE `links` SET `link`='" . $link . "', `links_type` = 7 WHERE `id` = " . $value["id"] . " LIMIT 1;";
  unset($link);
}
die(print_p($Update));

*/
/***************  link parser end   ************************ */
$arr = Select($conn, 'SELECT * FROM 
`wp_posts` WHERE 
`wp_posts`.`post_content` NOT LIKE "%episodelist%" && 
`wp_posts`.`post_content` LIKE "%datasheet%"
ORDER BY post_name
LIMIT 20');

foreach ($arr as $key => $value)
{
  $arr[$key]["post_content"] = json_decode($value["post_content"], true);
}
print_p($arr);

















function Select($conn, $sql="")
{
    $result = $conn->query($sql);
    if(isset($result->num_rows))
    if($result->num_rows > 0)
        while($row = $result->fetch_assoc())
        {
            $data[] = $row;
        }
    else $data=array();
    else exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre>" . "<br />");

    return $data;
}

function Update($conn, $sql="")
{
  if ($conn->query($sql) === TRUE)
  {
    return true;
  }else
  {
    print_p("Error: " . $sql . "<br>" . $conn->error);
  }
}
function print_p($p = '')
{
    $type = gettype($p);
    echo '<pre>';
    if($type == "integer" || $type == "double" || $type == "string")
        echo($p);
    elseif($type == "array" || $type == "object")
        print_r($p);
    elseif($type == NULL || $type == "boolean")
        var_dump($p);
    echo '</pre>';
}

function Insert($conn, $sql="", $id=FALSE)
{
    if($conn->query($sql) === TRUE) {
        return ($id == FALSE)?TRUE:$conn->insert_id;
    } else {
        exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre><br />".$conn->error . "<br />");
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
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
  } else{
    return False;
  }
  if(20 < strlen($ip))
    return false;
  else
    return $ip;
}




?>