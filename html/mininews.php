<?php
//$servername = "localhost";
//$username = "rlight";
//$password = "nF79Fn3FuMZGK7kMK3MydU9cBKk9eVZe";
//$database = "animem";

require_once("../Config/loadConfig.php");

$siteConfig = DbConfig::getSiteConfig();
if (!defined("BASEURL")) {
	define("BASEURL", "https://".$siteConfig['domain']."/");
}

$dbConfig = DbConfig::getDbConfig();

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error)
{
	die("Connection failed: " . $conn->connect_error);
}

/****************************************************************** */ /****************************************************************** */
$g = gpi();
if (isset($g[0]) && $g[0] == "rss")
{



	header("Content-type: text/xml");
?>

	<rss version='2.0'>
		<channel>

			<title>Animem.org - MiniNews</title>
			<link><?= BASEURL ?>mininews.php/rss/</link>
			<description>Animem.org az animék országa. Az animék országában szinte bármilyen animét megtalálhatsz. Legyen az szinkronos vagy feliratos. Mindezt a lehető legjobb minőségben.</description>
			<language>hu</language>
			<image>
				<url><?= BASEURL ?>mininews.php/rss/themes/images/info/rss-icon.png</url>
				<title>Animem.org - MiniNews</title>
				<link><?= BASEURL ?>mininews.php/rss/</link>
			</image>


			<?php

//			$sql = "SELECT CONCAT(`mal__anime`.`id`, '/',  (REPLACE(`mal__anime`.`title`, ' ', '-'))) AS 'link_url', REPLACE(`mininews`.`link_description`, '{{TITLE}}', `mal__anime`.`title`) AS 'link_description', DATE_FORMAT(`mininews`.`link_updated`, '%Y. %m. %d. %H:%i' ) as 'link_updated'
//			FROM `mininews` INNER JOIN `mal__anime` ON `mal__anime`.`id` = `mininews`.`link_url` ORDER BY `link_updated` DESC limit 50;";
			$sql = "SELECT CONCAT(`datasheet`.`id`, '/',  (REPLACE(`datasheet`.`title`, ' ', '-'))) AS 'link_url', REPLACE(`mininews`.`link_description`, '{{TITLE}}', `datasheet`.`title`) AS 'link_description', DATE_FORMAT(`mininews`.`link_updated`, '%Y. %m. %d. %H:%i' ) as 'link_updated'
							FROM `mininews` INNER JOIN `datasheet` ON `datasheet`.`id` = `mininews`.`link_url` ORDER BY `link_updated` DESC limit 50;";
			$result = $conn->query($sql);
			if ($result->num_rows > 0)
			{
				// output data of each row
				while ($row = $result->fetch_assoc())
				{
			?>

					<item>
						<title><?= $row["link_description"]; ?></title>
						<link><?= BASEURL ?>DataSheet/<?= $row["link_url"]; ?></link>
						<guid isPermaLink='true'><?= BASEURL ?>DataSheet/<?= $row["link_url"]; ?></guid>
						<description><?= $row["link_description"]; ?></description>
						<pubDate><?= $row["link_updated"]; ?></pubDate>
					</item>
				<?php
				}



				?>

		</channel>
	</rss>
<?php
			}
		}
		else
		{


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

			$template = findfilefromdir("Assets/template");
			$template[] = $template[0];
			unset($template[0]);

?>

<html>

<head>
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

	<link href="<?= BASEURL ?>Assets/def.css?=45575" rel="stylesheet" type="text/css">
	<link href="<?= BASEURL ?>Assets/template/Superhero.min.css" rel="stylesheet" />
	<title>animem.org | MiniNews</title>
	<style>
		body {
			scrollbar-color: #333 rgba(0, 0, 0, 0.3);
			scrollbar-width: thin;
			overflow-x: hidden;
			overflow-y: auto;
		}
		body::-webkit-scrollbar {
			width: 10px;
			background-color: #ccb3b3;
		}
		body::-webkit-scrollbar-thumb {
			-webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, .3);
			background-color: #333;
			border: solid 1px #8184ad;
		}
		body::-webkit-scrollbar-track {
			-webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, .3);
			background-color: #ccb3b3;
		}
	</style>
</head>

<body>


	<?php

//$sql = "SELECT CONCAT(`mal__anime`.`id`, '/',  (REPLACE(`mal__anime`.`title`, ' ', '-'))) AS 'link_url', REPLACE(`mininews`.`link_description`, '{{TITLE}}', `mal__anime`.`title`) AS 'link_description', DATE_FORMAT(`mininews`.`link_updated`, '%Y. %m. %d. %H:%i' ) as 'link_updated'
//FROM `mininews` INNER JOIN `mal__anime` ON `mal__anime`.`id` = `mininews`.`link_url` ORDER BY `link_updated` DESC limit 50;";
	$sql = "SELECT CONCAT(`datasheet`.`id`, '/',  (REPLACE(`datasheet`.`title`, ' ', '-'))) AS 'link_url', REPLACE(`mininews`.`link_description`, '{{TITLE}}', `datasheet`.`title`) AS 'link_description', DATE_FORMAT(`mininews`.`link_updated`, '%Y. %m. %d. %H:%i' ) as 'link_updated'
					FROM `mininews` INNER JOIN `datasheet` ON `datasheet`.`id` = `mininews`.`link_url` ORDER BY `link_updated` DESC limit 50;";
			$result = $conn->query($sql);
	?>
	<div class="row bg-secondary opacity-50">
		<div class="col-12">
			<?php
			if ($result->num_rows > 0)
			{
				// output data of each row
				while ($row = $result->fetch_assoc())
				{
			?>

					<div class="row border-bottom">
						<div class="col-12 text-center">
							<h5 class="text-white"><?= $row["link_updated"]; ?>:</h5>
							<span class="px-2">
								<a class="text-decoration-none text-warning" href="<?= BASEURL ?>DataSheet/<?= $row["link_url"]; ?>" target="_parent" title="<?= htmlspecialchars_decode($row["link_description"]); ?>"><?= htmlspecialchars_decode($row["link_description"]); ?></a>
							</span>
						</div>
					</div>
				<?php

				}
			}
			else
			{
				?>
			<?php
			}

			?>
		</div>
	</div>
</body>

</html>
<?php }

		$conn->close();
		exit();



		function gpi($p = NULL)
		{
			$explode = (isset($_SERVER['PATH_INFO'])) ? explode("/", substr($_SERVER['PATH_INFO'], 1)) : array();
			//$explode = (isset($_SERVER['REDIRECT_URL'])) ? explode("/", substr($_SERVER['REDIRECT_URL'], 1)) : array();
			return (isset($explode[$p])) ? $explode[$p] : $explode;
		}

		function print_c($var)
		{
			if (is_numeric($var) || is_string($var))
				echo "\n" . $var . "\n";
			elseif (is_array($var) || is_object($var))
			{
				echo "\n";
				print_r($var);
				echo "\n";
			}
			else
			{
				echo "\n";
				var_dump($var);
				echo "\n";
			}
		}
?>
