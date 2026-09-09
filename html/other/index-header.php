<?php

session_start();
$_SESSION['session'] = session_id();

$search = (isset($_GET["s"])) ? $_GET["s"] : "";

//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database = "animem";

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error)
  die("Connection failed: " . $conn->connect_error);

function RealEscapeString($string = "")
{
  return RealEscapeStringNew($string);
}

/******************************************************************************************************* */
/******************************************************************************************************* */
/******************************************************************************************************* */
function template()
{
  $template = findfilefromdir("Assets/template");
  $template[] = $template[0];
  unset($template[0]);
  return $template;
}
/******************************************************************************************************* */
/******************************************************************************************************* */
function logged()
{

  if (!isset($_COOKIE["userID"]) || empty($_COOKIE["userID"]))
  {
    return FALSE;
  }
  else
  {
    $id = SelectNew("SELECT `id` FROM `users` WHERE `id` = {$_COOKIE["userID"]} LIMIT 1;");
    if (!isset($id[0]["id"]))
    {
      setcookie("userID", "", time() - (60 * 60 * 24 * 7), "/");
      unset($_COOKIE["userID"]);
    }else
      return TRUE;
  }
}
/******************************************************************************************************* */
/******************************************************************************************************* */
/******************************************************************************************************* */




$visits_today_unreg = todayUsers($conn);

setVisitorsFromDatabase($conn);
/******************************************************************************************************* */

