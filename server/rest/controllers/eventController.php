<?php
require_once __DIR__.'/userController.php';

require_once __DIR__.'/../models/eventModel.php';
require_once __DIR__.'/../models/tagModel.php';
require_once __DIR__.'/../models/imageModel.php';
require_once __DIR__.'/../models/cityModel.php';
require_once __DIR__.'/../models/rateModel.php';
require_once __DIR__.'/../models/guestModel.php';

require_once __DIR__.'/../class/Ics.php';

class eventController extends Controller {
    protected $_event,$_user;
    protected $user_fields_to_copy=['address','country','lat','lng','city','postal','payment','phone'];

    /**
     * @return eventModel
     */    
    protected function event($e=null)
    {
	if (is_null($this->_event)) $this->_event=new eventModel();
	if (!is_null($e)) $this->_event->get($e);
	return $this->_event;
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
	
	$tags=new tagModel();
	$userController=new userController();
	
	if ($this->id+0>0)
	{
	    
	    $event=$this->event()->get($this->id);
	    if (!Bootstrap::$main->isAdmin()) if (isset($event['user']) && $event['user']!=Bootstrap::$main->user['id']) return $this->error(19);
	    if (!isset($event['user'])) return $this->error(29);
	    
	    $event['tags']=Tools::tags(null,$tags->for_event($this->id));
	    
	    $images=new imageModel();
	    $event['images']=$images->getEventImages($event['id'])?:[];
	    
	    $public=$this->public_data($event);
	    if (isset($public['img'])) $event['img']=$public['img'];
	    
	    $event['guests']=$this->get_guests($event['id'],$userController,true)?:[];
	    $event['host_url']=Bootstrap::$main->user['url'];
	    
	    if (in_array('host_agreement',array_keys(Bootstrap::$main->user))) $event['host_agreement']=Bootstrap::$main->user['host_agreement'];
	    
	    return $this->status($event);
	}
	
	$events=$this->event()->get_for_user(Bootstrap::$main->user['id'])?:[];
		
	foreach ($events AS &$event)
	{
	    $event['guests']=$this->get_guests($event['id'],$userController,true)?:[];
	    $event['sort']=Bootstrap::$main->now -($event['d_change']?:$event['d_create']);
	    
	    if ($event['active_err'])
	    {
		$this->error_trap=[];
		$this->error($event['active_err']);
		$event['active_err_txt'] = isset($this->error_trap['error']['info'])?$this->error_trap['error']['info']:'';
		$this->error_trap=null;
	    }
	    
	    $event['canAddPhoto']=$event['d_event_start']<Bootstrap::$main->now && $event['active']==1;
	    if ($event['active']==1 && $event['d_event_end']<Bootstrap::$main->now) $event['active']=2;
	    
	}
	
	return $this->status($events,true,'events');
    }

    public function get_check()
    {
	$this->requiresLogin();

	$event=$this->event()->get(0+$this->id);

	if (!isset($event['user']) || $event['user']!=Bootstrap::$main->user['id']) return $this->error(19);
	
	$data=$this->data;
	$data['active']=1;
	$model=new eventModel($event);
	
	$this->verify_data($data,$model);
	return $this->status();
    }
    
    public function put_check()
    {
	return $this->get_check();
    }
    
