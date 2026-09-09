<?php

function SelectNew($sql = "")
{
	$conn = Connect::getconn();
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
function RealEscapeStringNew($string = "")
{
	$conn = Connect::getconn();
	$result = $conn->real_escape_string($string);
	return $result;
}
function InsertNew($sql = "", $id = FALSE)
{
	$conn = Connect::getconn();
	if ($conn->query($sql) === TRUE)
	{
		return ($id == FALSE) ? TRUE : $conn->insert_id;
	}
	else
	{
		exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre><br />" . $conn->error . "<br />");
	}
}
function DeleteNew($sql = "", $message = FALSE)
{
	$conn = Connect::getconn();
	if ($conn->query($sql))
		return ($message !== FALSE) ? "Records were deleted successfully." : TRUE;
	else
		return ($message !== FALSE) ? ("ERROR: Could not able to execute: *-  $sql  -*. " . $conn->error) : FALSE;
}
function UpdateNew($sql = "", $message = FALSE)
{
	$conn = Connect::getconn();
	if ($conn->query($sql))
		return ($message !== FALSE) ? "Records were updated successfully." : TRUE;
	else
		return ($message !== FALSE) ? ("ERROR: Could not able to execute: *-  $sql  -*. " . $conn->error) : FALSE;
}
