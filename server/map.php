<?php

	$lat=$lng=0;


	$uri=explode('/',$_SERVER['REQUEST_URI']);
	$g=explode(',',end($uri));
	if (isset($g[1]))
	{
		$lat=$g[0];
			$lng=$g[1];
	}

	$url="https://maps.googleapis.com/maps/api/staticmap?center=$lat,$lng&zoom=14&size=800x300&maptype=roadmap&markers=color:red%7Clabel:A%7C$lat,$lng";

	Header('Content-type: image/png'); die(file_get_contents($url));
	//die($url);
	header("Location: $url");
