<?php
require_once __DIR__.'/class/Tools.php';

class Bootstrap
{
    private static $SESSION_PREFIX = 'epapoo';
    private $session_prefix;
    private $conn,$config,$root;
    public static $main;
    public $ip;
    public $now;
    public $user;
    public $datetime_format='Y-m-d\TH:i:s\Z';
    public $tags;
    public $currency='PLN';
    /*
    ARS pesos argentinos,
    https://www.mercadopago.com.ar/developers/
    www.mercadopago.com.ar
    info@mediahat.net
    ({pBD8K{j^b2b

    Client_id:		5690133368745219
    Client_secret:	gjOERyZFs3mvqvxdPrze67avJvjTqy8c
    
    https://www.mercadopago.com.ar/developers/en/tools/sdk/server/php#install
    
    */
    
    public $lang,$langs;
    public $appengine=false;
    public $beta=false;
    protected $debug=null;
    public $admin=false;
    public $system=[];
    
    public function __construct($config)
    {
	$this->system['start']=isset($_SERVER['jemyrazem_start'])?$_SERVER['jemyrazem_start']:microtime(true);
	$this->system['db']=false;
	$postfix=isset($_SERVER['APPLICATION_ID'])?$_SERVER['APPLICATION_ID']:__DIR__;
        $this->session_prefix = self::$SESSION_PREFIX . '_' . md5($postfix);
        self::$main = $this;
        $this->now = time();
        $this->ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'0.0.0.0');
	$this->config=$config;
    
	if (isset($_SERVER['REQUEST_URI']))
	{
	    $uri = $_SERVER['REQUEST_URI'];
	    $root = dirname($_SERVER['SCRIPT_NAME']);
    
	    $uri = str_replace($root, '', $uri);
	    if ($root != '/') $root .= '/';
    
	    $this->root = $root;
	}
	else $root='/';
	
	$this->tags=explode(',',$config['tags']);
    
	$pos=strpos($this->config['db.dsn'],'dbname=');
	if ($pos) $this->session('db_name',substr($this->config['db.dsn'],$pos+7));
	
	$this->user=$this->session('user');
	
      
	$this->lang=$this->session('lang')?:$this->session('lang',$this->lang());
	$this->langs=$this->session('langs')?:$this->session('langs',$this->langs());
	
	if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
	{
	    $this->appengine=true;
	    if (isset($_SERVER['APPLICATION_ID']) && strstr($_SERVER['APPLICATION_ID'],'beta')) $this->beta=true;
	}
	else {
	    $this->beta=true;
	}
	
