<?php
require_once __DIR__.'/Controller.php';
require_once __DIR__.'/eventController.php';
require_once __DIR__.'/userController.php';

require_once __DIR__.'/../models/imageModel.php';
require_once __DIR__.'/../models/eventModel.php';
require_once __DIR__.'/../models/guestModel.php';
require_once __DIR__.'/../models/userModel.php';
require_once __DIR__.'/../models/rateModel.php';
require_once __DIR__.'/../models/paymentModel.php';

require_once __DIR__.'/../class/Ics.php';

class guestController extends Controller {
    protected $_event,$_guest,$_user;

    /**
     * @return eventModel
     */    
    protected function event()
    {
	if (is_null($this->_event)) $this->_event=new eventModel();
	return $this->_event;
    }
    
    /**
     * @return guestModel
     */    
    protected function guest()
    {
	if (is_null($this->_guest)) $this->_guest=new guestModel();
	return $this->_guest;
    }
    
    /**
     * @return userModel
     */    
    protected function user()
    {
	if (is_null($this->_user)) $this->_user=new userModel();
	return $this->_user;
    }    
    
    public function get()
    {
	$this->requiresLogin();
	$rate=new rateModel();
	
	if ($this->id) {
	    $this->guest()->get($this->id+0);
	    
	    if ($this->guest()->user!=Bootstrap::$main->user['id']) $this->error(19);
	    return $this->status($this->guest()->data());
	    
	} else  {
	    $tickets=$this->guest()->getForUser(Bootstrap::$main->user['id'])?:[];
	    $eventController=new eventController();
	    
	    if (is_array($tickets)) foreach($tickets AS &$ticket)
	    {
		$ticket['url']=$ticket['user_url'].'/'.$ticket['event_url'];
		
		$ticket['guests']=$eventController->get_guests($ticket['event_id']);
		if (!$ticket['d_cancel'] && !$ticket['d_payment'])
		{
		    $ticket['payu']=$this->get_pay_link($ticket['id']);
		}
		
		$ticket['sort']=abs(Bootstrap::$main->now - $ticket['d_event_start']);
		if ($ticket['d_payment']) $ticket['sort']=abs(Bootstrap::$main->now - $ticket['d_payment']);
	    
		$ticket['rated']=$rate->user_has_rated_event($ticket['event'])?true:false;
		$ticket['canAddPhoto']=$ticket['d_event_start']<Bootstrap::$main->now && $ticket['d_payment'] && !$ticket['d_cancel'];

	    }

    
	    return $this->status($tickets);
	}
	
    }
    
 
    protected function get_pay_link($id)
    {
	$guest=$this->guest()->get($id);
	$event=$this->event()->get($guest['event']);
	
	$operator='paypal';
	switch ($event['currency'])
	{
	    case 'PLN':
		$operator='payu';
		break;
	    case 'ARS':
		$operator='mercadopago';
		break;
	}
	
	
	return Bootstrap::$main->getConfig('protocol').'://'.$_SERVER['HTTP_HOST'].Bootstrap::$main->getRoot().'guest/'.$operator.'/'.$id;

	
    }
    
