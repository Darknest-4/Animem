<?php

$array = json_decode(file_get_contents("Dai_Project_List.json"), true);
echo "<h4> Aktív projektek:</h4>";
print_project($array['a']);
echo "<h4> Befejezett projektek:</h4>";
print_project($array['b']);

function print_project($array)
{

  foreach ($array as $key => $link)
  {
?>
    <ul>
      <li><?= $link['title']; ?></li>
      <ol>
        <?php
        foreach ($link['subtitle'] as $key => $subtitle)
        {
        ?>
          <li value="<?= $key + 1; ?>">rész: <a href="<?= $subtitle; ?>" target="_blank">Felirat</a>, <a href="<?= $link['video'][$key]; ?>" target="_blank">Torrent</a></li>
        <?php
        }
        ?>
      </ol>
    </ul>
<?php
  }
}



function print_p($p = '')
{
  echo '<pre>';
  var_export($p);
  echo '</pre>';
}

function searchLinks($url, $contains)
{
  $html = file_get_contents($url);
  preg_match_all('/href="[^"]*(' . $contains . ')[^"]*"/', $html, $matches);
  $result = array_map(function ($val)
  {
    return str_replace(["href=", "\"", "'"], "", $val);
  }, $matches[0]);
  return $result;
}
function getContentBetweenTags($source, $tag, $search)
{
  $start = strpos($source, "<$tag>") + strlen($tag) + 2;
  $end = strpos($source, "</$tag>", $start);
  $content = substr($source, $start, $end - $start);
  if (strpos($content, $search) !== false)
  {
    return $content;
  }
  else
  {
    return "";
  }
}
