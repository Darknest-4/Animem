<?php


class Database {
  private static $instance = null;
  private $connection;

  private function __construct($db_name = null) {
      if ($db_name === null) {
          $db_name = "default";
      }
      // Credentials come from the environment; dbconfig.json is a fallback.
      require_once dirname(__DIR__, 2) . '/Config/credentials.php';
      $db_config = animem_db_credentials(FOLDER_CONFIG . "dbconfig.json", $db_name);
      $this->connection = new PDO(
          "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4",
          $db_config['username'],
          $db_config['password'],
          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
      );
  }

  public static function getInstance($db_name = null) {
      if (self::$instance === null) {
          self::$instance = new self($db_name);
      }
      return self::$instance;
  }

  public function getConnection() {
      return $this->connection;
  }
  public function close() {
    $this->connection = null;
    self::$instance = null;
}
}
function Update($sql, $db_name = "default") {
  return Execute($sql, $db_name);
}

function Delete($sql, $db_name = "default") {
  return Execute($sql, $db_name);
}

function Execute($sql, $db_name = "default") {
  if (!is_string($sql)) {
      return "Error: invalid input";
  }
  try {
      $db = Database::getInstance($db_name);
      $connection = $db->getConnection();
      $query = $connection->query($sql);
      return true;
  } catch (PDOException $e) {
    die("Error: invalid SQL syntax<br />" . $sql);
  }
}

function RealEscapeString($string = "", $db_name = "default") {
  if (!is_string($string)) {
      return "Error: invalid input";
  }
  try {
      $db = Database::getInstance($db_name);
      $connection = $db->getConnection();
      return $query = $connection->quote($string);
  } catch (PDOException $e) {
    die("Error: invalid SQL syntax<br />" . $string);
  }
}
function Insert($sql, $db_name = "default", $return_id = false) {

  $v = func_get_args();
  if(isset($v[1]) && ((isset($v[2]) && !is_string($v[1]))||(!isset($v[2]) && !is_string($v[1]) && !is_bool($v[1]))))return "'\$db_name' can only be a string value.";
  if(isset($v[2]) && !is_bool($v[2]))return "'\$return_id' can only be a boolean value.";
  $db_name=(isset($v[1]) && is_string($v[1]))?$db_name:"default";  
  $return_id=(isset($v[1]) && !isset($v[2]) && is_bool($v[1]))?$v[1]:((isset($v[2]) && is_bool($v[2]))?$return_id:false);

  if (!is_string($sql)) {
      return "Error: invalid input";
  }
  try {
      $db = Database::getInstance($db_name);
      $connection = $db->getConnection();
      $query = $connection->query($sql);
      if($return_id == true)
      {
        return $connection->lastInsertId();
      }
      else {
        return true;
      }
  } catch (PDOException $e) {
      return "Error: invalid SQL syntax";
  }
}
function Select($sql, $db_name = "default") {
  if (!is_string($sql)) {
      return "Error: invalid input";
  }
  try {
      $db = Database::getInstance($db_name);
      $connection = $db->getConnection();
      $query = $connection->query($sql);
      return $query->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      die("Error: invalid SQL syntax<br />" . $sql);
  }
}




$json ='
{
  "default": {
      "host": "localhost",
      "dbname": "my_database",
      "username": "root",
      "password": "root"
  },
  "second_db": {
      "host": "localhost",
      "dbname": "second_database",
      "username": "user",
      "password": "password"
  }
}
';
