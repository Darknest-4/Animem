<?php

function fGetCon($a = "", $isFile=TRUE)
{
  if ($isFile === TRUE)
    if (file_exists($a))
      return file_get_contents($a);
    else
      return False;
  else
    return file_get_contents($a);
}

function findfilefromdir($dirs)
{
  if (file_exists($dirs))
  {
    $aa = scandir($dirs);
    $dir = [];
    foreach ($aa as $val)
    {
      if ($val != '.' && $val != '..')
        $dir[] = $val;
    }
    return $dir;
  }
}
