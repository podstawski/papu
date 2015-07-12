<?php


class Bootstrap extends Bootstrapbase
{
    private static $SESSION_PREFIX = 'epapoo';
    public static $main;

    public $datetime_format='Y-m-d\TH:i:s\Z';
    public $tags;
    public $currency='PLN';
    /*
    ARS pesos argentinos,
    https://www.mercadopago.com.ar/developers/
    www.mercadopago.com.ar
    info@mediahat.net
    ({pBD8K{j^b2b

    
    https://www.mercadopago.com.ar/developers/en/tools/sdk/server/php#install
    
    */
    
    public $lang,$langs;

    protected $debug=null;

    
    public function __construct($config)
    {
	self::$main = $this;
	parent::__construct($config);
	
	
	$this->tags=explode(',',$config['tags']);

	$this->lang=$this->session('lang')?:$this->session('lang',$this->lang());
	$this->langs=$this->session('langs')?:$this->session('langs',$this->langs());

	
	if ($config['app.debug']) $this->debug=['timestamp'=>date('d-m-Y, H:i:s'),'file'=>$config['app.debug']];
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
	$controller_file=__DIR__.'/../controllers/'.$controller_name.'Controller.php';
	if (!file_exists($controller_file)) $this->result(array('name'=>$controller_name),2);
	
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
	
        $this->result($result);
    }
    

    
    public function result($result,$error=null,$die=true)
    {
        header("Content-Type: application/json; charset=utf8");
        if (!is_array($result)) $result=array();
	
        if (!is_null($error))
	{
	    $result['status']=false;
	    ini_set('display_erros','on');
	    require_once __DIR__.'/Error.php';
	    $result['error']=Error::e($error);

	}
	$this->clear_data($result);
	self::$main->system('total');
	unset(self::$main->system['start']);
	$result['x_system']=self::$main->system;
	if ($die) die(json_encode($result,JSON_NUMERIC_CHECK));
	return $result;
    }

    protected function clear_data(&$data)
    {
	if (is_array($data)) foreach($data AS $k=>&$v)
	{
	    if (substr($k,0,2)=='d_' || substr($k,0,4)=='ref_') {
		unset ($data[$k]);
		continue;
	    }
	    if (is_array($v)) $this->clear_data($v);
	}
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
    
    
}