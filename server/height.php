<?php
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer=strtolower($_SERVER['HTTP_REFERER']);
        $pos=strpos($referer,'//');
        $referer_ok=substr($referer,0,$pos+2);
        $referer=substr($referer,$pos+2);
        $pos=strpos($referer,'/');
        $referer_ok.=substr($referer,0,$pos);
        

        Header('Access-Control-Allow-Origin: '.$referer_ok);
        Header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        Header('Access-Control-Allow-Methods: GET');
        header("Access-Control-Allow-Credentials: true");

    }

    session_start();
    
    $height=0;
    if (isset($_GET['height']) && isset($_GET['url']))
    {
        $_SESSION['jh'][$_GET['url']]=$_GET['height'];
    }
    
    if (isset($_GET['url']) && isset( $_SESSION['jh'][$_GET['url']])) $height=$_SESSION['jh'][$_GET['url']];
    
    echo $height;