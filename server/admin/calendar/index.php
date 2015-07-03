<?php
require_once __DIR__.'/../base.php';

require_once __DIR__.'/../../rest/models/userModel.php';
require_once __DIR__.'/calendarController.php';

$title='Calendar';
$menu='calendar';
include __DIR__.'/../head.php';

$user=new userModel();


foreach ($user->ical_users()?:[] AS $u)
{
    $calendar=new calendarController($u['id'],$u);
    $calendar->processUserEvents();
}

include __DIR__.'/../foot.php';