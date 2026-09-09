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
      <script src="https://cdn.tiny.cloud/1/qagffr3pkuv17a8on1afax661irst1hbr4e6tbv888sz91jc/tinymce/5-stable/tinymce.min.js"></script>
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/@json-editor/json-editor@latest/dist/jsoneditor.min.js"></script>


      <script src="https://cdn.ckeditor.com/ckeditor5/31.1.0/inline/ckeditor.js"></script>



      <title>Home</title>

      <style>
      .document-editor {
        border: 1px solid var(--ck-color-base-border);
        border-radius: var(--ck-border-radius);
    
        /* Set vertical boundaries for the document editor. */
        max-height: 700px;
    
        /* This element is a flex container for easier rendering. */
        display: flex;
        flex-flow: column nowrap;
    }



      </style>
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
                      <a class="nav-link active" aria-current="page" href="' . BASEURL . "datasheet.php" . '">
                        DataSheet
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="' . BASEURL . "blog.php" . '">
                        Blog
                      </a>
                    </li>
                    <li class="nav-item me-2">
                      <form action="/blog.php" method="get" id="formsearch">
                        <div class="input-group">
                          <input type="text" class="form-control" name="search" placeholder="Search" form="formsearch" value="' . $search . '">
                          <button class="input-group-text" form="formsearch" type="submit">Search</button>
                        </div>
                      </form>
                    </li>
                    <li class="nav-item me-2">
                      <form action="/blog.php" method="get" id="formnew">
                        <div class="input-group">
                          <input type="text" class="form-control" name="new" placeholder="New Blog" form="formnew">
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