function totalUsers($conn)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `users`;";
  $return =  Select($conn, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && is_numeric($return[0]["count"])) ? $return[0]["count"] : 0;
}
function totalAnime($conn)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `mal__anime`;";
  $return =  Select($conn, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && is_numeric($return[0]["count"])) ? $return[0]["count"] : 0;
}
function totalLinks($conn)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `links`;";
  $return =  Select($conn, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && is_numeric($return[0]["count"])) ? $return[0]["count"] : 0;
}
function newUsers($conn)
{
  $today = date("Y-m-d 00:00:00");
  $sql = "SELECT COUNT(*) as 'count' FROM `users` WHERE '{$today}' <= `regtime`;";
  $return =  Select($conn, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && is_numeric($return[0]["count"])) ? $return[0]["count"] : 0;
}
function onlineUsers($conn)
{
  $timeout = time() - (30 * 60);
  $sql = "SELECT COUNT(*) as 'count' FROM `statistic__visits_today` WHERE '{$timeout}' <= `last_time` && 0 < `updated`;";
  $return =  Select($conn, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && is_numeric($return[0]["count"])) ? $return[0]["count"] : 0;
}
function todayUsers($conn)
{
  $sql = "SELECT COUNT(*) as 'count' FROM `statistic__visits_today` WHERE 0 < `updated`;";
  $return =  Select($conn, $sql);
  unset($sql);
  return (is_array($return) && !empty($return) && is_numeric($return[0]["count"])) ? $return[0]["count"] : 0;
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


//////////////////////////////Boss Model//////////////////////////////////
function select_ass($conn, $column, $table, $where = "", $w = false)
{
  $sql = "SELECT $column FROM `$table`";
  if ($w)
    $sql .= " WHERE $where";

  $result = $conn->query($sql);

  if (isset($result->num_rows)  && 0 < $result->num_rows)
  {
    while ($row = $result->fetch_assoc())
    {
      $return[] = $row;
    }
    return $return;
  }
  else
  {
    return "0 results. Command: " . $sql;
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
  }
  else
  {
    die("Error: " . $sql . "<br>" . $conn->error);
  }
}
/*
$column = "`ip`";
if (getip() !== False)
{
  $value = '"' . getip() . '"';
  $vtu = select_ass($conn, "*", "visits_today_unreg", "ip = '" . getip() . "' LIMIT 1", true);
  if (!is_array($vtu))
  {
    $insert = insert_row($conn, "visits_today_unreg", $column, $value);
  }
  else
  {
    $update = mysqli_query($conn, "UPDATE visits_today_unreg SET used=1 WHERE ip = '" . getip() . "' LIMIT 1");
    $update = mysqli_query($conn, "UPDATE visits_today_unreg SET last_date=" . time() . " WHERE ip = '" . getip() . "' LIMIT 1");
  }
}
*/

$cycle = [
  "genre" => "Műfaj:",
  "demographic" => "Demográfia:",
  "producers" => "Producer:",
  "licensors" => "Licencadók:",
  "studios" => "Studio:",
  "theme" => "Téma:"
];


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


function fgc($p = '')
{
  return (!empty($p)) ? file_get_contents($p) : FALSE;
}

function gpi($p = NULL)
{
  $explode = (isset($_SERVER['REQUEST_URI'])) ? explode("/", substr($_SERVER['REQUEST_URI'], 1)) : array();
  return (isset($explode[$p])) ? $explode[$p] : $explode;
}
function gpi2($p = NULL)
{
  $explode = (isset($_SERVER['PATH_INFO'])) ? explode("/", substr($_SERVER['PATH_INFO'], 1)) : array();
  return (isset($explode[$p])) ? $explode[$p] : $explode;
}





function animek($conn, $g)
{
  $Template["anime"] = '<a class="div" href="https://animem.org/{{LINK}}" style="" title="{{TITLE}}">'
    . '<div class="dive"><h6 style="margin:5px;" class="cut-text">{{TITLE}}</h6></div>'
    . '<div><img src="{{IMG}}" style="width: 200px!important; height:300px!important; display: block;"></div></a>';
  $PInfo = strtoupper($g[1]);

  $CharList = explode(", ", "A, B, C, D, E, F, G, H, I, J, K, L, M, N, O, P, Q, R, S, T, U, V, W, X, Y, Z");
  $Where = (in_array($PInfo, $CharList)
    ? "`datasheet`.`series` = 1 && `mal__anime`.title LIKE '" . $PInfo . "%' && `mal__anime`.title != ''"
    : ($PInfo == "ALL"
      ? "`datasheet`.`series` = 1 && `mal__anime`.title != ''"
      : ($PInfo == "FILMEK"
        ? "`mal__anime`.`type_id` = 2 && `mal__anime`.title != ''"
        : "`datasheet`.`series` = 1 && Upper(substr(`mal__anime`.title,1,1)) NOT in ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z') && `mal__anime`.title != ''"
      )
    )
  );


  $mal = Select($conn, "SELECT
    `datasheet`.`link`,
    `datasheet`.`title` AS 'dtitle',
    `mal__anime`.`title`,
    `mal__anime`.`img`,
    `datasheet`.`series`
  FROM
      `mal__anime`
  INNER JOIN `datasheet` ON
      `datasheet`.`myanimelist` = `mal__anime`.`mal_id`
  WHERE
       " . $Where . "
  GROUP BY
      `mal__anime`.`title`
  ORDER BY
      `mal__anime`.`title`;");

  $ecs = "";

  if (isset($mal[0]))
  {
    echo '
			<div class="rapidwp-box-inside" style="margin-top:10px;">
								<header class="entry-header">
									<div class="entry-header-inside">
										<h1 class="post-title entry-title">Animék - ' . strtoupper($g[1]) . '</h1>
										<div class="rapidwp-entry-meta-single"></div>
									</div>
								</header><div class="elementor-section-wrap"> 
								<h6 style="text-align: center; width:100%;">';



    foreach (str_split("abcdefghijklmn", 1) as $key => $value)
    {
      echo "<a href=\"https://animem.org/animek/" . $value . "\" style=\"border:solid 1px white; margin:2px; padding:4px;\">" . strtoupper($value) . "</a>";
    }
    echo '</h6><h6 style="text-align: center; width:100%;">';

    foreach (str_split("opqrstuwvxyz", 1) as $key => $value)
    {
      echo "<a href=\"https://animem.org/animek/" . $value . "\" style=\"border:solid 1px white; margin:2px; padding:4px;\">" . strtoupper($value) . "</a>";
    }
    echo "<a href=\"https://animem.org/animek/1\" style=\"border:solid 1px white; margin:2px; padding:4px;\">1-9</a>";
    echo '</h6><h6 style="text-align: center; width:100%;">';
    echo "<a href=\"https://animem.org/animek/Filmek\" style=\"border:solid 1px white; margin:2px; padding:4px;\">Filmek</a>";
    echo '</h6> <div class="bdp-list-main bdp-design-1 bdp-clearfix"> <div class="bdp-post-list bdp-clearfix">';
    foreach ($mal as $key => $value)
    {
      $ecs .= str_replace(["{{LINK}}", "{{TITLE}}", "{{IMG}}"], [$value["link"], ((textInText("Series", $value["dtitle"])) ? $value["title"] . " Series" : $value["title"]), $value["img"]], $Template["anime"]);
    }
    $ecs = '<section style="display: block;vertical-align: top;text-align: center;">' . $ecs;
    $ecs .= '</section>';

    echo $ecs;


    echo '</div></div>
							</div>
							<footer class="entry-footer"></footer>
						</div>';
  }
  else
  {
    echo '
		<div class="rapidwp-box-inside" style="margin-top:10px;">
							<header class="entry-header">
								<div class="entry-header-inside">
									<h1 class="post-title entry-title">404-es Hiba!+</h1>
									<div class="rapidwp-entry-meta-single"></div>
								</div>
							</header>
							<div class="entry-content clearfix">
							Sajnáljuk, de a kért oldal nem található.
							</div>
							<footer class="entry-footer"></footer>
						</div>';
  }
}
