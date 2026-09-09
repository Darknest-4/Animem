<?php //if(getip() != "45.128.71.2") exit("Késő este van és nem telik karbantartás png-re. Igy most ezt a szoveget kell olvasnod. Nyugi reggel már menni fog az oldal. :) "); ?>
<?php
//exit(require_once("503.php"));
$search = (isset($_GET["search"]))?$_GET["search"]:"";

$title = "";
$template = findfilefromdir("Assets/template");
$template[] = $template[0];
unset($template[0]);
function findfilefromdir($dirs)
{
  if (file_exists($dirs))
  {
    $aa = scandir($dirs);
    $dir = [];
    foreach ($aa as $val)
    {
      if ($val != '.' && $val != '..')
        $dir[] = $val;
    }
    return $dir;
  }
}
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
  <script src="http://sortablejs.github.io/Sortable/st/app.js"></script>
  <script src="http://sortablejs.github.io/Sortable/st/prettify/prettify.js"></script>
  </body>
  </html>
';


//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database = "animem";

require_once("../../Config/loadConfig.php");

$siteConfig = DbConfig::getSiteConfig();
if (!defined("BASEURL")) {
  define("BASEURL", "https://".$siteConfig['domain']."/admin/");
}

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

$conn = new mysqli($servername, $username, $password, $database);
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);


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
  require_once("Views/header.phtml");require_once("Views/admin-navbar.phtml");
  $arr = Select($conn, 'SELECT `uploaders`.* FROM `uploaders` WHERE `uploaders`.`name` LIKE "%' . $_GET["search"] . '%"');
  
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
                <th scope="col">Name</th>
                <th scope="col">FaceBook</th>
                <th scope="col">IndaVideo</th>
                <th scope="col">WebSite</th>
                <th scope="col">View</th>
                <th scope="col">Edit</th>
                <th scope="col">Delete</th> 
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($arr as $key => $value)
              {
              ?>
              <tr <?php if($value["collector"]==1) echo 'class="collector"';?>>
                <th scope="row"><?= $key+1;?></th>
                <td><span><?= $value["name"];?>  | id: <?= $value["id"];?></span></td>
                <td><a href="https://www.facebook.com/<?= $value["fb_link"];?>"><?= $value["fb_link"];?></a></td>
                <td><a href="https://indavideo.hu/profile/<?= $value["inda_link"];?>"><?= $value["inda_link"];?></a></td>
                <td><a href="<?= $value["fan_link"];?>"><?= $value["fan_link"];?></a></td>
                <td>[<a href="<?= str_replace('admin/', '', BASEURL); ?>FanSub/<?= $value["id"];?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL;?>uploaders.php?edit=<?= $value["id"];?>" target="_blank">Edit</a>]</td>
                <td>[<a href="<?= BASEURL;?>uploaders.php?delete=<?= $value["id"];?>"   onclick="return confirm('Are you sure you want to delete this?\n<?= $value['name'];?>')"target="_blank">Delete</a>]</td>
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
  require_once("Views/header.phtml");require_once("Views/admin-navbar.phtml");
  
  $arr = Select($conn, 'SELECT `uploaders`.* FROM `uploaders` WHERE `uploaders`.`id` = ' . $_GET["edit"] . ' LIMIT 1');
  
  ?>
    <div class="container-fluid">
      <div class="row">
        <h3>
          Edit: <?= $arr[0]["name"];?>
        </h3>
      </div>
      <form action="<?= BASEURL; ?>/uploaders.php" method="get" id="form2">
        <input type="text" style="display:none;" type="text" name="reform" value="<?= $_GET["edit"];?>" readonly="readonly" form="form2">
      </form>
      <form action="<?= BASEURL; ?>/uploaders.php" method="get" id="form1">
        <input type="text" style="display:none;" type="text" name="save" value="<?= $_GET["edit"];?>" readonly="readonly" form="form1">
          <div class="row">
            <div class="col">
              <button type="submit" class="btn btn-success" form="form1">Save</button>
              <a class="btn btn-danger"  onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL;?>uploaders.php?delete=<?= $arr[0]["id"]; ?>">Delete</a>
            </div>
          </div>
          <div class="row">
            <div class="col">
              <div class="table-responsive">
                <table class="table table-striped">
                  
                  <tbody>
                    <tr>
                      <td>id</td>
                      <td><span ><?= $arr[0]["id"];?></span></td>
                    </tr>
                    <tr>
                      <td>Név</td>
                      <td><input type="text" class="form-control" type="text" name="name" value="<?= $arr[0]["name"];?>" form="form1"></td>
                    </tr>
                    <tr>
                      <td>Facebook</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text" id="facebook">https://www.facebook.com/</span>
                          <input type="text" class="form-control" id="basic-url" aria-describedby="facebook" name="facebook" value="<?= $arr[0]["fb_link"];?>" form="form1">
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Weboldal</td>
                      <td>
                        <div class="input-group">
                          <input type="text" class="form-control" id="basic-url" aria-describedby="website" name="website" value="<?= $arr[0]["fan_link"];?>" form="form1">
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Indavideo</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text" id="indavideo">https://indavideo.hu/profile/</span>
                          <input type="text" class="form-control" id="basic-url" aria-describedby="indavideo" name="indavideo" value="<?= $arr[0]["inda_link"];?>" form="form1">
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>E-mail</td>
                      <td>
                        <div class="input-group">
                          <input type="email" class="form-control" id="basic-url" aria-describedby="email" name="email" value="<?= $arr[0]["email"];?>" form="form1">
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Code</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text" id="code">[</span>
                          <input type="text" class="form-control" id="basic-url" aria-describedby="code" name="code" value="<?= $arr[0]["code"];?>" form="form1">
                          <span class="input-group-text" id="code">]</span>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Collector</td>
                      <td>
                        <div class="input-group">
                          <select class="form-select" name="collector" form="form1">
                            <option value="0" <?php if($arr[0]["collector"]==0) echo "selected";?>>Nem</option>
                            <option value="1" <?php if($arr[0]["collector"]==1) echo "selected";?>>Igen</option>
                          </select>
                          
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Titkos Leírás</td>
                      <td>
                        <div class="input-group">
                          <input type="textarea" class="form-control" id="basic-url" aria-describedby="tdes" name="tdes" value="<?= $arr[0]["tdes"];?>" form="form1">
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Publikus Leírás</td>
                      <td>
                        <div class="input-group">
                          <input type="textarea" class="form-control" id="basic-url" aria-describedby="pdes" name="pdes" value="<?= $arr[0]["pdes"];?>" form="form1">
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
            </div>
            </div>
          </div>
          <div class="row">
            <div class="col">
              <button type="submit" class="btn btn-success" form="form1">Save</button>
              <a class="btn btn-danger"  onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL;?>uploaders.php?delete=<?= $arr[0]["id"]; ?>">Delete</a>
            </div>
          </div>
      </form>
    </div>

  <?php
  
  echo $footer;
}elseif(isset($_GET["view"]))
{
  require_once("Views/header.phtml");require_once("Views/admin-navbar.phtml");
  
  $value = Select($conn, 'SELECT `uploaders`.* FROM `uploaders` WHERE `uploaders`.`id` = ' . $_GET["view"] . ' LIMIT 1');
  
  $value = $value[0];
  ?>

  <div class="rapidwp-box-inside" style="margin-top:10px;">
            <header class="entry-header">
              <div class="entry-header-inside">
                <h1 class="post-title entry-title"><a href="<?= BASEURL;?>uploaders.php?view=<?= $value["id"];?>"><?= $value["name"];?></a></h1>
                <div class="rapidwp-entry-meta-single"></div>
              </div>
            </header>
            <div class="entry-content clearfix">
            <table style="border: 1px solid black; width: 98%;">
              <tbody>
                <tr>
                  <td>Név</td>
                  <td><?= $value["name"];?></td>
                </tr>
                <tr>
                  <td>Facebook</td>
                  <td><a href="https://www.facebook.com/<?= $value["fb_link"];?>"><span style="color:#409bd4;">Facebook</span></a></td>
                </tr>
                <tr>
                  <td>Weboldal</td>
                  <td><a href="<?= $value["fan_link"];?>"><span style="color:#409bd4;">Weboldal</span></a></td>
                </tr>
                <tr>
                  <td>Indavideo</td>
                  <td><a href="https://indavideo.hu/profile/<?= $value["inda_link"];?>"><span style="color:#409bd4;">Indavideo</span></a></td>
                </tr>
              </tbody>
            </table>
            </div>
            <footer class="entry-footer"></footer>
          </div>

  <?php

  
  require("template/footer.phtml");
}elseif(isset($_GET["save"]))
{
  
  //$arr = Select($conn, 'SELECT `wp_posts`.`post_content`, `wp_posts`.`post_name`, `wp_posts`.`post_title` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["save"] . '" && `wp_posts`.`post_status` = "publish" LIMIT 1');
  print_p($_GET);
 // Insert($conn, "INSERT INTO `ep_links` (`link`) VALUES ('" . $value2 . "')", TRUE);
   
  $select = Select($conn, 'SELECT `uploaders`.* FROM `uploaders` WHERE `uploaders`.`id` = ' . $_GET["save"] . ' LIMIT 1');
  
  if(is_array($select) && isset($select[0]["id"]))
    Update($conn, "UPDATE `uploaders` SET 
    `name`='" . $conn -> real_escape_string($_GET["name"]) . "',
    `inda_link`='" . $conn -> real_escape_string($_GET["indavideo"]) . "',
    `fan_link`='" . $conn -> real_escape_string($_GET["website"]) . "',
    `fb_link`='" . $conn -> real_escape_string($_GET["facebook"]) . "',
    `code`='" . $conn -> real_escape_string($_GET["code"]) . "',
    `collector`=" . $conn -> real_escape_string($_GET["collector"]) . ",
    `email`='" . $conn -> real_escape_string($_GET["email"]) . "',
    `pdes`='" . $conn -> real_escape_string($_GET["pdes"]) . "',
    `tdes`='" . $conn -> real_escape_string($_GET["tdes"]) . "'
     WHERE `id` = '" . $select[0]["id"] . "'");


  header("Location: " . BASEURL . "uploaders.php?edit=" . $_GET["save"]);
  
}elseif(isset($_GET["delete"]))
{
  //$arr = Select($conn, 'SELECT `wp_posts`.`post_content`, `wp_posts`.`post_name`, `wp_posts`.`post_title` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["save"] . '" && `wp_posts`.`post_status` = "publish" LIMIT 1');
  print_p($_GET);
 // Insert($conn, "INSERT INTO `ep_links` (`link`) VALUES ('" . $value2 . "')", TRUE);
   
  $select = Select($conn, 'SELECT `uploaders`.* FROM `uploaders` WHERE `uploaders`.`id` = ' . $_GET["delete"] . ' LIMIT 1');
  
  if(is_array($select) && isset($select[0]["id"]))
    Update($conn, "DELETE FROM `uploaders` WHERE `id` =" . $select[0]["id"] . "");

  header("Location: " . BASEURL . "uploaders.php");
  
}elseif(isset($_GET["new"]))
{
  //$arr = Select($conn, 'SELECT `wp_posts`.`post_content`, `wp_posts`.`post_name`, `wp_posts`.`post_title` FROM `wp_posts` WHERE `wp_posts`.`post_name` = "' . $_GET["save"] . '" && `wp_posts`.`post_status` = "publish" LIMIT 1');
  //print_p($_GET);
  $id = Insert($conn, "INSERT INTO `uploaders` (`name`) VALUES ('" .$conn -> real_escape_string($_GET["new"]). "')", TRUE);

  header("Location: " . BASEURL . "uploaders.php?edit=" . $id);
  
}else
{
  require_once("Views/header.phtml");require_once("Views/admin-navbar.phtml");
  $arr = Select($conn, 'SELECT `uploaders`.* FROM `uploaders` ORDER BY `name`');
  
  ?>
    <div class="container-fluid">
      <div class="row">
        <div class="col">
          <table class="table table-striped">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Name</th>
                <th scope="col">FaceBook</th>
                <th scope="col">IndaVideo</th>
                <th scope="col">WebSite</th>
                <th scope="col">View</th>
                <th scope="col">Edit</th>
                <th scope="col">Delete</th>
              </tr>
            </thead>
            <tbody class="list">
              <?php
              foreach ($arr as $key => $value)
              {
              ?>
              <tr <?php if($value["collector"]==1) echo 'class="collector"';?>>
                <th scope="row"><?= $key+1;?></th>
                <td><span><?= $value["name"];?>  | id: <?= $value["id"];?></span></td>
                <td><a href="https://www.facebook.com/<?= $value["fb_link"];?>"><?= $value["fb_link"];?></a></td>
                <td><a href="https://indavideo.hu/profile/<?= $value["inda_link"];?>"><?= $value["inda_link"];?></a></td>
                <td><a href="<?= $value["fan_link"];?>"><?= $value["fan_link"];?></a></td>
                <td>[<a href="<?= str_replace('admin/', '', BASEURL); ?>FanSub/<?= $value["id"];?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL;?>uploaders.php?edit=<?= $value["id"];?>" target="_blank">Edit</a>]</td>
                <td>[<a href="<?= BASEURL;?>uploaders.php?delete=<?= $value["id"];?>"  onclick="return confirm('Are you sure you want to delete this?\n<?= $value['name'];?>')" target="_blank">Delete</a>]</td>
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


$conn->close();


?>
