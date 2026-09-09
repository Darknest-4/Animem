<?php
function getRU($p = NULL)
{
  $p = ($p !== NULL) ? $p : "";
  $explode = (isset($_SERVER['PATH_INFO'])) ? explode("/", substr($_SERVER['PATH_INFO'], 1)) : array();
  return (empty($explode)) ? "" : ((isset($explode[$p])) ? $explode[$p] : $explode);
}
