<?php

class DbConfig {
  const DB_FILE = 'config.json';

  /**
   * @var null|array
   */
  static $config = null;

  static function loadConfig()
  {
    if (null === self::$config) {
      $configJson = file_get_contents(__DIR__."/".self::DB_FILE);
      self::$config =  json_decode($configJson, true);
    }
  }

  /**
   * return array
   */
  static function getDbConfig()
  {
    self::loadConfig();

    return self::$config['db'];
  }

  /**
   * return array
   */
  static function getSiteConfig()
  {
    self::loadConfig();

    return self::$config['site'];
  }
}
