<?php

ini_set('display_errors', '1');
// Credentials come from the environment (see Config/credentials.php).
require_once dirname(__DIR__) . '/Config/credentials.php';
$primary    = animem_db_credentials(dirname(__DIR__) . '/Config/config.json');
$servername = $primary['host'];
$username   = $primary['username'];
$password   = $primary['password'];
$database   = $primary['database'];

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}else {
    //echo "Connected successfully";
}

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
        else exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre>" . "<br />");

        return $data;
    }
}
if(!function_exists('Update'))
{
    function Update($conn, $sql="")
    {

        if ($conn->query($sql) === TRUE)
        {
          //echo "Record updated successfully";
        }else
        {
          echo "<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre>" . "<br />";
        }
    }
}


set_time_limit(0);
require_once("shd.php");


foreach (findfilefromdir("full") as $value) {
    $file = file_get_contents("full/" . $value);
    $file = json_decode($file, true);
    $file["_type"] = "datasheet";
    if(!empty($file["myanimelist"]))
    {
        $file = json_encode($file, JSON_FORCE_OBJECT);
        $file = $conn->real_escape_string($file);
        $filename = str_replace(".json", "", $value);
        $ar = Select($conn, "SELECT wp_posts.ID FROM `wp_posts` WHERE wp_posts.post_name = '$filename' && wp_posts.post_status = 'publish' LIMIT 1");
        if (is_array($ar) && isset($ar[0]["ID"]))
        {
            Update($conn, "UPDATE `wp_posts` SET wp_posts.post_content = '".$file."' WHERE wp_posts.ID = '".$ar[0]["ID"]."' LIMIT 1");
        }
    }else
    {
        $filename = str_replace(".json", "", $value);
        echo "<br />" . $filename . "<br />";
    }
}



die("All success");
foreach (findfilefromdir("az") as $value) {
    assoc_query3($value);
}
die;
function assoc_query2($file)
{
    $html = str_get_html(file_get_contents("test/".$file));
    if($a_tag = $html->find('a'))
    foreach ($a_tag as $key => $value)
    {
        $href = str_replace("https://animem.org/", "", $value->href);
        if(strlen($href) < strlen($value->href) && !empty($href))
        {
            $href = str_replace(["https://animem.org/", ":", "/", "?"],["","","_", "."],$value->href);
            $dirfile=str_replace("_.", ".", "test/{$href}.html");
            $file = file_get_contents($value->href);
            if(empty($file))
            $file = '<a href="https://animem.org"></a>';
            if(!file_exists($dirfile))
                file_put_contents($dirfile, $file);
        }
    }
}
function assoc_query($link)
{
    $html = file_get_html($link);
    if($a_tag = $html->find('a'))
    foreach ($a_tag as $key => $value)
    {
        $href = str_replace("https://animem.org/", "", $value->href);
        if(strlen($href) < strlen($value->href) && !empty($href))
        {
            $href = str_replace(["https://animem.org/", ":", "/", "?"],["","","_", "."],$value->href);
            $dirfile=str_replace("_.", ".", "test/{$href}.html");
            $file = file_get_contents($value->href);
            if(empty($file))
            $file = '<a href="https://animem.org"></a>';
            if(!file_exists($dirfile))
                file_put_contents($dirfile, $file);
        }
    }
}
function assoc_query3($link)
{
    echo "<br />".$link;
    $html = file_get_html("az/".$link);
    if($a_tag = $html->find('a'))
    foreach ($a_tag as $key => $value)
    {
        $href = str_replace("https://animem.org/", "", $value->href);
        if(strlen($href) < strlen($value->href) && !empty($href))
        {
            $href = str_replace(["https://animem.org/", ":", "/", "?"],["","","_", "."],$value->href);
            $dirfile=str_replace("_.", ".", "full/{$href}.html");
            $file = file_get_contents($value->href);
            if(empty($file))
                $file = '<a href="https://animem.org/animek-a-z/"></a>';
            else{
                $ht = file_get_html($value->href);
                if($file = $ht->find('div.entry-content', 0))
    {
            if(!file_exists($dirfile))
                file_put_contents($dirfile, $file);
            }
            }
        }
    }
}

function findfilefromdir($dirs)
{
	$aa = scandir($dirs);
	$dir = [];
	foreach($aa as $val)
	{
		if($val != '.' && $val != '..' && $val != 'log.txt')
		$dir[] = $val;
	}
	return $dir;
}


foreach (findfilefromdir("az") as $value) {
    assoc_query3($value);
}
?>


