<?php
require_once __DIR__.'/../base.php';

require_once __DIR__.'/../../rest/controllers/userController.php';

$user=new userController();
Bootstrap::$main->human_datetime_format();
$users=$user->ranking();
foreach ($users AS &$u)
{
    unset($u['photo']);
    unset($u['title']);
    unset($u['cover']);
}

header('Content-type: text/csv; charset=utf8');
header('Content-disposition: attachment;filename=jemyrazem-users-'.date('Ymd').'.csv');



echo implode(',',array_keys($users[0]))."\r\n";
foreach ($users AS &$u)
{
    echo '"'.implode('","',$u).'"'."\r\n";
}