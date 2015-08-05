<?php

  if (!function_exists('mydie')) include __DIR__.'/admin/base.php';

  require_once __DIR__.'/rest/controllers/eventController.php';
  require_once __DIR__.'/rest/controllers/userController.php';
  require_once __DIR__.'/rest/controllers/cityController.php';
  require_once __DIR__.'/rest/models/cityModel.php';
  require_once __DIR__.'/rest/models/imageModel.php';
  
  function url($url)
  {
    if (isset($_GET['referer']))
    {
      $url.=strstr($url,'?')?'&':'?';
      $url.='referer='.urlencode($_GET['referer']);
    }
    return $url;
  }
  

  
  $part = substr($_SERVER['REQUEST_URI'], strlen(dirname($_SERVER['SCRIPT_NAME'])));
  if ($part[0]=='/') $part=substr($part,1);
  $pos = strpos($part, '?');
  if ($pos!==false) $part = substr($part, 0, $pos);
  $part=preg_replace('~/+~','/',$part);
  
  $parts = explode('/', $part);
  
  include __DIR__.'/locale.php';
  $original_lang=$lang;
  $user_country=$country;
  
  if (isset($_GET['referer']))
  {
    $referer=json_decode(base64_decode($_GET['referer']),1);
    if (isset($referer['u']))
    {
      $user=new userModel($referer['u']);
      if ($user->lang) $lang=$user->lang;
      if ($user->country) $user_country=$user->country;
    }
  }
  
  $img_url=$url;
  
  $title=Tools::translate('page-title',$lang);
  $keywords=Tools::translate('page-keywords',$lang);
  $description=Tools::translate('page-description',$lang);
  $photo=$url.'images/eating-friends.jpg';
  $img=$url.'images/jemy-razem.jpg';
  $button="";

  $rate=false;
  $itemscope='Events';
  $ul=[];
  $dates=[];
  
  switch ($parts[0]) {
    case '':
      $city=new cityController();
      $cities=$city->get($country);
    
      foreach ($cities['cities'] AS $c)
      {
        $ul[]=['class'=>'city','url'=>$url.'events/'.$c['lat'].'/'.$c['lng'].'/'.(0+$c['distance']).'/'.urlencode($c['name']),'name'=>$c['name'],'img'=>$c['square']];
      }
    
      break;
    
    case 'konkurs':
      $title='Konkurs JemyRazem.pl!';
      $description='Zostań gospodarzem najlepszego przyjęcia w mieście! Zostań Gościem, który docenia kunszt i zaangażowanie Gospodarza.';
      $photo=$img_url.'images/konkurs-banner.jpg';
      $url.='konkurs/';
      break;
    
    case 'about':
      $title=Tools::translate('AboutH21',$lang);
      $description=Tools::translate('AboutH31',$lang);
      $photo=$img_url.'images/organize-3.jpg';
      $img=$img_url.'images/organize-2.png';
      
      $url.='about/';
      break;
    
    case 'how-it-works':
      $title=Tools::translate('How-it-works-H2',$lang);
      $description=Tools::translate('How-it-works-H3',$lang);
      
      $url.='how-it-works/';
      
    case 'events':
      $lat=isset($parts[1])?$parts[1]:0;
      $lng=isset($parts[2])?$parts[2]:0;
      $distance=isset($parts[3])?$parts[3]:0;
    
      if ($lat & $lng)
      {
        $city=new cityModel();
        $cities=$city->getByLocation($lat,$lng)?:[];
        if (isset($cities[0]['img2']) && $cities[0]['img2'])
        {
          $image=new imageModel($cities[0]['img2']);
          $photo=$image->url;
        }
        elseif (isset($cities[0]['img']) && $cities[0]['img'])
        {
          $image=new imageModel($cities[0]['img']);
          $photo=$image->url;
          
        }
        $itemscope='City';
      }
    
      if (isset($parts[4])) $title=urldecode($parts[4]);
      $eventController=new eventController(0,['lat'=>$lat,'lng'=>$lng,'distance'=>$distance]);
      $events=$eventController->get_search();
      if (isset($events['events'])) foreach($events['events'] AS $event)
      {
        $ul[]=['class'=>'event','url'=>$url.$event['url'],'name'=>$event['name'],'img'=>$event['img']['square']];
      }
    
      $url.='events/'.$lat.'/'.$lng.'/'.$distance.'/'.urlencode($title);
      break;
    
    default:
      if (!isset($parts[1]))
      {
        $userController=new userController(0,[],$parts);
        $method='get_'.$parts[0];
        $user=$userController->$method();
        
        if (isset($user['user']))
        {
          $itemscope='Person';
          if (isset($user['user']['rate'])) $rate=$user['user']['rate'];
          $title=$user['user']['firstname'].' '.$user['user']['lastname'];
          if ($user['user']['cover']) $photo=$user['user']['cover'];
          if ($user['user']['photo']) $img=$user['user']['photo'];
          if ($user['user']['about']) $description=$user['user']['about'];
          
          if (isset($user['user']['tags']) && is_array($user['user']['tags']))
          {
            $tags=[];
            foreach ($user['user']['tags'] AS $t) $tags[]=$t['name'];
            $keywords=implode(',',$tags);
          }
          
          $urls=[];
          
          if (is_array($user['user']['events'])) foreach($user['user']['events']  AS $event)
          {
            $ul[]=['class'=>'event','url'=>$url.$event['url'],'name'=>$event['name'],'img'=>$event['img']['square']];
            $urls[$event['url']]=true;
          }
          
          if (is_array($user['user']['events_hosted'])) foreach($user['user']['events_hosted']  AS $event)
          {
            if (isset($urls[$event['url']])) continue;
            $ul[]=['class'=>'event','url'=>$url.$event['url'],'name'=>$event['name'],'img'=>$event['img']['square']];
            $urls[$event['url']]=true;
          }       
          $url.=$user['user']['url'];
          $img_url=$url;
        }

      }
      elseif ($parts[1])
      {
        $eventController=new eventController($parts[1],[],$parts);
        $method='get_'.$parts[0];
        $event=$eventController->$method();
        if (isset($event['event']))
        {
          $event_model=new eventModel();
          $u=explode('/',$event['event']['url']);
          $e=$event_model->find_on_url($u[0],$u[1]);
          $dates=$event_model->get_dates($e['id'],false)?:[];

          $itemscope='Events';
          if (isset($event['event']['rate'])) $rate=$event['event']['rate'];
          $title=$event['event']['name'];
          $pagetitle=$title.' - '.$event['event']['city'];
          $description=$event['event']['about'];
          $photo=$event['event']['img']['url'];
          $img=$event['event']['host']['photo'];
        
          if (isset($event['event']['tags']) && is_array($event['event']['tags']))
          {
            $tags=[];
            foreach ($event['event']['tags'] AS $t) $tags[]=$t['name'];
            $keywords=implode(',',$tags).','.$keywords;
          } 
          
          
          $img_url.=$event['event']['host']['url'];
          $url.=$event['event']['url'];
          $button=Tools::translate('ClickToJoin',$lang);
          if (substr($description,-1)!='.' && substr($description,-1)!='!') $description.='.';
          $description.=' '.$button;
        }
  
      }
  }
  
  if (!isset($pagetitle)) $pagetitle=$title;
  
  Tools::log('bots',['server'=>$_SERVER]);

  include (__DIR__.'/embedded.html');
