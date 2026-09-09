<?php

if (isset($g[0]) && $g[0] == "animek-az")
{ // Animek-AZ


  $title = "Animék 'A'-tól 'Z'-ig";
  require_once("template/header.phtml");

  echo '
    <div class="rapidwp-box-inside" style="margin-top:10px;">
      <header class="entry-header">
        <div class="entry-header-inside">
          <h1 class="post-title entry-title">Animék - ' . strtoupper($g[1]) . '</h1>
          <div class="rapidwp-entry-meta-single"></div>
        </div>
      </header>
      <div class="elementor-section-wrap"> 
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
  echo '</h6>';

  $mal = Select($conn3, "SELECT `buta_wp`, `title`, `img`  FROM `mal__anime` WHERE (`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '') ORDER BY RAND() ASC LIMIT 12");

  $ecs = "";

  foreach ($mal as $key => $value)
  {
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

  echo '</div><footer class="entry-footer"></footer></div>';



  require_once("template/footer.phtml");
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();
  $conn2->close();
  $conn3->close();
  exit();
}
/****************************************************************** */
if (isset($g[0]) && $g[0] == "animek")
{ // Animek

  $title = "Animék 'A'-tól 'Z'-ig";
  require_once("template/header.phtml");
  animek($conn, $conn2, $conn3, $g);
  require_once("template/footer.phtml");
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();
  $conn2->close();
  $conn3->close();
  exit();
}
