<?php
require_once __DIR__.'/../base.php';
require_once __DIR__.'/../../rest/models/guestModel.php';
require_once __DIR__.'/../../rest/models/paymentModel.php';
require_once __DIR__.'/../lib/class/payu.php';


$guest=new guestModel();
$payment=new paymentModel();

$timetoapprove = 10 * 24*3600;
$from=Bootstrap::$main->now - $timetoapprove;

$payu=new payu();

$payus=$payment->getAllForChannel('payu',$from,5);

if (is_array($payus )) foreach ($payus AS $p)
{
    $prc=100*(Bootstrap::$main->now - $p['d_payment_create'])/$timetoapprove;
    
    if ($p['d_cancel']) {
        $r=$payu->cancel_payment($p);
        Tools::log('payu',['cancel',$r,$p]);
    } elseif ($p['d_deadline']+3600<Bootstrap::$main->now || $prc>90) {
        $r=$payu->confirm_payment($p);
        Tools::log('payu',['confirm',$r,$p]);
    }
}


foreach ($guest->getCanceledGuests()?:[] AS $g)
{
    $payments=$payment->getForGuest($g['id']);
    if (is_array($payments)) foreach($payments AS $p)
    {
        $classname=$p['channel'];
        $classfile=__DIR__.'/../lib/class/'.$classname.'.php';
        if (!file_exists($classfile)) continue;
        require_once $classfile;
        $c=new $classname();
        $c->cancel_payment($p);
    }
}


