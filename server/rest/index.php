<?php
$_SERVER['jemyrazem_start']=microtime(true);
$_SERVER['backend_start']=microtime(true);
include __DIR__.'/library/backend/include/all.php';
allow_origin('webkameleon.com');
autoload([__DIR__.'/class',__DIR__.'/models',__DIR__.'/controllers']);
$config=json_config(__DIR__.'/config/application.json');
$method=http_method();


if ( in_array( strtolower( ini_get( 'magic_quotes_gpc' ) ), array( '1', 'on' ) ) )
{
    $_POST = array_map( 'stripslashes', $_POST );
    $_GET = array_map( 'stripslashes', $_GET );
    $_COOKIE = array_map( 'stripslashes', $_COOKIE );
    
    ini_set('magic_quotes_gpc', 0);
}
function myshutdown()
{
    @Bootstrap::$main->closeConn();
}


register_shutdown_function('myshutdown');

ini_set('display_errors',1);
$bootstrap = new Bootstrap($config);

$result=$bootstrap->run(strtolower($method));

