<?php //if(getip() != "45.128.71.3") exit('<img src="https://cdn.discordapp.com/attachments/791772926608408577/934908801268715591/Nagy_karbantartas.png" alt="Késő este van és nem telik karbantartás png-re. Igy most ezt a szoveget kell olvasnod. Nyugi reggel már menni fog az oldal. :)" />'); ?>
<?php //if(getip() != "animegun.eu") header("Location: https://www.patreon.com/join/animem?"); ?>
<?php
		require_once("../Config/loadConfig.php");
		require_once("other/index-header.php");

	$g = gpi();
	/****************************************************************** */
	if(isset($g[0]) && !empty($g[0]))
	{// DataSheet
		$select = Select($conn, 'SELECT * FROM `datasheet` WHERE `datasheet`.`link` = "' . $conn -> real_escape_string($g[0]) . '" LIMIT 1');
		if(isset($select[0]) && !empty($select[0]))
		{
			$select = $select[0];
			
			$title = $select["title"];
			$select["anime"] = myanimelist($conn3, $select["myanimelist"]);
			
			
			$uploaders = Select($conn, 'SELECT `fansub1`, `fansub2`, `fansub3` FROM `datasheet_uploaders` WHERE `datasheet_id` = ' . $select["id"] . '');
		
			$uploaders = json_decode($select["save"], true);
			$uploaders = $uploaders["fansub"];
			//  print_p($uploaders);
			if(is_array($uploaders) && !empty($uploaders))
			{
					if(isset($uploaders[0]) && !is_array($uploaders[0]))
					{
						foreach($uploaders as $k1 => $v1)
						{
							$uploaders2[$k1][] = $v1;
						}
						$uploaders = $uploaders2;
					}
				foreach($uploaders as $k => $v)
				{
					$key = 0;
					if(isset($uploaders[$k][$key]))
					{
						$uploaders[$k]["fansub1"] = $uploaders[$k][$key];
						unset($uploaders[$k][$key]);
					}else
					$uploaders[$k]["fansub1"] = "";
					$key = 1;
					if(isset($uploaders[$k][$key]))
					{
						$uploaders[$k]["fansub2"] = $uploaders[$k][$key];
						unset($uploaders[$k][$key]);
					}else
					$uploaders[$k]["fansub2"] = "";
					$key = 2;
					if(isset($uploaders[$k][$key]))
					{
						$uploaders[$k]["fansub3"] = $uploaders[$k][$key];
						unset($uploaders[$k][$key]);
					}else
					$uploaders[$k]["fansub3"] = "";
				}
				$select["uploaders"] = getUploaders($conn, $uploaders);
			}else
				$select["uploaders"] = "";
			
			// 	print_p($uploaders);
			

			$episodelist = Select($conn, 'SELECT `episodelist_id` FROM `datasheet_episodelist` WHERE `datasheet_id` = ' . $select["id"] . '');
			$select["episodelist"] = getEpisodelist2($conn, $select["save"], $select["datasheet"]);
			//$select["episodelist"] = getEpisodelist($conn, $episodelist);
			// print_p($select);
			require_once("template/header.phtml");
			require_once("template/datasheet.phtml");
			require_once("template/footer.phtml");
			$conn->close();
			$conn2->close();
			$conn3->close();
			exit();
		}
	}
	/****************************************************************** */
	if(isset($g[0]) && !empty($g[0]))
	{// EpisodeList
		$select = Select($conn, 'SELECT * FROM `episodelist` WHERE `episodelist`.`link` = "' . $conn -> real_escape_string($g[0]) . '" LIMIT 1');
		
		$episode_lang = Select($conn, 'SELECT * FROM `episode_lang` ORDER BY `text`');
		foreach ($episode_lang as  $value)
			$episode_langs[$value["id"]] = $value["text"];
		
		if(isset($select[0]) && !empty($select[0]))
		{
			$arr2 = Select($conn, 'SELECT `datasheet`.`link` FROM `datasheet` WHERE `datasheet`.`save` LIKE "%' . $select[0]["id"] . '%" && datasheet = 1 LIMIT 1');
			$select = $select[0];
			
			$title = $select["title"];
			
			$json = json_decode($select["save"], true);
			if(isset($json["_type"]))
			{
				$json2 = array();
				if(isset($json["_type"])) unset($json["_type"]);
				foreach ($json as $key => $value)
				{
					$link = Select($conn, "SELECT
					CONCAT(
							'https://',
							`links_type`.`link`,
							`links`.`link`
					) AS link FROM `links`
					INNER JOIN `links_type` ON `links_type`.`id` = `links`.`links_type` WHERE `links`.`id` = " . $value . " LIMIT 1;");
					$json2[preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $key) . " " . $episode_langs[$select["episode_lang"]]] = $link[0]["link"];
					$json3[preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $key) . " " . $episode_langs[$select["episode_lang"]]] = $value;
				}
			
				$link = Select($conn, "SELECT `datasheet_id`  FROM `datasheet_episodelist` WHERE `episodelist_id` =" . $select["id"] . " LIMIT 1;");
				if(isset($link[0]["datasheet_id"]))
					$link = Select($conn, "SELECT `myanimelist`  FROM `datasheet` WHERE `id` =" . $link[0]["datasheet_id"] . " LIMIT 1;");
				if(isset($link[0]["myanimelist"]))
					$link = Select($conn3, "SELECT `age_rating_id`  FROM `mal__anime` WHERE `mal_id` = " . $link[0]["myanimelist"] . " LIMIT 1;");
				if(isset($link[0]["age_rating_id"]))
					$link = Select($conn3, "SELECT `code`  FROM `mal__age_rating` WHERE `id` = " . $link[0]["age_rating_id"] . " LIMIT 1;");


			}

			require_once("template/header.phtml");

			echo '
				<div class="rapidwp-box-inside" style="margin-top:10px;">
				<header class="entry-header">
				<div>
				<div class="entry-header-inside" style="text-align:left; vertical-align:middle;display: table-cell;">
				<button style="padding: 7.2px 12px;">
				<a style="margin: 7.2px 12px; width:100%;" href="https://animem.org/'.$arr2[0]["link"].'" rel="bookmark">Vissza az adatlapra.</a>
				</button>
				</div>
				<div class="entry-header-inside" style="text-align:inherit; vertical-align:middle;display: table-cell;">
				<span class="post-title entry-title" style="vertical-align:center">
				<a href="https://animem.org/'.$select["link"].'" rel="bookmark">'.$select["title"].'</a>
				</span>
				</div>
				</div>
				<div class="rapidwp-entry-meta-single"></div>
				</header>

					<div class="entry-content clearfix">';
					
			if(isset($json2)&&isset($json3))
			{
				if(isset($link[0]["code"]))
					echo '
							<div id="div' .$link[0]["code"]. '"></div>';
				echo '
							<script>var jsonzx125 = '.json_encode($json2).';</script>
							<script>var tikitaki = '.json_encode($json3).';</script>
							<div id="jsonzx124"></div>
						</div>
						<footer class="entry-footer"></footer>
					</div>';
			}else
			{
				echo '
							'.str_replace("https://animem.org/wp-content/", "https://animem.org/Assets/", $select["save"]).'
						</div>
						<footer class="entry-footer"></footer>
					</div>';
			}
	

			require_once("template/footer.phtml");
			
			$conn->close();
			$conn2->close();
			$conn3->close();
			exit();
		}
	}
	/****************************************************************** */
	if(isset($g[0]) && $g[0] == "animek-az")
	{// Animek-AZ


		$title = "Animék 'A'-tól 'Z'-ig";
		require_once("template/header.phtml");

		echo '
			<div class="rapidwp-box-inside" style="margin-top:10px;">
				<header class="entry-header">
					<div class="entry-header-inside">
						<h1 class="post-title entry-title">Animék - '. strtoupper($g[1]) .'</h1>
						<div class="rapidwp-entry-meta-single"></div>
					</div>
				</header>
				<div class="elementor-section-wrap"> 
					<h6 style="text-align: center; width:100%;">';

		foreach (str_split("abcdefghijklmn", 1) as $key => $value)
		{
			echo "<a href=\"https://animem.org/animek/".$value."\" style=\"border:solid 1px white; margin:2px; padding:4px;\">".strtoupper($value)."</a>";
		}
		echo '</h6><h6 style="text-align: center; width:100%;">';
				
		foreach (str_split("opqrstuwvxyz1", 1) as $key => $value)
		{
			echo "<a href=\"https://animem.org/animek/".$value."\" style=\"border:solid 1px white; margin:2px; padding:4px;\">".strtoupper($value)."</a>";
		}
		echo '</h6>';

		$mal = Select($conn3, "SELECT `buta_wp`, `title`, `img`  FROM `mal__anime` WHERE (`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '') ORDER BY RAND() ASC LIMIT 12");

		$ecs ="";

		foreach ($mal as $key => $value) {
			$ecs .= '<a class="div" href="https://animem.org/' . $value["buta_wp"] . '" style="" title="' . htmlspecialchars_decode($value["title"])  . '">
				<div class="dive">
					<h6 style="margin:5px;" class="cut-text">' .htmlspecialchars_decode($value["title"])  . '</h6>
				</div>
				<div>
					<img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
				</div>
			</a>';
		}
		$ecs = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $ecs;
		$ecs .= '</section>';

		echo $ecs;

		echo '</div><footer class="entry-footer"></footer></div>';



		require_once("template/footer.phtml");
		$conn->close();
		$conn2->close();
		$conn3->close();
		exit();
	}
	/****************************************************************** */
	if(isset($g[0]) && $g[0] == "animek")
	{// Animek
		
		$title = "Animék 'A'-tól 'Z'-ig";
		require_once("template/header.phtml");
		animek($conn, $conn2, $conn3, $g);
		require_once("template/footer.phtml");
		$conn->close();
		$conn2->close();
		$conn3->close();
		exit();
  }
	/****************************************************************** */
	if(isset($g[0]) && $g[0] == "discord")
	{// Discord
		$conn->close();
		$conn2->close();
		$conn3->close();
		exit(header("Location: https://discord.gg/UDCszyA"));
  }
	/****************************************************************** */
	if(isset($g[0]) && $g[0] == "redirect")
	{// Redirect
		$conn->close();
		$conn2->close();
		$conn3->close();
		$redirect = explode("redirect/", $_SERVER["REQUEST_URI"]);
		if(isset($redirect[1]));
			exit(header("Location: " . $redirect[1]));
  }
	/****************************************************************** */
	if(isset($g[0]) && $g[0] == "fansub")
	{// Fansub
		
		require_once("template/header.phtml");
		require_once("template/fansub.phtml");
		require_once("template/footer.phtml");
		$conn->close();
		$conn2->close();
		$conn3->close();
		exit();
  }
	/****************************************************************** */
	if(isset($_GET["s"]))
	{// Keresés
	
		$title = "Keresés: " . $_GET["s"];
		$_GET["s"] = $conn3->real_escape_string($_GET["s"]);


    if(!empty($_GET["s"]))
    {
        foreach(explode(" ", $_GET["s"]) as $word)
        {
            if(!empty($word))
                if(!empty($where))
                {
                    $where .= " && (title LIKE '%".$word."%'";
                    $where .= " || english LIKE '%".$word."%'";
                    $where .= " || synonyms LIKE '%".$word."%'";
                    $where .= " || japanese LIKE '%".$word."%')";
                }else
                {
                    $where = "(title LIKE '%".$word."%'";
                    $where .= " || english LIKE '%".$word."%'";
                    $where .= " || synonyms LIKE '%".$word."%'";
                    $where .= " || japanese LIKE '%".$word."%')";
                }
        }
    }


		//echo "SELECT `buta_wp`, `title`, `img`, `english`  FROM `mal__anime` WHERE (`title` LIKE '%".$_GET["s"]."%' || `english` LIKE '%".$_GET["s"]."%' || `synonyms` LIKE '%".$_GET["s"]."%' || `japanese` LIKE '%".$_GET["s"]."%') && ((`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '')) ORDER BY `title`;";
		// echo "SELECT `buta_wp`, `title`, `img`, `english`  FROM `mal__anime` WHERE (" . $where . ") && ((`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '')) ORDER BY `title`;";
		$arr = Select($conn3, "SELECT *  FROM `mal__anime` WHERE (" . $where . ") && ((`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '')) ORDER BY `title`;");
		
		require_once("template/header.phtml");
		
		if(isset($arr[0]))
		{
			echo ' 
						<div class="rapidwp-box-inside" style="margin-top:10px;">
								<header class="entry-header">
									<div class="entry-header-inside">
										<h1 class="post-title entry-title">Keresés</h1>
										<div class="rapidwp-entry-meta-single"></div>
									</div>
								</header>
								<div class="entry-content clearfix">';
								
				echo '<div class="bdp-list-main bdp-design-1 bdp-clearfix"> <div class="bdp-post-list bdp-clearfix">';
				$ecs ="";
				foreach ($arr as $key => $value)
				{
					
					if(empty($value["buta_wp_title2"]))
					$value["buta_wp_title2"] = $value["title"];
					if(empty($value["buta_wp_title"]))
					$value["buta_wp_title"] = $value["title"];
					
					if(!empty($value["buta_wp2"]))
					{	
						$ecs .= '<a class="div" href="https://animem.org/' . $value["buta_wp2"] . '" style="" title="' . htmlspecialchars_decode($value["buta_wp_title2"])  . '">
						<div class="dive">
						<h6 style="margin:5px;" class="cut-text">' .htmlspecialchars_decode($value["buta_wp_title2"])  . '</h6>
						</div>
						<div>
						<img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
						</div>
						</a>';
					}
					if(!empty($value["buta_wp"]))
					{	
						$ecs .= '<a class="div" href="https://animem.org/' . $value["buta_wp"] . '" style="" title="' . htmlspecialchars_decode($value["buta_wp_title"])  . '">
						<div class="dive">
						<h6 style="margin:5px;" class="cut-text">' .htmlspecialchars_decode($value["buta_wp_title"])  . '</h6>
						</div>
						<div>
						<img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
						</div>
						</a>';
					}
				}
				$ecs = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $ecs;
				$ecs .= '</section>';

				echo $ecs;
				echo '</div></div></div>
								<footer class="entry-footer"></footer></div>';
		}else
		{
			
			$arr = Select($conn3, "SELECT `buta_wp`, `title`, `img`, `english`  FROM `mal__anime` WHERE (`buta_wp` IS NOT NULL && `buta_wp` != '') || (`buta_wp2` IS NOT NULL && `buta_wp2` != '') ORDER BY RAND() LIMIT 12;");
			echo ' 
			<div class="rapidwp-box-inside" style="margin-top:10px;">
								<header class="entry-header">
									<div class="entry-header-inside">
										<h1 class="post-title entry-title">Keresés</h1>
										<div class="rapidwp-entry-meta-single"></div>
									</div>
								</header>
								<div class="entry-content clearfix">';
								echo '<h4 style="text-align: center; ">Mivel nem találtuk meg amit keresel igy itt van random 12 anime.</h4><hr /><div class="bdp-list-main bdp-design-1 bdp-clearfix"> <div class="bdp-post-list bdp-clearfix">';
								$ecs ="";
								foreach ($arr as $key => $value)
								{
									$value["english"] = (!empty($value["english"]))?$value["english"]:"Nincs angol címe.";
									
									$ecs .= '<a class="div" href="https://animem.org/' . $value["buta_wp"] . '" style="" title="' . htmlspecialchars_decode($value["title"])  . '">
									<div class="dive">
									<h6 style="margin:5px;" class="cut-text">' .htmlspecialchars_decode($value["title"])  . '</h6>
									</div>
									<div>
									<img src="' . $value["img"] . '" style="width: 200px!important; height:300px!important; display: block;">
									</div>
									</a>';
								}
								$ecs = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $ecs;
								$ecs .= '</section>';

								echo $ecs;
								
								echo '</div></div></div>
												<footer class="entry-footer"></footer>
											</div>';

		}
		
		require_once("template/footer.phtml");
		$conn->close();
		$conn2->close();
		$conn3->close();
		exit();
	}
	/****************************************************************** */
	if(!isset($_GET["s"]))
	{// Kezdőlap
		$ecs = "";
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
		}
		$ecs = '<section class="elementor-section elementor-top-section elementor-element elementor-element-1091d31 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-element_type="section" style=" display: block;vertical-align: top;text-align: center;">' . $ecs;
		$ecs .= '</section>';


		$title = "Kezdőlap";
		require_once("template/header.phtml");
		require_once("template/home.phtml");
		require_once("template/footer.phtml");
		$conn->close();
		$conn2->close();
		$conn3->close();
		exit();
  }
	/****************************************************************** */