    protected function verify_data(&$data,$model=null,$name_required=false)
    {
	
	$country=@(isset($data['country']) && $data['country'])?$data['country']:(is_object($model)?$model->country:'');

	if ($country) $data['currency']=$this->country2currency($country);

	if (!$model) $model=$this->event();
	
	if ($name_required && !$model->name)  if (!isset($data['name']) || !$data['name']) return $this->error(21);
	
	$this->check_input(['tags'=>['*'=>1],'guests'=>['*'=>['tags'=>['*'=>1]]],'img'=>['labels'=>1],'images'=>['*'=>['labels'=>1]]]);
	
	if (isset($data['payment']) )
	{
	    $userController=new userController();
	    $userController->check_payment(Bootstrap::$main->user['country'],$data['payment']);
	}
	
	
	if (isset($data['host_agreement']) )
	{
	    $userController=new userController();
	    $userController->set_agreement();
	}
	
	if ($model->active==-1) return $this->error(61);
	
	if (isset($data['name']) && strlen($data['name'])>200) return $this->error(21);
	if (isset($data['url']) && strlen($data['url'])>200) return $this->error(21);
	if (isset($data['url'])) $data['url']=Tools::str_to_url($data['url'],-1);
	
	
	foreach($data AS $k=>$v) if (substr($k,0,2)=='d_') return $this->error(22);
	
	if (!$model->active) {
	    if (isset($data['event_end']) && isset($data['duration'])) unset($data['event_end']);
    
	    if (isset($data['event_start'])) $data['d_event_start']=$this->strtotime($data['event_start']);
	    if (isset($data['event_end'])) $data['d_event_end']=$this->strtotime($data['event_end']);
	    if (isset($data['deadline'])) $data['d_deadline']=$this->strtotime($data['deadline']);
	    
	    if (isset($data['d_event_start']) && $data['d_event_start']<Bootstrap::$main->now) return $this->error(66);
	    
	    if (!isset($data['d_event_end']) && !isset($data['d_event_start']) && $model->d_event_end)
	    {
		$data['d_event_end']=$model->d_event_end;
	    }
    
	    if (!isset($data['d_event_start']) && $model->d_event_start)
	    {
		$data['d_event_start']=$model->d_event_start;
	    }
    
    
	    
	    if (isset($data['d_event_start']) && isset($data['duration']) && !is_array($data['duration']))
	    {
		$data['d_event_end']=$data['d_event_start']+$data['duration'];
		unset($data['duration']);
	    }
	    
	    if (isset($data['d_event_start']) && isset($data['d_event_end']) && $data['d_event_start']>=$data['d_event_end'])
	    {
		return $this->error(25);
	    }
    
	    if (isset($data['d_event_start']) && isset($data['d_deadline']) && $data['d_event_start']<=$data['d_deadline'])
	    {
		$data['d_deadline']=$data['d_event_start']-3600*Bootstrap::$main->getConfig('event.default_deadline');
	    }
	    
	    if (isset($data['d_event_start']) && isset($data['d_deadline']) && $data['d_event_start']-$data['d_deadline']>7*24*3600)
	    {
		$data['d_deadline']=$data['d_event_start']-3600*Bootstrap::$main->getConfig('event.default_deadline');
	    }	    
	    
	    if (isset($data['d_event_start']) && !isset($data['d_event_end']))
	    {
		$data['d_event_end']=$data['d_event_start']+3600*Bootstrap::$main->getConfig('event.default_duration');
	    }
	    
	    if (isset($data['d_event_start']) && !isset($data['d_deadline']))
	    {
		$data['d_deadline']=$data['d_event_start']-3600*Bootstrap::$main->getConfig('event.default_deadline');
	    }
	    
	    
	    if (!isset($data['d_deadline']) && $model->d_deadline)
	    {
		$data['d_deadline']=$model->d_deadline;
	    }	
	    
	    if (!isset($data['min_guests']) && $model->min_guests)
	    {
		$data['min_guests']=$model->min_guests;
	    }
    
	    if (!isset($data['max_guests']) && $model->max_guests)
	    {
		$data['max_guests']=$model->max_guests;
	    }
	    
	    if (!isset($data['min_guests']) || !$data['min_guests']) $data['min_guests']=1;
	    if (!isset($data['max_guests']) || !$data['max_guests']) $data['max_guests']=$model->lastMaxGuests();
	    
	    if (!isset($data['max_guests']) || !$data['max_guests']) $data['max_guests']=4;
	    
    
	    if (isset($data['min_guests']) && isset($data['max_guests']) && $data['min_guests']>$data['max_guests'])
	    {
		return $this->error(26);
	    }
	    
	    
	    $user=new userModel(Bootstrap::$main->user);
	    $user_needs_save=false;
	    foreach ($this->user_fields_to_copy AS $uk)
	    {
		if ($this->data($uk) && !Bootstrap::$main->user[$uk])
		{
		    $user->$uk=$this->data($uk);
		    $user_needs_save=true;
		}
	    }
	    if ($user_needs_save)
	    {
		$user->save();
		Bootstrap::$main->user=$user->data();
		Bootstrap::$main->session('user',$user->data());
	    }
	}
	
	if (isset($data['active']))
	{
	    if ($data['active'] && !$model->active) {
		@$this->may_activate($data,$model);		
	    }
	    if (!$data['active'] && $model->active) $this->may_deactivate($model);
	    
	}
	
	if (isset($data['img']['id'])) $data['img']=$data['img']['id'];

    }
    
    protected function country2currency($country)
    {
	switch ($country)
	{
	    case 'PL':
		return 'PLN';
	    case 'AR':
		return 'ARS';
	    default:
		return 'USD';
	}
    }
    
    protected function may_activate($data,$model)
    {
	
	if ((!isset($data['name']) || !$data['name']) && !$model->name) return $this->error(21);

	
	if (!Bootstrap::$main->user['d_host_agreement']) return $this->error(64);
	
	foreach ($this->user_fields_to_copy AS $uk)
	{
	    if ($uk=='payment' && !$data['price'] && !$model->price) continue;
	    if (!Bootstrap::$main->user[$uk]) return $this->error(24,$uk);
	}
	
	if (!$model->img) return $this->error(38);
	
	if (!$data['url'] && !$model->url) return $this->error(23);
	if (!$data['d_event_start'] && !$model->d_event_start) return $this->error(39);
	if (!$data['d_event_end'] && !$model->d_event_end) return $this->error(40);
	if (!$data['d_deadline'] && !$model->d_deadline) return $this->error(41);
	
	$model->price+=0;
	$data['price']+=0;
	if (!$data['about'] && !$model->about) return $this->error(42);
	if ($data['price']<0 && $model->price<0) return $this->error(48);
	
	if (!$data['lat'] && !$model->lat) return $this->error(46);
	if (!$data['lng'] && !$model->lng) return $this->error(46);
	
	if (!$data['d_event_start']) return $data['d_event_start']=$model->d_event_start;
	if ($data['d_event_start']<Bootstrap::$main->now ) return $this->error(44);
	
	if (Bootstrap::$main->getConfig('event.limit_to_known_cities')) {
	    $city=new cityModel();
	    if (!$city->getByLocation($data['lat']?:$model->lat,$data['lng']?:$model->lng)) return $this->error(49);
	}
    }
    
