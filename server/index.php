<?php
$pass='';
if (strstr($_SERVER['HTTP_HOST'],'www')) $pass=['notsofast'];
//if (strstr($_SERVER['HTTP_HOST'],'beta')) $pass=['papu2015'];


if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
	if (!strstr($_SERVER['HTTP_HOST'],'beta') && !strstr($_SERVER['HTTP_HOST'],'epapu')) {
		if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS']!='on') {
			header("HTTP/1.1 301 Moved Permanently");
			Header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			die();
		}
	}

if (isset($_GET['embedded']))
{
	if ($_GET['embedded']=='js')
	{
		Header('Content-type: text/javascript');
		$req='https://'.$_SERVER['HTTP_HOST'].str_replace('embedded=js','embedded',$_SERVER['REQUEST_URI']);
		$random=rand(10000,99999).time();

		session_start();
		
		$_SESSION['jh'][$req]=0;
		echo "var jemyrazem_url_$random='$req'\nvar jemyrazem_random_$random='$random';\n";
		
		$height=str_replace('index.php','height.php',$_SERVER['SCRIPT_NAME']).'?url='.urlencode($req);
		if ($height[0]=='/') $height=substr($height,1);
		echo "var jemyrazem_height_$random='https://".$_SERVER['HTTP_HOST'].'/'.$height."';\n";
		
		$js=file_get_contents(__DIR__.'/ebmedded.js');
		$js=str_replace('_RANDOM_',$random,$js);
		
		echo $js;
	}
	else
	{
		include __DIR__.'/html.php';
	}
	
	die();
}

foreach (['facebook','google','twitterbot','pinterest','msnbot'] AS $agent)
	if (isset($_SERVER['HTTP_USER_AGENT']) && strstr(strtolower($_SERVER['HTTP_USER_AGENT']),$agent)) {
		include __DIR__.'/html.php';
		die();
	}
	
include __DIR__.'/rest/library/backend/include/all.php';
if ($pass) simple_pass($pass);

autoload([__DIR__.'/rest/class',__DIR__.'/rest/models',__DIR__.'/rest/controllers']);
$config=json_config(__DIR__.'/rest/config/application.json');
$bootstrap = new Bootstrap($config);
$geo=Tools::geoip();
$locale='i18n/angular-locale_'.$bootstrap->lang.'-'.strtolower($geo['location']['country']).'.js';

if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine') && substr($_SERVER['REQUEST_URI'],0,6)!='/test/') 
{
	$html=file_get_contents(__DIR__.'/index.html');
	$html=str_replace('bower_components/angular-i18n/angular-locale_en-us.js',$locale,$html);
	$html=str_replace('<title>epapu</title>','<title>'.Tools::translate('page-title').'</title>',$html);
	
	die($html);
}
else
{
	include (__DIR__.'/test.php');
}
