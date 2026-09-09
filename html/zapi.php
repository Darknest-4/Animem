<?php
header('Access-Control-Allow-Origin: https://admin.animem.org');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 'On');
define("DS", DIRECTORY_SEPARATOR);
$token = "4d97675dfd7e70a0263575b74cdd3ca7c0d5dccb74e3f8211456747f439a3cc3";

//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database = "animem";
$database3 = "xanimem";

require_once("../Config/loadConfig.php");

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
$conn3 = new mysqli($servername, $username, $password, $database3);

if ($conn->connect_error)
  die("Connection failed: " . $conn->connect_error);
if ($conn3->connect_error)
  die("Connection 3 failed: " . $conn3->connect_error);

$json["success"] = FALSE;
if (isset($_GET["s"]) && isset($_GET["token"]) && $_GET["token"] == $token)
{ // Keresés

  $_GET["s"] = $conn3->real_escape_string($_GET["s"]);

  $json["success"] = TRUE;
  foreach (explode(" ", $_GET["s"]) as $word)
  {
    if (!empty($word))
      if (!empty($where))
      {
        $where .= " && (title LIKE '%" . $word . "%'";
        $where .= " || english LIKE '%" . $word . "%'";
        $where .= " || synonyms LIKE '%" . $word . "%'";
        $where .= " || japanese LIKE '%" . $word . "%')";
      }
      else
      {
        $where = "(title LIKE '%" . $word . "%'";
        $where .= " || english LIKE '%" . $word . "%'";
        $where .= " || synonyms LIKE '%" . $word . "%'";
        $where .= " || japanese LIKE '%" . $word . "%')";
      }
  }

  $arr = Select($conn3, "SELECT *  FROM `mal__anime` WHERE (" . $where . ") && ((`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '')) ORDER BY `title`;");

  if (isset($arr[0]))
  {
    foreach ($arr as $key => $value)
    {
      if (empty($value["buta_wp_title2"]))
        $value["buta_wp_title2"] = $value["title"];
      if (empty($value["buta_wp_title"]))
        $value["buta_wp_title"] = $value["title"];

      if (!empty($value["buta_wp2"]))
      {
        $json["result"][$key] = $value;
        $json["result"][$key]["link"] = "https://animem.org/" . $value["buta_wp2"];
        $arr2 = Select($conn, "SELECT `description`  FROM `datasheet` WHERE  `link` = '{$value["buta_wp2"]}' LIMIT 1;");
        if (isset($arr2[0]["description"]))
          $json["result"][$key]["description"] = $arr2[0]["description"];

        $arr2 = Select($conn3, "SELECT `name`  FROM `mal__age_rating` WHERE  `id` = '{$value["age_rating_id"]}' LIMIT 1;");
        if (isset($arr2[0]["name"]))
          $json["result"][$key]["ageRating"] = $arr2[0]["name"];

        $arr2 = Select($conn3, "SELECT `name`  FROM `mal__type` WHERE  `id` = '{$value["type_id"]}' LIMIT 1;");
        if (isset($arr2[0]["name"]))
          $json["result"][$key]["type"] = $arr2[0]["name"];

        $arr2 = Select($conn3, "SELECT `name`  FROM `mal__source` WHERE  `id` = '{$value["source_id"]}' LIMIT 1;");
        if (isset($arr2[0]["name"]))
          $json["result"][$key]["source"] = $arr2[0]["name"];
      }
      if (!empty($value["buta_wp"]))
      {
        $json["result"][$key] = $value;
        $json["result"][$key]["link"] = "https://animem.org/" . $value["buta_wp"];
        $arr2 = Select($conn, "SELECT `description`  FROM `datasheet` WHERE  `link` = '{$value["buta_wp"]}' LIMIT 1;");
        if (isset($arr2[0]["description"]))
          $json["result"][$key]["description"] = $arr2[0]["description"];

        $arr2 = Select($conn3, "SELECT `name`  FROM `mal__age_rating` WHERE  `id` = '{$value["age_rating_id"]}' LIMIT 1;");
        if (isset($arr2[0]["name"]))
          $json["result"][$key]["ageRating"] = $arr2[0]["name"];

        $arr2 = Select($conn3, "SELECT `name`  FROM `mal__type` WHERE  `id` = '{$value["type_id"]}' LIMIT 1;");
        if (isset($arr2[0]["name"]))
          $json["result"][$key]["type"] = $arr2[0]["name"];

        $arr2 = Select($conn3, "SELECT `name`  FROM `mal__source` WHERE  `id` = '{$value["source_id"]}' LIMIT 1;");
        if (isset($arr2[0]["name"]))
          $json["result"][$key]["source"] = $arr2[0]["name"];
      }
    }
  }
  else
  {
    $json["success"] = FALSE;
  }

  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();
  $conn3->close();
}
print_r(json_encode($json));
exit();




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
