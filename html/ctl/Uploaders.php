<?php



if (isset($g[0]) && $g[0] == "fansub")
{ // Fansub
  $title = "Fansub";
  require_once("template/header.phtml");
  require_once("template/fansub.phtml");
  require_once("template/footer.phtml");
  // $sql = "UPDATE `statistic_meta` SET `open_site`= `open_site`+1, `updated` = `updated`+1 WHERE `id` = '[value-2]';";
  // Update($conn, str_replace(["[value-1]", "[value-2]"], [date("Y-m-d h:i:s", time()-$stat_delay), $browser_token_id], $sql));
  $conn->close();
  $conn2->close();
  $conn3->close();
  exit();
}