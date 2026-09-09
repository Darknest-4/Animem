<!DOCTYPE html>
<html>

<head>

	<!--
		<link rel="stylesheet" href="https://cdn.plyr.io/3.5.7/plyr.css" />
		<script src="https://cdn.plyr.io/3.5.7/plyr.js"></script>

		<link rel="stylesheet" href="https://cdn.plyr.io/3.6.3/plyr.css">
		<script src="https://cdn.plyr.io/3.6.3/plyr.js"></script>

		<script src="https://cdn.plyr.io/3.6.8/plyr.polyfilled.js"></script>
		<script src="https://cdn.plyr.io/3.5.7-beta.0/demo.js"></script>
	-->

	<link rel="stylesheet" href="https://cdn.plyr.io/3.6.3/plyr.css">
	<script src="https://cdn.plyr.io/3.6.3/plyr.js"></script>

	<style>
		@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

		* {
			margin: 0;
			padding: 0;
			border: 0;
			box-sizing: border-box;
			outline: none;
		}

		html {
			font-family: 'Poppins', sans-serif;
		}

		body {
			background-color: #23272a;
		}

#player {
			max-height: 486px;
			width: 100%;
		}

		?>.click-me {
position: absolute;
bottom: 45px;
right: 20px;
z-index: 10;
background-color: blue;
content: 'click me';
padding: 5px;
border-radius: 10px;
		}

		/*
		.plyr__video-wrapper, .plyr__poster {
			background:url(/Assets/uploads/fontos_kepek/180.jpg) 0.5% 3% no-repeat,linear-gradient(rgba(0,0,0,0),rgba(0,0,0,.5))!important;
			background-size:100px auto,auto!important
		 }.video-description {
			 z-index: -110000;
		background: transparent;
		position: absolute;
		top: -72%;
		right: 0;
		bottom: 0;
		left: -55%;
		display: -webkit-box;
		display: -ms-flexbox;
		display: flex;
		-webkit-box-align: center;
		-ms-flex-align: center;
		align-items: center;
		-webkit-box-pack: center;
		-ms-flex-pack: center;
		justify-content: center;			background:url(/Assets/uploads/fontos_kepek/180.jpg) 0.5% 3% no-repeat,linear-gradient(rgba(0,0,0,0),rgba(0,0,0,.5))!important;
		background-size:100px auto,auto!important
		}
		*/
		.video {
			display: inline-block;
			float: left;
			background: #000000b3;
			padding: 0;
			text-align: center;
			width: 100%;
			height: 486px;
			position: relative;
		}

		.plyr {
			height: 100%;
			width: 100%;
		}

		.plyr__video-wrapper {
			height: 100%;
		}

		.plyr__video-wrapper iframe {
			width: 100%;
			height: 100%;
		}
	</style>
</head>

