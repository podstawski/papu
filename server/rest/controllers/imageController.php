<?php
require_once __DIR__.'/Controller.php';
require_once __DIR__.'/../models/imageModel.php';
require_once __DIR__.'/../models/eventModel.php';


if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine')) {
    require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
} else {
    require_once __DIR__.'/../class/Image.php';
}

use google\appengine\api\cloud_storage\CloudStorageTools;


class imageController extends Controller {
    protected $_image;
    protected $_media_dir,$_media,$_prefix='img';

    /**
     * @return imageModel
     */    
    protected function image()
    {
	if (is_null($this->_image)) $this->_image=new imageModel();
	return $this->_image;
    }
    
    public function init()
    {
	parent::init();
	if (!$this->_appengine)
	{
	    $this->_media_dir=realpath(__DIR__.'/../../../media');
	    $this->_media=$_SERVER['REQUEST_URI'];
	    if ($pos=strpos($this->_media,'?')) $this->_media=substr($this->_media,0,$pos);
	    $this->_media=dirname(dirname(dirname($this->_media))).'/media';
	}
    }
    
    
    protected function http2https($url)
    {
	if (Bootstrap::$main->getConfig('protocol')!='https') return $url;
	return str_replace('http://','https://',$url);
    }
    
    public function get()
    {
	if ($this->id)
	{
	    
	    $id=0+$this->id;
	    $data=false;
	    if ($id) $data=$this->image()->get($id);
	    
	    if ($data)
	    {
		unset($data['user']);
		unset($data['src']);
		$data['labels']=$this->image()->getLabels();
		return $this->status($data);
	    } else $this->error(18);
	    
	}
	
	$this->requiresLogin();
	
	$this->check_input(['ctx'=>1]);
	
	$upload_url=Bootstrap::$main->getRoot().'image';
	if ($this->_appengine)
	{
	    $upload_url = CloudStorageTools::createUploadUrl($upload_url, []);
	    $upload_url = $this->http2https($upload_url);
	}
	else
	{
	    $upload_url='http://'.$_SERVER['HTTP_HOST'].$upload_url;
	}
	
	$ret=array('success'=>true,'url'=>$upload_url);

	if (isset($this->data['ctx']))
	{
	    if (!$this->data['ctx']) $ctx=false;
	    else {
		if (is_string($this->data['ctx']) && substr($this->data['ctx'],0,1)=='{') {
		    $this->data['ctx']=json_decode($this->data['ctx'],true);
		}
		if (is_string($this->data['ctx'])) $ctx=explode(',',$this->data['ctx']);
		else {
		    $ctx=$this->data['ctx'];
		    if (!isset($ctx['event'])) $this->error(35);
		    if (!Tools::userHasAccessToEvent($ctx['event'])) $this->error(36);
		}
	    }
	
	    $ret['ctx']=$ctx;
	    Bootstrap::$main->session('image_ctx',$ctx);
	}
	
	
	return $ret;
    }
    
    public function post()
    {
	$this->requiresLogin();

	if (isset($_FILES))
	{
	    foreach ($_FILES AS $name=>$file)
	    {
		$f=$this->upload_file($file['tmp_name'],$file['name']);
		if (is_array($f)) return $this->status($f);
	    }
	}
	
	
	return $this->status();
    }
    

    
    
    protected function checkChunks($file)
    {
	$size=0;
	for ($i=1;$i<=$this->data['flowTotalChunks'];$i++)
	{
	    $f=preg_replace('/part[0-9]+$/','part'.$i,$file);
	    if (!file_exists($f)) return false; // file is not ready, still chunked
	    $size+=filesize($f);
	}
	
	if ($size!=$this->data['flowTotalSize']) return false;
	
	$final=preg_replace('/\.part[0-9]+$/','',$file);
	if (Tools::semaphore($final)) return false;
	
	Tools::semaphore($final,true);
	$data='';
	for ($i=1;$i<=$this->data['flowTotalChunks'];$i++)
	{
	    $f=preg_replace('/part[0-9]+$/','part'.$i,$file);
	    $data.=file_get_contents($f);
	    unlink($f);
	}
	Tools::semaphore($final,false);
	
	file_put_contents($final,$data);
	return true;
    }
    