    public function post()
    {
	$this->requiresLogin();

	$this->check_input();
	
	if (!isset($this->data['event'])) $this->error(31);
	$event=$this->event()->get($this->data['event']);
	if (!isset($event['user'])) $this->error(29);
	if ($event['user']==Bootstrap::$main->user['id']) $this->error(32);
	if ($event['active']!=1) $this->error(62);
	
	if (!isset($this->data['persons']) || !($this->data['persons']+0)) $this->data['persons']=1;
	
	if ($event['d_event_end'] < Bootstrap::$main->now ) $this->error(34);
	if ($event['d_deadline'] < Bootstrap::$main->now && !$event['bookafterdeadline']  ) $this->error(34);
	
	if ($this->data['persons'] > $this->event()->getSlots()) $this->error(33);

	if ($this->data('guest_agreement'))
	{
	    $userController=new userController();
	    $userController->set_agreement(false);
	}
	if (!Bootstrap::$main->user['d_guest_agreement']) $this->error(64);
	
	if ($event['fb_friends']) $this->require_fb_friend($event['user'],true);
	
	$data=['persons'=>$this->data['persons'],'event'=>$this->data['event'],'user'=>Bootstrap::$main->user['id'],'d_create'=>Bootstrap::$main->now,'message'=>$this->data('message')];
    
	$model=new guestModel($data,true);
	
	$model->guest_price=$event['guest_price'];
	$model->host_price=$event['host_price'];
	$model->save();
	
	
	$data=$model->data();
	
	if ($model->id)
	{
	    if (!Bootstrap::$main->user['ref_user'])
	    {
		if (!$this->event()->get_for_user(Bootstrap::$main->user['id']))
		{
		    $me=new userModel(Bootstrap::$main->user['id']);
		    $me->ref_user=$event['user'];
		    if (!$me->ref_site) $me->ref_site='guest';
		    $me->save();
		}
	    }
	    
	    if ($event['price']>0) $data['payu']=$this->get_pay_link($model->id);
	
	    
	    if ($event['price']+0==0) {
		$payment=new paymentModel();
		$payment->amount=0;
		$payment->guest=$model->id;
		$this->paid($payment,1);
	    }
	    
	    Bootstrap::$main->session('book',[]);
	}
	
	
	return $this->status($data,$model->id?true:false);
    }
    
    
    public function put()
    {
	$this->requiresLogin();
	$this->check_input();

	if (!$this->id) $this->error();
	$this->guest()->get($this->id+0);
	
	if ($this->guest()->user!=Bootstrap::$main->user['id']) $this->error(19);

	if ($this->guest()->d_payment) $this->error(56);
	if ($this->data('persons')+0<=0) $this->error(57);
	
	$this->data['persons']=round($this->data['persons']);
	
	$this->event()->get($this->guest()->event);
	
	if ($this->data['persons'] > $this->event()->getSlots()) $this->error(33);
    
	$this->guest()->persons = $this->data['persons'];
	if ($this->data('message')) $this->guest()->message=$this->data('message');
	$this->guest()->save();
	
	return $this->status($this->guest()->data());
    }
    
    public function delete()
    {
	$this->requiresLogin();
	$this->check_input();

	$guest=$this->guest()->get($this->id);
	if (!isset($guest['user'])) $this->error(29);
	
	if ($guest['user']!=Bootstrap::$main->user['id'])
	{
	    Bootstrap::$main->human_datetime_format();
	}
	
	$event=$this->event()->get($guest['event']);
	
	if ($guest['user']!=Bootstrap::$main->user['id'] && $event['user']!=Bootstrap::$main->user['id']) $this->error(19);
	
	if (!$this->guest()->d_payment) {
	    $this->guest()->remove();
	    return $this->status();
	}
	
	if ($event['d_event_start']<Bootstrap::$main->now) $this->error(70);
	
	$this->guest()->cancel_reason=$this->data('reason');
	$this->guest()->d_cancel=Bootstrap::$main->now;
	$this->guest()->canceler=Bootstrap::$main->user['id'];
	$this->guest()->save();

	$eventController=new eventController();
	$eventController->recalculate_fullhouse($this->guest()->event);

	Bootstrap::$main->human_datetime_format();
	$this->event()->get($this->guest()->event);
	
	$data=$this->guest()->data();
	$data['event']=$this->event()->data();
	$data['guest']=$this->user()->get($guest['user']);
	$data['host']=$this->user()->get($event['user']);
	
	$ics=Ics::cancelation($event,$data['host'],$data['guest'],$event['create'],$event['change']);
	if ($event['user']==Bootstrap::$main->user['id'])
	{
	    Tools::observe('guest-canceled-by-host',$data,[['cancel.ics'=>$ics]]);
	}
	else
	{
	    Tools::observe('event-canceled',$data,[['cancel.ics'=>$ics]]);
	    Tools::observe('event-canceled-to-host',$data);
	}
	
	return $this->status($this->guest()->data());
    }
    
    
    protected function before_operator($guest,$event)
    {
	$this->requiresLogin();
	if (!isset($guest['user'])) $this->error(29);
	//if ($guest['user']!=Bootstrap::$main->user['id']) $this->error(19);
		if ($guest['d_payment']) $this->error(56);
	if ($guest['d_cancel']) $this->error(58);
	if ($event['active']!=1) $this->error(62);
    }
    
