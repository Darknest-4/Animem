<?php

//$servername = "localhost";
//$username = "rlight";
//$database = "animem";

$database2 = "xanime";
$database3 = "xanimem";

require_once("../../Config/loadConfig.php");

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
$conn2 = new mysqli($servername, $username, $password, $database2);
$conn3 = new mysqli($servername, $username, $password, $database3);

if ($conn->connect_error)
  die("Connection failed: " . $conn->connect_error);
if ($conn2->connect_error)
  die("Connection 2 failed: " . $conn2->connect_error);
if ($conn3->connect_error)
  die("Connection 3 failed: " . $conn3->connect_error);


  function total_online($conn)
  {
   $current_time=time();
   $timeout = $current_time - (60);
  
   $session_exist = $conn->query("SELECT session FROM visits_today_unreg WHERE session='{$_SESSION['session']}' LIMIT 1");

   $session_check = (isset($session_exist->num_rows))?$session_exist->num_rows:0;
  
   if($session_check==0 && $_SESSION['session']!="")
   {
    $conn->query("INSERT INTO visits_today_unreg (`session`, `time`) values ('".$_SESSION['session']."','".$current_time."')");
   }
   else
   {
    $conn->query("UPDATE visits_today_unreg SET time='".time()."' WHERE session='{$_SESSION['session']}' LIMIT 1");
   }
   $select_total = Select($conn, "SELECT COUNT(*) as count FROM visits_today_unreg WHERE time>= '$timeout';");

   $total_online_visitors = (isset($select_total[0]["count"]))?$select_total[0]["count"]:0;
   return $total_online_visitors;
  }
  
// To Get Total Online Visitors
$total_online_visitors=total_online($conn);
$visits_today_unreg = Select($conn, "SELECT COUNT(*) as count FROM visits_today_unreg;");
$visits_today_unreg = (isset($visits_today_unreg[0]["count"]))?$visits_today_unreg[0]["count"]:0;

  /*
  if(isset($_GET['get_online_visitor']))
  {
   $total_online=total_online($conn);
   echo $total_online;
   exit();
  }
*/