    protected function may_deactivate($model)
    {
	if ($model->getGuests()) return $this->error(43);
    }
    
    
    protected function check_before_save(&$model)
    {
	if ($model->active!=0) return;
	$this->error_trap=[];
	@$this->may_activate($model->data(false),$model);
	$model->active_err = (isset($this->error_trap['error']['number'])) ? $this->error_trap['error']['number'] : 0;
	$this->error_trap=null;
    }
    
    public function post()
    {
	$this->requiresLogin();
	
	if ($this->id) return $this->error(63);
	
	if (isset($this->data['parent']))
	{
	    $event['parent']=$this->data['parent'];
	    $d_event_start=0;
	    $duration=0;
	    while ($event['parent']) {
		$event=$this->event()->get($event['parent']);
		if (!$d_event_start && isset($event['d_event_start'])) $d_event_start=0+$event['d_event_start'];
		if (!$duration && isset($event['duration'])) $duration=0+$event['duration'];
	    }
	    if ($d_event_start)
	    {
		if ($d_event_start>Bootstrap::$main->now) $d_event_start+=7*24*3600;
		else {
		    $d=date('Y-m-d ').date('H:i',$d_event_start);
		    $d_event_start=strtotime($d)+7*24*3600;
		}
	    }
	    
	    if (isset($event['user']))
	    {
		$parent=$event['id'];
		if ($event['user']!=Bootstrap::$main->user['id']) return $this->error(19);
		//if ($event['parent']) return $this->error(28);
		
		
		if (!$d_event_start)
		{
		    foreach ($event AS $k=>$v)
		    {
		        if (substr($k,0,2)=='d_') unset($event[$k]);
		    }
		}
		else
		{
		    $event['d_event_start']=$d_event_start;
		    $event['d_event_end']=$d_event_start+$duration;
		    $event['d_deadline']=$d_event_start-3600*Bootstrap::$main->getConfig('event.default_deadline');
		}
		foreach (['id'] AS $key) unset($event[$key]);
		$event['active']=0;
		$event['_guest_count']=0;
		$event['parent']=$parent;
		$event['fullhouse']=null;
		
		$event['d_create']=Bootstrap::$main->now;
		$event['d_change']=Bootstrap::$main->now;
		
		$model=new eventModel($event,true);
	
		$this->check_before_save($model);
		$model->save();
		$data=$model->data();
		$result=$data['id']>0;

		return $this->status($data,$result);
	    }
	}

	
	if ($this->data('name')) {
	    $this->data['url'] = Tools::str_to_url((isset($this->data['url']) && $this->data['url'] ) ? $this->data['url'] : $this->data['name'],-1);

	
	    if ($this->event()->find_on_url(Bootstrap::$main->user['id'],$this->data['url']))
	    {
		$url=$this->data['url'];
		$i=1;
		while ($this->event()->find_on_url(Bootstrap::$main->user['id'],$url.$i)) $i++;
		$this->data['url']=$url.$i;
	    }
	} else {
	    $this->data['name']='';
	    $this->data['url']='';
	}
	$data=$this->data;
	
	$this->verify_data($data);
	
	
	$data['active']=0;
	$data['_guest_count']=0;
	$data['d_create']=Bootstrap::$main->now;
	$data['user']=Bootstrap::$main->user['id'];
	if ($this->data('name')) $data['d_change']=Bootstrap::$main->now;
	
	$last=$this->event()->lastEvent()?:[];
	
	foreach ($this->user_fields_to_copy AS $uk)
	{
	    $data[$uk]=isset($last[$uk]) ? $last[$uk] : Bootstrap::$main->user[$uk];
	}
	
	
	
	$model=new eventModel($data,true);
	$model->currency=$this->country2currency($model->country);
	
	if ($model->lat && $model->lng) {
	    $hints=$model->lastHints();
	    if ($hints) $model->hints=$hints;
	}
	
	$this->check_before_save($model);
	$model->save();
	
	if (!$model->id) return $this->status($data,false);
	
	$tags=new tagModel();
	//$tags->user2event($model->user,$model->id);
	
	$data=$model->data();
	
	$data['tags']=Tools::tags(null,$tags->for_event($model->id)); //$tags->for_event($model->id);
	
	//Tools::observe('new-event',['event'=>$data,'host'=>Bootstrap::$main->user,'parent'=>0]);
	
	return $this->status($data);
	
    }
    
    
    public function put()
    {
	$this->requiresLogin();

	$event=$this->event()->get(0+$this->id);

	if (!Bootstrap::$main->isAdmin()) if (!isset($event['user']) || $event['user']!=Bootstrap::$main->user['id']) return $this->error(19);

	$model=new eventModel($event);
	
	if (is_array($this->data))
	{
	    $this->verify_data($this->data,$model);
	    
	    if ($model->parent)
	    {
		foreach (['parent','name','url','lat','lng','address','city','postal','price','fb_friends'] AS $k) if (isset($this->data[$k])) unset($this->data[$k]);
	    }
	    else
	    {
		if (isset($this->data['url']) && $this->data['url'] && $this->data['url']!=$model->url)
		{
		    if (!$this->data['url']) return $this->error(23);
		    if ($model->find_on_url($model->user,$this->data['url'],$this->id+0)) return $this->error(17);
		} elseif (isset($this->data['name']) && ($this->data['name']!=$model->name || !$this->data('url'))) {
		    $url=Tools::str_to_url($this->data['name'],-1);
    
		    if ($this->event()->find_on_url(Bootstrap::$main->user['id'],$url,$this->id+0))
		    {
			$i=1;
			while ($this->event()->find_on_url(Bootstrap::$main->user['id'],$url.$i,$this->id+0)) $i++;
			$this->data['url']=$url.$i;
		    }
		    else
		    {
			$this->data['url']=$url;    
		    }
		    
		}
	    }

	    $active=$model->active;
	    
	    if (!$active && isset($this->data['city']) && isset($this->data['country'])
		&& isset($this->data['address']) && isset($this->data['postal']) &&
		(   isset($this->data['lat']) && !$this->data['lat']
		 || isset($this->data['lng']) && !$this->data['lng']
		 || $this->data['city']!=$model->city
		 || $this->data['country']!=$model->country
		 || $this->data['address']!=$model->address
		 || $this->data['postal']!=$model->postal
		) 
	    )
	    {
		$url='https://maps.google.com/maps/api/geocode/json?address='.urlencode($this->data['address'].', '.$this->data['postal'].' '.$this->data['city']).'&sensor=false&region='.$this->data['country'].'&key='.Bootstrap::$main->getConfig('maps.api_key');  
		
		for($i=0;$i<5;$i++) {
		    $place=json_decode($this->req($url),true);
		    if (isset($place['results'][0]['geometry']['location'])) break;
		    usleep(rand(1000,1000000));
		}

		if (isset($place['results'][0]['geometry']['location']))
		{
		    $this->data['lat'] = $place['results'][0]['geometry']['location']['lat'];
		    $this->data['lng'] = $place['results'][0]['geometry']['location']['lng'];
		}
	    
		if ($this->data('lat') && $this->data('lng'))
		{
		    $hints=$this->event()->lastHints($this->data('lat'),$this->data('lng'));
		    if ($hints) $this->data['hints']=$hints;
		}
	    
	    }
	    	    
	    
	    
	    foreach ($this->data AS $k=>$v)
	    {
		if ($k=='id') continue;
		if ($active && !in_array($k,['min_guests','active','unlisted','_vip','img','bookafterdeadline']) && (!isset($this->data['active']) || $this->data['active']) ) continue;
		
		$model->$k=$v;
	    }

	    
	    $model->d_change=Bootstrap::$main->now;
	    if (!$active) $this->check_before_save($model);
	    $model->save();
	    
	    
	    if (!$active && !$model->parent)
	    {
		$model->updateFam(['price'=>$model->price]);
	    }
	
	    $this->recalculate_fullhouse($model->id);
	    $data=$model->data();
	
	    if (isset($this->data['tags'])) $data['tags']=Tools::tags(null,$this->add_tags($data,$this->data['tags']));
	    
	    $public=$this->public_data($data);
	    if (isset($public['img'])) $data['img']=$public['img'];	    

	    if (isset($this->data['active'])) {
		if ($this->data('active') && !$active)
		{
		    Bootstrap::$main->human_datetime_format();
		    $ics=Ics::invitation($model->data(),Bootstrap::$main->user,Bootstrap::$main->user,$model->create,$model->change);
		    Tools::observe('new-event',['event'=>$this->event()->get($model->id),'host'=>Bootstrap::$main->user,'parent'=>$model->parent],[['invite.ics'=>$ics]]);
		}
		if (!$this->data('active') && $active)
		{
		    Bootstrap::$main->human_datetime_format();
		    $ics=Ics::cancelation($model->data(),Bootstrap::$main->user,Bootstrap::$main->user,$model->create,$model->change);
		    Tools::observe('stop-event',['event'=>$this->event()->get($model->id),'host'=>Bootstrap::$main->user,'parent'=>$model->parent],[['invite.ics'=>$ics]]);
		}
	    }
	    
	    return $this->status($data);
	}


    }
    
