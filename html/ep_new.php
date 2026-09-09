<?php

set_time_limit(0);
//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database2 = "animem";
$database = "t_allin";

require_once("../Config/loadConfig.php");

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database2 = $dbConfig['database'];

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error)  die("Connection failed: " . $conn->connect_error);

$conn2 = new mysqli($servername, $username, $password, $database2);
if ($conn->connect_error)  die("Connection failed: " . $conn->connect_error);


if(!function_exists('Select'))
{
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
        else exit("\nSQL Syntax Error! SQL:\n<pre>" . $sql . "</pre>" . "\n");

        return $data;
    }
}

if(!function_exists('Update'))
{
  function Update($conn, $sql="")
  {
    if ($conn->query($sql) === TRUE)
    {
      return true;
    }else
    {
      print_p("Error: " . $sql . "<br>" . $conn->error);
      die;
    }
  }
}

if(!function_exists('Insert'))
{
    function Insert($conn, $sql="", $id=FALSE)
    {
        if($conn->query($sql) === TRUE) {
            return ($id == FALSE)?TRUE:$conn->insert_id;
        } else {
            exit("\nSQL Syntax Error! SQL:\n<pre>" . $sql . "</pre>\n".$conn->error . "\n");
        }
        
    }
}
if(!function_exists('print_p'))
{
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
}


if(!function_exists('removeWhiteSpace'))
{
    function removeWhiteSpace($text)
    {
        $text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
        $text = preg_replace('/([\s])\1+/', ' ', $text);
        $text = trim($text);
        return $text;
    }
}




























$list = Select($conn, "SELECT `ID`, `post_content`, `post_name` FROM `wp_posts` ORDER BY `ID` ASC");

foreach ($list as $key => $value)
{
  echo shell_exec("clear");
  echo $key+1 . " / " . count($list) . "\n";
  $exp = explode("{", removeWhiteSpace($value["post_content"]));
  $exp = explode("}", $exp[1]);
  $json = $exp[0];
  if(substr($json, -1) == ",") 
    $json = substr($json, 0, -1);
  // echo "\n".$value["ID"]." => \n";
  $json = json_decode("{".$json."}", true);
  if (!is_array($json))
  {
    var_dump($exp[0]);
    die("\n".$value["ID"]." => \n");
  }
  $json2 = array();
  $json2["_type"] = "episodelist";
  foreach ($json as $key => $value2)
  {
    //echo "\n" . $value2;
    $select = Select($conn, "SELECT `id` FROM `ep_links` WHERE `link` = '" . $value2 . "' ORDER BY `id` ASC LIMIT 1");
    if(is_array($select) && empty($select))
      $json2[$key] = Insert($conn, "INSERT INTO `ep_links` (`link`) VALUES ('" . $value2 . "')", TRUE);
    else
      $json2[$key] = $select[0]["id"];
  }
  //print_p($json2);
  $select = Select($conn, "SELECT `ID` FROM `wp_posts2` WHERE `ID` = " . $value["ID"] . " ORDER BY `ID` ASC LIMIT 1");
  if(is_array($select) && empty($select))
    Insert($conn, "INSERT INTO `wp_posts2` (`ID`, `post_content`, `post_name`) VALUES (" . $value["ID"] . ", '" . $conn -> real_escape_string(json_encode($json2)) . "', '" . $conn -> real_escape_string($value["post_name"]) . "')", TRUE);


}
echo "\n1. Success!\n ";

sleep(5);


$list = Select($conn, "SELECT `ID`, `post_content`, `post_name` FROM `wp_posts2` ORDER BY `ID` ASC");

foreach ($list as $key => $value)
{
  echo shell_exec("clear");
  echo $key+1 . " / " . count($list) . "\n";
  Update($conn2, "UPDATE `wp_posts` SET `post_content`='" . $conn -> real_escape_string($value["post_content"]) . "' WHERE `post_name` = '" . $value["post_name"] . "'");
}
echo "\n2. Success!\n ";





































?>