function getip()
{
  if (!empty($_SERVER['HTTP_CLIENT_IP']))
  {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  }
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  elseif (!empty($_SERVER['REMOTE_ADDR']))
  {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  else
  {
    return False;
  }
  if (20 < strlen($ip))
    return false;
  else
    return $ip;
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
if (!function_exists('print_p'))
{
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
}

if (!function_exists('Insert'))
{
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
}
if (!function_exists('fgc'))
{
  function fgc($p = '')
  {
    return (!empty($p)) ? file_get_contents($p) : FALSE;
  }
}
if (!function_exists('gpi'))
{
  function gpi($p = NULL)
  {
    $explode = (isset($_SERVER['REDIRECT_URL'])) ? explode("/", substr($_SERVER['REDIRECT_URL'], 1)) : array();
    return (isset($explode[$p])) ? $explode[$p] : $explode;
  }
}

function anime($conn, $id)
{
  $data["cycle"] = [
    "genre" => "Műfaj:",
    "demographic" => "Demográfia:",
    "producers" => "Producer:",
    "licensors" => "Licencadók:",
    "studios" => "Studio:",
    "theme" => "Téma:"
  ];
  $data["anime"] = "https://x.animem.org/DataSheet/index/" . $id;
  $data["anime"] = json_decode(fgc($data["anime"]), true);
  $data["anime"] = (isset($data["anime"])) ? $data["anime"] : array();

  return $data["anime"];
}


function save($conn, $mal_id, $description = NULL, $link = NULL)
{
  $errors = [
    "001" => "Az ID nem lehet üres és csak szám lehet!",
    "002" => "Nem sikerült lekérni az adatokat.",
    "003" => "Az ID nem létezik. Vagy probálja meg később.",
    "004" => "Az adatlap már létezik.",
    "005" => "A 'saveOthers' Fuggvény üres tábla értéket kapott."
  ];

  /*********************************************************************************************************************** */

  if (!empty(Select($conn, "SELECT `id` FROM `mal__anime` WHERE `mal_id` = " . $mal_id . " LIMIT 1;")))
  {
  }
  else
  {
    if (!($data = json_decode(file_get_contents("https://x.animem.org/DataSheet/get/" . $mal_id), true))) echo ($errors["002"]);
    if ($data["success"] != TRUE) echo ($errors["003"]);
    // db connect; text; table
    $data["anime"]["information"]["type_id"] = saveOthers($conn, $data["anime"]["information"]["type"], "mal__type");
    $data["anime"]["information"]["age_rating_id"] = saveOthers($conn, $data["anime"]["information"]["rating"], "mal__age_rating");
    $data["anime"]["information"]["source_id"] = saveOthers($conn, $data["anime"]["information"]["source"], "mal__source");

    $data["anime"]["others"]["synopsis"] = ($description != NULL) ? $description : $data["anime"]["others"]["synopsis"];
    $data["anime"]["others"]["buta_wp"] = ($link != NULL) ? $link : NULL;

    $data["anime"]["id"] = saveAnime($conn, $data["anime"]);
    saveGenres($conn, $data["anime"]);
    saveStudios($conn, $data["anime"]);
    // echo json_encode(array("text" => "Az anime mentése megtortént Most már szerepel az adatbázisban. Mint: ". $data["anime"]["title"], "success"=> true));
  }
}

function saveGenres($conn, $data)
{
  if (!is_array($data)) return;

  $genres = [];
  $where = "";
  foreach (["genre", "genres", "theme", "demographic"] as $key)
    if (isset($data["information"][$key]))
      foreach ($data["information"][$key] as $link => $genre)
      {
        $genres[$genre] = [
          "type" => str_replace("s", "", $key),
          "link" => intval($link),
          "name" => $genre
        ];
        if (!empty($where)) $where .= " || ";
        $where .= "`name` = '" . $genre . "'";
      }
  if (!empty($where))
  {
    $select = Select($conn, "SELECT `id`, `name` FROM `mal__genres` WHERE " . $where . ";");


    foreach ($select as $value) unset($genres[$value["name"]]);

    $insert = "";
    foreach ($genres as $value)
    {
      if (!empty($insert)) $insert .= ", ";
      $insert .= "('" . $value["name"] . "', " . $value["link"] . ", '" . $value["type"] . "')";
    }
    if (!empty($insert)) Insert($conn, "INSERT INTO `mal__genres`(`name`, `link`, `type`) VALUES " . $insert);

    $insert = "";
    $select = Select($conn, "SELECT `id`, `name` FROM `mal__genres` WHERE " . $where . ";");
    foreach ($select as $value)
    {
      if (!empty($insert)) $insert .= ", ";
      $insert .= "(" . $data["id"] . ", " . $value["id"] . ")";
    }
    if (!empty($insert)) Insert($conn, "INSERT INTO `mal__anime__genres` (`anime_id`, `genre_id`) VALUES " . $insert);
  }
  else
	if (!empty($insert)) Insert($conn, "INSERT INTO `mal__anime__genres` (`anime_id`, `genre_id`) VALUES (" . $data["id"] . ", 41)");
}
function saveStudios($conn, $data)
{
  if (!is_array($data)) return;

  $studios = [];
  $where = "";
  foreach (["licensors", "studios", "producers"] as $key)
  {
    $studios_type_id = saveOthers($conn, $key, "mal__studios_type");
    if (isset($data["information"][$key]))
      foreach ($data["information"][$key] as $link => $studio)
      {
        $studios[$studio] = [
          "type_id" => $studios_type_id,
          "link" => intval($link),
          "name" => $studio
        ];
        if (!empty($where)) $where .= " || ";
        $where .= "`name` = '" . $studio . "'";
      }
  }
  $where2 = $where;
  $studios2 = $studios;

  $select = Select($conn, "SELECT `id`, `name` FROM `mal__studios` WHERE " . $where . ";");
  foreach ($select as $value)
  {
    $where = str_replace("`name` = '" . $value["name"] . "'", "", $where);
    $where = str_replace(" || ||", "||", $where);
    unset($studios[$value["name"]]);
  }
  if (!empty($studios) && !empty($where))
  {
    $insert = "";
    foreach ($studios as $value)
    {
      if (!empty($insert)) $insert .= ", ";
      $insert .= "('" . $value["name"] . "', '" . $value["link"] . "')";
    }
    if (!empty($insert)) Insert($conn, "INSERT INTO `mal__studios`(`name`, `link`) VALUES " . $insert);
  }
  $insert = "";
  $select = Select($conn, "SELECT `id`, `name` FROM `mal__studios` WHERE " . $where2 . ";");
  foreach ($select as $value)
  {
    if (!empty($insert)) $insert .= ", ";
    $insert .= "(" . $data["id"] . ", " . $value["id"] . ", " . $studios2[$value["name"]]["type_id"] . ")";
  }
  if (!empty($insert)) Insert($conn, "INSERT INTO `mal__anime__studios` (`anime_id`, `studios_id`, `studios_type_id`) VALUES " . $insert);
}

function saveAnime($conn, $data)
{
  $data["information"]["premiered"] = (!isset($data["information"]["premiered"]) || empty($data["information"]["premiered"]) || NULL == $data["information"]["premiered"]) ? array("") : $data["information"]["premiered"];
  $data["information"]["synonyms"] = (!isset($data["information"]["synonyms"]) || empty($data["information"]["synonyms"]) || NULL == $data["information"]["synonyms"]) ? "" : $data["information"]["synonyms"];
  $data["information"]["english"] = (!isset($data["information"]["english"]) || empty($data["information"]["english"]) || NULL == $data["information"]["english"]) ? "" : $data["information"]["english"];
  $data["information"]["japanese"] = (!isset($data["information"]["japanese"]) || empty($data["information"]["japanese"]) || NULL == $data["information"]["japanese"]) ? "" : $data["information"]["japanese"];


  $insert = "INSERT INTO `mal__anime`(";
  $values = ") VALUES (";
  $sqls = [
    '`title`, ' => $data["title"],
    '`english`, ' => $data["information"]["english"],
    '`synonyms`, ' => $data["information"]["synonyms"],
    '`japanese`, ' => $data["information"]["japanese"],
    '`type_id`, ' => intval($data["information"]["type_id"]),
    '`episodes`, ' => $data["information"]["episodes"],
    '`premiered`, ' => reset($data["information"]["premiered"]),
    '`source_id`, ' => intval($data["information"]["source_id"]),
    '`age_rating_id`, ' => intval($data["information"]["age_rating_id"]),
    '`score`, ' => $data["information"]["score"],
    '`members`, ' => $data["information"]["members"],
    '`favorites`, ' => $data["information"]["favorites"],
    '`ranked`, ' => $data["information"]["ranked"],
    '`preview`, ' => $data["others"]["preview"],
    '`synopsis`, ' => $data["others"]["synopsis"],
    '`popularity`, ' => $data["information"]["popularity"],
    '`aired`, ' => $data["information"]["aired"],
    '`status`, ' =>  $data["information"]["status"],
    '`mal_id`, ' => intval($data["mal_id"]),
    '`img`, ' => $data["img"],
    '`buta_wp`' => $data["others"]["buta_wp"]
  ];

  foreach ($sqls as $key => $value)
  {
    if ($value == "N/A")
      $value = 0;
    $insert .= $key;
    $values .= (is_string($value)) ? '"' . $conn->real_escape_string(htmlspecialchars($value)) . '", ' : $value . ', ';
  }
  $sql = $insert . rtrim($values, ", ") . ");";

  return Insert($conn, $sql, TRUE);
}
function saveOthers($conn, $data, $table)
{
  if (is_array($data) && count($data) == 1)
    $data = reset($data);
  if (empty($table)) exit($errors["005"]);

  $select = Select($conn, "SELECT `id` FROM `" . $table . "` WHERE `name` = '" . $data . "' LIMIT 1;");
  if (empty($select))
    return Insert($conn, "INSERT INTO `" . $table . "`(`name`) VALUES ('" . $data . "')", TRUE);
  else
    return $select[0]["id"];
}

function animek($conn, $conn2, $conn3, $g)
{

  $PInfo = strtoupper($g[1]);

  $CharList = explode(", ", "A, B, C, D, E, F, G, H, I, J, K, L, M, N, O, P, Q, R, S, T, U, V, W, X, Y, Z");
  $Limit = 20;
  $Where = (in_array($PInfo, $CharList)
    ? "`xanimem`.`mal__anime`.title LIKE '" . $PInfo . "%' && `xanimem`.`mal__anime`.title != ''"
    : ($PInfo == "ALL"
      ? "`xanimem`.`mal__anime`.title != ''"
      : "Upper(substr(`xanimem`.`mal__anime`.title,1,1)) NOT in ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z') && `xanimem`.`mal__anime`.title != ''"
    )
  );


  $mal = Select($conn3, "SELECT
    `xanimem`.`mal__anime`.`title`,
    `xanimem`.`mal__anime`.`buta_wp`,
    `xanimem`.`mal__anime`.`buta_wp2`,
    `xanimem`.`mal__anime`.`buta_wp_title`,
    `xanimem`.`mal__anime`.`buta_wp_title2`,
    `xanimem`.`mal__anime`.`img`,
    `animem`.`datasheet`.`series`
  FROM
      `xanimem`.`mal__anime`
  INNER JOIN `animem`.`datasheet` ON
      `animem`.`datasheet`.`myanimelist` = `xanimem`.`mal__anime`.`mal_id`
  WHERE
      `xanimem`.`mal__anime`.`buta_wp` IS NOT NULL && `xanimem`.`mal__anime`.`buta_wp` != '' && `animem`.`datasheet`.`series` = 1 && " . $Where . "
  GROUP BY
      `xanimem`.`mal__anime`.`title`
  ORDER BY
      `xanimem`.`mal__anime`.`title`;");

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

    foreach (str_split("opqrstuwvxyz1", 1) as $key => $value)
    {
      echo "<a href=\"https://animem.org/animek/" . $value . "\" style=\"border:solid 1px white; margin:2px; padding:4px;\">" . strtoupper($value) . "</a>";
    }
    echo '</h6> <div class="bdp-list-main bdp-design-1 bdp-clearfix"> <div class="bdp-post-list bdp-clearfix">';
    foreach ($mal as $key => $value)
    {
      if (empty($value["buta_wp_title2"]))
        $value["buta_wp_title2"] = $value["title"];
      if (empty($value["buta_wp_title"]))
        $value["buta_wp_title"] = $value["title"];

      if (!empty($value["buta_wp2"]))
      {
        $ecs .= '<a class="div" href="https://animem.org/' . $value["buta_wp2"] . '" style="" title="' . htmlspecialchars_decode($value["buta_wp_title2"])  . '">
        <div class="dive">
        <h6 style="margin:5px;" class="cut-text">' . htmlspecialchars_decode($value["buta_wp_title2"])  . '</h6>
        </div>
        <div>
        <img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
        </div>
        </a>';
      }
      elseif (!empty($value["buta_wp"]))
      {
        $ecs .= '<a class="div" href="https://animem.org/' . $value["buta_wp"] . '" style="" title="' . htmlspecialchars_decode($value["buta_wp_title"])  . '">
        <div class="dive">
        <h6 style="margin:5px;" class="cut-text">' . htmlspecialchars_decode($value["buta_wp_title"])  . '</h6>
        </div>
        <div>
        <img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
        </div>
        </a>';
      }
    }
    $ecs = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $ecs;
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
