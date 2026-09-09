<?php //if(getip() != "45.128.71.2") exit("Késő este van és nem telik karbantartás png-re. Igy most ezt a szoveget kell olvasnod. Nyugi reggel már menni fog az oldal. :) "); ?>
<?php

$search = (isset($_GET["search"]))?$_GET["search"]:"";

require_once("../Config/loadConfig.php");

$siteConfig = DbConfig::getSiteConfig();
if (!defined("BASEURL")) {
  define("BASEURL", "https://".$siteConfig['domain']."/");
}

$header = '
  <!doctype html>
  <html lang="hu">
    <head>
      <!-- Required meta tags -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <!-- Bootstrap CSS -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
      <link href="<?= BASEURL;?>Assets/fontawesome/free/5.15.2/css/all.css" rel="stylesheet">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>


      <title>DataSheet Editor</title>

    <style>
        
      .list tr
      {
        background-color: antiquewhite;
      }
      
      .list tr:first-child
      {
        background-color: burlywood;
      }
      .list tr:nth-child(2n)
      {
        background-color: darkseagreen;
      }
      
      .list tr:nth-child(3n) + tr
      {
        background-color: burlywood;
      }

        .scroll
        {	
          scrollbar-color: #444 rgba(0,0,0,0.3);
          scrollbar-width: thin;
          overflow-x: hidden;
          overflow-y: auto;
        }
        .scroll::-webkit-scrollbar
        {
          width: 10px;
          background-color: #F5F5F5;
        }
        .scroll::-webkit-scrollbar-thumb{
          -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
          background-color: #222;
        }
        .scroll::-webkit-scrollbar-track{
          -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
          background-color: #F5F5F5;
        }
      </style>
      
    </head>
    <body class="scroll">
      <div class="container-fluid mt-3">
        <div class="row mx-0">
          <div class="col-12 px-0">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mt-0">
              <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo03" aria-controls="navbarTogglerDemo03" aria-expanded="false" aria-label="Toggle navigation">
                  <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand">Admin</a>
                <div class="collapse navbar-collapse" id="navbarTogglerDemo03">
                  <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="' . BASEURL . "episode.php" . '">
                        EpisodeList
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="' . BASEURL . "uploaders.php" . '">
                        Uploaders
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="' . BASEURL . "datasheet2.php" . '">
                        DataSheet
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="' . BASEURL . "blog.php" . '">
                        Blog
                      </a>
                    </li>
                    <li class="nav-item me-2">
                      <form action="/datasheet2.php" method="get" id="formsearch">
                        <div class="input-group">
                          <input type="text" class="form-control" name="search" placeholder="Search" form="formsearch" value="' . $search . '">
                          <button class="input-group-text" form="formsearch" type="submit">Search</button>
                        </div>
                      </form>
                    </li>
                    <li class="nav-item me-2">
                      <form action="/datasheet2.php" method="get" id="formnew">
                        <div class="input-group">
                          <input type="text" class="form-control" name="new" placeholder="New DataSheet" form="formnew">
                          <button class="input-group-text" form="formnew" type="submit">New</button>
                        </div>
                      </form>
                    </li>
                  </ul>
                </div>
              </div>
            </nav>
          </div>
';
$footer = '
  <!-- Optional JavaScript; choose one of the two! -->

  <!-- Option 1: Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  <!-- Option 2: Separate Popper and Bootstrap JS -->
  <!--
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
  -->
  <script src="http://sortablejs.github.io/Sortable/Sortable.js"></script>
  <script>/*
  var sortable = Sortable.create(el);
    new Sortable(example1, {
        animation: 150,
        ghostClass: \'blue-background-class\'
    });*/
  </script>
  </body>
  </html>
';
$header2 = file_get_contents("template/header.phtml");

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





