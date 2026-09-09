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
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_GET["prefix"]))
{
  $sql = 'DELETE FROM `mininews` WHERE `link_description` LIKE "%Notice%";';
  $conn->query($sql);
}
?>

<form action="/mininewseditor.php" method="get" id="mininews">
  <button type="submit" class="btn btn-info" name="prefix" value="true" form="mininews">prefix</button>
</form>
