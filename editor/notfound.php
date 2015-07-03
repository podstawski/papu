<?php

	$url=substr(str_replace(dirname($_SERVER['SCRIPT_NAME']),'',$_SERVER['REQUEST_URI']),1);
	$url=str_replace('%7Bapp_root%7D','https://www.jemyrazem.pl/',$url);
	
	$url=str_replace('%7Bevent.img.thumbnail%7D','https://lh6.ggpht.com/x4bVqL2iLAHxiOCXqOjRtnzU3WFvTmhTRlXr5dUUl0oPquSPqrJwgGO7NcrizpJV7qH4KVrM9b3MWyaLv97L=s1140',$url);
	$url=str_replace('%7Bhost.photo%7D','https://lh5.ggpht.com/2Vfo7Bi23Hr_r2wjv5XIc1xB4vPJ_Z6sH3I4dwN6i0FRr56KlgKfn_ecoVwwmAyKZBtlnXEw-itQxcxTqlM=s350-c',$url);
	
	$url=str_replace('%7Bevent.lat%7D','52.413862',$url);
	$url=str_replace('%7Bevent.lng%7D','16.9182258',$url);
	
	
	//die($url);
	
	header('Location: '.$url);
