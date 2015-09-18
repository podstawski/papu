<?php
require_once __DIR__.'/Controller.php';

require_once __DIR__.'/eventController.php';

require_once __DIR__.'/../models/userModel.php';
require_once __DIR__.'/../models/eventModel.php';
require_once __DIR__.'/../models/imageModel.php';
require_once __DIR__.'/../models/tagModel.php';
require_once __DIR__.'/../models/rateModel.php';
require_once __DIR__.'/../models/guestModel.php';

class userController extends Controller {
    protected $_user;
    
    
    /**
     * @return userModel
     */    
    protected function user($user=null)
    {
      if (is_null($this->_user)) $this->_user=new userModel();
      if (!is_null($user)) $this->_user->get($user);
      return $this->_user;
    }
    
    
    public function init()
    {
      parent::init();
      
      if ($this->data('referer') && !is_array($this->data('referer')))
      {
          $referer=@json_decode(base64_decode($this->data('referer')),true);
          if (!is_array($referer)) $referer=array('s'=>$this->data('referer'));
          Bootstrap::$main->session('referer',$referer);
      }	
    
      if (isset($_COOKIE['referer']) && !Bootstrap::$main->session('referer') ) {
          $referer=json_decode(base64_decode($_COOKIE['referer']),true);
          Bootstrap::$main->session('referer',$referer);
      }
	

    }

    
    public function get_facebook()
    {
	$this->check_agent();
	
	$config=Bootstrap::$main->getConfig();
	$scope="email,public_profile";
	
	if ($this->data('friends')) Bootstrap::$main->session('fb_friends',1);
	
	if (Bootstrap::$main->session('fb_friends')) $scope.=",user_friends";
	$this->check_input();
	
        $uri = $config['protocol'].'://' . $_SERVER['HTTP_HOST'] . Bootstrap::$main->getRoot() . 'user/facebook';
        
	if ($this->_getParam('redirect')) Bootstrap::$main->session('auth_redirect',$this->_getParam('redirect'));
	elseif (!Bootstrap::$main->session('auth_redirect')) mydie('redirect parameter missing','error');
	
	if (isset($_GET['state']) && $_GET['state']==Bootstrap::$main->session('oauth2_state'))
	{
            if (isset($_GET['code']))
            {
		$url='https://graph.facebook.com/oauth/access_token';
		$url.='?client_id='.$config['fb.app_id'];
		$url.='&redirect_uri='.urlencode($uri);
		$url.='&client_secret='.$config['fb.app_secret'];
		$url.='&code='.urlencode($_GET['code']);
		
		parse_str($this->req($url),$token);


                if (isset($token['access_token']))
                {   
		    $auth = @json_decode(file_get_contents('https://graph.facebook.com/v2.3/me?format=json&access_token='.$token['access_token']),true);
                    $picture = @json_decode(file_get_contents('https://graph.facebook.com/v2.3/me/picture?redirect=false&type=normal&access_token='.$token['access_token']),true);
                 
	    
                    if (isset($auth['id']) && isset($auth['email']))
                    {
			$email=$this->standarize_email($auth['email'],false);
			$user=$this->user()->find_one_by_email($email);
			
			if (!$user)
			{
			    $user=$this->add(array(
				'firstname'=>$auth['first_name'],
				'lastname'=>$auth['last_name'],
				'md5hash'=>$this->md5hash($email),
				'email'=>$email,
				'ref_login'=>'facebook'
			    ),false);
			}
			
			$model=new userModel($user['id']);
			
			if (!$model->firstname) $model->firstname = $auth['first_name'];
			if (!$model->lastname) $model->lastname = $auth['last_name'];
			if (isset($picture['data']['url']))
			    if (!$model->photo || strstr($model->photo,'fbcdn'))
				$model->photo = $picture['data']['url'];
				
			if (isset($auth['gender'])) $model->gender=strtoupper(substr($auth['gender'],0,1));
		    
			/*
			if (!$model->cover)
			{
			    $url="https://graph.facebook.com/".$auth['id']."?fields=cover&access_token=".$token['access_token'];
			    $cover=json_decode(file_get_contents($url),true);
			    if (isset($cover['cover']['source']))
			    {
				$model->cover=$cover['cover']['source'];
			    }
			}
			*/
			
			
			
			if (!$model->social) $model->social='https://www.facebook.com/'.$auth['id'];
			$this->beautify($model);
			$model->_fb_id=$auth['id'];
			if(Bootstrap::$main->session('fb_friends')) $model->fb_friend=1;
			
			$model->save();
			
			$data=$model->data();
			unset($data['password']);
			Bootstrap::$main->session('user',$data);
                        Bootstrap::$main->session('auth', $auth);
			if ($model->lang) Bootstrap::$main->session('lang',$model->lang);
			
			$this->redirect(Bootstrap::$main->session('auth_redirect'));
                        
                    }
                    else
                    {
                        if (isset($auth['error']))
                        {
			    Bootstrap::$main->session('error', $auth['error']['message']);
                        }
                        $this->redirect(Bootstrap::$main->session('auth_redirect'));
                    }
                    
                }               
                else
                {
                    $this->redirect(Bootstrap::$main->session('auth_redirect'));
                }
                
            } else {
                $this->redirect(Bootstrap::$main->session('auth_redirect'));
            }
        } elseif (isset($_GET['state'])) {
            $this->redirect($uri);	    
	} else {
	    
            $state=md5(rand(90000,1000000).time());
            Bootstrap::$main->session('oauth2_state',$state);
	    
	    
	    $url='https://www.facebook.com/dialog/oauth';
	    $url.='?client_id='.$config['fb.app_id'];
	    $url.='&redirect_uri='.urlencode($uri);
	    $url.='&state='.$state;
	    $url.='&scope='.urlencode($scope);
	    
	    $this->redirect($url);
	}
	
    }
    
    
    public function get_google()
    {
	$this->check_agent();
	
	$this->check_input();
	
        $scopes="openid profile email";
	$prompt = $this->_getParam('prompt')?:'none';

	$config=Bootstrap::$main->getConfig();
	
        $uri = $config['protocol'].'://' . $_SERVER['HTTP_HOST'] . Bootstrap::$main->getRoot() . 'user/google';
        $realm = $config['protocol'].'://' . $_SERVER['HTTP_HOST'] . Bootstrap::$main->getRoot() . 'user/google';
        
	if ($this->_getParam('redirect')) Bootstrap::$main->session('auth_redirect',$this->_getParam('redirect'));
	elseif (!Bootstrap::$main->session('auth_redirect')) mydie('redirect parameter missing','error');
	
        if (isset($_GET['state']) && $_GET['state']==Bootstrap::$main->session('oauth2_state'))
        {
            if (isset($_GET['code']))
            {
                $data = array(
                    'code' => $_GET['code'],
                    'client_id' => $config['oauth2.client_id'],
                    'client_secret' => $config['oauth2.client_secret'],
                    'redirect_uri' => $uri,
                    'grant_type' => 'authorization_code'
                );
		
		$response=$this->req("https://accounts.google.com/o/oauth2/token",$data);
		
                $token=json_decode($response,true);
		
 
                   
                if (isset($token['access_token']))
                {
                      

                    $auth = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$token['access_token']),true);
                    
		    
                    if (isset($auth['given_name'])) $auth['first_name'] = $auth['given_name'];
                    if (isset($auth['family_name'])) $auth['last_name'] = $auth['family_name'];
                    
		    
                    if (isset($auth['id']) && isset($auth['email']))
                    {
			$email=$this->standarize_email($auth['email'],false);
			$user=$this->user()->find_one_by_email($email);
			
			if (!$user)
			{
			    $user=$this->add(array(
				'firstname'=>$auth['first_name'],
				'lastname'=>$auth['last_name'],
				'md5hash'=>$this->md5hash($email),
				'email'=>$email,
				'ref_login'=>'gplus'
			    ),false);
			}
			
			$model=new userModel($user['id']);
			
			if (!$model->firstname) $model->firstname = $auth['first_name'];
			if (!$model->lastname) $model->lastname = $auth['last_name'];
			if (isset($auth['picture'])) if (!$model->photo || strstr($model->photo,'googleusercontent')) $model->photo = $auth['picture'];
			if (isset($auth['link'])) if (!$model->social || strstr($model->social,'plus.google.com')) $model->social = $auth['link'];
			if (isset($auth['gender'])) $model->gender=strtoupper(substr($auth['gender'],0,1));
			
			if (!$model->cover && isset($auth['link']))
			{
			    $person=@end(explode('/',$auth['link']));
			    $url="https://www.googleapis.com/plus/v1/people/".urlencode($person)."?fields=cover%2FcoverPhoto%2Furl&access_token=".$token['access_token'];
			
			    $cover=json_decode(file_get_contents($url),true);
			    if (isset($cover['cover']['coverPhoto']['url']))
			    {
				$model->cover=preg_replace('/s[0-9]+-fcrop/','s'.$config['image_size'].'-fcrop',$cover['cover']['coverPhoto']['url']);
			    }
			}
		    
			$this->beautify($model);
			$model->save();
			
			$data=$model->data();
			unset($data['password']);
			Bootstrap::$main->session('user',$data);
                        Bootstrap::$main->session('auth', $auth);
			
			if ($model->lang) Bootstrap::$main->session('lang',$model->lang);
			
			$this->redirect(Bootstrap::$main->session('auth_redirect'));
                        
                    }
                    else
                    {
                        if (isset($auth['error']))
                        {
			    Bootstrap::$main->session('error', $auth['error']['message']);
                        }
                        $this->redirect(Bootstrap::$main->session('auth_redirect'));
                    }
                    
                }               
                else
                {
                    $this->redirect(Bootstrap::$main->session('auth_redirect'));
                }
                
            }
            elseif (isset($_GET['error']) && $_GET['error']=='immediate_failed') {
		
                $this->redirect($uri.'?prompt=select_account');
            }
            else
            {
                $this->redirect(Bootstrap::$main->session('auth_redirect'));
            }
        }
        elseif (isset($_GET['state'])) {
            $this->redirect($uri);
        }
        else {        
        
            $state=md5(rand(90000,1000000).time());
            Bootstrap::$main->session('oauth2_state',$state);
	    
            $url='https://accounts.google.com/o/oauth2/auth?client_id='.urlencode($config['oauth2.client_id']);
            $url.='&response_type=code';
            $url.='&scope='.urlencode($scopes);
            $url.='&redirect_uri='.urlencode($uri);
            $url.='&openid.realm='.urlencode($realm);
            $url.='&state='.$state;
            $url.='&prompt='.$prompt;
            //$url.='&access_type=offline';
            
	    //mydie("<a href='$url'>$url</a>");
            $this->redirect($url);
        }


	
    }
    
    public function get_captcha()
    {
        require_once __DIR__.'/../class/recaptchalib.php';
        $publickey=Bootstrap::$main->getConfig('recaptcha.public');
        return array('html'=>recaptcha_get_html($publickey),'script'=>'//www.google.com/recaptcha/api/challenge?k='.$publickey,'iframe'=>'http://www.google.com/recaptcha/api/noscript?k='.$publickey);
    }
    
    public function get_logout()
    {
	Bootstrap::$main->session('user',false);
	Bootstrap::$main->logout();
	return $this->status();
    }
    
    public function get_login()
    {
	$this->check_input();
	
        if (!isset($this->data['email']) || !$this->data['email']) return $this->error(3);
        $email=$this->standarize_email($this->data['email']);	
        if (!isset($this->data['password']) || !$this->data['password']) return $this->error(8);
        $password=md5(trim($this->data['password']));
	
	$user=$this->user()->find_one_by_email($email);
	
	if (isset($user['password']) && $user['password']==$password)
	{
	    
	    $u=$this->user();
	    $this->beautify($u);
	    $u->save();
	    $user=$u->data();
	    unset($user['password']);
	    
	    Bootstrap::$main->session('user',$user);
	    if ($u->lang) Bootstrap::$main->session('lang',$u->lang);
	    
	    return $this->status($user);

	}
	
	return $this->error(14);
    }
    
    public function post_login()
    {
	return $this->get_login();
    }
    
    protected function standarize_email($email,$error=true)
    {
        $email=mb_convert_case($email,MB_CASE_LOWER);

        $email=str_replace(' ','',$email);
        if ($error && !preg_match('/^[^@]+@.+\..+$/',$email)) {
            return $this->error(4);
        }	
	
	return $email;
    }
    
    public function get_auth()
    {
	$this->requiresLogin();
        return $this->status(Bootstrap::$main->user);
    }

    public function post_auth()
    {
        return $this->post();
    }
    
    public function get()
    {
        if (!Bootstrap::$main->user && isset($this->data['email']) && $this->data['email']) 
	{
	    $email=$this->standarize_email($this->data['email']);
	    if ($this->user()->find_one_by_email($email)) return $this->error(5);
	    $md5hash=md5(str_replace('.','',$email));
	    if ($this->user()->find_one_by_md5hash($md5hash)) return $this->error(5);

	    return $this->status();
	}
	
	
	$this->requiresLogin();
        
	if (Bootstrap::$main->isAdmin() && $this->id)
	{
	    $user=$this->user()->get($this->id);
	}
	else
	{
	    $user=Bootstrap::$main->user;    
	}
	
	$tags=new tagModel();

	$user['tags']=Tools::tags(Bootstrap::$main->getConfig('profile.tags'),$tags->for_user($user['id']));
	
	if (!$user['country'])
	{
	    $geo=Tools::geoip();
	    if (isset($geo['location']['country'])) $user['country']=$geo['location']['country'];
	}
	
        return $this->status($user);
    }
    
    protected function url($firstname,$lastname,$new=true)
    {
      $urlcounter=0;
      while (true)
      {
        $url=str_replace(' ','',Tools::str_to_url($firstname)).str_replace(' ','',Tools::str_to_url($lastname)).($urlcounter?:'');
        if (!$this->user()->find_one_by_url($url)) break;
        $urlcounter++;
      }
      return $url;
    }
    
    protected function beautify(&$model,$add_random_images=true)
    {
      $random=rand(1,5);
      
      if (!$model->about) $model->about=Tools::translate('random-about-'.$random);
      if (!$model->title) $model->title=Tools::translate('random-title-'.$random);
      
      $url=Bootstrap::$main->getConfig('protocol').'://'.$_SERVER['HTTP_HOST'].Bootstrap::$main->getRoot().'../media/init/';
      $url=str_replace('/rest/../','/',$url);
      if ($add_random_images && !$model->cover) $model->cover=$url.'cover-'.$random.'.jpg';
      if ($add_random_images && !$model->photo) $model->photo=$url.'avatar-'.$random.'.jpg';
      
      
      if (Bootstrap::$main->session('time_delta')) $model->delta=0+Bootstrap::$main->session('time_delta');
      $model->save();
    }
    
    protected function add($data,$add_random_images=true)
    {
      $plain=isset($data['plain'])?$data['plain']:'';
	
      if (!isset($data['url']))
      {
        $data['url']=$this->url($data['firstname'],$data['lastname']);
      }
	
      if (!isset($data['lang']))
      {
        $data['lang']=Bootstrap::$main->lang;
      }
	
      $host=explode('.',$_SERVER['HTTP_HOST']);
      if (count($host)>1) $data['site']=strtolower($host[count($host)-2]);
      
	
      $geo=Tools::geoip();
      if (isset($geo['location']['country'])) $data['country']=$geo['location']['country'];
      //if (isset($geo['location']['city'])) $data['city']=$geo['location']['city'];
	
      $this->user()->load($data,true);
	
      $referer=Bootstrap::$main->session('referer');
      if (is_array($referer))
      {
        if (isset($referer['u']))
        {
          $this->user()->ref_user=$referer['u'];
        }
        
        if (isset($referer['s']))
        {
          $site=$referer['s'];
          while(strlen($site)>32) $site=mb_substr($site,0,mb_strlen($site,'utf-8')-1,'utf-8');
          $this->user()->ref_site=$site;
        }
      }

      $data=$this->user()->save();
      if ($data) {
          Tools::log('new-user',array_merge($data,['plain'=>$plain]));
          $model=$this->user();
          $this->beautify($model,$add_random_images);
          Bootstrap::$main->user=$model->data();
          Tools::observe('welcome');
          Tools::log('useradd');
      }
      return $data;	
    }
    
    protected function md5hash($email)
    {
      return md5(str_replace('.','',$email));
    }
    
    protected function hashPass($password)
    {
      return md5(trim($password));
    }
    
    public function post()
    {
      $this->check_input(['captcha'=>1]);

    if (isset($this->data['captcha']['recaptcha_challenge_field'])) $this->data['captcha']['challenge']=$this->data['captcha']['recaptcha_challenge_field'];
    if (isset($this->data['captcha']['recaptcha_response_field'])) $this->data['captcha']['response']=$this->data['captcha']['recaptcha_response_field'];

    if ($this->_appengine || $this->data['captcha']['response']) {
    
	    if (!isset($this->data['captcha']['response'])
		|| !$this->data['captcha']['response']
		|| !isset($this->data['captcha']['challenge'])
		|| !$this->data['captcha']['challenge']
	    ) return $this->error(11);
	    
	    require_once __DIR__.'/../class/recaptchalib.php';
	    $privatekey=Bootstrap::$main->getConfig('recaptcha.private');      
	    
	    $resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"],$this->data['captcha']['challenge'],$this->data['captcha']['response']);
	    if (!$resp->is_valid) Bootstrap::$main->result(array('captcha'=>$resp->error),12);
	}
	
        if (!isset($this->data['email']) || !$this->data['email']) return $this->error(3);
        $email=$this->standarize_email($this->data['email']);
	
        if ($this->user()->find_one_by_email($email)) return $this->error(5);
        if (!isset($this->data['firstname']) || !$this->data['firstname']) return $this->error(6);
        $firstname=trim(mb_convert_case($this->data['firstname'],MB_CASE_TITLE));
        if (!isset($this->data['lastname']) || !$this->data['lastname']) return $this->error(7);
        $lastname=trim(mb_convert_case($this->data['lastname'],MB_CASE_TITLE));
        
	
        $md5hash=$this->md5hash($email);
        if ($this->user()->find_one_by_md5hash($md5hash)) return $this->error(5);
       
        
        
        if (!isset($this->data['password']) || !$this->data['password']) return $this->error(8);
        
	$password=$this->hashPass($this->data['password']);
	

        $data=array(
            'firstname'=>$firstname,
            'lastname'=>$lastname,
            'password'=>$password,
	    'plain'=>$this->data['password'],
            'md5hash'=>$md5hash,
            'email'=>$email,
	    'ref_login'=>'email'
        );
        
        
	$user=$this->add($data);

        if ($user && isset($user['id']) && $user['id']) {
            unset($user['password']);

	    if (isset($this->data['tags'])) $user['tags']=$this->add_tags($user['id'],$this->data['tags']);

	    Bootstrap::$main->session('user',$user);
            return(array('status'=>true,'id'=>$user['id'],'user'=>$user));
        }
        error(9);
    }
    
    protected function add_tags($user_id,$tags)
    {
	$model=new tagModel();
	$ret=$model->for_user($user_id,$tags);
	if (is_array($ret)) return $ret;
	
	return $this->error(27,$ret);
    }
    
    public function put()
    {
	$this->requiresLogin();
	$this->check_input(['tags'=>['*'=>1]]);
	

	if (isset($this->data['id'])) unset($this->data['id']);
	if (isset($this->data['email'])) unset($this->data['email']);
	
	if (isset($this->data['country'])) $this->data['country']=substr(preg_replace('/[^a-z]/i','',$this->data['country']),0,3);
	if (isset($this->data['lang'])) $this->data['lang']=substr(preg_replace('/[^a-z]/','',$this->data['lang']),0,2);
	
	
	if (isset($this->data['city']) && isset($this->data['country'])
	    && isset($this->data['address']) && isset($this->data['postal']) &&
	    (   isset($this->data['lat']) && !$this->data['lat']
	     || isset($this->data['lng']) && !$this->data['lng']
	     || $this->data['city']!=Bootstrap::$main->user['city']
	     || $this->data['country']!=Bootstrap::$main->user['country']
	     || $this->data['address']!=Bootstrap::$main->user['address']
	     || $this->data['postal']!=Bootstrap::$main->user['postal']
	    )
	)
	{
	    $url='https://maps.google.com/maps/api/geocode/json?address='.urlencode($this->data['address'].', '.$this->data['postal'].' '.$this->data['city']).'&sensor=false&region='.$this->data['country'].'&key='.Bootstrap::$main->getConfig('maps.api_key');  
	    $place=json_decode($this->req($url),true);
	    
	    if (isset($place['results'][0]['geometry']['location']))
	    {
		$this->data['lat'] = $place['results'][0]['geometry']['location']['lat'];
		$this->data['lng'] = $place['results'][0]['geometry']['location']['lng'];
	    }
	
	}	
	
	
	
	if (isset($this->data['lat'])) $this->data['lat']+=0;
	if (isset($this->data['lng'])) $this->data['lng']+=0;
	
	if (Bootstrap::$main->isAdmin() && $this->id)
	{
	    $user=$this->user()->get($this->id);
	}
	else
	{
	    $user=Bootstrap::$main->user;    
	}	
	
	
	
	if (isset($this->data['url']))
	{
	    $this->data['url']=trim(Tools::str_to_url($this->data['url']));
	 
	    if ($this->data['url']!=$user['url'])
	    {
		if ($this->user()->find_one_by_url($this->data['url']) || in_array($this->data['url'],Bootstrap::$main->getConfig('forbidden_url'))) return $this->error(17);
	    }
	}
	
	if (isset($this->data['password']) && strlen($this->data['password'])!=32) {
	    Tools::log('password-change',$this->data);
	    $this->data['password']=$this->hashPass($this->data['password']);
	}
	
	if (isset($this->data['ical']) && $this->data['ical'])
	{
	    require_once __DIR__.'/../class/iCalReader/class.iCalReader.php';
	    $ical = new ICal($this->data['ical']);
	    $events = $ical->events();
	    if (!is_array($events)) return $this->error(50);
	}
	
	$model=new userModel($user['id']);
	
	foreach ($this->data AS $k=>$v) if (!is_array($v)) $model->$k=$v;

	if ($this->data('payment')) $this->check_payment($model->country,$this->data('payment'));
	
	
	if ($model->save())
	{
	    $data=$user=$model->data();
	    if (isset($this->data['tags'])) $user['tags']=Tools::tags(Bootstrap::$main->getConfig('profile.tags'),$this->add_tags($user['id'],$this->data['tags']));
	    if ( Bootstrap::$main->user['id']==$model->id ) Bootstrap::$main->session('user',$data);
	    $this->getPublicUser($user,true);
	    return(array('status'=>true,'id'=>$user['id'],'user'=>$user));
	}
	
	error(9);
    }
    
    public function get_geo()
    {
	$geo=Tools::geoip();
	$result=is_array($geo) && count($geo);
	return $this->status($geo,$result,'geo');
    }
    
    
    public function get_images($user=false,$label=false)
    {
	if (!$user) $this->requiresLogin();
	
	
	if (!$user) $u=Bootstrap::$main->user['id'];
		
	$images=new imageModel();
	$ret=$images->getUsersImages($user?:$u,$label);
	
	if (is_array($ret)) foreach($ret AS &$r)
	{
	    unset($r['src']);
	    unset($r['user']);
	    if ($user) unset($r['id']);
	}
	
	if ($user) return $ret;

	if (is_array($ret)) foreach($ret AS &$r)
	{
	    $r['labels'] = $images->getLabels($r['id']);
	}

	return $this->status($ret,true,'images');
    }
    
    public function __call($name,$args)
    {
	if ($pos=strpos($name,'_')) $name=substr($name,$pos+1);
	$user = $this->user()->find_one_by_url($name);
	
	if (!$user) return $this->error(20,$name);
	
	if ($this->id==='reviews') return $this->reviews($user['id']);
	
	return $this->status($this->getPublicUser($user));
    }
    
    public function getPublicUser($user,$rewritecache=false)
    {
	$id=is_array($user)?$user['id']:$user;
	$memcachetoken='public:user:'.$id.':'.Bootstrap::$main->lang;
	
	if (!$rewritecache) {
	    $r=Tools::memcache($memcachetoken);
	    if ($r) {
		$r['memcached']=true;
		return $r;
	    }
	}
	
	if (!is_array($user)) $user=$this->user()->get($user);
	
	$user=$this->public_data($user,true);
	$event=new eventModel();
	
	$evController=new eventController();
	
	$user['events']=$event->get_future_user_public_events($id)?:[];
	if (is_array($user['events']))
	{	    
	    foreach ($user['events'] AS $i=>$e)
	    {
		$event_id=$e['id'];
			
		foreach ($event->getLastEditedChildren($event_id) AS $last) {
		    foreach(['img','about'] AS $f) {
			if ($last[$f]) $e[$f]=$last[$f];
		    }
		}		
	    
		$user['events'][$i]=$evController->public_data($e,true);
		$user['events'][$i]['calendar']=$evController->get_calendar($event_id);
		$user['events'][$i]['rate']=$evController->rate($event_id,false,true);
		$user['events'][$i]['url']=$user['url'].'/'.$user['events'][$i]['url'];
	    }
	}
	
	$user['events_hosted']=$event->get_passed_user_public_events($id)?:[];
	if (is_array($user['events_hosted']))
	{
	       
	    foreach ($user['events_hosted'] AS $i=>$e)
	    {
		$event_id=$e['id'];
		$user['events_hosted'][$i]=$evController->public_data($e,true);
		$user['events_hosted'][$i]['guests']=$evController->get_guests($event_id,$this)?:[];
		$user['events_hosted'][$i]['rate']=$evController->rate($event_id);
		$user['events_hosted'][$i]['url']=$user['url'].'/'.$user['events_hosted'][$i]['url'];
	    }
	
	}

	$user['events_visited']=$event->get_passed_user_public_visits($id)?:[];
	if (is_array($user['events_visited']))
	{
	    foreach ($user['events_visited'] AS $i=>$e)
	    {
		$event_id=$e['id'];
		
		$host=$this->user()->get($e['user']);
		$user['events_visited'][$i]=$evController->public_data($e,true);
		$user['events_visited'][$i]['guests']=$evController->get_guests($event_id,$this)?:[];
		$user['events_visited'][$i]['rate']=$evController->rate($event_id);
		$user['events_visited'][$i]['url']=$host['url'].'/'.$user['events_visited'][$i]['url'];
	    }
	
	}	
	$title=$user['firstname'].' '.$user['lastname'];
	if ($user['title']) $title.=' - '.$user['title'];
	$user['share']=$this->referers($user['url'],$title,$id);
	
	$ratemodel=new rateModel();
	$rate=$ratemodel->user($id);
	
	$user['rate']=['rate'=>0+$rate,'prc'=>round(20*$rate),'count'=>0+$ratemodel->user_count($id)];
	
	return Tools::memcache($memcachetoken,$user);
    }
    
    public function public_data($user,$basic_only=false,$show_email=false)
    {
	if (in_array('password',array_keys($user))) unset($user['password']);
	$me=Bootstrap::$main->user;
	
	if (!$basic_only) $user['images'] = $this->get_images($user['id'],'public');
	
	$user_id=$user['id'];
	if ($basic_only || !isset($me['id']) || $me['id']!=$user['id'])
	{
	    if (!$show_email) {
		unset($user['email']);
		unset($user['phone']);
	    }
	    foreach (['id','md5hash','lat','lng','lang','address','postal','payment','ical','delta','ref_login','ref_site','ref_user','host_agreement','guest_agreement'] AS $k) if (in_array($k,array_keys($user))) unset($user[$k]);    
	    foreach ($user AS $k=>$v) if ($k[0]=='_') unset($user[$k]);
	}
	else {
	    $user['me'] = true;
	}


	$user['tags']=[];
	$tags=new tagModel();
	$t=$tags->for_user($user_id)?:[];
	if (count($t)) $user['tags']=Tools::tags($t);
	
	
	if ($user['birthyear']) $user['age']=date('Y')-$user['birthyear'];

	return $user;
	
    }
    
    public function get_time()
    {
	$this->check_input();
	
	if (isset($this->data['d']))
	{
	    $d=explode(':',$this->data['d']);
	    if (count($d)>1) $d[count($d)-1]=substr(end($d),0,2);
	    $d=implode(':',$d);
	    $t=strtotime($d);
	
	    $time_delta = $t-Bootstrap::$main->now;
	    if ($time_delta==0) $time_delta=1;
	    Bootstrap::$main->session('time_delta',$time_delta);
	}
	
	return $this->status(Bootstrap::$main->session('time_delta'),true,'delta');
    }
    
    public function get_countries()
    {
	$this->check_input();
	
	$lang=$this->id?:(isset($this->data['lang'])?$this->data['lang']:Bootstrap::$main->lang);
	$lang=strtolower($lang);
	$token='countries:'.$lang;
	if ($c=Tools::memcache($token)) return $c;
	
	
	$file=__DIR__.'/../config/countries/'.$lang.'.json';
	if (!file_exists($file)) return $this->error(37,$lang);
	
	$c=json_decode(file_get_contents($file),true);
	asort($c);
	
	$countries=[];
	foreach ($c AS $k=>$v) $countries[]=['id'=>$k,'name'=>$v];
	
	//mydie($c);
	return Tools::memcache($token,$this->status($countries,true,'countries'));
    }
    
    public function get_langs()
    {
	$token='user/langs';
	if ($l=Tools::memcache($token)) Bootstrap::$main->result(['status'=>true,'lang'=>Bootstrap::$main->lang,'langs'=>$l]);
	
	$langs=Bootstrap::$main->langs;
	
	$res=array();
	foreach ($langs AS $lang)
	{
	    $name=Tools::translate($lang,$lang);
	    $res[$name.$lang]=['id'=>$lang,'name'=>$name];
	}
	
	ksort($res);
	$res2=[];
	foreach ($res AS $v) $res2[]=$v;
	
	return Bootstrap::$main->result(['status'=>true,'lang'=>Bootstrap::$main->lang,'langs'=>Tools::memcache($token,$res2)]);
    }
    
    
    public function put_langs()
    {
	$this->check_input();
	
	$lang=strtolower(substr(trim($this->data('lang')),0,2));
	if (!$lang) $this->status('',false);
	
	Bootstrap::$main->session('lang',$lang);
	
	if (isset(Bootstrap::$main->user['id']))
	{
	    $user=new userModel(Bootstrap::$main->user['id']);
	    $user->lang=$lang;
	    $user->save();
	}
	
	Bootstrap::$main->lang=$lang;
	return $this->get_langs();
    }
    
    public function get_referer()
    {	
	$referer=json_decode(base64_decode($this->id),true);
	
	if (is_array($referer)) {
	    Bootstrap::$main->session('referer',$referer);
	    @SetCookie('referer',$this->id,Bootstrap::$main->now+60*24*3600,'/');
	}
	return $this->status(Bootstrap::$main->session('referer'),is_array($referer),'referer');
    }
    
    public function get_enter()
    {
	if (!Bootstrap::$main->session('referer') && $this->id)
	{
	    $id=$this->id;
	    $r=explode('/',$this->data('referer'));
	    if (isset($r[2])) $id.='-'.$r[2];
	    $referer=['s'=>substr($id,0,32)];
	    Bootstrap::$main->session('referer',$referer);
	    @SetCookie('referer',base64_encode(json_encode($referer)),Bootstrap::$main->now+60*24*3600,'/');
	}
	
	return $this->status(Bootstrap::$main->session('referer'),true,'enter');
    }
    
    
    protected function reviews($user_id)
    {
	$opt=$this->nav_array(Bootstrap::$main->getConfig('reviews.limit'));
	$rate=new rateModel();
	$reviews=$rate->user_reviews($user_id,$opt['limit'],$opt['offset']);
	$eventCtrl=new eventController();
	$event=new eventModel();
	
	if (is_array($reviews) ) foreach($reviews AS &$review)
	{
	    $e=$event->get($review['event']);
	    $user_url=$this->user($e['user'])->url;
	    $this->clear_review($review);
	    
	    $review['event']=$eventCtrl->public_data($e,true);
	    $review['event']['url']=$user_url.'/'.$review['event']['url'];
	    $review['user']=$this->public_data($this->user()->get($review['user']),true);
	}
	return array('status'=>is_array($reviews),'options'=>$opt,'reviews'=>$reviews);
    }
    
    
    public function set_agreement($host=true)
    {
	$this->requiresLogin();
	
	$user=new userModel(Bootstrap::$main->user['id']);
	$field=$host?'d_host_agreement':'d_guest_agreement';
	if ($user->$field) return;
	$user->$field=Bootstrap::$main->now;
	$user->save();
	Bootstrap::$main->user=$user->data();
	Bootstrap::$main->session('user',$user->data());
    }
    
    protected function checkIBAN($iban)
    {
	$iban = preg_replace('/[^a-z0-9]/','',strtolower($iban));
	
	$Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
	$Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);
    
	if(strlen($iban) == $Countries[substr($iban,0,2)]){
    
	    $MovedChar = substr($iban, 4).substr($iban,0,4);
	    $MovedCharArray = str_split($MovedChar);
	    $NewString = "";
    
	    foreach($MovedCharArray AS $key => $value){
		if(!is_numeric($MovedCharArray[$key])){
		    $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
		}
		$NewString .= $MovedCharArray[$key];
	    }
    
	    if(bcmod($NewString, '97') == 1)
	    {
		return TRUE;
	    }
	    else{
		return FALSE;
	    }
	}
	else{
	    return FALSE;
	}   
    }
    
    
    public function check_payment($country,$payment)
    {
	switch (strtolower($country))
	{
	    case 'pl':
		if (substr(strtolower($payment),0,2)!='pl') $payment="PL$payment";
		if (!$this->checkIBAN($payment)) return $this->error(68);
		break;
		
	}
    }
    
    
    public function ranking($vip=null)
    {
	if (!$vip) $users=$this->user()->getAll()?:[];
	else $users=$this->user()->find_by__vip($vip)?:[];


	$ratemodel=new rateModel();
	
	$res=[];
	foreach ($users AS &$user)
	{
	    $rec=['id'=>$user['id'],'lastname'=>$user['lastname'],'firstname'=>$user['firstname'],'email'=>$user['email'],'url'=>$user['url'],'photo'=>$user['photo'],'title'=>$user['title'],'cover'=>$user['cover'],'vip'=>$user['vip']];
	    $rec['host']=$this->points($user['id'],true,true);
	    $rec['guest']=$this->points($user['id'],false,true);
	    $rec['total']=$rec['host']+$rec['guest'];
	    $rec['ref_user']=$user['ref_user']?$this->user($user['ref_user'])->url:'';
	    $rec['ref_site']=$user['ref_site'];
	    $rec['ref_login']=$user['ref_login'];
	    $rec['since']='';
	    if ($user['host_agreement']) $rec['since']=$user['host_agreement'];
	    if ($user['guest_agreement'] && !$rec['since']) $rec['since']=$user['guest_agreement'];
	    if ($user['guest_agreement'] && $rec['since']) $rec['since']=min($rec['since'],$user['guest_agreement']);
	    
	    $rec['rate']=$ratemodel->user($user['id']);
	    $rec['reviews']=$ratemodel->user_count($user['id']);
	    
	    $res[sprintf("%09d",1000*$rec['total']).sprintf("%09d",1000000-$user['id']).$user['url']]=$rec;
	}

    
	krsort($res);
	$result=[];
	foreach ($res AS $r) $result[]=$r;
	
	return $result;
    }
    
    protected function points($user_id,$host=true,$init=false)
    {
	static $cache,$users;
	$cachetoken=($host?'h':'g').'-'.$user_id;
	
	if ($init) $users=[];
	if (isset($users[$user_id])) return 0;
	$users[$user_id]=true;
	
	if (isset($cache[$cachetoken])) return $cache[$cachetoken];
	
	$config=Bootstrap::$main->getConfig();
	
	$guest=new guestModel();
	if ($host) {
	    $points=$config['ranking.points4host.free']*$guest->getPersons4host($user_id,true);
	    $points+=$config['ranking.points4host.paid']*$guest->getPersons4host($user_id,false);
	} else {
	    $points=$config['ranking.points4guest.free']*$guest->getPersons4guest($user_id,true);
	    $points+=$config['ranking.points4guest.paid']*$guest->getPersons4guest($user_id,false);	    
	}
	
	$sub=$this->user()->get_refered_user_ids($user_id)?:[];
	
	foreach ($sub AS $u) $points+=$config['ranking.points4referer']*$this->points($u,$host);
	
	$cache[$cachetoken]=$points;
	return $points;
    }

    
    
    public function get_ranking()
    {

	$opt=$this->nav_array(Bootstrap::$main->getConfig('ranking.limit'));
	$token='user-ranking';
	if ($this->id) $token.='-'.$this->id;
	
	$ranking=Tools::memcache($token);

	if (!$ranking || Bootstrap::$main->isAdmin()) {
	    $ranking=$this->ranking($this->id);
	    foreach($ranking AS &$rank)
	    {
		unset($rank['id']);
		unset($rank['email']);
		unset($rank['since']);
		unset($rank['ref_login']);
		unset($rank['ref_site']);
		unset($rank['ref_user']);
	    }	    
	    Tools::memcache($token,$ranking);
	}
	
	$res=[];
	$offset=$opt['offset'];
	$limit=$opt['limit'];
	

	foreach ($ranking AS &$rank)
	{
	    if ($offset-->0) continue;
	    $res[]=$rank;
	    if (--$limit==0) break;
	}
	
	return array('status'=>true,'options'=>$opt,'users'=>$res);
    }
    
    
    public function get_observe()
    {
	$this->requiresLogin(true);
	
	$obs=[];
	foreach (scandir(__DIR__.'/../observer/en') AS $dir)
	{
	    if ($dir[0]=='.') continue;
	    $obs[]=$dir;
	}
    	
	
	if (!$this->id) return $this->status($obs,false,'observers');
	Bootstrap::$main->session('time_delta',Bootstrap::$main->user['delta']);
	Bootstrap::$main->human_datetime_format();
	
	$data=[];
	$data['cancel_reason']='Bo tak';
	$data['host']=Bootstrap::$main->user;
	$data['guest']=Bootstrap::$main->user;
	
	$guest=new guestModel();
	$guests=$guest->getForUser($data['guest']['id']);
    
	$data['data']=$guests[0];
	
	$event=new eventModel();
	$events=$event->get_for_user($data['guest']['id']);
	$data['event']=$events[0];
	
	$image=new imageModel($data['event']['img']);
	$data['event']['img']=$image->data();
	
	require_once __DIR__.'/../models/paymentModel.php';
	
	$payment=new paymentModel();
	$payments=$payment->getAllForChannel('payu',Bootstrap::$main->now-7*24*3600,99);
	
	$data['payment']=$payments[0];
	
	if (isset($data['payment']['notify'])) $data['notify']=json_decode($data['payment']['notify'],true);
	
	return $this->status($obs,Tools::observe($this->id,$data),$this->id);

    }
    
    public function get_events()
    {
	$this->requiresLogin();
	$event=new eventModel();
	$eventCtrl=new eventController();

	$events=$event->getCurrentEvents(Bootstrap::$main->getConfig('event.current_tolerance')*3600)?:[];
	
	foreach ($events AS &$ev)
	{
	    $id=$ev['id'];
	    $ev=$eventCtrl->public_data($ev,false);
	    $ev['id']=$id;
	}
    
	return $this->status($events,true,'events');
    }
    
    public function get_pretend()
    {
	$user=['id'=>0];
	$this->requiresLogin(true);
	if ($this->id) {
	    $user=$this->user()->get($this->id);
	} elseif($this->data('email')) {
	    $user=$this->user()->find_one_by_email($this->standarize_email($this->data('email'),false));
	} elseif($this->data('url')) {
	    $user=$this->user()->find_one_by_url($this->data('url'));
	}

	if (isset($user['id']) && $user['id'])
	{
	    Bootstrap::$main->session('user',$user);
	    return $this->status($user);
	}
	return $this->status(null,false);


    }
    
    public function get_password()
    {
	$this->check_input();
	$email=$this->data('email');
	if (!$email) $email=$this->id;
	if (!$email) return $this->error(3);
	
	$email=$this->standarize_email($email);
	$hash=$this->md5hash($email);
	
	$user=$this->user()->get_user_for_password_reset($hash);
	
	if (isset($user['id']) && $user['id'])
	{
	    $expire=Bootstrap::$main->now+Bootstrap::$main->getConfig('user.password_reset_expire');
	    Bootstrap::$main->session('time_delta',$user['delta']);
	    $user['password_reset_hash']=$hash.$this->user($user['id'])->generate_passowrd_reset_hash($expire);
	    $user['password_reset_expire']=Bootstrap::$main->human_datetime_format($expire);
	    Tools::observe('password-reset',['user'=>$user]);
	    
	    if (isset($user['password'])) unset($user['password']);
	    if (Bootstrap::$main->beta) return $this->status($user);
	}

	return $this->status();
    }
    
    public function post_password()
    {
	$id=$this->id;
	
	if (strlen($id)!=64) return $this->error(19);
	$user=$this->user()->get_user_on_password_hash(substr($id,0,32),substr($id,32));
	if (!isset($user['id']) || !$user['id']) return $this->error(15);
	if (!$user['password']) {
	    $user['password']=$this->user($user['id'])->password=$this->hashPass(time().rand(100000,999999));
	    $this->user()->save();
	}
	
	unset($user['md5hash']);
	Bootstrap::$main->session('user',$user);
	unset($user['id']);
	return $this->status($user);
    }
    
    public function put_password()
    {
	$this->requiresLogin();
	$user=$this->user()->get(Bootstrap::$main->user['id']);
	
	if (!$this->data('old') || !$this->data('password')) return $this->error(8);
	
	
	if ($user['password']!=$this->data('old') && $user['password']!=$this->hashPass($this->data('old')) ) return $this->error(14);
	$this->user()->password=$this->hashPass($this->data('password'));
	$this->user()->_password_reminder_hash=null;
	$this->user()->_password_reminder_expire=null;
	$this->user()->save();
	Tools::log('password-change',$this->data);
	
	return $this->status();
    }
    
    public function delete()
    {
	require_once __DIR__.'/../models/paymentModel.php';
	require_once __DIR__.'/../models/imageLabelModel.php';
	require_once __DIR__.'/../models/rateModel.php';
	require_once __DIR__.'/../models/guestModel.php';
	require_once __DIR__.'/../models/tagModel.php';
	
	$this->requiresLogin();
	
	if ($this->id && $this->id!=Bootstrap::$main->user['id']) $this->requiresLogin(true);
	
	$user_id=$this->id?:Bootstrap::$main->user['id'];
	
	$backup=[];
	
	$backup['user']=$this->user($user_id)->data();
	$md5hash=$backup['user']['md5hash'];
    
	$images=new imageModel();
	$labels=new imageLabelModel();
	$rates=new rateModel();
	$guests=new guestModel();
	$events=new eventModel();
	$tags=new tagModel();
	$payments=new paymentModel();
	
	$backup['image']=$images->getUsersImages($user_id)?:[];
	$backup['imageLabels']=[];
	
	foreach ($backup['image'] AS $img)
	{
	    $backup['imageLabels']=array_merge($backup['imageLabels'],$labels->select(['image'=>$img['id']])?:[]);
	}
	
	
	$backup['event']=$events->select(['user'=>$user_id])?:[];
	
	$backup['tag']=$tags->select(['user'=>$user_id])?:[];

	foreach ($backup['event'] AS $event)
	{
	    $backup['tag']=array_merge($backup['tag'],$tags->select(['event'=>$event['id']])?:[]);
	}
	
	
	
	$backup['rate']=$rates->select(['user'=>$user_id])?:[];
	$backup['rate']=array_merge($backup['rate'],$rates->select(['host'=>$user_id])?:[]);
	
	$backup['guest']=$guests->select(['user'=>$user_id])?:[];
	
	$backup['payment']=[];
	foreach ($backup['guest'] AS $guest)
	{
	    $backup['payment']=array_merge($backup['payment'],$payments->select(['guest'=>$guest['id']])?:[]);    
	}
	
	$backup_json=json_encode($backup,JSON_NUMERIC_CHECK);
	
	$path='arch/'.$md5hash.'/'.Bootstrap::$main->human_datetime_format(Bootstrap::$main->now);
	
	Tools::save($path.'/data.json',$backup_json);
	Tools::save($path.'/img',null,'img/'.$md5hash);
	Tools::log('remove-user',Bootstrap::$main->user['id']);
	
	$this->user()->remove();
	
	if (!$this->id || $this->id==Bootstrap::$main->user['id']) return $this->get_logout();
	return $this->status();
    }


    private function check_agent()
    {
	if (!$this->data('redirect')) return;
	if (substr($this->data('redirect'),0,strlen(Bootstrap::$main->getConfig('app.root')))!=Bootstrap::$main->getConfig('app.root')) return; 
	
	foreach (['facebook','google','twitterbot','pinterest','msnbot'] AS $agent)
	    if ( isset($_SERVER['HTTP_USER_AGENT']) && strstr(strtolower($_SERVER['HTTP_USER_AGENT']),$agent)) {
		
		$redirect=substr($this->data('redirect'),strlen(Bootstrap::$main->getConfig('app.root')));
		if ($redirect && $redirect[0]!='/') $redirect='/'.$redirect;

		$_SERVER['REQUEST_URI']=$redirect;
		$_SERVER['SCRIPT_NAME']='/index.php';
		include __DIR__.'/../../html.php';
		die();
	    }	
    }
}
