<?php
require_once __DIR__.'/../base.php';
require_once __DIR__.'/../../rest/models/eventModel.php';
require_once __DIR__.'/../../rest/controllers/eventController.php';

$event=new eventModel();

$events=$event->getEventsAfterDeadlineToCancel();

if (is_array($events)) foreach ($events AS $id) {
    
    $guests=$event->getGuests($id);
    if (!$guests || !count($guests)) continue;
    
    $e=$event->get($id);
    Bootstrap::$main->user['id']=$e['user'];
    
    $eventController=new eventController($id,[]);
    
    $eventController->delete(true);
}