die;
// Ez még hibás
function episodelist($conn, $q)
{
	$Episodelist = "";
	$uploader2 = Select($conn, 'SELECT `secret_message`, `link_id` FROM `episodelist_links` WHERE `episode_id` = ' . $q . ' LIMIT 1');

	if(!empty($uploader2[0]["title"]))
		foreach($q as $key => $value)
		{
			$uploader = Select($conn, 'SELECT `secret_message`, `link_id` FROM `episodelist_links` WHERE `episodelist_links` = ' . $value["link_id"] . ' LIMIT 1');

			if(!empty($uploader2[0]["title"]))
			{
				$Episodelist .= '<div><h2 class="bdp-post-title">';
				$Episodelist .= '<a href="'.$uploader2[0]["link"].'">'.$uploader2[0]["title"].'</a>';
				$Episodelist .= '</h2></div>';
			}
			unset($uploader2);
				
		}
	return $Episodelist;
}

function getEpisodelist($conn, $q)
{
	$Episodelist = "";
	foreach($q as $key => $value)
	{
		$uploader2 = Select($conn, 'SELECT `title`, `link` FROM `episodelist` WHERE `id` = ' . $value["episodelist_id"] . ' LIMIT 1');
		
		if(!empty($uploader2[0]["title"]))
		{
			$Episodelist .= '<div><h2 class="bdp-post-title">';
			$Episodelist .= '<a href="'.$uploader2[0]["link"].'">'.$uploader2[0]["title"].'</a>';
			$Episodelist .= '</h2></div>';
		}
		unset($uploader2);
			
	}
	return $Episodelist;
}
function getEpisodelist2($conn, $q, $w)
{
	$Episodelist = "";
	$q = json_decode($q, true);
	if(is_array($q["links"]))
		foreach($q["links"] as $id)
		{
			if($w==true)
				$uploader2 = Select($conn, 'SELECT `title`, `link` FROM `episodelist` WHERE `id` = ' . $id . ' LIMIT 1');
			else
				$uploader2 = Select($conn, 'SELECT `title`, `link` FROM `datasheet` WHERE `id` = ' . $id . ' LIMIT 1');
			if(!empty($uploader2[0]["title"]))
			{
				$Episodelist .= '<div><h2 class="bdp-post-title">';
				$Episodelist .= '<a href="https://animem.org/'.$uploader2[0]["link"].'">'.$uploader2[0]["title"].'</a>';
				$Episodelist .= '</h2></div>';
			}
			unset($uploader2);
				
		}
	return $Episodelist;
}
function getUploaders($conn, $q)
{
	$uploadersList = "";
	foreach($q as $key => $value)
	{
		if(!empty($value["fansub1"]))
			$uploader2[] = Select($conn, 'SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub1"] . ' LIMIT 1');
		if(!empty($value["fansub2"]))
			$uploader2[] = Select($conn, 'SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub2"] . ' LIMIT 1');
		if(!empty($value["fansub3"]))
			$uploader2[] = Select($conn, 'SELECT `id`, `name`, `code` FROM `uploaders` WHERE `id` = ' . $value["fansub3"] . ' LIMIT 1');
		
		if(count($uploader2) == 1)
		{
			if(empty($uploadersList))
			{
				$uploadersList = '[<a href="https://animem.org/fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a>]';
			}else
			{
				$uploadersList .= ', [<a href="https://animem.org/fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a>]';
			}
	
		}elseif(count($uploader2) == 2)
		{
			if(empty($uploadersList))
			{
				$uploadersList = '[<a href="https://animem.org/fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="https://animem.org/fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a>]';
			}else
			{
				$uploadersList .= ', [<a href="https://animem.org/fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="https://animem.org/fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a>]';
			}
		}elseif(count($uploader2) == 3)
		{
			if(empty($uploadersList))
			{
				$uploadersList = '[<a href="https://animem.org/fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="https://animem.org/fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a> & <a href="https://animem.org/fansub/?' . $uploader2[2][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[2][0]["name"] . '</a>]';
			}else
			{
				$uploadersList .= ', [<a href="https://animem.org/fansub/?' . $uploader2[0][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[0][0]["name"] . '</a> & <a href="https://animem.org/fansub/?' . $uploader2[1][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[1][0]["name"] . '</a> & <a href="https://animem.org/fansub/?' . $uploader2[2][0]["id"] . '" target="_blank" class="bluuuu">' . $uploader2[2][0]["name"] . '</a>]';
			}
		}
		unset($uploader2);
			
	}
	return $uploadersList;
}
function myanimelist($conn, $q)
{
  $data["anime"] = 
  Select($conn, "SELECT
		`mal__anime`.`id`,
		`mal__anime`.`title`,
		`mal__anime`.`english`,
		`mal__anime`.`synonyms`,
		`mal__anime`.`japanese`,
		`mal__anime`.`episodes`,
		`mal__anime`.`score`,
		`mal__anime`.`members`,
		`mal__anime`.`ranked`,
		`mal__anime`.`favorites`,
		`mal__anime`.`preview`,
		`mal__anime`.`synopsis`,
		`mal__anime`.`img`,
		`mal__anime`.`popularity`,
		`mal__anime`.`aired`,
		`mal__anime`.`status`,
		`mal__anime`.`description`,
		`mal__anime`.`create_date`,
		`mal__age_rating`.`name` AS `age_rating`,
		`mal__age_rating`.`name` AS `age_rating`,
		CONCAT(
				'https://myanimelist.net/anime/',
				`mal__anime`.`mal_id`,
				'/',
						REPLACE
								(
				`mal__anime`.`title`,' ','_')
		) AS link,
		RIGHT(
				`mal__anime`.`premiered`,
				LOCATE(
						' ',
						REVERSE(`mal__anime`.`premiered`)
				) - 1
		) AS 'year',
		REPLACE
		(
		REPLACE
				(
				REPLACE
						(
						REPLACE
								(
										LEFT(
												`mal__anime`.`premiered`,
												LOCATE(' ', `mal__anime`.`premiered`) - 1
										),
										'Winter',
										'Tél'
								),
								'Spring',
								'Tavasz'
				),
				'Summer',
				'Nyár'
		),
		'Fall',
		'Ősz'
		) AS 'season',
		`mal__type`.`name` AS type,
		`mal__source`.`name` AS source
		FROM
		mal__anime
		INNER JOIN `mal__age_rating` ON `mal__age_rating`.`id` = `mal__anime`.`age_rating_id`
		INNER JOIN `mal__source` ON `mal__source`.`id` = `mal__anime`.`source_id`
		INNER JOIN `mal__type` ON `mal__type`.`id` = `mal__anime`.`type_id`
		WHERE
		`mal__anime`.`mal_id` = " . $q . " LIMIT 1"
  );
	
	return $data["anime"][0];

}