if(isset($_GET["search"]))
{
  echo $header;
  $arr = Select($conn, 'SELECT `datasheet`.`save`, `datasheet`.`link`, `datasheet`.`title` FROM `datasheet` WHERE `datasheet`.`title` LIKE "%' . $_GET["search"] . '%" ORDER BY `title`');
  
  ?>
    <div class="container-fluid">
      <div class="row">
        <h3>
          Search: <?= $_GET["search"];?>
        </h3>
      </div>
      <div class="row">
        <div class="col">
          <table class="table table-striped">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Cim</th>
                <th scope="col">Link</th>
                <th scope="col">Edit</th>
              </tr>
            </thead>
            <tbody class="list">
  <?php
  
  
  foreach ($arr as $key => $value)
  {
    ?>
              <tr>
                <th scope="row"><?= $key+1;?></th>
                <td><?= $value["title"];?></td>
                <td>[<a href="<?= BASEURL;?><?= $value["link"];?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL;?>datasheet2.php?edit=<?= $value["link"];?>" target="_blank">Edit</a>]</td>
              </tr>
    <?php
  }

  ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php

  echo $footer;
}elseif(isset($_GET["edit"]))
{
  $arr = Select($conn, 'SELECT * FROM `datasheet` WHERE `link` = "' . $_GET["edit"] . '" LIMIT 1');
  
  if(isset($arr[0]['id']))
  {
    $arr = $arr[0];
    $title = $arr["title"];
    $title2 = $arr["title2"];
    $link = $arr["link"];
    $myanimelist = $arr["myanimelist"];
    $description = $arr["description"];
    $fansub = $arr["save"];
    $links = $arr["save"];

    echo $header;
    require_once("template/datasheet/edit2.php");
    echo $footer;
  }
}elseif(isset($_GET["new"]) && !empty($_GET["new"]))
{
  $data["fansub"] = array();
  $data["links"] = array();
  $link = str_replace(["'", " ", "?", "!", '"', "__"], "_", $_GET["new"]);
  $title = str_replace(["'", '"'], ["\'", '\"'], $_GET["new"]);
  $arr = Select($conn, 'SELECT `datasheet`.`link` FROM `datasheet` WHERE `datasheet`.`link` = "' . $link . '" LIMIT 1');
  print_p($_GET);
  if (isset($arr[0]["link"]))
  {
    $link = $arr[0]["link"];
  }else
  {
    $id = Insert($conn, "INSERT INTO `datasheet` (`title`,`link`, `save`) VALUES ('" .$title. "', '" .$link. "', '" .json_encode($data). "')", TRUE);
    $link = $conn -> real_escape_string($_GET["new"]);
  }
  header("Location: " . BASEURL . "datasheet2.php?edit=" . $link);
  
  
}elseif(isset($_GET["mininews"]) && !empty($_GET["mininews"]))
{
  $_GET["link_url"] = (isset($_GET["link_url"]) && !empty($_GET["link_url"]))?$conn -> real_escape_string($_GET["link_url"]):NULL;
  $_GET["link_description"] = (isset($_GET["link_description"]) && !empty($_GET["link_description"]))?$conn -> real_escape_string($_GET["link_description"]):NULL;

  print_p($_GET);
  // die();

  if($_GET["mininews"] != NULL)
  {
    Insert($conn, "INSERT INTO `wp_links`(`link_url`, `link_description`)
                    VALUES ('" . $_GET["link_url"] . "',
                            '" . $_GET["link_description"] . "')");
  }
  header("Location: " . BASEURL . "datasheet2.php?edit=" . $_GET["mininews"]);
  
  
}elseif(isset($_GET["delete"]))
{
  $select = Select($conn, 'SELECT `datasheet`.`ID` FROM `datasheet` WHERE `datasheet`.`link` = "' . $_GET["delete"] . '" LIMIT 1');
  print_p($_GET);
 // Insert($conn, "INSERT INTO `links` (`link`) VALUES ('" . $value2 . "')", TRUE);
   
  if(is_array($select) && isset($select[0]["ID"]))
    Update($conn, "DELETE FROM `datasheet` WHERE `ID` =" . $select[0]["ID"] . "");

  header("Location: " . BASEURL . "datasheet2.php");
  
}elseif(isset($_GET["save"]))
{
  
  //$arr = Select($conn, 'SELECT `datasheet`.`save`, `datasheet`.`link`, `datasheet`.`title` FROM `datasheet` WHERE `datasheet`.`link` = "' . $_GET["save"] . '" && `datasheet`.`post_status` = "publish" LIMIT 1');
  //print_p($_GET);

  $title = $_GET["title"];
  $title2 = $_GET["title2"];
  $link = $_GET["link"];
  $myanimelist = $_GET["myanimelist"];
  $description = $_GET["description"];



  $json["_type"] = "datasheet";
  $json["fansub"] = json_decode($_GET["fansub"]);
  $json["links"] = json_decode($_GET["episodelist"]);

  $json = json_encode($json);
 // print_p($json);


  $select = Select($conn, "SELECT `datasheet`.`id`, `datasheet`.`save` FROM `datasheet` WHERE `datasheet`.`link` = '" . $_GET["save"] . "' ORDER BY `id` ASC LIMIT 1");

  
  if(is_array($select) && isset($select[0]["id"]))
    Update($conn, "UPDATE `datasheet` SET
    `save2`='" . $select[0]["save"] . "',
    `save`='" . $json . "',
    `title`='" . $title . "',
    `title2`='" . $title2 . "',
    `link`='" . $link . "',
    `description`='" . $description . "',
    `myanimelist`= " . $myanimelist . "
    WHERE `id` = '" . $select[0]["id"] . "'");

  header("Location: " . BASEURL . "datasheet2.php?edit=" . $_GET["save"]);
  
}elseif(isset($_GET["reform"]))
{
  
  $select = Select($conn, 'SELECT `datasheet`.`ID`, `datasheet`.`save`, `datasheet`.`save2`, `datasheet`.`link`, `datasheet`.`title` FROM `datasheet` WHERE `datasheet`.`link` = "' . $_GET["reform"] . '" && `datasheet`.`post_status` = "publish" LIMIT 1');
  print_p($_GET);
  print_p($select);
  if(isset($select[0]["save2"]) && !empty($select[0]["save2"]))
  {
    Update($conn, "UPDATE `datasheet` SET `save`='" . $conn -> real_escape_string($select[0]["save2"]) . "' WHERE `ID` = '" . $select[0]["ID"] . "'");
  }
  
  
   header("Location: " . BASEURL . "datasheet2.php?edit=" . $_GET["reform"]);
  
}elseif(isset($_GET["test"]))
{
  
  $datasheet = Select($conn, 'SELECT * FROM `datasheet` ORDER BY `title` ASC');
  foreach ($datasheet as $key => $value)
  {
    $sql = 'SELECT `post_content` FROM `wp_posts` WHERE `post_name` = "{{D1}}" && `post_title` = "{{D2}}" && `post_content` LIKE "%datasheet%" ORDER BY `post_title` ASC LIMIT 1';
    $sql = str_replace(["{{D1}}", "{{D2}}"],[$value["link"], $value["title"]], $sql);
    $wp_datasheet = Select($conn, $sql);
    if(isset($wp_datasheet[0]["post_content"]))
    {
      $save = $wp_datasheet[0]["post_content"];
      $save = json_decode($save, true);
      if(!is_array($save))
      {
        echo "<br />" . $sql;
        $save = json_encode(array());
      }else
      {
        if(isset($save["_type"])) unset($save["_type"]);
        if(isset($save["myanimelist"])) unset($save["myanimelist"]);
        if(isset($save["description"])) unset($save["description"]);
        if(isset($save["links"]) && is_array($save["links"]))
        foreach ($save["links"] as $text => $link)
        {
          $sql = 'SELECT `id` FROM `episodelist` WHERE `link` LIKE "%{{D1}}%" LIMIT 1';
          
          
          if(substr($link, -1) == "/")
            $link = substr($link, 0, -1);
          $link = explode("animem.org/", $link);
          if(isset($link[1]))
            $link = $link[1];
          else
            $link = "redirect/". $link[0];
        

          $sql = str_replace(["{{D1}}"],[$link], $sql);
          $link_sel = Select($conn, $sql);
          if(isset($link_sel[0]["id"]))
          {
            $save["links2"][] = $link_sel[0]["id"];
          }else
          {
            $sql = 'SELECT `ID`, `post_content` FROM `wp_posts` WHERE `post_name` LIKE "%{{D1}}%" && `post_content` LIKE "%episodelist%" LIMIT 1';
            $sql = str_replace(["{{D1}}"],[$link], $sql);
            $link_sel = Select($conn, $sql);
            if(isset($link_sel[0]["ID"]))
            {
              $id = "INSERT INTO `episodelist`(`title`, `link`, `save`) VALUES ('{{D1}}', '{{D2}}', {{D3}});";
              $id = Insert($conn, str_replace(["{{D1}}", "{{D2}}", "{{D3}}"],[$text, $link, $link_sel[0]["post_content"]], $id), true);
              $save["links2"][] = $id;
            }elseif(strlen(str_replace("redirect/", "", $link)) < strlen($link))
            {
              $id = "INSERT INTO `episodelist`(`title`, `link`) VALUES ('{{D1}}', '{{D2}}');";
              $id = Insert($conn, str_replace(["{{D1}}", "{{D2}}"],[$text, $link], $id), true);
              $save["links2"][] = $id;
            }else
            {
              $save["links2"] = array();
            }
          }

        }
        $save["links"] = $save["links2"];
        unset($save["links2"]);
        $save = str_replace("'", "\'", preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', json_encode($save)));
      }
      $datasheet[$key]["save"] = $save;
    }else
    {
      echo "<br />" . $sql;
    }
  }
  foreach ($datasheet as $key => $value)
  {
    $sql = "UPDATE `datasheet` SET `save`='{{D1}}' WHERE `id` = {{D2}} LIMIT 1";
    $sql = str_replace(["{{D1}}", "{{D2}}"],[$value["save"], $value["id"]], $sql);
    Update($conn, $sql);
  }
  print_p($datasheet);
    

}else
{
  echo $header;
  $arr = Select($conn, 'SELECT `datasheet`.`save`, `datasheet`.`link`, `datasheet`.`title` FROM `datasheet`  ORDER BY `title`');
  
  ?>
    <div class="container-fluid">
      <div class="row">
        <h3>
          All DataSheet: <?= count($arr);?>
        </h3>
      </div>
      <div class="row">
        <div class="col">
          <table class="table table-striped">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Cim</th>
                <th scope="col">Link</th>
                <th scope="col">Edit</th>
              </tr>
            </thead>
            <tbody class="list">
  <?php
  
  
  foreach ($arr as $key => $value)
  {
    ?>
              <tr>
                <th scope="row"><?= $key+1;?></th>
                <td><?= $value["title"];?></td>
                <td>[<a href="<?= BASEURL;?><?= $value["link"];?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL;?>datasheet2.php?edit=<?= $value["link"];?>" target="_blank">Edit</a>]</td>
              </tr>
    <?php
  }

  ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php

  echo $footer;
}


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

$conn->close();
$conn3->close();
?>
