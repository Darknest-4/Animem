<?php


if (isset($g[0]) && $g[0] == "statistic")
{ // Statisztika
  $statistic = "";
  $epc = Select($conn, "SELECT COUNT(*) as count FROM `episodelist_links`");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">Összesen ' . $value["count"]  . '. rész található az oldalon.</h6>
        </div>';
  }
  $epc = Select($conn, "SELECT COUNT(*) as count FROM `datasheet`");
  foreach ($epc as $key => $value)
  {
    $statistic .= '
        <div class="dive">
          <h6 style="margin:5px;" class="">Összesen ' . $value["count"] * 2  . '. Anime található az oldalon.</h6>
        </div>';
  }
  $statistic = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $statistic;
  $statistic .= '</section>';


  $title = "Statisztika";
  require_once("template/header.phtml");
  require_once("template/statistic.phtml");
  require_once("template/footer.phtml");
  //  date("Y-m-d h:i:s", time()-$stat_delay)
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();
  $conn2->close();
  $conn3->close();
  exit();
}