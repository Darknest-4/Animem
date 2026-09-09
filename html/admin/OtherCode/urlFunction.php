<?php
function getRU($p = NULL)
{
  $p = ($p !== NULL) ? $p : "";
  $explode = (isset($_SERVER['REQUEST_URI'])) ? explode("/", substr($_SERVER['REQUEST_URI'], 1)) : array();
  return (empty($explode)) ? "" : ((isset($explode[$p])) ? $explode[$p] : $explode);
}
