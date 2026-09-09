<?php


if (isset($_GET["s"]))
{ // Keresés

  $title = "Keresés: " . $_GET["s"];
  $_GET["s"] = $conn3->real_escape_string($_GET["s"]);


  if (!empty($_GET["s"]))
  {
    foreach (explode(" ", $_GET["s"]) as $word)
    {
      if (!empty($word))
        if (!empty($where))
        {
          $where .= " && (title LIKE '%" . $word . "%'";
          $where .= " || english LIKE '%" . $word . "%'";
          $where .= " || synonyms LIKE '%" . $word . "%'";
          $where .= " || japanese LIKE '%" . $word . "%')";
        }
        else
        {
          $where = "(title LIKE '%" . $word . "%'";
          $where .= " || english LIKE '%" . $word . "%'";
          $where .= " || synonyms LIKE '%" . $word . "%'";
          $where .= " || japanese LIKE '%" . $word . "%')";
        }
    }
  }


  //echo "SELECT `buta_wp`, `title`, `img`, `english`  FROM `mal__anime` WHERE (`title` LIKE '%".$_GET["s"]."%' || `english` LIKE '%".$_GET["s"]."%' || `synonyms` LIKE '%".$_GET["s"]."%' || `japanese` LIKE '%".$_GET["s"]."%') && ((`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '')) ORDER BY `title`;";
  // echo "SELECT `buta_wp`, `title`, `img`, `english`  FROM `mal__anime` WHERE (" . $where . ") && ((`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '')) ORDER BY `title`;";
  $arr = Select($conn3, "SELECT *  FROM `mal__anime` WHERE (" . $where . ") && ((`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '')) ORDER BY `title`;");

  require_once("template/header.phtml");

  if (isset($arr[0]))
  {
    echo ' 
          <div class="rapidwp-box-inside" style="margin-top:10px;">
              <header class="entry-header">
                <div class="entry-header-inside">
                  <h1 class="post-title entry-title">Keresés</h1>
                  <div class="rapidwp-entry-meta-single"></div>
                </div>
              </header>
              <div class="entry-content clearfix">';

    echo '<div class="bdp-list-main bdp-design-1 bdp-clearfix"> <div class="bdp-post-list bdp-clearfix">';
    $ecs = "";
    foreach ($arr as $key => $value)
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
      if (!empty($value["buta_wp"]))
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
    echo '</div></div></div>
              <footer class="entry-footer"></footer></div>';
  }
  else
  {

    $arr = Select($conn3, "SELECT `buta_wp`, `title`, `img`, `english`  FROM `mal__anime` WHERE (`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '') ORDER BY RAND() LIMIT 12;");
    echo ' 
    <div class="rapidwp-box-inside" style="margin-top:10px;">
              <header class="entry-header">
                <div class="entry-header-inside">
                  <h1 class="post-title entry-title">Keresés</h1>
                  <div class="rapidwp-entry-meta-single"></div>
                </div>
              </header>
              <div class="entry-content clearfix">';
    echo '<h4 style="text-align: center; ">Mivel nem találtuk meg amit keresel igy itt van random 12 anime.</h4><hr /><div class="bdp-list-main bdp-design-1 bdp-clearfix"> <div class="bdp-post-list bdp-clearfix">';
    $ecs = "";
    foreach ($arr as $key => $value)
    {
      $value["english"] = (!empty($value["english"])) ? $value["english"] : "Nincs angol címe.";

      $ecs .= '<a class="div" href="https://animem.org/' . $value["buta_wp"] . '" style="" title="' . htmlspecialchars_decode($value["title"])  . '">
                <div class="dive">
                <h6 style="margin:5px;" class="cut-text">' . htmlspecialchars_decode($value["title"])  . '</h6>
                </div>
                <div>
                <img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
                </div>
                </a>';
    }
    $ecs = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $ecs;
    $ecs .= '</section>';

    echo $ecs;

    echo '</div></div></div>
                      <footer class="entry-footer"></footer>
                    </div>';
  }

  require_once("template/footer.phtml");
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();
  $conn2->close();
  $conn3->close();
  exit();
}