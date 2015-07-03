<?php
$_SERVER['jemyrazem_start']=microtime(true);

if (isset($_SERVER['HTTP_REFERER'])) {
    $referer=strtolower($_SERVER['HTTP_REFERER']);
    $pos=strpos($referer,'//');
    $referer_ok=substr($referer,0,$pos+2);
    $referer=substr($referer,$pos+2);
    $pos=strpos($referer,'/');
    $referer_ok.=substr($referer,0,$pos);
    
    if (preg_match('/webkameleon.com/i',$referer_ok)) {
        Header('Access-Control-Allow-Origin: '.$referer_ok);
        Header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        Header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE');
        header("Access-Control-Allow-Credentials: true");
    }
}

require_once __DIR__.'/Bootstrap.php';


function mydie($txt,$h1='Info',$print_r=true)
{
    @header('Content-type: text/html; charset=utf-8');
    
    if ($h1) echo "<h1>$h1</h1>";
    die('<pre>' . ($print_r ? print_r($txt, 1) : var_export($txt,1)));
}
function myshutdown()
{
    @Bootstrap::$main->closeConn();
}

if ( in_array( strtolower( ini_get( 'magic_quotes_gpc' ) ), array( '1', 'on' ) ) )
{
    $_POST = array_map( 'stripslashes', $_POST );
    $_GET = array_map( 'stripslashes', $_GET );
    $_COOKIE = array_map( 'stripslashes', $_COOKIE );
    
    ini_set('magic_quotes_gpc', 0);
}
require_once __DIR__.'/class/Session.php';
session_start();
mb_internal_encoding('utf8');

if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
{
    $config_file=str_replace('~','-',$_SERVER['APPLICATION_ID']);
}
else
{
    $config_file=strtolower($_SERVER['HTTP_HOST']);
}

$f=__DIR__.'/config/application.json';
$config=json_decode(file_get_contents($f),true);


$f=__DIR__.'/config/'.$config_file.'.json';
if (file_exists($f))
{
    $config=array_merge($config,json_decode(file_get_contents($f),true));
}
else
{
    Bootstrap::result(array('missing file'=>$f),1);
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
    if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
        $method = 'DELETE';
    } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
        $method = 'PUT';
    }
}

register_shutdown_function('myshutdown');
$bootstrap = new Bootstrap($config);
$result=$bootstrap->run(strtolower($method));