    protected function upload_file($tmp,$name)
    {
	//mydie($this->_media_dir,$this->_media);
	$ext=@strtolower(end(explode('.',$name)));
	$user=Bootstrap::$main->user;
	
	if (isset($this->data['flowIdentifier'])) $lp=$this->data['flowIdentifier'];
	else $lp=1+$this->image()->getUsersCount($user['id']);
	
	$name=$this->_prefix.'/'.$user['md5hash'].'/'.md5($lp.'-'.$name).'.'.$ext;
	

	$chunks=false;
	$original_name=$name;
	if (isset($this->data['flowTotalChunks']) && $this->data['flowTotalChunks']>1 && isset($this->data['flowChunkNumber']))
	{
	    $chunks=true;
	    $name.='.part'.$this->data['flowChunkNumber'];
	}
	
	
	
	if ($this->_appengine) {
	    $file='gs://'.CloudStorageTools::getDefaultGoogleStorageBucketName().'/'.$name;
	    move_uploaded_file($tmp,$file);
	    
	} else {
	    $file=$this->_media_dir.'/'.$name;
	    @mkdir(dirname($file),0755,true);
	    move_uploaded_file($tmp,$file);

	    //mydie(exif_read_data($tmp));
	}

	if ($chunks)
	{
	    if (!$this->checkChunks($file)) return false;
	    $name=$original_name;
	    $file=preg_replace('/\.part[0-9]+$/','',$file);
	}
	
	if (!file_exists($file) || !filesize($file)) $this->error(18);

	$model=new imageModel();
	$model->user=$user['id'];
	$model->src=$name;
	$model->d_uploaded=Bootstrap::$main->now;	
	
	$exif=[];
	$imagesize=@getimagesize($file,$exif);
	if (!is_array($imagesize) || !$imagesize[0]) $imagesize=[5000,5000];
	
	if (is_array($exif)) foreach ($exif  AS $k=>$a)
	{
	    
	    if (substr($a,0,4)=='Exif')
	    {
		$matches=[];
		preg_match_all('/[0-9]{4}:[0-9]{2}:[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/',$a,$matches);
		$d='';
		
		if (isset($matches[0][1])) {
		    $d=$matches[0][1];
		} elseif (isset($matches[0][0])) {
		    $d=$matches[0][0];
		}
		if ($d)
		{

		    $d=preg_replace('/([0-9]{4}):([0-9]{2}):([0-9]{2})/','\1-\2-\3',$d);		    
		    $model->d_taken=$this->strtotime($d);
		}
	    }
	    
	}
	
	if ($this->_appengine) {
	    

	    $model->url = CloudStorageTools::getImageServingUrl($file,['size'=>0+Bootstrap::$main->getConfig('image_size'),'secure_url'=>Bootstrap::$main->getConfig('protocol')=='https']);
	   
	    $full=CloudStorageTools::getImageServingUrl($file,['size'=>1234,'secure_url'=>Bootstrap::$main->getConfig('protocol')=='https']);
	    $model->full = str_replace('=s1234','=s'.Bootstrap::$main->getConfig('full_size'),$full);
	    
	    $model->thumbnail = CloudStorageTools::getImageServingUrl($file,['size'=>0+Bootstrap::$main->getConfig('thumbnail_size'),'secure_url'=>Bootstrap::$main->getConfig('protocol')=='https']);
	    $model->square = CloudStorageTools::getImageServingUrl($file,['size'=>0+Bootstrap::$main->getConfig('square_size'),'secure_url'=>Bootstrap::$main->getConfig('protocol')=='https','crop'=>true]);
	
	} else {
	    $image=new Image($file);
	    
	    $w=$h=0;
	    if ($imagesize[0] > Bootstrap::$main->getConfig('image_size'))
	    {
		$w=Bootstrap::$main->getConfig('image_size');
		$img=preg_replace("/\.$ext\$/",'-i.'.$ext,$file);
		$image->min($img,$w,$h,true);
		$model->url='http://'.$_SERVER['HTTP_HOST'].$this->_media.'/'.preg_replace("/\.$ext\$/",'-i.'.$ext,$name);
	    } else $model->url='http://'.$_SERVER['HTTP_HOST'].$this->_media.'/'.$name;

	    $w=$h=0;
	    if ($imagesize[0] > Bootstrap::$main->getConfig('full_size'))
	    {
		$w=Bootstrap::$main->getConfig('full_size');
		$img=preg_replace("/\.$ext\$/",'-f.'.$ext,$file);
		$image->min($img,$w,$h,true);
		$model->full='http://'.$_SERVER['HTTP_HOST'].$this->_media.'/'.preg_replace("/\.$ext\$/",'-f.'.$ext,$name);
	    } else $model->full='http://'.$_SERVER['HTTP_HOST'].$this->_media.'/'.$name;
	    
	    $w=$h=0;
	    
	    if ($image->w() > $image->h()) $w=Bootstrap::$main->getConfig('thumbnail_size');
	    else $h=Bootstrap::$main->getConfig('thumbnail_size');
	    $thmb=preg_replace("/\.$ext\$/",'-t.'.$ext,$file);
	    $image->min($thmb,$w,$h,true);
	    $model->thumbnail='http://'.$_SERVER['HTTP_HOST'].$this->_media.'/'.preg_replace("/\.$ext\$/",'-t.'.$ext,$name);
	    
	    $w=$h=Bootstrap::$main->getConfig('square_size');
	    $square=preg_replace("/\.$ext\$/",'-s.'.$ext,$file);
	    $image->min($square,$w,$h,false,true);
	    $model->square='http://'.$_SERVER['HTTP_HOST'].$this->_media.'/'.preg_replace("/\.$ext\$/",'-s.'.$ext,$name);	    
	}
		
	
	$model->save();
	$ret=$model->data();
	
	
	if ($ctx=Bootstrap::$main->session('image_ctx'))
	{
	    
	    $model->setLabels($ctx);    
	    $ret['labels']=$model->getLabels();
	    if (is_array($ctx))
	    {
		foreach ($ctx AS $k=>$e)
		{
		    if ($k=='event')
		    {
			$event=new eventModel($e);
			
			if ($event->user==Bootstrap::$main->user['id'] && !$event->img)
			{		    
			    $event->img=$model->id;
			    $event->save();
			}
			$model->title=$event->name;
			$model->save();
		    }
		}
	    }
	}
	
	return $this->status($ret);
    }
    