    protected function add_tags($event,$tags)
    {
	$model=new tagModel();
	$ret=$model->for_event($event['id'],$tags);
	if (is_array($ret)) return $ret;
	return $this->error(27,$ret);
    }    
    
    
    public function delete($auto=false)
    {
	$this->requiresLogin();

	$event=$this->event()->get(0+$this->id);
	
	if (!isset($event['user']) || $event['user']!=Bootstrap::$main->user['id']) return $this->error(19);

	$model=new eventModel($event);
	
	if (!$model->getGuests() && count($model->get_dates($model->id,false))==1) {
	    $model->remove();
	    return $this->status();
	} elseif ( !$auto && $model->d_event_start < Bootstrap::$main->now) {
	    return $this->error(60);
	} else {
	    $guests=$model->getGuests();
	    $guestModel=new guestModel();
	    $otoken=$auto?'auto-cancel':'host-cancel';
	    Bootstrap::$main->human_datetime_format();
	    $event=$this->event()->get(0+$this->id);
	    $host=$this->user()->get($event['user']);
	    
	    if (is_array($guests)) foreach($guests AS $guest)
	    {
		$guestModel->get($guest['guest_id']);
		$guestModel->cancel_reason=$this->data('reason');
		$guestModel->d_cancel=Bootstrap::$main->now;
		$guestModel->canceler=Bootstrap::$main->user['id'];
		$guestModel->save();
		
		$ics=Ics::cancelation($event,$host,$guest,$event['create'],$event['change']);
		$data=$guestModel->data();
		$data['event']=$event;
		$data['guest']=$guest;
		$data['host']=$host;
		
		if ($guestModel->d_payment) Tools::observe($otoken,$data,[['cancel.ics'=>$ics]]);
	    }
	    $model->active=-1;
	    $model->save();
	    Tools::log('event-'.$otoken,['event'=>$model->data(),'guests'=>$guests]);
	}
	
	return $this->status();
    }

    
    public function get_calendar($event_id)
    {
	if (!$event_id) return;
	
	$calendar=$this->event()->get_dates($event_id);
	
	if ($calendar) foreach ($calendar AS &$cal)
	{
	    foreach ($cal AS $k=>$v)
	    {
		if (substr($k,0,2)=='d_') unset($cal[$k]);
	    }
	    $cal['free_slots']=$this->event()->getSlots($cal['id']);
	}
	
	return $calendar;
    }
    
