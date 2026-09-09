<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */


$servername = "localhost";
$username = "animem";
$password = "OV3SY1WCZyew6sZEDFu4D5ClzHAeHj8U0O4X2SPwwmy4KdnPn1Z81PLzesUhu4Ud";
$dbname = "animem";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
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
	//////////////////////////////Boss Model//////////////////////////////////
	function select_ass($conn, $column, $table, $where = "", $w=false)
	{
		$sql = "SELECT $column FROM `$table`";
		if($w)
			$sql .= " WHERE $where";

			$result = $conn->query($sql);

		if (isset($result->num_rows)  && 0 < $result->num_rows)
		{
			while($row = $result->fetch_assoc())
			{
				$return[] = $row;
			}
			return $return;
		}else
		{
			return "0 results. Command: ".$sql;
		}
	}
	
	function select_count($conn, $table, $where = "", $w=false)
	{
		$sql = "SELECT COUNT(*) FROM `$table`";
		if(empty($where))
			$where = 1;
		if($w)
			$sql .= " WHERE $where";

		$result = $conn->query($sql);

		if(isset($result->num_rows) && $result->num_rows > 0)
		{
			while($row = $result->fetch_assoc())
			{
				$return = $row;
			}
			return $return["COUNT(*)"];
		}else
		{
			return 0;
		}
	}
	function update($conn, $table, $column, $value, $where)
	{
		if(is_numeric($value))
			$sql = "UPDATE `$table` SET $column = $value";
		else
			$sql = "UPDATE `$table` SET $column = \"$value\"";
		
		
		$sql .= " WHERE $where";

		if ($conn->query($sql) === TRUE)
		{
			return true;
		}else
		{
			print_p("Error: " . $sql . "<br>" . $conn->error);
		}
	}
	function delete_row($conn, $table, $scolumn = "id", $vcolumn)
	{
		if(is_numeric($vcolumn))
			$sql = "DELETE FROM `$table` WHERE $scolumn=$vcolumn";
		else
			$sql = "DELETE FROM `$table` WHERE $scolumn=\"$vcolumn\"";
			
		if ($conn->query($sql) === TRUE)
		{
			return "Record deleted successfully";
		}else
		{
			return "Error deleting record: " . $conn->error;
		}
	}


	function insert_row($conn, $table, $column, $value)
	{
		$sql = "INSERT INTO `$table` ($column)
		VALUES ($value)";

		if ($conn->query($sql) === TRUE)
		{
			return true;
			//return "New record created successfully";
		}else
		{
			die("Error: " . $sql . "<br>" . $conn->error);
		}
	}


$column = "`ip`";
if(getip() !== False)
{
  $value = '"' . getip() . '"';
  $vtu = select_ass($conn, "*", "visits_today_unreg", "ip = '".getip()."' LIMIT 1", true);
  if(!is_array($vtu))
  {
    $insert = insert_row($conn, "visits_today_unreg", $column, $value);
  }else
  {
    
    
  
  $update = mysqli_query($conn, "UPDATE visits_today_unreg SET used=1 WHERE ip = '".getip()."' LIMIT 1");
  $update = mysqli_query($conn, "UPDATE visits_today_unreg SET last_date=".time()." WHERE ip = '".getip()."' LIMIT 1");
  
  }}
define( 'WP_USE_THEMES', true );

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp-blog-header.php';
