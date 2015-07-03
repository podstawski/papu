<?php
require_once __DIR__.'/../../../rest/controllers/guestController.php';
require_once __DIR__.'/../../../rest/models/paymentModel.php';

class payu extends guestController {


    protected function payu_req($url,$payment)
    {
        $config=Bootstrap::$main->getConfig();
        
        $data=json_decode($payment['notify'],true);
        
        $req = array();
        $req['pos_id'] = $data['pos_id'];
        $req['session_id'] = $data['session_id'];
        $req['ts'] = time() + microtime(true);
        $req['sig'] = md5($req['pos_id'] . $req['session_id'] . $req['ts'] . $config['payu.key1']);

        $resp=$this->req($url,$req);
        
	$res=$this->resp2array($resp);

	return $res;
    }
    
    public function cancel_payment($payment)
    {
        if ($payment['status']!='5') return false;

        $url = 'https://www.platnosci.pl/paygw/UTF/Payment/cancel/txt';
	
        $res=$this->payu_req($url,$payment);
        
	$payment['resp']=$res;
	if ($res['status']!='OK') Tools::observe('payu-cancel',$payment);
        
	return $res;
    }
    
    public function confirm_payment($payment)
    {
        $url = 'https://www.platnosci.pl/paygw/UTF/Payment/confirm/txt';
        $res=$this->payu_req($url,$payment);
	$payment['resp']=$res;
	if ($res['status']!='OK') Tools::observe('payu-confirm',$payment);
        
	return $res;
    }    
    
    
}