    public function put()
    {
	$this->requiresLogin();
	$user=Bootstrap::$main->user;
	
	
	$this->check_input(['labels'=>1]);

	$id=0+$this->id;
	$data=false;
	if ($id) $data=$this->image()->get($id);
	
	if (!$data) $this->error(18);
	if ($data['user']!=$user['id']) $this->error(19);

	
	$model=$this->image();
	foreach (['title','description'] AS $f)
	{
	    if (isset($this->data[$f])) $model->$f=$this->data[$f];
	}
		
	$model->save();

	
	return $this->status($model->data());
    }
    
    public function delete()
    {
	$this->requiresLogin();
	$user=Bootstrap::$main->user;

	$id=0+$this->id;
	$data=false;
	if ($id) $data=$this->image()->get($id);
	
	if (!$data) $this->error(18);
	if ($data['user']!=$user['id']) $this->error(19);
	
	if ($this->_appengine) {
	    $file='gs://'.CloudStorageTools::getDefaultGoogleStorageBucketName().'/'.$data['src'];
	    CloudStorageTools::deleteImageServingUrl($file);
	} else {
	    $file=$this->_media_dir.'/'.$data['src'];
	    $ext=@end(explode('.',$file));
	    @unlink(preg_replace("/\.$ext\$/",'-t.'.$ext,$file));
	    @unlink(preg_replace("/\.$ext\$/",'-s.'.$ext,$file));
	}
	@unlink($file);
	$this->image()->remove($data['id']);
	return $this->status();
	
    }


}
