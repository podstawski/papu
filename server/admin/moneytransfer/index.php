<?php
require_once __DIR__.'/../base.php';
require_once __DIR__.'/../../rest/models/eventModel.php';
require_once __DIR__.'/../../rest/models/userModel.php';
require_once __DIR__.'/../../rest/models/guestModel.php';
require_once __DIR__.'/../../rest/controllers/eventController.php';

require_once __DIR__.'/../lib/class/fakturownia.php';

$title='Money transfers';
$menu='moneytransfer';
include __DIR__.'/../head.php';

echo "Start analyzing ... <br/>";

$event=new eventModel();
$user=new userModel();
$guest=new guestModel();
$fakturownia=new fakturownia();
$events=$event->getEventsToTransferMoney()?:[];
$users=[];
$transfers=[];

Bootstrap::$main->human_datetime_format();

foreach ($events AS &$e) {
    if (!$e['persons']) continue;
    if (!isset($users[$e['user']])) $users[$e['user']]=$user->get($e['user'],true);
    $e['user']=$users[$e['user']];
    Bootstrap::$main->session('time_delta',$e['user']['delta']);
    $e['event']=$event->get($e['id']);
    $e['total']=$e['persons']*$e['event']['host_price'];
    
    switch (strtoupper($e['country']))
    {
        case 'PL':
            if (!isset($transfers['pl'])) {
                require_once __DIR__.'/../lib/class/bzwbk.php';
                $transfers['pl']=new bzwbk();
            }
            $transfers['pl']->add($e);
            
            
            $guests=$guest->select(['event'=>$e['id'],'d_payment'=>['>',0],'d_cancel'=>null,'guest_price'=>['>',0]]);
            $e['commision']=[];
            foreach ($guests AS $g) {
                $commision=$g['guest_price']-$g['host_price'];
                if (!isset($e['commision'][$commision])) $e['commision'][$commision]=0;
                $e['commision'][$commision]+=$g['persons'];
            }

            $user->get($e['user']['id']);
            $user->_payment_id=$fakturownia->invoice($e);
            if ($user->_payment_id) $user->save();
            break;
    }
    
}
foreach ($transfers AS $country=>$t) {
    $f=$t->complete('money-transfer/'.$country.'/'.date('Y').'/'.date('m').'/day-'.date('d').'.txt');
    //mydie($f,$country);
    if ($f) Tools::observe('money-transfer-'.$country,[],[['transfer.txt'=>$f]]);
}

include __DIR__.'/../foot.php';