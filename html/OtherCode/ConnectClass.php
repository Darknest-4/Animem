<?php

require_once("../Config/loadConfig.php");

class Connect
{

  private static $instance;
  private $conn;

  private function __construct()
  {
  }

  /**
   *
   * @return Connect
   */
  private static function getInstance()
  {
    if (self::$instance == null)
    {
      $className = __CLASS__;
      self::$instance = new $className;
    }

    return self::$instance;
  }

  /**
   *
   * @return Connect
   */
  private static function initConnection()
  {
    $db = self::getInstance();
//    $config = fGetCon("../Config/database.json");
//    $config = json_decode($config, true);
//    $db->conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

    $dbConfig = DbConfig::getDbConfig();

    $servername = $dbConfig['host'];
    $username = $dbConfig['username'];
    $password = $dbConfig['password'];
    $database = $dbConfig['database'];

    $db->conn = new mysqli($servername, $username, $password, $database);
    if ($db->conn->connect_error) die("Connection failed: " . $db->conn->connect_error);

    $db->conn->set_charset('utf8');
    return $db;
  }

  public function __clone()
  {
    throw new Exception("Can't clone a singleton");
  }


  /**
   * @return mysqli
   */
  public static function getconn()
  {
    try
    {
      $db = self::initConnection();
      return $db->conn;
    }
    catch (Exception $ex)
    {
      echo "I was unable to open a connection to the database. " . $ex->getMessage();
      return null;
    }
  }
}