    public function get_guests($event_id,$uController=null,$show_email=false)
    {
	if (!$event_id) return;
	
	if (!$uController) $uController=new userController();
	$guests=$this->event()->getGuests($event_id);
	
	$result=[];
	if (is_array($guests))
	{
	    foreach ($guests AS $guest)
	    {
		if (!$guest['d_payment']) continue;
		if ($guest['d_cancel']) continue;
		if (!$show_email) unset($guest['message']);
		if (in_array('ical',array_keys($guest))) unset($guest['ical']);
		$guest=$uController->public_data($guest,true,$show_email);
		$result[]=$guest;
	    }
	}
	return $result;	
    }


    public function __call($name,$args)
    {
	if ($pos=strpos($name,'_')) $name=substr($name,$pos+1);
	$user = $this->user()->find_one_by_url($name);
	
	if (!$user) return $this->error(20,$name);
	
	if (!$this->id) return $this->error(29);
	
	$user_id=$user['id'];
	
	
	$event=$this->event()->find_on_url($user_id,$this->id);
	if (!isset($event['id']) || !$event['id']) return $this->error(29,$this->id);
	
	if (isset($this->parts[3]) && $this->parts[3]=='reviews') return $this->reviews($event['id']);
	
	return $this->status($this->getPublicEvent($event,$user));
    }
    
    protected function getPublicEvent($event,$user=null,$rewritecache=false)
    {
	$memcachetoken='public:event:'.(is_array($event)?$event['id']:$event).':'.Bootstrap::$main->lang;
	
	if (!$rewritecache) {
	    $r=Tools::memcache($memcachetoken);
	    
	    if ($r) {
		$r['memcached']=true;
		$user=0;
		if (!isset(Bootstrap::$main->user['id']))
		{
		    if (is_array($event)) $user=$event['user'];
		    else $user=$this->event($event)->user;
		}
		$r['share']=$this->referers($r['url'],$r['name'],$user);
		return $r;
	    }
	}
	
	
	if (!is_array($event)) $event=$this->event()->get($event);
	$user_id=$event['user'];
	
	if (is_null($user)) {
	    $user=$this->user()->get($user_id);
	}
	
	$uController=new userController();
	$user=$uController->public_data($user);	
	
	
	foreach ($this->event()->getLastEditedChildren($event['id'])?:[] AS $last) {
	    foreach(['img','about'] AS $f) {
		if ($last[$f]) $event[$f]=$last[$f];
	    }
	}
	
	foreach ($event AS $k=>$v)
	{
	    if (substr($k,0,2)=='d_')
	    {
		unset($event[$k]);
		if (isset($event[substr($k,2)])) unset($event[substr($k,2)]);
	    }
	}
	
	
	
	
	$event['calendar']=$this->get_calendar($event['id'])?:[];
	foreach ($event['calendar'] AS $i=>$cal)
	{
	    $event['calendar'][$i]['guests']=$this->get_guests($cal['id'],$uController)?:[];
	}
	
	
	$all=$this->event()->get_dates($event['id'],false);
	
	$event_id=$event['id'];
	
	$images=new imageModel();
	if (is_array($all) && count($all)>1)
	{
	    $events_related=array();
	    foreach ($all AS $e) $events_related[]=$e['id'];
	    $event['images']=$images->getEventImages($events_related)?:[];
	}
	else
	{
	    $event['images']=$images->getEventImages($event['id'])?:[];
	}
	$img_ids=[];
	foreach($event['images'] AS $i=>&$img)
	{
	    if (isset($img_ids[$img['id']])) {
		unset($event['images'][$i]);
		continue;
	    }
	    $img_ids[$img['id']]=true;
	    $img['caption']=$img['title'];
	    if ($img['d_taken']) {
		if($img['caption']) $img['caption'].=' - ';
		else $img['caption']='';
		$img['caption'].=Bootstrap::$main->human_datetime_format($img['d_taken']);
	    }
	}
	
	$tags=new tagModel();
	$tags4event=$tags->for_event($event['id']);
	$event['tags']=$tags4event?Tools::tags($tags4event):[];
	
	$user=$this->_get_user($event['user']);
	$event['host']=$user;
        if (isset($user['url'])) $event['url']=$user['url'].'/'.$event['url'];	
	
	$event['guests']=$this->get_guests($event['id'],$uController);
	$event['share']=$this->referers($event['url'],$event['name'],$event['user']);
	$event=$this->public_data($event,true);
	
	$event['rate']=$this->rate($event_id,false,true);
	

	
	//$event['about']=nl2br($event['about']);
	return Tools::memcache($memcachetoken,$event);
    }

    
    public function public_data($event,$allpublic=false)
    {
	$this->geo_tolerance($event,$allpublic);		

	foreach ($event AS $k=>$v) if (substr($k,0,2)=='d_') unset($event[$k]);
	if (isset($event['img']) && $event['img'] && !is_array($event['img']))
	{
	    $image=new imageModel($event['img']);
	    unset($event['img']);
	    $data=$image->data();
	    foreach ($data AS $k=>$v)
	    {
		if ($k=='user' || $k=='src' || substr($k,0,2)=='d_') continue;
		$event['img'][$k]=$v;
	    }
	}
	
	foreach (['id','user','parent','active','min_guests','max_guests','unlisted','fullhouse','ical_id','transfer','change','active_err'] AS $k) unset($event[$k]);
	return $event;
    }

