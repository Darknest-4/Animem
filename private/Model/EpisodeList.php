<?php
// FanSub Model

function getUploadersAll()
{
  $sql = "SELECT
  `U`.`id` AS 'UID',
  `U`.`name` AS 'Name'
FROM
  `uploaders` AS `U`
    ORDER BY `U`.`name`;
  ";
  return Select($sql);
}
function getUploadersData($id)
{
  $sql = "SELECT
  `U`.`id` AS 'UID',
  `U`.`name` AS 'U-name',
  `U`.`code` AS 'U-code',
  `U`.`inda_link` AS 'U-indavideo',
  `U`.`fan_link` AS 'U-website',
  `U`.`fb_link` AS 'U-facebook',
  `U`.`email` AS 'U-email',
  `U`.`pdes` AS 'U-description'
FROM
  `uploaders` AS `U`
WHERE
  `U`.`id` = {$id};
  ";
  return Select($sql);
}
function getUploadersProject($id)
{
  $sql = "SELECT
      `D`.`id` AS 'ID',
      `A`.`img` AS 'img',
      `A`.`title` AS 'title'
    FROM `datasheet` AS `D`
      LEFT JOIN `mal__anime` AS `A` ON `A`.`mal_id` = `D`.`myanimelist`
    WHERE JSON_EXTRACT(`D`.`save`,'$.fansub') LIKE '%\"{$id}\"%'
    GROUP BY `A`.`id`
    ORDER BY `A`.`title`;
  ";

  return Select($sql);
}