if(!function_exists('Insert'))
{
    function Insert($conn, $sql="", $id=FALSE)
    {
        if($conn->query($sql) === TRUE) {
            return ($id == FALSE)?TRUE:$conn->insert_id;
        } else {
            exit("<br />SQL Syntax Error! SQL:<br /><pre>" . $sql . "</pre><br />".$conn->error . "<br />");
        }
        
    }
}
if(!function_exists('fgc'))
{
    function fgc($p = '')
    {
        return (!empty($p)) ? file_get_contents($p) : FALSE;
    }
}
if(!function_exists('gpi'))
{
    function gpi($p = NULL)
    {
        $explode = (isset($_SERVER['REDIRECT_URL'])) ? explode("/", substr($_SERVER['REDIRECT_URL'], 1)) : array();
        return (isset($explode[$p])) ? $explode[$p] : $explode;
    }
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




if(isset($_GET["search"]))
{
  echo $header;
  $arr = Select($conn, 'SELECT `wp_posts`.`post_content`, `wp_posts`.`post_name`, `wp_posts`.`post_title` FROM 
  `wp_posts` WHERE `wp_posts`.`post_title` LIKE "%' . $_GET["search"] . '%" 
  && `wp_posts`.`post_content` NOT LIKE
   "%episodelist%" && `wp_posts`.`post_content` NOT LIKE "%datasheet%" ORDER BY post_name');
  
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
                <th scope="col">Delete</th>
              </tr>
            </thead>
            <tbody class="list">
  <?php
  
  
  foreach ($arr as $key => $value)
  {
    ?>
              <tr>
                <th scope="row"><?= $key+1;?></th>
                <td><?= $value["post_title"];?></td>
                <td>[<a href="<?= BASEURL;?><?= $value["post_name"];?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL;?>blog.php?edit=<?= $value["post_name"];?>" target="_blank">Edit</a>]</td>
                <td>[<a href="<?= BASEURL;?>blog.php?delete=<?= $value["post_name"];?>"   onclick="return confirm('Are you sure you want to delete this?\n<?= $value['post_title'];?>')"target="_blank">Delete</a>]</td>
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
  echo $header;
  $arr = Select($conn, 'SELECT `wp_posts`.`post_content`, `wp_posts`.`post_name`, `wp_posts`.`post_title` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["edit"] . '" LIMIT 1');
  $uploaders = Select($conn, 'SELECT `uploaders`.* FROM `uploaders` ORDER BY `name`');
  $uploader = "";
  $link = array();
  $fansub = array();
  
  foreach ($uploaders as $key => $value) {
    $uploader .= (empty($uploader))?'"' . $value["name"] . '"':', "' . $value["name"] . '"';
  }
  $json = json_decode($arr[0]["post_content"],true);
  foreach ($json["links"] as $key => $value) {
    $link[] = [
      "link"=> $value
    ];
  }
  foreach ($json["fansub"] as $key => $value) {
    $fansub[] = [      
      "text"=> $key,
      "link"=> $value
    ];
  }
  $json["links"] = json_encode($link);
  print_p($json);

  ?>
    <div class="container-fluid">
      <div class="row">
        <h3>
          Edit: <?= $arr[0]["post_title"];?>
        </h3>
      </div>
      <form action="/blog.php" method="get" id="form2">
        <input type="text" style="display:none;" type="text" name="reform" value="<?= $_GET["edit"];?>" readonly="readonly" form="form2">
      </form>
      <form action="/blog.php" method="get" id="mininews">
        <input type="text" style="display:none;" type="text" name="mininews" value="<?= $arr[0]["post_name"];?>" readonly="readonly" form="mininews">
        <input type="text" style="display:none;" type="text" name="link_url" value="<?= $arr2[0]["post_name"];?>" readonly="readonly" form="mininews">
      </form>
      <form action="/blog.php" method="get" id="form1">
        <input type="text" style="display:none;" type="text" name="save" value="<?= $_GET["edit"];?>" readonly="readonly" form="form1">
          <div class="row">
            <div class="col-8">
              <button type="submit" class="btn btn-success" form="form1">Save</button>
              <input class="btn btn-warning" type="button" value="Add Rows" onclick="addRow()"/>
              <input class="btn btn-danger" type="button" value="Clear All" onclick="Clear_All()"/>
              <a class="btn" target="_blank" href="<?= BASEURL;?><?= $arr[0]["post_name"];?>" style="background-color:#d63384; color:#fff;">View</a>
              <button type="submit" class="btn btn-info" form="form2">Elöző Visszaállítása</button>
              <a class="btn btn-danger"  onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL;?>blog.php?delete=<?= $arr[0]["post_name"]; ?>">Delete</a>
              
            </div>
            <div class="col-4">
              <button type="submit" class="btn btn-warning" name="link_description" value="A(z) <?= $arr2[0]["post_title"];?> legújabb része megjelent." form="mininews">Send New Episode</button>
              <button type="submit" class="btn btn-info" name="link_description" value="A(z) <?= $arr2[0]["post_title"];?> felkerült az oldalra." form="mininews">Send New Anime</button>
              <button type="submit" class="btn btn-warning" name="link_description" value="<?= $arr2[0]["post_title"];?> javítva lett." form="mininews">Send Anime Fixed</button>
            </div>
          </div>
          <div class="row">
            <div class="col">
              <div id="editor_holder" data-theme="bootstrap5">
            </div>
            </div>
          </div>
      </form>
    </div>
  <script>
  
    function addRow() {
      let table = document.getElementById("example1");
      
      var row = table.insertRow(table.rows.length);
      row.style = 'vertical-align: top; text-align: center;';
      row.innerHTML = '  <th scope="row"></th>'
                      + '  <td><input type="text" class="form-control" type="text" name="name[]" form="form1"></td>'
                      + '  <td><input type="text" class="form-control" name="link[]" form="form1"></td>'
                      + '  <td><input type="button" class="btn btn-danger" value="Delete Row" onclick="deleteRow(this)"/></td>';
            for (var i = 0; i< table.rows.length; i++){
              table.rows[i].cells[0].innerHTML = i+1;
            }  
    }
    function Clear_All() {
      let table = document.getElementById("example1").innerHTML = "";
      addRow();
    }


    function deleteRow(btn) {
      let table = document.querySelector("#example1");
      var row = btn.parentNode.parentNode;
      row.parentNode.removeChild(row);

            for (var i = 0; i< table.rows.length; i++){
              table.rows[i].cells[0].innerHTML = i+1;
            }  
    }
  </script>

    <script>

      // Initialize the editor with a JSON schema
      var editor = new JSONEditor(document.getElementById('editor_holder'),{
        theme: 'bootstrap4',
        disable_collapse: true,
        schema: {
          "title": "DataSheet",
          "type": "object",
          "disable_collapse": true,
          "required": [
            "myanimelist",
            "description",
            "_type",
            "fansub",
            "links"
          ],
          "properties": {
            "myanimelist": {
              "title": "MyAnimeList:",
              "type": "string",
              "default": "<?= $json["myanimelist"];?>"
            },
            "description": {
              "title": "Leírás:",
              "type": "string",
              "default": "<?= $json["description"];?>"
            },
            "_type": {
              "title": "Type:",
              "type": "string",
              "default": "datasheet",
              "enum": [
                "datasheet"
              ]
            },
            "fansub": {
              "type": "array",
              "format": "table",
              "title": "Fansubs",
              "items": {
                "type": "object",
                "title": "Fansub",
                "properties": {
                  "fansub1": {
                    "type": "string",
                    "title": "Csapat 1",
                    "enum": [<?= $uploader; ?>],
                    "default": ""
                  },
                  "fansub2": {
                    "type": "string",
                    "title": "Csapat 2",
                    "enum": [<?= $uploader; ?>],
                    "default": ""
                  },
                  "fansub3": {
                    "type": "string",
                    "title": "Csapat 3",
                    "enum": [<?= $uploader; ?>],
                    "default": ""
                  }
                }
              },
              "default": [
                {
                  "fansub1": "",
                  "fansub2": "",
                  "fansub3": ""
                }
              ]
            },
            "links": {
              "type": "array",
              "format": "table",
              "title": "Links",
              "items": {
                "type": "object",
                "title": "Links",
                "properties": {
                  "link": {
                    "type": "string",
                    "title": "Link",
                    "default": ""
                  }
                }
              },
              "default": <?= $json["links"]; ?>
            }
          }
        }
      });

      // Hook up the submit button to log to the console
      document.getElementById('submit').addEventListener('click',function() {
        // Get the value from the editor
        console.log(editor.getValue());
      });
    
    </script>




  <?php
  
  echo $footer;
}elseif(isset($_GET["new"]) && !empty($_GET["new"]))
{
  $data["_type"] = "episodelist";
  $arr = Select($conn, 'SELECT `wp_posts`.`post_name` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["new"] . '" && `wp_posts`.`post_status` = "publish" LIMIT 1');
  print_p($_GET);
  if (isset($arr[0]["post_name"]))
  {
    $post_name = $arr[0]["post_name"];
  }else
  {
    $id = Insert($conn, "INSERT INTO `wp_posts` (`post_title`,`post_name`, `post_content`) VALUES ('" .$_GET["new"]. "', '" .$conn -> real_escape_string($_GET["new"]). "', '" .$conn -> real_escape_string(json_encode($data)). "')", TRUE);
    $post_name = $conn -> real_escape_string($_GET["new"]);
  }
  header("Location: " . BASEURL . "blog.php?edit=" . $post_name);
  
  
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
  header("Location: " . BASEURL . "blog.php?edit=" . $_GET["mininews"]);
  
  
}elseif(isset($_GET["delete"]))
{
  $select = Select($conn, 'SELECT `wp_posts`.`ID` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["delete"] . '" && `wp_posts`.`post_status` = "publish" LIMIT 1');
  print_p($_GET);
 // Insert($conn, "INSERT INTO `ep_links` (`link`) VALUES ('" . $value2 . "')", TRUE);
   
  if(is_array($select) && isset($select[0]["ID"]))
    Update($conn, "DELETE FROM `wp_posts` WHERE `ID` =" . $select[0]["ID"] . "");

  header("Location: " . BASEURL . "blog.php");
  
}elseif(isset($_GET["save"]))
{
  
  //$arr = Select($conn, 'SELECT `wp_posts`.`post_content`, `wp_posts`.`post_name`, `wp_posts`.`post_title` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["save"] . '" && `wp_posts`.`post_status` = "publish" LIMIT 1');
  print_p($_GET);



  $select = Select($conn, "SELECT `wp_posts`.`ID`, `wp_posts`.`post_content` FROM `wp_posts` WHERE `wp_posts`.`post_name` = '" . $_GET["save"] . "' && `wp_posts`.`post_status` = 'publish' ORDER BY `ID` ASC LIMIT 1");

  
  if(is_array($select) && isset($select[0]["ID"]))
    Update($conn, "UPDATE `wp_posts` SET `post_content2`='" . $conn -> real_escape_string($select[0]["post_content"]) . "' WHERE `ID` = '" . $select[0]["ID"] . "'");
  if(is_array($select) && isset($select[0]["ID"]))
    Update($conn, "UPDATE `wp_posts` SET `post_content`='" . $conn -> real_escape_string($_GET["textarea"]) . "' WHERE `ID` = '" . $select[0]["ID"] . "'");
  
  header("Location: " . BASEURL . "blog.php?edit=" . $_GET["save"]);
  
}elseif(isset($_GET["reform"]))
{
  
  $select = Select($conn, 'SELECT `wp_posts`.`ID`, `wp_posts`.`post_content`, `wp_posts`.`post_content2`, `wp_posts`.`post_name`, `wp_posts`.`post_title` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["reform"] . '" && `wp_posts`.`post_status` = "publish" LIMIT 1');
  print_p($_GET);
  print_p($select);
  if(isset($select[0]["post_content2"]) && !empty($select[0]["post_content2"]))
  {
    Update($conn, "UPDATE `wp_posts` SET `post_content`='" . $conn -> real_escape_string($select[0]["post_content2"]) . "' WHERE `ID` = '" . $select[0]["ID"] . "'");
  }
  
  
   header("Location: " . BASEURL . "episode.php?edit=" . $_GET["reform"]);
  
}else
{
  echo $header;
  ?>
    <div class="container-fluid">
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
            <tbody id="list">
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php 
  echo $footer;
}


$conn->close();
$conn3->close();
?>