    protected function geo_tolerance(&$event,$public=false)
    {
	if (!$event['restaurant'] && ($public || !Tools::userHasAccessToEvent($event['id']))) {
	    $tolerance=Bootstrap::$main->getConfig('event.geo_tolerance');
	    
	    for($q=10;$q*$tolerance<1;$q*=10);
	    $max=$q*$tolerance;
	    $prec=10000;
	    $max*=$prec;
	    $q*=$prec;
	    
	    $event['lat']=$event['lat']+(mt_rand(0,1)?:-1)*(mt_rand(0,$max)/$q);
	    $event['lng']=$event['lng']+(mt_rand(0,1)?:-1)*(mt_rand(0,$max)/$q);
	    $event['tolerance']=$tolerance;
	    
	    foreach (['address','postal','hints'] AS $k) unset($event[$k]);
	} else {
	    $event['tolerance']=0;
	}
	
    }
    
    public function get_map()
    {
	$opt=$this->nav_array(Bootstrap::$main->getConfig('event.map_limit'));
    
	$this->check_input();
	foreach(['lat1','lat2','lng1','lng2'] AS $p)
	{
	    if (!(0+$this->data($p))) return $this->error(67,$p);
	    $$p=$this->data($p);
	    $opt[$p]=$$p;
	}
	
	if ($lat1>$lat2) {
	    $lat=$lat1;
	    $lat1=$lat2;
	    $lat2=$lat;
	}

	if ($lng1>$lng2) {
	    $lng=$lng1;
	    $lng1=$lng2;
	    $lng2=$lng;
	}
	
	if ($lng2-$lng1>1 || $lat2-$lat1>1) return $this->error(69);
	
	$events=$this->event()->map($lat1,$lat2,$lng1,$lng2,$opt['limit'],$opt['offset']);
	$this->extend_search($events);
	$opt['count']=is_array($events)?count($events):0;
	
	
	$ret=array('status'=>true,'options'=>$opt,'events'=>$events);
	
	//mydie($ret);
	
	return $ret;
	
    }
    