    public function get_mercadopago()
    {
	Bootstrap::$main->human_datetime_format();
	$guest=$this->guest()->get($this->id);
	$event=$this->event()->get($guest['event']);
	$this->before_operator($guest,$event);
	
	$config=Bootstrap::$main->getConfig();
	
	$data=Bootstrap::$main->user;
	$data['client_ip']=Bootstrap::$main->ip;
	foreach($config AS $k=>$v) if (current($a=explode('.',$k))=='mercadopago') $data[end($a)]=$v;
	
	require_once __DIR__.'/../class/mercadopago.php';

	$preference_data = array(
	    "items" => array(
		    array(
			    "title" => $event['name'],
			    "quantity" => $guest['persons'],
			    "currency_id" => $event['currency'], 
			    "unit_price" => $guest['guest_price']
		    )
	    ),
	    "payer" => array (
		'name' => Bootstrap::$main->user['firstname'],
		'surname' => Bootstrap::$main->user['lastname'],
		'email' => Bootstrap::$main->user['email'],
	    ),
	    
	    "back_urls" => array (
		'success'=>$config['app.root'].'profile',
		'pending'=>$config['app.root'].'profile',
		'failure'=>$config['app.root'].'profile',
	    ),
	    
	    "notification_url" => Bootstrap::$main->getConfig('protocol').'://'.$_SERVER['HTTP_HOST'].Bootstrap::$main->getRoot().'guest/mercadopago'
	);
	

	try {
	    $mp = new MP($data['client_id'], $data['client_secret']);
	    $preference = $mp->create_preference($preference_data);
	    
	} catch (Exception $e) {
	    mydie($e);
	}

	//mydie($preference);
	
	$data=array_merge($data,$event,$guest);
	$user=new userModel($event['user']);
	
	$payment=new paymentModel();
	$payment->status=1;
	$payment->guest=$this->id;
	$payment->d_create=Bootstrap::$main->now;
	$payment->channel='mercadopago';
	$payment->amount=$data['guest_price']*$data['persons'];
	$payment->order_id=$preference['response']['id'];
	$payment->save();
	
	
	$html=file_get_contents(__DIR__.'/../resources/mercadopago.html');
	
	
	foreach ($preference['response'] AS $k=>$v) if (!is_array($v)) $html=str_replace('{'.$k.'}',$v,$html);
	
	die($html);
	
    }
    
    public function post_mercadopago()
    {
	require_once __DIR__.'/../class/mercadopago.php';
	$config=Bootstrap::$main->getConfig();
	
	try {
	    $mp = new MP($config['mercadopago.client_id'], $config['mercadopago.client_secret']);

	    $topic = $this->data["topic"];
	    $merchant_order_info = null;
	    
	    switch ($topic) {
		case 'payment':
		    $payment_info = $mp->get("/collections/notifications/".$_GET["id"]);
		    $merchant_order_info = $mp->get("/merchant_orders/".$payment_info["response"]["collection"]["merchant_order_id"]);
		    break;
		case 'merchant_order':
		    $merchant_order_info = $mp->get("/merchant_orders/".$_GET["id"]);
		    break;
		default:
		    $merchant_order_info = null;
	    }
	    
	    if($merchant_order_info == null) {
		echo "Error obtaining the merchant_order";
		die();
	    }
	    
	    if ($merchant_order_info["status"] == 200) {
		$payment=new paymentModel();
		$payment->find_one_by_order_id($merchant_order_info['response']['preference_id']);
    
		if ($payment->id) {
		    $payment->d_response=Bootstrap::$main->now;
		    $payment->notify=json_encode($this->data);
		    if (!$payment->response) $payment->response='';
		    $payment->response=$payment->response.date('d-m-Y H:i:s')." GMT\n".print_r($merchant_order_info['response'],1)."\n";	
		    $payment->save();
		    $this->paid($payment,$merchant_order_info['response']['total_amount']);
		}

	    }

	    
	    
	    
	    
	} catch (Exception $e) {
	    mydie($e);
	}
	
	
	
	
	
	
    }
    
