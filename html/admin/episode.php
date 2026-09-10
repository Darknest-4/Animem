<?php //if(getip() != "45.128.71.2") exit("Késő este van és nem telik karbantartás png-re. Igy most ezt a szoveget kell olvasnod. Nyugi reggel már menni fog az oldal. :) "); 
?>
<?php
//exit(require_once("503.php"));
$search = (isset($_GET["search"])) ? $_GET["search"] : "";

$template = findfilefromdir("Assets/template");
$template[] = $template[0];
unset($template[0]);
?>
<?php

$footer = '
  <!-- Optional JavaScript; choose one of the two! -->

  <script src="http://sortablejs.github.io/Sortable/Sortable.js"></script>
  <script>/*
  var sortable = Sortable.create(el);
    new Sortable(example1, {
        animation: 150,
        ghostClass: \'blue-background-class\'
    });*/
  </script>
  <style>
    table.table-striped tr:hover {
        background: rgba(255, 255, 255, 0.5);
    }
  </style>
  </body>
  </html>
';

//$servername = "localhost";
//$username = "rlight";
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
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_GET["search"]))
{
  require_once("Views/header.phtml");
  require_once("Views/admin-navbar.phtml");
  $_GET["search"] = clean($_GET["search"]);
  $arr = Select($conn, 'SELECT * FROM `episodelist` WHERE `episodelist`.`link` LIKE "%' . $_GET["search"] . '%" ORDER BY `last_update` DESC, `title` ASC');
  $linktype = Select($conn, 'SELECT * FROM `links_type` ORDER BY `name`');
?>


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
              <th scope="col">Import</th>
              <th scope="col">Export</th>
              <th scope="col">Link</th>
              <th scope="col">Edit</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="list">
            <?php


            foreach ($arr as $key => $EpList)
            {

              $json = json_decode($EpList["save"], true);
              $json2 = array();
              if (isset($json["_type"])) unset($json["_type"]);
              $i = 1;
              $exportLinks = array();
              if(is_array($json))
              foreach ($json as  $link_id)
              {
                $link = Select($conn, "SELECT  `links`.`link`, `links_type`.`link` AS `type_link`, `links_type`.`id` AS `type_id` FROM `links` INNER JOIN `links_type` ON `links_type`.`id` = `links`.`links_type` WHERE `links`.`id` = " . $link_id . " LIMIT 1;");

                if (isset($link[0]))
                if (isset($linktype[0]))
                {
                  $set_link = NULL;
                  foreach ($linktype as $type)
                  {
                    if ($type["link"] == $link[0]["type_link"])
                      if (is_null($set_link))
                        $set_link = $type["link"];
                  }
                  $exportLinks[] = "https://" . str_replace(["On/", "Off/", "Null/"], [""], $set_link . $link[0]["link"]);
                }



            ?>

              <?php

              }



              ?>
              <tr>
                <th scope="row"><?= $key + 1; ?></th>
                <td><?= $EpList["title"]; ?> | id: <?= $EpList["id"]; ?></td>
                <td><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal_<?= $EpList["id"]; ?>">Import</button></td>
                <td><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal_<?= $EpList["id"]; ?>">Export</button></td>
                <td>[<a href="<?= str_replace('admin/', '', BASEURL); ?>EpisodeList/<?= $EpList["id"]; ?>/<?= $EpList["link"]; ?>" target="_blank">Link</a>]</td>
                <td>[<a href="<?= BASEURL; ?>episode.php?edit=<?= $EpList["id"]; ?>" target="_blank">Edit</a>]</td>
                <td>
              <form action="<?= BASEURL; ?>/episode.php" method="post" id="biglinks_<?= $EpList["id"]; ?>">
                <input type="text" style="display:none;" type="text" name="biglinks" value="<?= $EpList["id"]; ?>" readonly="readonly" form="biglinks_<?= $EpList["id"]; ?>">
                <div class="modal fade" id="importModal_<?= $EpList["id"]; ?>" tabindex="-1" aria-labelledby="importModalLabel_<?= $EpList["id"]; ?>" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel_<?= $EpList["id"]; ?>">Import: <?= $EpList["title"]; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <textarea class="form-control" style="min-width: 100%; min-height: 400px" name="links"></textarea>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-info" form="biglinks_<?= $EpList["id"]; ?>">Import</button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </td>
              </tr>

              <!-- Modal -->
              <div class="modal fade" id="exportModal_<?= $EpList["id"]; ?>" tabindex="-1" aria-labelledby="exportModalLabel_<?= $EpList["id"]; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="exportModalLabel_<?= $EpList["id"]; ?>">Export: <?= $EpList["title"]; ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <textarea class="form-control" style="min-width: 100%; min-height: 400px"><?= implode(",\n", $exportLinks); ?></textarea>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>
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
elseif (isset($_GET["edit"]))
{
  require_once("Views/header.phtml");
  require_once("Views/admin-navbar.phtml");
  require_once("Views/header.phtml");
  require_once("Views/admin-navbar.phtml");
  $link = clean($_GET["edit"]);
  $arr = Select($conn, 'SELECT * FROM `episodelist` WHERE `episodelist`.`id` = "' . $link . '" LIMIT 1');
  $arr2 = Select($conn, 'SELECT `datasheet`.`link`, `datasheet`.`title`, `datasheet`.`id` FROM `datasheet` WHERE `datasheet`.`save` LIKE "%' . $arr[0]["id"] . '%" LIMIT 1');
  $linktype = Select($conn, 'SELECT * FROM `links_type` ORDER BY `name`');
  $episode_lang = Select($conn, 'SELECT * FROM `episode_lang` ORDER BY `text`');

  if (isset($linktype[0])) {
    $linkTypeOptions = '';
    foreach ($linktype as $value) {
      $linkTypeOptions .= '<option class="" value="' . $value["id"] . '" >' . $value["link"] . '</option>';
    }
  }

?>
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
  </script>
  <div class="container-fluid">
    <div class="row">
      <h3>
        Edit: <?= $arr[0]["title"]; ?>
      </h3>
    </div>
    <form action="<?= BASEURL; ?>/episode.php" method="get" id="form2">
      <input type="text" style="display:none;" type="text" name="reform" value="<?= $_GET["edit"]; ?>" readonly="readonly" form="form2">
    </form>
    <form action="<?= BASEURL; ?>/episode.php" method="get" id="mininews">
      <input type="text" style="display:none;" type="text" name="mininews" value="<?= $_GET["edit"]; ?>" readonly="readonly" form="mininews">
      <input type="text" style="display:none;" type="text" name="link_url" value="<?= $arr2[0]["id"]; ?>" readonly="readonly" form="mininews">
    </form>
    <form action="<?= BASEURL; ?>/episode.php" method="post" id="biglinks">
      <input type="text" style="display:none;" type="text" name="biglinks" value="<?= $_GET["edit"]; ?>" readonly="readonly" form="biglinks">

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
    <form action="<?= BASEURL; ?>/episode.php" method="post" id="form1">
      <input type="text" style="display:none;" type="text" name="save" value="<?= $_GET["edit"]; ?>" readonly="readonly" form="form1">
      <div class="row">
        <div class="col-8">
          <button type="submit" class="btn btn-success" form="form1">Save</button>
          <input class="btn btn-warning" type="button" value="Add Rows" onclick="addRow()" />
          <a class="btn" target="_blank" href="<?= str_replace("admin/", "", BASEURL); ?>EpisodeList/<?= $arr[0]["id"]; ?>" style="background-color:#d63384; color:#fff;">View</a>
          <input class="btn btn-danger" type="button" value="Clear All" onclick="Clear_All()" />
          <button type="submit" class="btn btn-info" form="form2">Elöző Visszaállítása</button>
          <button type="button" class="btn btn-primary" onclick="importModal.show();">Import</button>
          <button type="button" class="btn btn-primary" onclick="exportModal.show();">Export</button>
          <a class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL; ?>episode.php?delete=<?= $_GET["edit"]; ?>">Delete</a>

          <?php
          if (isset($episode_lang[0]))
          {
            $set_type = NULL;
            $echo = "";
            echo  '<select class="btn btn-outline-secondary text-primary" name="lang">';
            foreach ($episode_lang as $value)
            {
              $echo .= '<option value="' . $value["id"] . '" ' . (($value["id"] == $arr[0]["episode_lang"]) ? 'selected' : "") . '>' . $value["text"] . '</option>';

              if ($value["id"] == $arr[0]["episode_lang"])
                if (is_null($set_type))
                  $set_type = $value["text"];
            }
            echo $echo . '</select>';
          }

          ?>
        </div>
        <div class="col-4">
          <button type="submit" class="btn btn-warning" name="link_description" value="A(z) {{TITLE}} legújabb része megjelent." form="mininews">Send New Episode</button>
          <button type="submit" class="btn btn-info" name="link_description" value="A(z) {{TITLE}} felkerült az oldalra." form="mininews">Send New Anime</button>
          <button type="submit" class="btn btn-warning" name="link_description" value="{{TITLE}} javítva lett." form="mininews">Send Anime Fixed</button>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <td>Cím:</td>
                  <td colspan="4"><input type="text" class="form-control" type="text" name="title" value="<?= htmlspecialchars($arr[0]["title"]); ?>" form="form1"></td>
                </tr>
                <tr>
                  <td>Link:</td>
                  <td colspan="4">
                    <div class="input-group">
                      <span class="input-group-text"><?= str_replace('admin/', '', BASEURL); ?></span>
                      <input type="text" class="form-control" name="linklink" value="<?= $arr[0]["link"]; ?>" form="form1">
                      <span class="input-group-text"><a href="<?= str_replace('admin/', '', BASEURL); ?>EpisodeList/<?= $arr[0]["id"]; ?>/<?= $arr[0]["link"]; ?>" target="_blank">Open</a></span>
                    </div>
                  </td>
                </tr>
              </thead>
              <thead>
                <tr style="vertical-align: top; text-align: center;">
                  <th class="col-1"><?= $arr[0]["id"]; ?></th>
                  <th class="col-2">Cim</th>
                  <th class="col-8">Link</th>
                  <th class="col-1">Delete Row</th>
                </tr>
              </thead>
              <tbody id="example1">
                <?php
                $json = json_decode($arr[0]["save"], true);
                $json2 = array();
                if (isset($json["_type"])) unset($json["_type"]);
                $i = 1;
                $exportLinks = array();
                foreach ($json as $key => $value)
                {
                  $link = Select($conn, "SELECT  `links`.`link`,
                          `links_type`.`link` AS `type_link`, `links_type`.`id` AS `type_id` FROM `links` INNER JOIN `links_type` ON `links_type`.`id` = `links`.`links_type` WHERE `links`.`id` = " . $value . " LIMIT 1;");


                ?>

                  <tr style="vertical-align: top; text-align: center;">
                    <th class="col-1" scope="row"><?= $i++; ?></th>
                    <td class="col-2">

                      <div class="input-group">
                        <input type="text" class="form-control" type="text" name="name[]" value="<?= preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $key); ?>" form="form1">
                        <span class="input-group-text"><?= $set_type; ?></span>
                      </div>


                    </td>
                    <td class="col-2">
                      <div class="input-group">

                        <?php

                        if (isset($linktype[0]))
                        {
                          $set_link = NULL;
                          $echo = "";
                          echo  '<span class="input-group-text p-0">
                          <select class="btn btn-outline-secondary text-primary" name="type[]">';
                          foreach ($linktype as $value)
                          {
                            $echo .= '<option class="" value="' . $value["id"] . '" ' . (($value["link"] == $link[0]["type_link"]) ? 'selected' : "") . '>' . $value["link"] . '</option>';

                            if ($value["link"] == $link[0]["type_link"]) {
                                $set_link = $value["link"];
                            }
                          }
                          echo $echo . '</select></span>';

                          if (in_array((int) $link[0]["type_id"], [8, 10, 12])) {
                            $episodeLink = str_replace(["On/", "Off/", "Null/"], [""], $link[0]["link"]);
                          } else {
                            $episodeLink = "https://" . str_replace(["On/", "Off/", "Null/"], [""], $set_link . $link[0]["link"]);
                          }
                          $exportLinks[] = $episodeLink;
                        ?>

                          <input type="text" class="form-control" name="link[]" value="<?= $link[0]["link"]; ?>" form="form1">
                          <span class="input-group-text"><a href="<?= $episodeLink ?>" target="_blank">Open</a></span>
                      </div>
                    </td>

                  <?php
                        }



                  ?><td class="col-1"><input type="button" class="btn btn-danger" value="Delete Row" onclick="deleteRow(this)" /></td>
                  </tr>

                <?php

                }
                ?>
              </tbody>
            </table>

            <!-- Modal -->
            <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <textarea class="form-control" style="min-width: 100%; min-height: 400px"><?= implode(",\n", $exportLinks); ?></textarea>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <button type="submit" class="btn btn-success" form="form1">Save</button>
          <input class="btn btn-warning" type="button" value="Add Rows" onclick="addRow()" />
          <input class="btn btn-danger" type="button" value="Clear All" onclick="Clear_All()" />
          <button type="submit" class="btn btn-info" form="form2">Elöző Visszaállítása</button>
          <a class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL; ?>episode.php?delete=<?= $_GET["edit"]; ?>">Delete</a>
        </div>
      </div>
    </form>
  </div>
  <script>
    function addRow() {
      let table = document.getElementById("example1");
      let episodes = document.querySelectorAll("#example1 select.btn.btn-outline-secondary.text-primary");
      let lastEpisodeType = undefined;
      if (episodes.length > 0) {
        lastEpisodeType = episodes[episodes.length - 1];
      }

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
        '<select class="btn btn-outline-secondary text-primary" name="type[]">' +
        '<?= $linkTypeOptions; ?>' +
        '</select></span>' +
        '<input type="text" class="form-control" name="link[]" value="" form="form1">' +
        '<span class="input-group-text"><a href="" target="_blank">Open</a></span>' +
        '</div>' +
        '</td>' +
        '<td class="col-1"><input type="button" class="btn btn-danger" value="Delete Row" onclick="deleteRow(this)"></td>';
      for (var i = 0; i < table.rows.length; i++) {
        table.rows[i].cells[0].innerHTML = i + 1;
      }

      if (undefined !== lastEpisodeType) {
        episodes = document.querySelectorAll("#example1 select.btn.btn-outline-secondary.text-primary");
        episodes[episodes.length - 1].value = lastEpisodeType.value;
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

  //$arr = Select($conn, 'SELECT `mal__anime`.`id` FROM `mal__anime` WHERE `mal__anime`.`id` = "' . $_GET["link_url"] . '"  LIMIT 1');
  $arr = Select($conn, 'SELECT datasheet.id FROM datasheet WHERE datasheet.id = "' . $_GET["link_url"] . '"  LIMIT 1');
  print_p($_GET);
  if (isset($arr[0]["id"]))
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
//  print_p($_GET);
//  print_p($select);
  if (isset($select[0]["save2"]) && !empty($select[0]["save2"]))
  {
    Update($conn, "UPDATE `episodelist` SET `save`='" . $conn->real_escape_string($select[0]["save2"]) . "' WHERE `id` = '" . $select[0]["id"] . "'");
  }


  header("Location: " . BASEURL . "episode.php?edit=" . $_GET["reform"]);
}
else
{

  require_once("Views/header.phtml");
  require_once("Views/admin-navbar.phtml");
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
                <td>[<a href="<?= str_replace('admin/', '', BASEURL); ?>EpisodeList/<?= $value["id"]; ?>/<?= $value["link"]; ?>" target="_blank">Link</a>]</td>
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
  $string = str_replace(['_', ' ', '.', 'ö', 'ő', 'ó', 'á', 'é', 'í', 'ü', 'ú', 'ű'], ['-', '-', '-', 'o', 'o', 'o', 'a', 'e', 'i', 'u', 'u', 'u'], $string); // Replaces spaces with hyphens.
  $string = str_replace(['--'], ['-'], $string);
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
?>
