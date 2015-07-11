<?php

$_SERVER['jemyrazem_start']=microtime(true);
$_SERVER['backend_start']=microtime(true);
include __DIR__.'/../rest/library/backend/include/all.php';

function myshutdown()
{
    @Bootstrap::$main->closeConn();
}

mb_internal_encoding('utf8');

autoload([__DIR__.'/../rest/class',__DIR__.'/../rest/models',__DIR__.'/../rest/controllers']);
$config=json_config(__DIR__.'/../rest/config/application.json');

register_shutdown_function('myshutdown');
$bootstrap = new Bootstrap($config);
$bootstrap->admin=true;

header('Content-type: text/html; charset=utf-8');
