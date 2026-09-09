<?php
/*
require_once("OtherCode/phpmailer/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


function emailsend($config)
{

  $link = $config['url']["base"]
    . "Users/Verify/"
    . $config['email']["send"]['token'];
  $emailBody = '
  <h1>Regisztráció megerősitése.</h1>  
  <p>
  Köszönjük, hogy regisztrált az animem.org-ra.<br />
  A következő linkre kattintva aktiválhatja a regisztrációját.<br />
  <a href="' . $link . '" target="_blank">Aktiválás</a>
  </p>
  ';

  $mail = new PHPMailer(true);

  //Enable SMTP debugging.
  #$mail->SMTPDebug = 3;                               
  //Set PHPMailer to use SMTP.
  $mail->isSMTP();
  //Set SMTP host name                          
  $mail->Host = $config['email']["SMTPHost"];
  //Set this to true if SMTP host requires authentication to send email
  $mail->SMTPAuth = true;
  //Provide username and password     
  $mail->Username = $config['email']["username"];
  $mail->Password = $config['email']["password"];
  //If SMTP requires TLS encryption then set it
  $mail->SMTPSecure = "ssl";

  $mail->Encoding = 'base64';
  $mail->CharSet = 'UTF-8';

  //Set TCP port to connect to
  $mail->Port = $config['email']["port"];

  $mail->From = $config['email']["username"];
  $mail->FromName = $config['email']["send"]['title'];

  $mail->addAddress($config['email']["send"]['email'], $config['email']["send"]['email']);

  $mail->isHTML(true);

  $mail->Subject = $config['email']["send"]['subject2'];
  $mail->Body = $emailBody;
  $mail->AltBody = "";

  try
  {
    $mail->send();
    return true;
    # echo "Message has been sent successfully";
  }
  catch (Exception $e)
  {
    //return false;
    echo "Mailer Error: " . $mail->ErrorInfo;
  }
}
*/

function generateRandomString($length = 20)
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++)
  {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}
function perm($code, $type = NULL)
{
  if (!($type !== NULL && ($type == "site" || $type == "method"))) exit("Hát... Valami nagyon elcsesződött. Probáld máskor. :)");

  if (isset($_COOKIE["userID"]))
  {
    $pg_ids = Select("SELECT `perm_groups_id` FROM `perm__user` WHERE `user_id` = {$_COOKIE["userID"]} LIMIT 1;");
    if (is_array($pg_ids))
      foreach ($pg_ids as $pg_id)
      {
        $type_ids = Select("SELECT `{$type}_id` FROM `perm__access` WHERE `perm_groups_id` = {$pg_id["perm_groups_id"]} && `{$type}_id` != '';");
        if (is_array($type_ids))
          foreach ($type_ids as $type_id)
          {
            $type_name = Select("SELECT `name` FROM `perm__{$type}` WHERE `id` = {$type_id["{$type}_id"]} LIMIT 1;");

            if (is_array($type_name))
              if ($type_name[0]["name"] == $code)
                return TRUE;
          }
        else
          return FALSE;
      }
    else
      return FALSE;
  }
  else
  {
    $type_ids = Select("SELECT `{$type}_id` FROM `perm__access` WHERE `perm_groups_id` = 2 && `{$type}_id` != '';");
    if (is_array($type_ids))
      foreach ($type_ids as $type_id)
      {
        $type_name = Select("SELECT `name` FROM `perm__{$type}` WHERE `id` = {$type_id["{$type}_id"]} LIMIT 1;");

        if (is_array($type_name))
          if ($type_name[0]["name"] == $code)
            return TRUE;
      }
    else
      return FALSE;
  }
}

function setVisitorsFromDatabase()
{
  $current_time = time();
  $timeout = $current_time - (60);
  $ip = getip();
  if (!empty($ip))
  {
    $session_check = Select("SELECT `session` FROM `statistic__visits_today` WHERE `session`='{$_SESSION['session']}' LIMIT 1;");
    $session_check = (is_array($session_check) && !empty($session_check)) ? 1 : 0;

    if ($session_check == 0 && $_SESSION['session'] != "")
    {
      if (isset($_COOKIE["userID"]))
        Insert("INSERT INTO `statistic__visits_today` (`user_id`, `session`, `ip`, `create_time`, `last_time`) values ({$_COOKIE["userID"]}, '{$_SESSION['session']}', '{$ip}', '{$current_time}', '{$current_time}');");
      else
        Insert("INSERT INTO `statistic__visits_today` (`session`, `ip`, `create_time`, `last_time`) values ('{$_SESSION['session']}', '{$ip}', '{$current_time}', '{$current_time}');");
    }
    else
    {
      Update("UPDATE `statistic__visits_today` SET `last_time`='{$current_time}', `ip` = '{$ip}', `updated` = `updated`+1 WHERE `session`='{$_SESSION['session']}' LIMIT 1;");
    }
  }
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
