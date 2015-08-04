<?php
require_once __DIR__.'/Controller.php';
require_once __DIR__.'/../models/cityModel.php';
require_once __DIR__.'/../models/eventModel.php';

class cityController extends Controller {
 
    protected $_city;

    /**
     * @return cityModel
     */    
    protected function city()
    {
	if (is_null($this->_city)) $this->_city=new cityModel();
	return $this->_city;
    }

    public function get($country=null)
    {
	
	if ($this->id && $this->id>0)
	{
	    $city=$this->city()->get($this->id);
	    return $this->status($city,isset($city['id']));
	}
	
	if (is_null($country))
	{
	    $geo=Tools::geoip();
	    $country=$geo['location']['country'];
	}
	
	$token='cities:'.$country;
	
	
	
	if ($data=Tools::memcache($token)) return $this->status($data,true,'cities');
	
	
	foreach([$country,null] AS $cntr) {
	    
	    $cities=$this->city()->country($cntr)?:[];
	    
	    $data=array();
	    $event=new eventModel();
	    
	    foreach($cities AS $city)
	    {
		$events=$event->search(0,0,null,$city['lat'],$city['lng'],$city['distance']?:50)?:[];
		$c=sprintf('%05d',count($events));
		$city['count']=$c+0;
		if (count($events)) $data[$c.'-'.$city['name']]=$city;
	    }
    
	    krsort($data);
	    $data2=[];
	    foreach($data AS $c)
	    {
		$data2[]=$c;
		if (count($data2)==3) break;
	    }
	    
	    if (count($data2)) break;
	}
	return $this->status(Tools::memcache($token,$data2),true,'cities');
    }
    
    public function post()
    {
	$this->requiresLogin(true);
	
	if (!$this->data('name')) $this->error(45);
	if (!$this->data('country')) $this->error(47);
	
	if (!$this->data('lat') || !$this->data('lng'))
	{
    
	    $url='https://maps.google.com/maps/api/geocode/json?address='.urlencode($this->data['name']).'&sensor=false&region='.$this->data['country'].'&key='.Bootstrap::$main->getConfig('maps.api_key');  
	    $city=json_decode($this->req($url),true);
	    
	    
	    if (isset($city['results'][0]['geometry']['location']))
	    {
		$this->data['lat'] = $city['results'][0]['geometry']['location']['lat'];
		$this->data['lng'] = $city['results'][0]['geometry']['location']['lng'];
	    }
	    
	}
	
	if ($this->data('lat')+0==0) $this->error(46,'lat');
	if ($this->data('lng')+0==0) $this->error(46,'lng');
	
	if (!$this->data('distance')) $this->data['distance']=25;
	
	if (isset($this->data['id'])) unset($this->data['id']);
	
	$this->city()->load($this->data,true);
	$this->city()->save();
	
	return $this->status($this->city()->data());
    }
    
    public function put()
    {
	$this->requiresLogin(true);
	$this->city()->get($this->id);
	
	if (!$this->city()->id) $this->error(13);
	
	foreach ($this->data AS $k=>$v)
	{
	    if ($k!='id') $this->city()->$k=$v; 
	}
	
	$this->city()->save();
	
	return $this->status($this->city()->data());
    }

    public function delete()
    {
	$this->requiresLogin(true);
	$this->city()->get($this->id);
	
	if (!$this->city()->id) $this->error(13);
	$this->city()->remove();
	
	return $this->status();
    }
    
    public function get_list()
    {
	$this->requiresLogin(true);
	return $this->status($this->city()->getAll());
    }
    
    
    
    
}