    public function get_search()
    {
	$this->check_input(['tags'=>['*'=>1]]);
	
	$opt=$this->nav_array(Bootstrap::$main->getConfig('event.search_limit'));
	$opt['tags']=isset($this->data['tags']) && is_array($this->data['tags']) ? $this->data['tags'] : [];
	if (isset($this->data['tags']) && !is_array($this->data['tags'])) $opt['tags'] = explode(',',$this->data['tags']);
	
	foreach($opt['tags'] AS $tag)
	{
	    if (!in_array($tag,Bootstrap::$main->tags)) return $this->error(27,$tag);
	}
	
	if ($this->data('lng')) $opt['lng']=$this->data('lng');
	else $opt['lng']=$this->data('lng');
	if ($this->data('lat')) $opt['lat']=$this->data('lat');
	else $opt['lat']=$this->data('lat');
	
	
	if (!isset($this->data['lng']) || !isset($this->data['lat']))
	{
	    $geo=Tools::geoip();
	
	    if (isset($geo['location']['longitude']) && isset($geo['location']['latitude']))
	    {
		$opt['lat']=$geo['location']['latitude'];
		$opt['lng']=$geo['location']['longitude'];
	    }
	}
	
	$country=null;
	if (!$opt['lat'] || !$opt['lng'])
	{
	    $geo=Tools::geoip();
	    if (isset($geo['location']['country'])) $country=$geo['location']['country'];
	}
	
	if ($this->data('distance')) {
	    $opt['distance']=$this->data['distance'];
	} else {
	    $opt['distance']=Bootstrap::$main->getConfig('event.search_distance');
	}
	
	$start=0;
	$end=0;
	
	if ($this->data('start'))
	{
	    $start+=$this->strtotime($this->data('start'));
	    $opt['start']=$this->data('start');
	}
	if ($this->data('end'))
	{
	    $end+=$this->strtotime($this->data('end'))+24*3600;
	    $opt['end']=$this->data('end');
	}
	
	$status=true;
	
	$mc_token='';
	
	if (!$opt['lat'] || !$opt['lng'])
	{
	    $mc_token='search-events,0,0-'.$country.$this->data('vip').$opt['offset'].','.$opt['limit'].'-'.$this->data('unique');
	}
	
	if ($mc_token)
	{
	    $ret=Tools::memcache($mc_token);
	    if ($ret) return $ret;
	}
	
	$events=$this->event()->search($opt['offset'],$opt['limit'],$opt['tags'],$opt['lat'],$opt['lng'],$opt['distance'],$start,$end,$this->data('vip'),$country)?:[];

	
	
	$zabezpieczenie=$opt['limit'];
	$offset=$opt['offset']+$opt['limit'];
	while ($this->data('unique') && $zabezpieczenie>0)
	{
	    $zabezpieczenie--;
	    $event_tokens=[];
	    foreach($events AS $i=>$event)
	    {
		$token=$event['user'].':'.$event['url'];
		if (isset($event_tokens[$token])) {
		    unset($events[$i]);
		    continue;
		}
		$event_tokens[$token]=true;
	    }
	    if (count($events)<$opt['limit'])
	    {
		$events2=$this->event()->search($offset,$opt['limit']-count($events),$opt['tags'],$opt['lat'],$opt['lng'],$opt['distance'],$start,$end)?:[];
		if (!count($events2)) break;
		$offset+=count($events2);
		$events=array_merge($events,$events2);
	    }
	    else
	    {
		break;
	    }
	    
	
	}
	if ($this->data('unique'))
	{
	    $events2=[];
	    foreach($events AS $event) $events2[]=$event;
	    $events=$events2;
	}
	
	
	$this->extend_search($events);
	$opt['count']=is_array($events)?count($events):0;
	//if (!$opt['count']) $status=false;
	
	$ret=array('status'=>$status,'options'=>$opt,'events'=>$events);

	Tools::memcache($mc_token,$ret);
	return $ret;
    }
    
    
    protected function extend_search(&$events)
    {
	$tags=new tagModel();
	$image=new imageModel();
	
	
	if(is_array($events)) foreach ($events AS &$event)
	{	    
	    $this->geo_tolerance($event,true);
	    
	    if ($event['parent']) {
		$event['url']=$this->event($event['parent'])->url;
	    }

	    $t=$tags->for_event($event['parent']?:$event['id']);
	    $event['tags']=$t?Tools::tags($t):[];
	    if (!is_array($event['img']) && $event['img']>0 && !isset($event['img_url']))
	    {
		$event['img']=$image->get($event['img']);
		foreach(['id','src','user'] AS $f) if (isset($event['img'][$f])) unset($event['img'][$f]);
	    }
	    foreach ($event AS $k=>$v)
	    {
		if (substr($k,0,2)=='d_') unset($event[$k]);   
	    }
	    $user=$this->_get_user($event['user']);
	    $event['host']=$user;
	    if (isset($user['url'])) $event['url']=$user['url'].'/'.$event['url'];
	    
	    $event['free_slots']=$this->event()->getSlots($event['id']);
	    foreach (['user','parent','active','min_guests'] AS $k) unset($event[$k]);
	    
	}
	
    }
    
    public function post_search()
    {
	return $this->get_search();
    }
    
    public function delete_rate()
    {
	$this->requiresLogin();
	$rate_id=$this->id+0;
	if (!$rate_id) return $this->error(55);
	$model=new rateModel($rate_id);
	
	if ($model->id!=$rate_id) return $this->error(55);
	if ($model->user!=Bootstrap::$main->user['id']) return $this->error(19);
	
	$model->remove();
	$this->rate($model->event,true,false);
	$this->rate($model->event,true,true);
	return $this->status();
    }


    public function put_rate()
    {
	$this->requiresLogin();
	$rate_id=$this->id+0;
	if (!$rate_id) return $this->error(55);
	$model=new rateModel($rate_id);
	
	if ($model->id!=$rate_id) return $this->error(55);
	if ($model->user!=Bootstrap::$main->user['id']) return $this->error(19);
	
	$overall=0;
	foreach (['food','cleanliness','atmosphere'] AS $rate)
	{
	    if (!$this->data($rate))
	    {
		$overall+=$model->$rate;
		continue;
	    }
	    if (round($this->data($rate))<1 || round($this->data($rate))>5) return $this->error(54,$rate);
	    
	    $model->$rate=round($this->data($rate));
	    $overall+=$model->$rate;
	}	
	
	if($this->data('description')) $model->description=$this->data('description');
	if($this->data('title')) $model->title=$this->data('title');
	
	$model->overall=round($overall/3,1);
	
	$model->save();
	$this->rate($model->event,true,false);
	$this->rate($model->event,true,true);
	return $this->status($model->data(),true,'rate');
    }


    
    public function post_rate()
    {
	$this->requiresLogin();
	$this->check_input();
	
	$event_id=$this->id+0;
	if (!$event_id) return $this->error(31);
	$this->event()->get($event_id);
	if ($this->event()->id!=$event_id) return $this->error(31);
	if ($this->event()->d_event_start > Bootstrap::$main->now) return $this->error(59);
	if ($this->event()->user==Bootstrap::$main->user['id']) return $this->error(51);
	if (!$this->event()->active) return $this->error(62);
	if ($this->event()->active==-1) return $this->error(61);
	if (!Tools::userHasAccessToEvent($event_id)) return $this->error(19);
	
	$model=new rateModel();
	if ($model->user_has_rated_event($event_id)) return $this->error(52);
	
	$overall=0;
	foreach (['food','cleanliness','atmosphere'] AS $rate)
	{
	    if (!$this->data($rate)) return $this->error(53,$rate);
	    if (round($this->data($rate))<1 || round($this->data($rate))>5) return $this->error(54,$rate);
	    
	    $model->$rate=round($this->data($rate));
	    $overall+=$model->$rate;
	}
	
	$model->description=$this->data('description');
	$model->title=$this->data('title');
	
	$model->user=Bootstrap::$main->user['id'];
	$model->event=$event_id;
	$model->host=$this->event()->user;
	$model->overall=round($overall/3,1);
	$model->d_create=Bootstrap::$main->now;
	$model->save();


	if ($model->id)
	{
	    $this->rate($model->event,true,false);
	    $this->rate($model->event,true,true);
	    return $this->status($model->data(),true,'rate');
	}
	
	return $this->status(null,false);
	
    }
    
