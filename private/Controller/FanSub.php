<?php
// FanSub Controller
require_once(FOLDER_MODEL . "FanSub.php");

function View()
{
  $data["title"] = "Fansub";
  $id = (is_numeric(getRU(1)) && !empty(getRU(1))) ? getRU(1) : 1;


  $data["uploaders"] = getUploadersData($id);
  $data["uploaders"] = (isset($data["uploaders"][0])) ? $data["uploaders"][0] : $data["uploaders"];
  $data["uploaders"]["project"] = getUploadersProject($id);
  viewBuilder(["Header", "Navbar", "L2Side", "Public" => "FanSub/one", "R2Side", "Footer"], $data);
}
function index()
{
  $data["title"] = "Fansub";

  
  exit(viewBuilder(["Header", "Navbar", "L2Side", "Public" => "403", "R2Side", "Footer"], $data));

  $data["uploaders"] = getUploadersAll();
  viewBuilder(["Header", "Navbar", "L2Side", "Public" => "FanSub/all", "R2Side", "Footer"], $data);
}
