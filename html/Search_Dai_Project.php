<?php


$links = searchLinks("https://mutekifansub.hu/projekts.php", "projekt.php");
$links2 = searchLinks("https://mutekifansub.hu/projektsf.php", "projekt.php");
$array = [];
foreach ($links as $key => $link)
  $array['a'][]["title"] =  str_replace("Cím: ", "", getContentBetweenTags(file_get_contents("https://mutekifansub.hu/" . $link), "p", "Cím: "));
foreach ($links2 as $key => $link)
  $array['b'][]["title"] =  str_replace("Cím: ", "", getContentBetweenTags(file_get_contents("https://mutekifansub.hu/" . $link), "p", "Cím: "));


foreach ($links as $key => $link)
  $array['a'][$key]["subtitle"] =  searchLinks("https://mutekifansub.hu/" . $link, "drive.google.com");
foreach ($links2 as $key => $link)
  $array['b'][$key]["subtitle"] =  searchLinks("https://mutekifansub.hu/" . $link, "drive.google.com");

foreach ($links as $key => $link)
  $array['a'][$key]["video"] =  searchLinks("https://mutekifansub.hu/" . $link, "nyaa.si");
foreach ($links2 as $key => $link)
  $array['b'][$key]["video"] =  searchLinks("https://mutekifansub.hu/" . $link, "nyaa.si");
file_put_contents("Dai_Project_List.json", json_encode($array));
if(file_exists("Dai_Project_List.json"))
  echo "Fájl sikeresen létrejött.";
else
  echo "Fájl sikeresen jött nem létre.";

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
  $start = strpos($source, '<$tag>') + strlen($tag) + 2;
  $end = strpos($source, '</$tag>', $start);
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
