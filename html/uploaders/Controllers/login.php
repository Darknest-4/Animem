<?php


if (isset($_POST["password"]) && isset($_POST["identification"]) && !empty($_POST["password"]) && !empty($_POST["identification"]))
{
    $identification = strip_tags(htmlspecialchars($_POST['identification']));
    $password = hash('sha256', strip_tags(htmlspecialchars($_POST['password'])));


    $sql = "SELECT `id` FROM `uploaders` WHERE `identification` = '{{identification}}' && `password` = '{{Password}}' LIMIT 1;";
    $id  =  Select(str_replace(["{{identification}}", "{{Password}}"], [$identification, $password], $sql));

    if (is_array($id) && !empty($id))
    {
      $id = $id[0]["id"];
        print_p($password);

        setcookie("userID", $id, time() + (86400 * (($meOut == "on") ? 365 : 7)), "/"); // 86400 = 1 day

        //die("Succesfull Login");
        exit(header("Location: " . BASEURL. "uploaders" .DS. "home"));
      
    }
    else
    {
      $data['error'] = 'Rossz Felhasználónév vagy Jelszó';
    }
}
else
{
  require_once("Views" . DS . "login.phtml");
}
