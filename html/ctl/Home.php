<?php
  $ecs = '
  <a class="div" href="https://animem.org/dagashi-kashi" style="" title="Dagashi Kashi">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Dagashi Kashi</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/1538/95686.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>
  <a class="div" href="https://animem.org/night-head-genesis" style="" title="Night Head Genesis">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Night Head Genesis</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/1208/111006.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>
  <a class="div" href="https://animem.org/hanebado" style="" title="Hanebado!">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Hanebado!</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/1288/93432.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>
  <a class="div" href="https://animem.org/Kunoichi_Tsubaki_no_Mune_no_Uchi" style="" title="Kunoichi Tsubaki no Mune no Uchi">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Kunoichi Tsubaki no Mune no Uchi</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/1724/121343.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>
  <a class="div" href="https://animem.org/devil-may-cry" style="" title="Devil May Cry">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Devil May Cry</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/4/26417.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>
  <a class="div" href="https://animem.org/zettai-junpaku-mahou-shoujo" style="" title="Zettai Junpaku♡Mahou Shoujo">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Zettai Junpaku♡Mahou Shoujo</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/9/65213.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>
  <a class="div" href="https://animem.org/Love_Live_Sunshine_The_School_Idol_Movie:_Over_the_Rainbow" style="" title="Love Live! Sunshine!! The School Idol Movie: Over the Rainbow">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Love Live! Sunshine!! The School Idol Movie: Over the Rainbow</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/1859/100474.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>
  <a class="div" href="https://animem.org/love-hina" style="" title="Love Hina">
  <div class="dive">
  <h6 style="margin:5px;" class="cut-text">Love Hina</h6>
  </div>
  <div>
  <img src="https://cdn.myanimelist.net/images/anime/1153/99366.jpg" style="width: 200px!important; height:300px!important; display: block;">
  </div>
  </a>';
  
  
  
  
  /*
  $mal = Select($conn3, "SELECT `buta_wp`, `title`, `img`  FROM `mal__anime` WHERE (`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '') ORDER BY RAND() LIMIT 8;");
  foreach ($mal as $key => $value)
  {
    $ecs .= '
      <a class="div" href="https://animem.org/' . $value["buta_wp"] . '" style="" title="' . htmlspecialchars_decode($value["title"]) . '">
        <div class="dive">
          <h6 style="margin:5px;" class="cut-text">' . htmlspecialchars_decode($value["title"])  . '</h6>
        </div>
        <div>
          <img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
        </div>
      </a>';
  }*/


  $title = "Kezdőlap";
  require_once("view/header.phtml");
  require_once("view/centerbox/home.phtml");
  require_once("view/footer.phtml");