	if ($config['app.debug']) $this->debug=['timestamp'=>date('d-m-Y, H:i:s'),'file'=>$config['app.debug']];
    }

    
    public function system($mod)
    {
	if (!isset($this->system['traps'])) $this->system['traps']=[];
	if (!isset($this->system['start'])) $this->system['start']=isset($_SERVER['jemyrazem_start'])?$_SERVER['jemyrazem_start']:microtime(true);
	$this->system['traps'][sprintf("%02d",count($this->system['traps'])+1).'_'.$mod]=microtime(true)-$this->system['start'];
    }
    
    public function langs()
    {
	return ['pl','en','es'];
	$langs=Tools::memcache('langs');
	if ($langs) return $langs;
	
	$langs=array();
	foreach(scandir(__DIR__.'/langs') AS $f)
	{
	    if (is_dir(__DIR__.'/langs/'.$f)) continue;
	    $langs[]=substr($f,0,2);
	}
	return Tools::memcache('langs',$langs);
    }
    
    public function lang()
    {
	
	if ($_l=$this->session('ulang')) return $_l;
	
        $langs = $this->langs();

        // break up string into pieces (languages and q factors)
	$alang=isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])?$_SERVER['HTTP_ACCEPT_LANGUAGE']:'en';
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',$alang , $matches);

        if (count($matches[1])) {
            $tmp = array_combine($matches[1], $matches[4]);

            foreach ($tmp as $lang => $val) {
                if ($val === '')
                    $tmp[$lang] = 1;
            }

            arsort($tmp, SORT_NUMERIC);

            $lang = substr(key($tmp), 0, 2);
            if (in_array($lang, $langs)) return $lang;
        }
	

        return 'en';
    }    
    
    public function getConn()
    {
	if (!is_object($this->conn))
	{
	    try {
		$this->conn = new PDO($this->config['db.dsn'],$this->config['db.user'],$this->config['db.pass']);
		$this->system['db']=true;
	    } catch (Exception $e) {
		mydie($e);
	    }
	    
	}

        return $this->conn;
    }

    
    public function session($key = null, $val = null)
    {

        if (is_null($key)) return isset($_SESSION[$this->session_prefix]) ? $_SESSION[$this->session_prefix] : array();
        if (is_null($val)) {
            return isset($_SESSION[$this->session_prefix][$key]) ? $_SESSION[$this->session_prefix][$key] : null;
        }
        if ($val !== false) $_SESSION[$this->session_prefix][$key] = $val; else unset($_SESSION[$this->session_prefix][$key]);

        return $val;
    }
    
    public function logout()
    {
	$_SESSION[$this->session_prefix]=array();
    }
    
    public function run($method='get')
    {
	
        $part = substr($_SERVER['REQUEST_URI'], 1+strlen(dirname($_SERVER['SCRIPT_NAME'])));
        if ($pos = strpos($part, '?')) $part = substr($part, 0, $pos);
        $part=preg_replace('~/+~','/',$part);
        $parts = explode('/', $part);

	
	$data=array();
	if ($method=='get' || $method=='delete')
	{
	    $data=$_GET;
	}
	else
	{
	    $data=file_get_contents("php://input");
	    
	    if ($data && isset($_SERVER['CONTENT_TYPE']))
	    {
		if (strstr($_SERVER['CONTENT_TYPE'],'json')) $data=json_decode($data,true);
		if (strstr($_SERVER['CONTENT_TYPE'],'form-urlencoded')) parse_str($data,$data);
		if (strstr($_SERVER['CONTENT_TYPE'],'form-data')) parse_str($data,$data);
	    }
	    else
	    {
		$data=$_REQUEST;
	    }
	}
	
	if (is_array($data) && !$this->isAdmin()) foreach($data AS $k=>$v) if ($k[0]=='_') unset($data[$k]);

	if (!strlen($parts[0])) $parts[0] = 'index';
	$controller_name=$parts[0];
	$controller_file=__DIR__.'/controllers/'.$controller_name.'Controller.php';
	if (!file_exists($controller_file)) self::result(array('name'=>$controller_name),2);
	
	require_once $controller_file;
	
        
	$id=0;

	if (isset($parts[1]) && $parts[1]+0>0) $id=$parts[1]+0;
	elseif (isset($parts[2])) $id=$parts[2];

	if (!$id && isset($data['id']))
	{
	    $id=$data['id'];
	    unset($data['id']);
	}	
	
	
	$controller_name.='Controller';
	$controller=new $controller_name($id,$data,$parts);
	$controller->init();
	
	$this->system('init');
	
	$controller_method=$method;
	
	if (isset($data['action']) && preg_match('/^[a-z]/i',$data['action']))
	{
	    $controller_method.='_'.strtolower($data['action']);
	}
	elseif (isset($parts[1]) && preg_match('/^[a-z]/i',$parts[1]))
	{
	    $controller_method.='_'.$parts[1];
	}
	
	if ($this->debug) {
	    $this->debug['id']=$id;
	    $this->debug['data']=$data;
	    $this->debug['controller']=$controller_name;
	    $this->debug['controller_method']=$controller_method;
	}
	
	$result=$controller->$controller_method();
	
        self::result($result);
    }
    
    public function closeConn()
    {
        if (is_object($this->conn)) unset($this->conn);
    }
    
    
    public static function result($result,$error=null,$die=true)
    {
        header("Content-Type: application/json; charset=utf8");
        if (!is_array($result)) $result=array();
	
        if (!is_null($error))
	{
	    $result['status']=false;
	    ini_set('display_erros','on');
	    require_once __DIR__.'/class/Error.php';
	    $result['error']=Error::e($error);

	}
	self::clear_data($result);
	self::$main->system('total');
	unset(self::$main->system['start']);
	$result['x_system']=self::$main->system;
	if ($die) die(json_encode($result,JSON_NUMERIC_CHECK));
	return $result;
    }

    protected static function clear_data(&$data)
    {
	if (is_array($data)) foreach($data AS $k=>&$v)
	{
	    if (substr($k,0,2)=='d_' || substr($k,0,4)=='ref_') {
		unset ($data[$k]);
		continue;
	    }
	    if (is_array($v)) self::clear_data($v);
	}
    }

    public function getConfig($index=null)
    {
	if ($index) return $this->config[$index];
	return $this->config;
    }
    
    
    public function getRoot()
    {
        return $this->root;
    }
    
    
    public function debug()
    {
	if (!$this->debug) return;
	$debug=$this->debug;
	$debug['args']=func_get_args();
	$file=$debug['file'];
	unset($debug['file']);
	file_put_contents($file,print_r($debug,1),FILE_APPEND);
    }
    
    public function human_datetime_format($dt=null)
    {
	$format='d-m-Y H:i';
	
	if (is_null($dt)) {
	    $this->datetime_format=$format;
	} else {
	    $row=['d_date'=>$dt];
	    $f=$this->datetime_format;
	    $this->datetime_format=$format;
	    Tools::change_datetime($row);
	    $this->datetime_format=$f;
	    return $row['date'];
	}
	
    }
    
    public function isAdmin()
    {
	if (!isset($this->user['id']) || !$this->user['id']) return false;
	return in_array($this->user['id'],$this->getConfig('admin.ids'));
    }
    
}