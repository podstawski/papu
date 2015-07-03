<?php
include_once __DIR__.'/../class/Tools.php';
include_once __DIR__.'/../models/rateModel.php';

class Controller {
    protected $id,$data,$name,$parts;
    protected $_appengine=false;
    protected $error_trap=null;
    
    public function __construct($id=0,$data=[],$parts=[])
    {
        $this->id=$id;
        $this->data=$data;
        $this->parts=$parts;
        $this->name = str_ireplace('controller','',get_class($this));
        $this->_appengine=Bootstrap::$main->appengine;
    }
    
    
    public function __call($name,$args)
    {
        Bootstrap::$main->result(array('name'=>$this->name,'method'=>$name),2);
    }
    
    public function init()
    {
    }
    
    public function get()
    {
        
    }

    public function post()
    {
        
    }
    public function delete()
    {
        
    }

    public function put()
    {
        
    }

    protected function error($id=0,$ctx=null)
    {
        if (!is_null($ctx) && !is_array($ctx)) $ctx=['ctx'=>$ctx];
        if (is_null($this->error_trap)) Bootstrap::$main->result($ctx,$id);
        $this->error_trap=Bootstrap::$main->result($ctx,$id,false);
    }

    protected function status($data=null,$status=true,$name=null)
    {
        if (is_null($name)) $name=$this->name;
        $ret=array('status'=>$status);
        if ($data || is_array($data))
        {
            if (is_array($data)) Tools::change_datetime($data);
            $ret[$name] = $data;
        }
        return $ret;
    }
    
    public function options()
    {
        die();
    }
    
    /**
     * @param string $name
     * @return mixed
     */
    protected function _getParam($name, $defaultValue = null)
    {
        $value = @$_REQUEST[$name];
        if (($value === null || $value === '') && ($defaultValue !== null)) {
            $value = $defaultValue;
        }
        return $value;
    }
    
    protected function redirect($redirect)
    {
        if ($redirect=='__close') die('<script>window.close();</script>');
        Header('Location: '.$redirect);
        die();
    }
    
    protected function requiresLogin($admin=false)
    {
        if (!Bootstrap::$main->user) $this->error(15);
        if ($admin && !Bootstrap::$main->isAdmin()) $this->error(19);
    }
    
    protected function urlencode(array $data)
    {
	$d='';
	foreach($data AS $k=>$v) {
	    if ($d) $d.='&';
	    $d.=urlencode($k).'='.urlencode($v);
	}
	return $d;	
    }    
    
    protected function req($url,$data=null,$method='POST',$header="Content-Type: application/x-www-form-urlencoded")
    {
	if (is_null($data)) return file_get_contents($url);
	
	if (is_array($data)) $data=$this->urlencode($data);

	$context = array("http" => array(
			    "method" => $method,
			    "header" => $header,
			    "content" => $data,
			    "follow_location" =>0
			 ),
			 "ssl" => array (
			    "verify_peer" => "0",
			    'ciphers'=>'AES256-SHA'
			 )
	);
	$context['https']=$context['http'];
	
	//mydie($context);
	$ctx = stream_context_create($context);

	return file_get_contents($url, false, $ctx);
    }
    
    protected function _get_user($user_id)
    {
        $token='user:'.$user_id;
        
        if ($ret=Tools::memcache($token)) return $ret;
        
        $user=new userModel($user_id);
        
        if (!$user->id) return false;
        $ret=[];
        foreach (['firstname','lastname','url','photo','about','gender','title','vip','lang'] AS $k) $ret[$k]=$user->$k;
        
        
        $ratemodel=new rateModel();
	$rate=$ratemodel->user($user_id);
	
	$ret['rate']=['rate'=>0+$rate,'prc'=>round(20*$rate),'count'=>0+$ratemodel->user_count($user_id)];
	
        
        return Tools::memcache($token,$ret);
        
    }
    
    protected function data($i)
    {
        if (isset($this->data[$i])) return $this->data[$i];
        return null;
    }
    
