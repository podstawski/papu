<?php
require_once __DIR__.'/../base.php';

require_once __DIR__.'/../../rest/models/eventModel.php';

$event=new eventModel();
$event->purge();


