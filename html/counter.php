<?php
//$servername = "localhost";
//$username = "rlight";
//$database = "animem";

require_once("../Config/loadConfig.php");

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function input_encode($str)
{
 return htmlspecialchars($str);
}


$q = (isset($_GET["q"]) && is_string($_GET["q"]))?$_GET["q"]:0;
$set = (isset($_GET["set"]) && is_string($_GET["set"]))?$_GET["set"]:"tesco";

$result = $conn->query("SELECT `count` FROM `links` WHERE `link` = '" . $q . "' LIMIT 1");

if (isset($result->num_rows) && $result->num_rows > 0)
{
	$row = $result->fetch_assoc();
	if ($set == "happy")
	{
		if ($conn->query("UPDATE `links` SET `count`= " . (1 + $row["count"]) . " WHERE `link` = '" . $q . "' LIMIT 1") === TRUE)
		{
			echo $row["count"]+1;
		}else
		{
			echo "Error updating record: " . $conn->error;
		}
	}else
	{
		echo $row["count"]+1;
	}
}else
{
  echo 0;
}
$conn->close();





?>