    public function rate($event_id,$overwrite=false,$family=false)
    {
	if (!$event_id) return 0;
	$token='rate:event:'.$event_id;
	if ($family) $token.=':fam';
	
	$rate=Tools::memcache($token);
	if ($rate && !$overwrite) return $rate;
	
	$model=new rateModel();
	$rate=$model->event($event_id,$family);
	$count=0+$model->event_count($event_id,$family);
	return Tools::memcache($token,['rate'=>0+$rate,'prc'=>round(20*$rate),'count'=>$count]);
    }
    
    public function post_image()
    {
	$event=0+$this->data('event');
	$image=0+$this->data('image');
	
	if (!$event) return $this->error(31);
	if (!$image) return $this->error(18);
	
	
	if (!Tools::userHasAccessToEvent($event)) return $this->error(19);
	
	$imageModel=new imageModel($image);
	if ($imageModel->user!=Bootstrap::$main->user['id']) return $this->error(19);
	
	return $this->status(null,$imageModel->addEvent($event));
    }

    public function put_image()
    {
	$event=0+$this->data('event');
	$image=0+$this->data('image');
	

	if (!$event) return $this->error(31);
	if (!$image) return $this->error(18);
	
	$this->event()->get($event);
	if ($this->event()->user != Bootstrap::$main->user['id']) return $this->error(19);
	
	$imageModel=new imageModel($image);
	if (!$imageModel->user) return $this->error(18);
	
	return $this->status(null,$imageModel->removeEvent($event));
    }

    
    protected function reviews($event_id)
    {
	$opt=$this->nav_array(Bootstrap::$main->getConfig('reviews.limit'));
	$rate=new rateModel();
	
	$reviews=$rate->event_reviews($event_id,true,$opt['limit'],$opt['offset']);
	$usCtrl=new userController();
	
	if (is_array($reviews) ) foreach($reviews AS &$review)
	{
	    $user_id=$review['user'];
	    $this->clear_review($review);
	    $review['user']=$usCtrl->public_data($this->user()->get($user_id),true);
	}

	$opt['add']=0;
	if (isset(Bootstrap::$main->user['id']) && $opt['offset']==0)
	{
	    $guest=new guestModel();
	    $rate=new rateModel();
	    $guests=$guest->getGuestsForAllEvents($event_id)?:[];
	    foreach($guests AS $g)
	    {
		if ($g['d_event_start']>Bootstrap::$main->now) continue;
		if ($rate->user_has_rated_event($g['event'])) continue;
		$opt['add']=$g['event'];
		break;
	    }
	}
	return array('status'=>is_array($reviews),'options'=>$opt,'reviews'=>$reviews);

    }
    
    public function recalculate_fullhouse($event)
    {
	
	$slots=$this->event($event)->getSlots();
	$this->event()->fullhouse=$slots>0?null:1;
	$this->event()->_guest_count=$this->event()->max_guests-$slots;
	$this->event()->save();
	
	
	if ($this->event()->active==1 || $this->event()->parent) {
	    
	    $lang=Bootstrap::$main->lang;
	    
	    foreach (Bootstrap::$main->langs() AS $l)
	    {
		Bootstrap::$main->lang=$l;
		if ($this->event()->parent) {
		    $this->getPublicEvent($this->event()->parent,null,true);
		} else {
		    $this->getPublicEvent($this->event()->data(),null,true);
		}
	    }	    
	    Bootstrap::$main->lang=$lang;
	    
	    $userController=new userController();
	    $userController->getPublicUser($this->event()->user,true);
	}
    }
    
    public function get_price()
    {
	$data=$this->data;
	Tools::change_datetime($data);
	return $this->status($data,true,'price');
	
    }
    
    public function get_last()
    {
	$this->check_input();
	
	$opt=$this->nav_array(Bootstrap::$main->getConfig('event.last_limit'));
	
	$geo=Tools::geoip();
	$country='XX';
	if (isset($geo['location']['country'])) $country=$geo['location']['country'];
	$opt['country']=$country;
	
	$token='last-events-'.$opt['limit'].'-'.$opt['offset'].'-'.$country;
	
	$events=Tools::memcache($token);
	
	if (!$events) {
            $events=$this->event()->get_passed_public_events($country,$opt['offset'],$opt['limit'])?:[];
	    if (!count($events)) $events=$this->event()->get_passed_public_events(null,$opt['offset'],$opt['limit'])?:[];
	    $this->extend_search($events);
	    Tools::memcache($token,$events,900);
	}

	$opt['count']=is_array($events)?count($events):0;

	
	$ret=array('status'=>true,'options'=>$opt,'events'=>$events);

	return $ret;
	
    }
    
}
