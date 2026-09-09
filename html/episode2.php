



<?php //if(getip() != "45.128.71.2") exit("Késő este van és nem telik karbantartás png-re. Igy most ezt a szoveget kell olvasnod. Nyugi reggel már menni fog az oldal. :) "); 
?>
<?php

$search = (isset($_GET["search"])) ? $_GET["search"] : "";

?>
<?php

$footer = '
  <!-- Optional JavaScript; choose one of the two! -->

  <script src="http://sortablejs.github.io/Sortable/Sortable.js"></script>

  <script>
  //$("input[type=\'number\']").inputSpinner()
</script>
  </body>
  </html>
';

//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database = "animem";
$database3 = "xanimem";

require_once("../Config/loadConfig.php");

$siteConfig = DbConfig::getSiteConfig();
define("BASEURL", "https://".$siteConfig['domain']."/");

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);



if (isset($_GET["search"]))
{
  $_GET["search"] = clean($_GET["search"]);
  require_once("template/header.php");
  $arr = Select($conn, 'SELECT * FROM `episodelist` WHERE `episodelist`.`link` LIKE "%' . $_GET["search"] . '%" ORDER BY `last_update` DESC, `title` ASC');?>
  <style>
    tr.hide-table-padding td {
      padding: 0;
    }

    .expand-button {
      position: relative;
    }

    .accordion-toggle .expand-button:after {
      position: absolute;
      left: .75rem;
      top: 50%;
      transform: translate(0, -50%);
      content: '-';
    }

    .accordion-toggle.collapsed .expand-button:after {
      content: '+';
    }
  </style>
  <div class="container-fluid">
    <div class="row">
      <h3>
        Search: <?= $_GET["search"]; ?>
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
                <th scope="row"><?= $key + 1; ?></th>
                <td><?= $value["title"]; ?> | id: <?= $value["id"]; ?></td>
                <td>[<a href="<?= BASEURL; ?><?= $value["link"]; ?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL; ?>episode.php?edit=<?= $value["id"]; ?>" target="_blank">Edit</a>]</td>
              </tr>
            <?php
            }

            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php echo $footer;
}
elseif (isset($_GET["edit"]))
{
  require_once("template/header.php");


  $link = clean($_GET["edit"]);
  $episodelist = Select($conn, 'SELECT * FROM `episodelist` WHERE `episodelist`.`id` = "' . $link . '" LIMIT 1');
  $episodelist = (isset($episodelist[0]["id"])) ? $episodelist : exit('Hibás episodelist ID.');
  $IDs = array();
  foreach ($episodelist as $value)
  {
    $json = json_decode($value["save"], true);
    foreach ($json as $key => $value2)
      if ($key != "_type")
        if ($value2 != "")
          $IDs[] = $value2;
  }
  $IDs = implode(", ", $IDs);
  $link = Select($conn, "SELECT * FROM `links` WHERE `id` IN ({$IDs});");
  unset($IDs);
  //print_p($link);
  $uploaders = Select($conn, "SELECT * FROM `uploaders` ORDER BY `name`;");
  $types = Select($conn, "SELECT * FROM `links_type`;");
  $langs = Select($conn, "SELECT * FROM `episode_lang`;");
  $episodes = Select($conn, "SELECT * FROM `episode_type`;"); ?>

  <script>
    function addRow() {
      let table = document.getElementById("example1");

      var row = table.insertRow(table.rows.length);
      row.style = 'vertical-align: top; text-align: center;';
      row.innerHTML = '  <th scope="row"></th>' +
        '  <td><input type="text" class="form-control" type="text" name="name[]" form="form1"></td>' +
        '  <td><input type="text" class="form-control" name="link[]" form="form1"></td>' +
        '  <td><input type="button" class="btn btn-danger" value="Delete Row" onclick="deleteRow(this)"/></td>';
      for (var i = 0; i < table.rows.length; i++) {
        table.rows[i].cells[0].innerHTML = i + 1;
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

      for (var i = 0; i < table.rows.length; i++) {
        table.rows[i].cells[0].innerHTML = i + 1;
      }
    }

    function getID(id) {
      return document.getElementById(id);
    }
  </script>
  <div class="container-fluid">
    <div class="row">
      <h3>
        Edit: <?= $episodelist[0]["title"]; ?>
      </h3>
    </div>
    <form action="/episode.php" method="get" id="form2">
      <input type="text" style="display:none;" type="text" name="reform" value="<?= $episodelist[0]["id"]; ?>" readonly="readonly" form="form2">
    </form>
    <form action="/episode.php" method="get" id="mininews">
      <input type="text" style="display:none;" type="text" name="mininews" value="<?= $episodelist[0]["id"]; ?>" readonly="readonly" form="mininews">
      <input type="text" style="display:none;" type="text" name="link_url" value="<?= $arr2[0]["link"]; ?>" readonly="readonly" form="mininews">
    </form>
    <form action="/episode.php" method="post" id="biglinks">
      <input type="text" style="display:none;" type="text" name="biglinks" value="<?= $episodelist[0]["id"]; ?>" readonly="readonly" form="biglinks">

      <!-- Modal -->
      <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="importModalLabel">Import</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <textarea class="form-control" style="min-width: 100%; min-height: 400px" name="links"></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-info" form="biglinks">Import</button>
            </div>
          </div>
        </div>
      </div>
    </form>
    <form action="/episode.php" method="post" id="form1">


      <!-------------------------------------------------------------------------------------------------->
      <div class="row m-3">
        <div class="col-12 text-center">
          <div class="row">
            <div class="col-6 px-0">
              <div class="input-group">
                <button class="btn btn-success d-grid col-3" type="submit" form="form1">Save</button>
                <input class="btn btn-warning d-grid col-3" type="button" value="Add Rows" onclick="addRow()" />
                <a class="btn btn-danger d-grid col-3" onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL; ?>episode.php?delete=<?= $_GET["edit"]; ?>">Delete</a>
                <input class="btn btn-danger d-grid col-3" type="button" value="Clear All" onclick="Clear_All()" />
              </div>
            </div>
            <div class="col-6 px-0">
              <div class="input-group">
                <button type="submit" class="btn btn-warning d-grid col-3" name="link_description" value="" form="mininews">Import/Export</button>
                <button type="submit" class="btn btn-warning d-grid col-3" name="link_description" value="" form="mininews">New Episode</button>
                <button type="submit" class="btn btn-info d-grid col-3" name="link_description" value="" form="mininews">New Anime</button>
                <button type="submit" class="btn btn-warning d-grid col-3" name="link_description" value="" form="mininews">Anime Fixed</button>
              </div>
            </div>
          </div>
        </div>
      </div>


      <input type="text" style="display:none;" type="text" name="save" value="<?= $episodelist[0]["id"]; ?>" readonly="readonly" form="form1">


      <!-------------------------------------------------------------------------------------------------->
      <div class="row m-3 border border-dark">
        <div class="col-12 text-center">
          <!------------------------------------------------------>
          <div class="row">
            <div class="col-1 py-2 border">
              <span>Cím:</span>
            </div>
            <div class="col-11 py-2 border">
              <input type="text" class="form-control" type="text" name="title" value="<?= $episodelist[0]["title"]; ?>" form="form1" />
            </div>
          </div>
          <!------------------------------------------------------>
          <div class="row">
            <div class="col-1 py-2 border">
              <span>Link:</span>
            </div>
            <div class="col-11 py-2 border">
              <div class="input-group">
                <span class="input-group-text"><?= BASEURL ?></span>
                <input type="text" class="form-control" name="linklink" value="<?= $episodelist[0]["link"]; ?>" form="form1">
                <span class="input-group-text"><a href="<?= BASEURL ?><?= $episodelist[0]["link"]; ?>" target="_blank">Open</a></span>
              </div>
            </div>
          </div>
          <!------------------------------------------------------>
          <div class="row">
            <div class="col-7 py-2 border">
              <div class="input-group">
                <span class="input-group-text d-grid col-3">Fansubok:</span>
                <?php
                  if (isset($uploaders[0]))
                  {
                    echo'<select class="btn btn-outline-secondary d-grid col-3" name="type[]">';
                    foreach ($uploaders as $uploader) echo '<option value="' . $uploader["id"] . '">' . $uploader["name"] . '</option>';
                    echo '</select>';
                    echo'<select class="btn btn-outline-secondary d-grid col-3" name="type[]">';
                    foreach ($uploaders as $uploader) echo '<option value="' . $uploader["id"] . '">' . $uploader["name"] . '</option>';
                    echo '</select>';
                    echo'<select class="btn btn-outline-secondary d-grid col-3" name="type[]">';
                    foreach ($uploaders as $uploader) echo '<option value="' . $uploader["id"] . '">' . $uploader["name"] . '</option>';
                    echo '</select>';
                  }
                ?>
              </div>

            </div>
            <div class="col-1 py-2 border">
                <?php
                  if (isset($episodes[0]))
                  {
                    echo'<select class="btn btn-outline-secondary" name="type[]">';
                    foreach ($episodes as $episode) echo '<option value="' . $episode["id"] . '">' . $episode["text"] . '</option>';
                    echo '</select>';
                  }
                ?>
            </div>
            <div class="col-2 py-2 border">
                <?php
                  if (isset($langs[0]))
                  {
                    echo'<select class="btn btn-outline-secondary" name="type[]">';
                    foreach ($langs as $lang) echo '<option value="' . $lang["id"] . '">' . $lang["text"] . '</option>';
                    echo '</select>';
                  }
                ?>
            </div>
            <div class="col-1 py-2 border">
              <select class="btn btn-outline-secondary" name="type[]">
                <option value="7">SS</option>
                <option value="7">360p</option>
                <option value="7">480p</option>
                <option value="7">"HD"</option>
                <option value="7">720p</option>
              </select>
            </div>
            <div class="col-1 py-2 border">
              <button type="submit" class="btn btn-warning" style="background-color:#d63384; color:#fff;">Update</button>
            </div>
          </div>
        </div>
      </div>
      <!-------------------------------------------------------------------------------------------------->
      <!-------------------------------------------------------------------------------------------------->
      <div class="row m-3 border border-dark">
        <div class="col-12 text-center">
          <!------------------------------------------------------>
          <div class="row">
            <div class="col-1 py-2 border"><span><b><?= $episodelist[0]["id"]; ?></b></span></div>
            <div class="col-3 py-2 border"><span><b>Cim</b></span></div>
            <div class="col-7 py-2 border"><span><b>Link</b></span></div>
            <div class="col-1 py-2 border"><span><b>Delete Row</b></span></div>
          </div>
          <!------------------------------------------------------>

          <?php 
          foreach ($link as $key => $value)
          { ?>
            <!----------------------------------------------------------------------------------------------->
            <div class="row border-top border-dark">
              <div class="col-1 py-2 border">
                <span class="btn" id="co<?= $value["id"]; ?>" data-id="collapse<?= $value["id"]; ?>" onclick="let th = getID(this.getAttribute('data-id')).classList; th.toggle('collapsing');this.classList.toggle('hidden');getID('cc<?= $value['id']; ?>').classList.toggle('hidden');">OPEN</span>
                <span class="btn hidden" id="cc<?= $value["id"]; ?>" data-id="collapse<?= $value["id"]; ?>" onclick="let th = getID(this.getAttribute('data-id')).classList; th.toggle('collapsing');getID('co<?= $value['id']; ?>').classList.toggle('hidden');this.classList.toggle('hidden');">CLOSE</span>
              </div>
              <div class="col-3 py-2 border">
                <div class="input-group">
                  <span class="input-group-text p-0">
                    <button class="btn btn-outline-secondary px-1" type="button" onclick="let th =this.parentNode.parentNode.querySelector('input'); th.value = th.value-1;">
                      <strong>−</strong>
                    </button>
                  </span>
                  <input type="text" class="form-control" style="text-align: center" name="name[]" form="form1" value="<?= $value["episode_num"]; ?>" min="0" max="2000" step="1" />
                  <span class="input-group-text p-0">
                    <button class="btn btn-outline-secondary px-1" type="button" onclick="let th =this.parentNode.parentNode.querySelector('input'); th.value = th.value*1+1;">
                      <strong>+</strong>
                    </button>
                  </span>
                  <span class="input-group-text p-0">
                <?php
                  if (isset($episodes[0]))
                  {
                    echo'<select class="btn btn-outline-secondary px-1" name="type[]">';
                    foreach ($episodes as $episode) echo '<option value="' . $episode["id"] . '">' . $episode["text"] . '</option>';
                    echo '</select>';
                  }
                ?>
                  </span>
                  <span class="input-group-text p-0">
                <?php
                  if (isset($langs[0]))
                  {
                    echo'<select class="btn btn-outline-secondary px-1" name="type[]">';
                    foreach ($langs as $lang) echo '<option value="' . $lang["id"] . '">' . $lang["text"] . '</option>';
                    echo '</select>';
                  }
                ?>
                  </span>
                </div>
              </div>
              <div class="col-7 py-2 border">
                <div class="input-group">
                  <span class="input-group-text p-0">
                <?php
                  if (isset($types[0]))
                  {
                    echo'<select class="btn btn-outline-secondary px-1" name="type[]">';
                    foreach ($types as $type) echo '<option value="' . $type["id"] . '">' . $type["link"] . '</option>';
                    echo '</select>';
                  }
                ?>
                  </span>
                  <input type="text" class="form-control" name="link[]" value="<?= $value["link"]; ?>" form="form1">
                  <span class="input-group-text"><a href="" target="_blank">Open</a></span>
                </div>
              </div>
              <div class="col-1 py-2 border">
                <input type="button" class="btn btn-danger" value="Delete Row" onclick="deleteRow(this)">
              </div>
            </div>

            <div class="collapsing mx-3" id="collapse<?= $value["id"]; ?>">
              <div class="row">
                <div class="col-6 py-2 border">
                  <div class="input-group">
                    <span class="input-group-text">cim</span>
                    <input type="text" class="form-control" name="linklink" value="<?= $value["title"]; ?>" form="form1">
                  </div>
                </div>
                <div class="col-6 py-2 border">
                  <div class="input-group">
                    <span class="input-group-text">https://embed.indavideo.hu/player/video/</span>
                    <input type="text" class="form-control" name="link[]" value="<?= $value["embed"]; ?>" form="form1">
                    <span class="input-group-text"><a href="" target="_blank">Open</a></span>
                  </div>

                </div>
              </div>
              <div class="row">
                <div class="col-11 py-2 border">
                  <div class="input-group">
                    <span class="input-group-text d-grid col-3">Fansubok:</span>
                <?php
                  if (isset($uploaders[0]))
                  {
                    echo'<select class="btn btn-outline-secondary d-grid col-3" name="type[]">';
                    foreach ($uploaders as $uploader) echo '<option value="' . $uploader["id"] . '">' . $uploader["name"] . '</option>';
                    echo '</select>';
                    echo'<select class="btn btn-outline-secondary d-grid col-3" name="type[]">';
                    foreach ($uploaders as $uploader) echo '<option value="' . $uploader["id"] . '">' . $uploader["name"] . '</option>';
                    echo '</select>';
                    echo'<select class="btn btn-outline-secondary d-grid col-3" name="type[]">';
                    foreach ($uploaders as $uploader) echo '<option value="' . $uploader["id"] . '">' . $uploader["name"] . '</option>';
                    echo '</select>';
                  }
                ?>
                  </div>

                </div>
                <div class="col-1 py-2 border">
                  <select class="btn btn-outline-secondary" name="type[]">
                    <option value="7">SS</option>
                    <option value="7">360p</option>
                    <option value="7">480p</option>
                    <option value="7">"HD"</option>
                    <option value="7">720p</option>
                  </select>

                </div>
              </div>
            </div>
            <!----------------------------------------------------------------------------------------------->

          <?php } ?>

          <!------------------------------------------------------>
        </div>
      </div>
      <!-------------------------------------------------------------------------------------------------->

      <div class="row">
        <div class="col">
          <button type="submit" class="btn btn-success" form="form1">Save</button>
          <input class="btn btn-warning" type="button" value="Add Rows" onclick="addRow()" />
          <a class="btn" target="_blank" href="<?= BASEURL; ?><?= $arr[0]["link"]; ?>" style="background-color:#d63384; color:#fff;">View</a>
          <input class="btn btn-danger" type="button" value="Clear All" onclick="Clear_All()" />
          <a class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL; ?>episode.php?delete=<?= $_GET["edit"]; ?>">Delete</a>
        </div>
      </div>
    </form>
  </div>
  <script>
    function addRow() {
      let table = document.getElementById("example1");

      var row = table.insertRow(table.rows.length);
      row.style = 'vertical-align: top; text-align: center;';
      row.innerHTML = '<tr style="vertical-align: top; text-align: center;">' +
        '<th class="col-1" scope="row">1</th>' +
        '<td class="col-2">' +
        '<div class="input-group">' +
        '<input type="text" class="form-control" name="name[]" value=". rész" form="form1">' +
        '<span class="input-group-text">magyar felirattal</span>' +
        '</div>' +
        '</td>' +
        '<td class="col-2">' +
        '<div class="input-group">' +
        '<span class="input-group-text p-0">' +
        '<select class="btn btn-outline-secondary" name="type[]"><option value="7">drive.google.com/file/d/</option><option value="1">embed.indavideo.hu/player/video/</option><option value="2">indavideo.hu/video/</option><option value="4">mega.nz/embed/</option><option value="5">mega.nz/file/</option><option value="6" selected="">embed.otamoon.hu/Embed/</option><option value="8">Semmi</option><option value="3">videa.hu/player?v=</option></select></span>' +
        '<input type="text" class="form-control" name="link[]" value="" form="form1">' +
        '<span class="input-group-text"><a href="" target="_blank">Open</a></span>' +
        '</div>' +
        '</td>' +
        '<td class="col-1"><input type="button" class="btn btn-danger" value="Delete Row" onclick="deleteRow(this)"></td>';
      for (var i = 0; i < table.rows.length; i++) {
        table.rows[i].cells[0].innerHTML = i + 1;
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

      for (var i = 0; i < table.rows.length; i++) {
        table.rows[i].cells[0].innerHTML = i + 1;
      }
    }
  </script>

  <script>
    var importModal = new bootstrap.Modal(document.getElementById("importModal"));
    var exportModal = new bootstrap.Modal(document.getElementById("exportModal"));

    // var el = document.getElementById('example1');
  </script>
<?php

  echo $footer;
}
elseif (isset($_GET["new"]) && !empty($_GET["new"]))
{
  $data["_type"] = "episodelist";
  $link = clean($_GET["new"]);
  $title = str_replace(["'", '"'], ["\'", '\"'], $_GET["new"]);
  $arr = Select($conn, 'SELECT `episodelist`.`id` FROM `episodelist` WHERE `episodelist`.`link` = "' . $link . '"  LIMIT 1');
  print_p($_GET);
  if (isset($arr[0]["id"]))
  {
    $id = $arr[0]["id"];
  }
  else
  {
    $id = Insert($conn, "INSERT INTO `episodelist` (`title`,`link`, `save`) VALUES ('" . $title . "', '" . $link . "', '" . $conn->real_escape_string(json_encode($data)) . "')", TRUE);
  }
  header("Location: " . BASEURL . "episode.php?edit=" . $id);
}
elseif (isset($_GET["mininews"]) && !empty($_GET["mininews"]))
{
  $_GET["link_url"] = (isset($_GET["link_url"]) && !empty($_GET["link_url"])) ? $conn->real_escape_string($_GET["link_url"]) : NULL;
  $_GET["link_description"] = (isset($_GET["link_description"]) && !empty($_GET["link_description"])) ? $conn->real_escape_string($_GET["link_description"]) : NULL;

  print_p($_GET);
  // die();

  if ($_GET["mininews"] != NULL)
  {
    Insert($conn, "INSERT INTO `mininews`(`link_url`, `link_description`)
                    VALUES ('" . $_GET["link_url"] . "',
                            '" . $_GET["link_description"] . "')");
  }
  header("Location: " . BASEURL . "episode.php?edit=" . $_GET["mininews"]);
}
elseif (isset($_GET["delete"]))
{
  $select = Select($conn, 'SELECT `episodelist`.`id` FROM `episodelist` WHERE `episodelist`.`id` = "' . $_GET["delete"] . '" LIMIT 1');
  print_p($_GET);
  // Insert($conn, "INSERT INTO `links` (`link`) VALUES ('" . $value2 . "')", TRUE);

  if (is_array($select) && isset($select[0]["id"]))
    Update($conn, "DELETE FROM `episodelist` WHERE `id` =" . $select[0]["id"] . "");

  header("Location: " . BASEURL . "episode.php");
}
elseif (isset($_POST["save"]))
{
  //  print_p($_POST);
  $linktype = Select($conn, 'SELECT * FROM `links_type` ORDER BY `name`');
  $episode_lang = Select($conn, 'SELECT * FROM `episode_lang` ORDER BY `text`');
  foreach ($episode_lang as  $value)
    $episode_langs[$value["text"]] = $value["id"];

  if (in_array($_POST["lang"], $episode_langs))
    $lang = $_POST["lang"];
  else
    $lang = 2;

  foreach ($linktype as  $value)
    $linktypes[$value["id"]] = $value["link"];

  foreach ($_POST["type"] as $key => $value)
  {
    $json[$key]["type"] = $_POST["type"][$key];
    $json[$key]["name"] = str_replace(array_keys($episode_langs), "", $_POST["name"][$key]);
    $json[$key]["link"] = $_POST["link"][$key];
  }
  //   print_p($json);
  $save["_type"] = "episodelist";
  foreach ($json as $key => $value)
  {
    if (
      (isset($value["type"]) && is_numeric($value["type"]) && !empty($value["type"])) &&
      (isset($value["name"]) && !empty($value["name"])) &&
      (isset($value["link"]) && !empty($value["link"]))
    )
    {
      $name = $value["name"];
      if (substr($name, -1) == " ")
        $name = substr($name, 0, -1);
      if (array_key_exists($value["type"], $linktypes))
      {
        $set_type_id = $value["type"];
        $set_link = $value["link"];
        foreach ($linktypes as  $type_id => $type_link)
        {


          $link = $value["link"];
          if (substr($link, -1) == "/")
            $link = substr($link, 0, -1);
          $link = explode($type_link, $link);
          $link = (isset($link[1])) ? $link[1] : $link[0];
          // print_p($link);
          $link = explode("?fb", $link);
          $link = (isset($link[1])) ? $link[1] : $link[0];
          $link = str_replace("/view", "/preview", $link);
          /**Meg kell csinálni a linkek hitelesitését! **/

          if (strlen($link) < strlen($value["link"]))
          {

            $set_type_id = $type_id;
            $set_link = $link;
          }
        }
        $select = Select($conn, "SELECT `id` FROM `links` WHERE `link` = '" . $set_link . "' LIMIT 1");
        if (!isset($select[0]["id"]) && is_array($select) && empty($select))
          $save[$name] = Insert($conn, "INSERT INTO `links` (`link`, `links_type`) VALUES ('" . $set_link . "', '" . $set_type_id . "')", TRUE);
        else
        {
          $save[$name] = $select[0]["id"];
          if (!is_null($set_type_id))
            Update($conn, "UPDATE `links` SET `links_type`= " . $set_type_id . " WHERE `id` = '" . $select[0]["id"] . "' LIMIT 1");
        }
        unset($select);
        unset($set_link);
      }
      else
      {
        echo "Match not found";
      }
    }
  }
  //    print_p($save);
  $linkBool = strpos($_POST["linklink"], "redirect");
  $link = (is_numeric($linkBool)) ? $_POST["linklink"] : clean($_POST["linklink"]);
  $select = Select($conn, "SELECT * FROM `episodelist` WHERE `episodelist`.`id` = " . $_POST["save"] . " ORDER BY `id` ASC");
  if (is_array($select) && isset($select[0]["id"]))
    Update($conn, "UPDATE `episodelist` SET
      `episode_lang` = " . $lang . ",
      `title`='" . $conn->real_escape_string($_POST["title"]) . "',
      `link`='" . $link . "',
      `save2`='" . $select[0]["save"] . "',
      `save`='" . json_encode($save) . "'
      WHERE `id` = '" . $select[0]["id"] . "' LIMIT 1");

  header("Location: " . BASEURL . "episode.php?edit=" . $_POST["save"]);
}
elseif (isset($_POST["biglinks"]))
{
  $_POST["link"] = (isset($_POST["links"]) && isset(explode(",", $_POST["links"])[0])) ? explode(",", $_POST["links"]) : array();
  //print_p($_POST);
  $linktype = Select($conn, 'SELECT * FROM `links_type` ORDER BY `name`');
  $episode_lang = Select($conn, 'SELECT * FROM `episode_lang` ORDER BY `text`');
  foreach ($episode_lang as  $value)
    $episode_langs[$value["text"]] = $value["id"];

  if (isset($_POST["lang"]) && in_array($_POST["lang"], $episode_langs))
    $lang = $_POST["lang"];
  else
    $lang = 2;

  foreach ($linktype as  $value)
    $linktypes[$value["id"]] = $value["link"];

  if (isset($_POST["link"]) && !empty($_POST["link"]))
    foreach ($_POST["link"] as $key => $value)
    {
      $json[$key]["type"] = 1;
      $json[$key]["name"] = $key + 1 . ". rész";
      $json[$key]["link"] = $_POST["link"][$key];
    }
  //print_p($json);
  $save["_type"] = "episodelist";
  foreach ($json as $key => $value)
  {
    if (
      (isset($value["type"]) && is_numeric($value["type"]) && !empty($value["type"])) &&
      (isset($value["name"]) && !empty($value["name"])) &&
      (isset($value["link"]) && !empty($value["link"]))
    )
    {
      $name = $value["name"];
      if (substr($name, -1) == " ")
        $name = substr($name, 0, -1);
      if (array_key_exists($value["type"], $linktypes))
      {
        $set_type_id = $value["type"];
        $set_link = $value["link"];
        foreach ($linktypes as  $type_id => $type_link)
        {


          $link = $value["link"];
          if (substr($link, -1) == "/")
            $link = substr($link, 0, -1);
          $link = explode($type_link, $link);
          $link = (isset($link[1])) ? $link[1] : $link[0];
          // print_p($link);
          $link = explode("?fb", $link);
          $link = (isset($link[1])) ? $link[1] : $link[0];
          $link = str_replace("/view", "/preview", $link);
          /**Meg kell csinálni a linkek hitelesitését! **/ // Miota ezt leirtam el telt kurva sok idő. Szerintem ez már, igy marad.

          if (strlen($link) < strlen($value["link"]))
          {

            $set_type_id = $type_id;
            $set_link = $link;
          }
        }
        $select = Select($conn, "SELECT `id` FROM `links` WHERE `link` = '" . $set_link . "' LIMIT 1");
        if (!isset($select[0]["id"]) && is_array($select) && empty($select))
          $save[$name] = Insert($conn, "INSERT INTO `links` (`link`, `links_type`) VALUES ('" . $set_link . "', '" . $set_type_id . "')", TRUE);
        else
        {
          $save[$name] = $select[0]["id"];
          if (!is_null($set_type_id))
            Update($conn, "UPDATE `links` SET `links_type`= " . $set_type_id . " WHERE `id` = '" . $select[0]["id"] . "' LIMIT 1");
        }
        unset($select);
        unset($set_link);
      }
      else
      {
        echo "Match not found";
      }
    }
  }
  //  print_p($save);
  $select = Select($conn, "SELECT * FROM `episodelist` WHERE `episodelist`.`id` = " . $_POST["biglinks"] . " ORDER BY `id` ASC");
  if (is_array($select) && isset($select[0]["id"]))
    Update($conn, "UPDATE `episodelist` SET
      `save2`='" . $select[0]["save"] . "',
      `save`='" . json_encode($save) . "'
      WHERE `id` = '" . $select[0]["id"] . "' LIMIT 1");

  header("Location: " . BASEURL . "episode.php?edit=" . $_POST["biglinks"]);
}
elseif (isset($_GET["reform"]))
{

  $select = Select($conn, 'SELECT * FROM `episodelist` WHERE `episodelist`.`id` = "' . $_GET["reform"] . '"  LIMIT 1');
  print_p($_GET);
  print_p($select);
  if (isset($select[0]["save2"]) && !empty($select[0]["save2"]))
  {
    Update($conn, "UPDATE `episodelist` SET `save`='" . $conn->real_escape_string($select[0]["save2"]) . "' WHERE `id` = '" . $select[0]["id"] . "'");
  }


  header("Location: " . BASEURL . "episode.php?edit=" . $_GET["reform"]);
}
else
{
  require_once("template/header.php");
  $arr = Select($conn, 'SELECT * FROM `episodelist` ORDER BY `last_update` DESC, `title` ASC');

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
          <tbody>
            <?php


            foreach ($arr as $key => $value)
            {
            ?>
              <tr>
                <th scope="row"><?= $key + 1; ?></th>
                <td><?= $value["title"]; ?> | id: <?= $value["id"]; ?></td>
                <td>[<a href="<?= BASEURL; ?><?= $value["link"]; ?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL; ?>episode.php?edit=<?= $value["id"]; ?>" target="_blank">Edit</a>]</td>
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

function clean($string)
{
  $string = str_replace(['_', ' ', 'ö', 'ő', 'ó', 'á', 'é', 'í', 'ü', 'ú', 'ű'], ['-', '-', 'o', 'o', 'o', 'a', 'e', 'i', 'u', 'u', 'u'], $string); // Replaces spaces with hyphens.
  return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}
$conn->close();

function textInText($p, $p2) // $p == array || String; $p2 == String
{
  if (is_array($p))
  {
    foreach ($p as $v)
      if (strlen(str_replace($v, "", $p2)) < strlen($p2)) return TRUE;
  }
  elseif (is_string($p))  return (strlen(str_replace($p, "", $p2)) < strlen($p2)) ? TRUE : FALSE;
}
?>