<body>
	<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('display_startup_errors', 1);
	error_reporting(0);
	function input_encode($str)
	{
		return htmlspecialchars($str);
	}
	defined('DS') || define('DS', DIRECTORY_SEPARATOR);

	$config["HttpReferers"] = [
		"https://animem.org/",
		"http://animem.org/",
		"https://www.animesekaiteam.nhely.hu/",
		"http://www.animesekaiteam.nhely.hu/",
		"https://animesekaiteam.nhely.hu/",
		"http://animesekaiteam.nhely.hu/"
	];
	$config["Error"] = [
		"XZ100" => "Hiányzó HttpReferer. Az oldalt csak iframe tagekben lehet használni.",
		"XZ101" => "Hiányzó _GET.",
		"XZ104" => "Hiányzó HttpSecFetchDest. Az oldalt csak iframe tagekben lehet használni.",
		"XZ105" => "A HttpReferer nem szerepel a listán."
	];
	//$HttpReferer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : exit($config["Error"]["XZ100"]);
	//if(in_array($HttpReferer, $config["HttpReferers"])){} else exit($config["Error"]["XZ105"]);
	 $URL = (isset($_GET["u"])) ? "https://indavideo.hu/video/" . input_encode($_GET["u"]) : exit($config["Error"]["XZ101"]);

	$sendlink = $URL;

	$http = "https://";
	$embed = "embed.indavideo.hu/player/video/";
	$inda = "indavideo.hu/video/";
	$amf = "https://amfphp.indavideo.hu/SYm0json.php/player.playerHandler.getVideoData/";
	$nxu = "https://indavideo.nxu.hu/url";
	$exit = '<iframe style="position: absolute; top: 0; left: 0; bottom: 0; border:0;right: 0; width: 100%; height: 100%;" src="https://animem.org/ntvep.php"></iframe>';

	$URL = (count(explode($embed, $URL)) == 2) ? explode($embed, $URL)[1] : $URL;
	$URL = (count(explode($inda, $URL)) == 2) ? explode($inda, $URL)[1] : $URL;


	$amf = json_decode(file_get_contents($amf . $URL), true);

	$URL = (isset($amf["data"]) && isset($amf["data"]["hash"])) ? $http . $inda . $amf["data"]["url_title"] : "https://animem.org/ntvep.php";
	$POSTER = (isset($amf["data"]["video_img"])) ? $amf["data"]["video_img"] : exit($exit);
	$postdata = http_build_query(
		[
			'url' => $URL
		]
	);

	$opts = [
		'http' =>
		[
			'method'  => 'POST',
			'header'  => 'Content-Type: application/x-www-form-urlencoded',
			'content' => $postdata
		]
	];
	$context  = stream_context_create($opts);
	$js = (file_get_contents("https://indavideo.nxu.hu/url", false, $context)) ? json_decode(file_get_contents($nxu, false, $context), true) : json_decode(json_encode(array("error" => false)), true);
	if (isset($js['error']) || (!isset($js['resolutions']['360']) && !isset($js['url'])))
		$js['resolutions']['360'] = $amf["data"]["video_file"] . "&token=" . $amf["data"]["filesh"][360];
	// exit($exit);
	$link720 = (isset($js['resolutions']['720'])) ? $js['resolutions']['720'] : "";
	$link360 = (isset($js['resolutions']['360'])) ? $js['resolutions']['360'] : "";



	?>

	<div id="wrapper" class="video">
		<?php
		$tesco = (isset($_GET["plyr"])) ? input_encode($_GET["plyr"]) : "";
		if (!empty($tesco) && $tesco == "no")
		{
		?>
			<div class="plyr">
				<div class="plyr__video-wrapper">
				<?php } ?>
				<video crossorigin="" playsinline="" poster="<?= $POSTER; ?>" id="player" src="<?= (!empty($link720)) ? $link720 : $link360; ?>" controls>


					<?php
					$tesco = (isset($_GET["plyr"])) ? input_encode($_GET["plyr"]) : "";
					if (!empty($tesco) && $tesco == "no")
					{
					?>
				</div>
			</div>
		<?php } ?>

		<?php
		$ricz = (isset($_GET["hd"])) ? input_encode($_GET["hd"]) : "";
		if (!empty($ricz) && $ricz == "no")
		{
		}
		else
		{
		?>

			<?php if (!empty($link720))
			{ ?>
				<source src="<?= $link720; ?>" type="video/mp4" size="720" />
				<a href="<?= $link720; ?>" download>Download 720p</a>

			<?php } ?>
		<?php } ?>




		<source src="<?= $link360; ?>" type="video/mp4" size="360" />
		<a href="<?= $link360; ?>" download>Download 360p</a>
		</video>
		<div class="video-description">
		</div>
	</div>
	<input data-plyr="seek" class="plyr__progress--seek" type="range" min="0" max="100" step="0.01" value="0" autocomplete="on" role="slider" aria-label="Seek" id="plyr-seek-{id}" aria-valuemin="0" style="user-select: none; touch-action: manipulation; display:none">


	<script>

		function my_function(){}
		const sleep = (delay) => new Promise((resolve) => setTimeout(resolve, delay))

		const repeatedGreetings = async () => {
			// await sleep(1000)
			//await sleep(1000 * 60 * 15)
			//sendlink("<?= $sendlink; ?>");
		}

		repeatedGreetings();

		function sendlink(str) {
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					console.log(this);
				}
			}
			xmlhttp.open("GET", "https://www.animem.org/counter.php?q=" + str + "&set=happy", true);
			xmlhttp.send();
		}
	</script>
	<?php
	$tesco = (isset($_GET["plyr"])) ? input_encode($_GET["plyr"]) : "";
	if (!empty($tesco) && $tesco == "no")
	{
	}
	else
	{
	?>
		<script>
				// This is the bare minimum JavaScript. You can opt to pass no arguments to setup.
				const controls = [
					'play-large', // The large play button in the center
					'restart', // Restart playback
					'rewind', // Rewind by the seek time (default 10 seconds)
					'play', // Play/pause playback
					'fast-forward', // Fast forward by the seek time (default 10 seconds)
					'progress', // The progress bar and scrubber for playback and buffering
					'current-time', // The current time of playback
					'duration', // The full duration of the media
					'mute', // Toggle mute
					'volume', // Volume control
					'captions', // Toggle captions
					'settings', // Settings menu
					'download', // Show a download button with a link to either the current source or a custom URL you specify in your options
					'fullscreen' // Toggle fullscreen
				];
				const supported = Plyr.supported("video", "html5", true);
				const player = new Plyr('#player', {
					controls,
					
  enabled: !/webOS|iPhone|iPad|iPod/i.test(navigator.userAgent)
, seekTime:5});
player.rewind(5);
player.forward(5);
				// Expose
				window.player = player;
		</script>

	<?php
	}
	?>