    protected function strtotime($t)
    {
        $delta=0+Bootstrap::$main->session('time_delta');
        $ret=strtotime($t);
        if (substr($t,-1)!='Z') $ret-=$delta;
        return $ret;
    }
    
    
    protected function referers($url,$title='',$user=null)
    {
        $title=trim($title);
        $media = array(
            'facebook' => array( 'url'=>'https://www.facebook.com/sharer/sharer.php?u={url}&t='.urlencode($title)),
            'gplus' => array( 'url'=>'https://plus.google.com/share?url={url}'),
            'linkedin' => array( 'url'=>'http://www.linkedin.com/shareArticle?mini=true&url={url}&title='.urlencode($title).'&summary=&source={url}'),
            'twitter' => array( 'url'=>'https://twitter.com/intent/tweet?source={url}&text=:%20{url}'),
            /*'tumblr' => array( 'url'=>'http://www.tumblr.com/share?v=3&u={url}&t='.urlencode($title).'&s='),
            'pinterest' => array( 'url'=>'http://pinterest.com/pin/create/button/?url={url}&description='.urlencode($title)),
            'getpocket' => array( 'url'=>'https://getpocket.com/save?url={url}&title='.urlencode($title)),
            'reddit' => array( 'url'=>'http://www.reddit.com/submit?url={url}&title='.urlencode($title)),
            'pinboard' => array( 'url'=>'https://pinboard.in/popup_login/?url={url}&title=&description='),
            */
            'blog' => array( 'url'=>'{url}&embedded=js'),
            'mail' => array( 'url'=>'mailto:?subject='.urlencode($title).'&body={url}'),
        );
        
        $u=isset(Bootstrap::$main->user['id'])?Bootstrap::$main->user['id']:$user;
        $root=Bootstrap::$main->getConfig('app.root');
        $res=[];
        foreach ($media AS $s=>$m)
        {
            $url2=$root.$url.(strstr($url,'?')?'&':'?');
            $referer=['s'=>$s];
            if ($u) $referer['u']=$u;
            $url2.='referer='.urlencode(base64_encode(json_encode($referer,JSON_NUMERIC_CHECK)));
            $type='popup';
            if (strstr($m['url'],'mailto:')) $type='mailto';
            if (strstr($m['url'],'embedded=js')) $type='blog';
            if (!strstr($m['url'],'embedded=js')) $url2=urlencode($url2);
            $res[]=['id'=>$s,'type'=>$type,'url'=>str_replace('{url}',$url2,$m['url'])];
        }
        
        return $res;
    }
    
    protected function nav_array($search_limit)
    {
        $opt=array();
	$opt['limit']=isset($this->data['limit']) && $this->data['limit']+0>0 ? $this->data['limit'] : $search_limit;
	$opt['offset']=isset($this->data['offset']) && $this->data['offset']+0>0 ? $this->data['offset'] : 0;
        return $opt;
    }
    
    protected function clear_review(&$review)
    {
        $review['editable']=isset(Bootstrap::$main->user['id']) && Bootstrap::$main->user['id']==$review['user'];
        foreach (['host','event'] AS $field) unset($review[$field]);
        if (!$review['editable']) unset($review['id']);
        foreach (['food','cleanliness','atmosphere','overall'] AS $field) $review[$field.'_prc']=round($review[$field]*20);          
    }
    
    protected function check_input($arrays=[],$data=null)
    {
        if (is_null($data)) $data=$this->data;
        

        if (is_array($data)) foreach ($data AS $k=>$v) {
            if (!is_array($v)) continue;
            if (!is_array($arrays) || ( !isset($arrays[$k]) && !isset($arrays['*'])) ) {
                $this->error(65,$k);
            }
            if (!isset($arrays[$k])) $this->check_input($arrays['*'],$v);
            else $this->check_input($arrays[$k],$v);
            
        }
    }
    
    protected function require_fb_friend($user_id,$error=true)
    {
	if ( !isset(Bootstrap::$main->user['fb_id']) || !Bootstrap::$main->user['fb_id'] || !isset(Bootstrap::$main->user['fb_friend']) || !Bootstrap::$main->user['fb_friend'])
	{
	    Bootstrap::$main->session('fb_friends',1);
	    return $error?$this->error(72):72;
	}
	
	require_once __DIR__.'/../models/userModel.php';
	$user=new userModel($user_id);
	$him=$user->fb_id;
	$me=Bootstrap::$main->user['fb_id'];
	
	if (!Bootstrap::$main->session('fb_access_token'))
	{
	    $config=Bootstrap::$main->getConfig();
	    $url='https://graph.facebook.com/oauth/access_token';
	    $url.='?client_id='.$config['fb.app_id'];
	    $url.='&client_secret='.$config['fb.app_secret'];
	    $url.='&grant_type=client_credentials&scope=user_friends';
	    
	    parse_str($this->req($url),$token);
	    if (isset($token['access_token'])) Bootstrap::$main->session('fb_access_token',$token['access_token']);
	}
	$url='https://graph.facebook.com/v2.3/'.$me.'/friends/'.$him.'?access_token='.Bootstrap::$main->session('fb_access_token');
	$friends=json_decode(file_get_contents($url),true);
    
	if (!isset($friends['data']) || !count($friends['data']))
	    return $error?$this->error(73):73;
	    
    
	return 0;
    }

}
