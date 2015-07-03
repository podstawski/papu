<?php
require_once __DIR__.'/../base.php';
require_once __DIR__.'/../../rest/models/eventModel.php';
require_once __DIR__.'/../../rest/models/imageModel.php';
require_once __DIR__.'/../../rest/models/userModel.php';


$event=new eventModel();
$image=new imageModel();
$user=new userModel();


$events=$event->getEventsToPay(24*3600)?:[];
	    
Bootstrap::$main->human_datetime_format();

foreach($events AS $e)
{
    $e['img']=$image->get($e['img']);
    $data=['guest'=>$user->get($e['guest']),'host'=>$user->get($e['user'])];
    Bootstrap::$main->session('time_delta',$data['host']['delta']);
    $data['event']=$event->get($e['id']);
    $data['event']['img']=$e['img'];
    
    echo $data['guest']['firstname'].' '.$data['guest']['lastname'].' ('.$data['guest']['email'].'), '.$data['guest']['lang'];
    echo ' &raquo; ';
    echo $data['event']['name'].' ('.$data['event']['event_start'].')';
    echo '<br/>';

    Tools::observe('payment-reminder',$data);

}