    public function get_payu()
    {
	Bootstrap::$main->human_datetime_format();
	$guest=$this->guest()->get($this->id);
	$event=$this->event()->get($guest['event']);
	$this->before_operator($guest,$event);
	
	$config=Bootstrap::$main->getConfig();
	
	$data=Bootstrap::$main->user;
	$data['client_ip']=Bootstrap::$main->ip;
	foreach($config AS $k=>$v) if (current($a=explode('.',$k))=='payu') $data[end($a)]=$v;
	
	
	$data=array_merge($data,$event,$guest);
	$user=new userModel($event['user']);
	
	$payment=new paymentModel();
	$payment->status=1;
	$payment->guest=$this->id;
	$payment->d_create=Bootstrap::$main->now;
	$payment->channel='payu';
	$payment->amount=$data['guest_price']*$data['persons'];
	$payment->order_id=$user->url.'/'.$data['url'].'/'.$data['event_start'];
	$payment->save();
	
	
	$data['total100x']=100*$payment->amount;
	$html=file_get_contents(__DIR__.'/../resources/payu.html');
	
	$data['custom_id']=$payment->id;
	$data['order_id']=$payment->order_id;
	
        $data['ts'] = time();
        $data['sig'] = md5(''
            . $data['pos_id']
            . $data['custom_id']
            . $data['pos_auth_key']
            . $data['total100x']
            . $data['desc']
	    . $data['order_id']
            . $data['firstname']
            . $data['lastname']
            . $data['email']
            . $data['client_ip']
            . $data['ts']
            . $data['key1']
        );
	
	
	
	foreach ($data AS $k=>$v) $html=str_replace('{'.$k.'}',$v,$html);
	
	die($html);
	
    }
    
    

    
    public function post_payu()
    {
	Tools::log('payu',['post',$this->data]);
	
	if (!$this->data('session_id')) die();
	$payment=new paymentModel($this->data['session_id']);
	if (!$payment->id) die();

	$config=Bootstrap::$main->getConfig();
	
	
	$this->data['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
	$payment->d_response=Bootstrap::$main->now;
	$payment->notify=json_encode($this->data);
	$payment->save();
	
        $req = array();
        $req['pos_id'] = $this->data('pos_id');
        $req['session_id'] = $this->data('session_id');
        $req['ts'] = time() + microtime(true);
        $req['sig'] = md5($req['pos_id'] . $req['session_id'] . $req['ts'] . $config['payu.key1']);

        $url = 'https://www.platnosci.pl/paygw/UTF/Payment/get/txt';
	
        $resp=$this->req($url,$req);

	if (!$payment->response) $payment->response='';
	$payment->response=$payment->response.date('d-m-Y H:i:s')." GMT\n".$resp."\n";
	
        $res = $this->resp2array($resp);
	
	Tools::log('payu',['check',$res,$payment->data()]);
	
	if ($res['status'] == 'OK')
	{
	    $payment->status=$res['trans_status']+0;
	    
	    if ($res['trans_status'] == 99 || $res['trans_status']==5)
	    {
		$this->paid($payment,$res['trans_amount']/100);
	    }
	    
	    if ($res['trans_status'] == 3 || $res['trans_status']==7)
	    {
		$payment->d_cancel_commit=Bootstrap::$main->now;
	    }
	    
	}
	
	$payment->save();
	
	die('OK');
    }
    
    protected function resp2array($resp)
    {
        $res = array();
        foreach (explode("\n", str_replace("\r", '', $resp)) as $line) {
            if (strpos($line,':'))
            {
                list ($key, $val) = explode(': ', $line);
                $res[$key] = $val;
            }
        }
 
	return $res;
	
    }
    
    
    protected function paid(paymentModel $payment,$amount)
    {
	
	if (0.9*$payment->amount < $amount)
	{
	    $image=new imageModel();
	    
	    $guest=new guestModel($payment->guest);
	    if ($guest->d_payment) return;
	    
	    $guest->d_payment=Bootstrap::$main->now;
	    $guest->save();
	    	    
	    $e=$this->event()->get($guest->event);
   
	    Tools::userHasAccessToEvent($guest->event,$guest->user,true);
	    
	    
	    $data=[];
	    $data['event']=$this->event()->data();
	    $user=new userModel($data['event']['user']);
	    $data['host']=$user->data();
	    
	    Bootstrap::$main->session('time_delta',$user->delta);
	    Bootstrap::$main->human_datetime_format();
	    
	    $data['event']=$this->event()->get($guest->event);
	    $data['event']['img']=$image->get($data['event']['img']);
	    
	    $user->get($guest->user);
	    $data['guest']=$user->data();
	    
	    $data['data']=$guest->data();
	    $data['payment']=$payment->data();
	    if (isset($data['payment']['notify'])) $data['notify']=json_decode($data['payment']['notify'],true);
	    
	    $ics=Ics::invitation($e,$data['host'],$data['guest'],$e['create'],$guest->create);
   
	    Tools::observe('event-paid-to-host',$data);
	    Tools::observe('event-paid-to-guest',$data,[['invite.ics'=>$ics]]);
	    Tools::log('guest',$data);

	    $eventController=new eventController();
	    $eventController->recalculate_fullhouse($guest->event);
    	    
	}
	
    }
    
    protected function zulu_date($d)
    {
	return preg_replace('/[^0-9TZ]/','',$d);
    }
    
    
}
