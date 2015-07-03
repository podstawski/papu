<?php

require_once __DIR__.'/../rest/Bootstrap.php';

function myshutdown()
{
    @Bootstrap::$main->closeConn();
}

function mydie($txt,$h1='Info',$print_r=true)
{
    @header('Content-type: text/html; charset=utf-8');
    
    if ($h1) echo "<h1>$h1</h1>";
    die('<pre>' . ($print_r ? print_r($txt, 1) : var_export($txt,1)));
}

mb_internal_encoding('utf8');



if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
{
    $config_file=str_replace('~','-',$_SERVER['APPLICATION_ID']);
}
else
{
    $config_file=strtolower($_SERVER['HTTP_HOST']);
}

$f=__DIR__.'/../rest/config/application.json';
$config=json_decode(file_get_contents($f),true);


$f=__DIR__.'/../rest/config/'.$config_file.'.json';
if (file_exists($f))
{
    $config=array_merge($config,json_decode(file_get_contents($f),true));
}
else
{
    Bootstrap::result(array('missing file'=>$f),1);
}

register_shutdown_function('myshutdown');
$bootstrap = new Bootstrap($config);
$bootstrap->admin=true;

header('Content-type: text/html; charset=utf-8');