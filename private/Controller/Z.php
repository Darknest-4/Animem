<?php
// Z Controller

function index()
{
  $data["title"] = "Fansub";
  exit(viewBuilder(["Header", "Navbar", "L2Side", "Public" => "403", "R2Side", "Footer"], $data));

  $data["uploaders"] = getUploadersAll();
  viewBuilder(["Header", "Navbar", "ESide", "Public" => "FanSub" . DS . "all", "R2Side", "Footer"], $data);
}
function EpisodeList()
{
  $data["title"] = "EpisodeList";
  exit(viewBuilder(["Header", "Navbar", "ESide", "Public" => "403", "R2Side", "Footer"], $data));
  exit(viewBuilder(["Header", "Navbar", "L2Side", "Public" => "403", "R2Side", "Footer"], $data));

  $data["uploaders"] = getUploadersAll();
  viewBuilder(["Header", "Navbar", "ESide", "Public" => "FanSub" . DS . "all", "R2Side", "Footer"], $data);
}
