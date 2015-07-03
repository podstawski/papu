<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $title?:'Admin panel';?></title>
  
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">

    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>

    <!-- fonts -->
    <link href='https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <!-- /fonts -->
    
    <!-- style -->
    <style>
        <?php include __DIR__.'/style.css'; ?>      
    </style>
  
  
</head>
<body>

<?php

    use google\appengine\api\users\User;
    use google\appengine\api\users\UserService;
    use google\appengine\api\cloud_storage\CloudStorageTools;

    require_once __DIR__.'/../rest/models/userModel.php';
    require_once __DIR__.'/../rest/models/eventModel.php';
    require_once __DIR__.'/../rest/models/guestModel.php'; 
    
    $session_token=md5('admin_session');
    $session_path=__DIR__.'/../../media/sessions';
    

    if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
    {
        require_once 'google/appengine/api/users/User.php';
        require_once 'google/appengine/api/users/UserService.php';
        require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';

        $mail = UserService::getCurrentUser()->getNickname();
        $session_token=md5($mail);
        $user=new userModel();
        $u=$user->find_one_by_email(strtolower($mail));

        if (isset($u['id'])) {
                Bootstrap::$main->session('user',$u);
                Bootstrap::$main->user=$u;
                Bootstrap::$main->session('time_delta',$u['delta']);
		echo '<h1><a href="/admin/">'.$u['firstname'].' '.$u['lastname'].'</a></h1>';
        }
        
        $session_path='gs://'.CloudStorageTools::getDefaultGoogleStorageBucketName().'/sessions';
    } else {
        echo "<h1>Witaj</h1>";
        @mkdir($session_path,0755);
    }
    
    $session_file="$session_path/$session_token.sess";
    $session=file_exists($session_file)?unserialize(file_get_contents($session_file)):[];


    
     
    $user=new userModel();
    $users=$user->count();
    
    $event=new eventModel();
    $events=$event->count(['active'=>1,'d_event_start'=>['>',Bootstrap::$main->now]]);

    
    $guest=new guestModel();
    $guests=$guest->join('event','events','id')->sum('persons',['active'=>1,'d_event_start'=>['>',Bootstrap::$main->now],'d_payment'=>['>',0],'d_cancel'=>null]);
   
    
    echo '<div class="menu"><ul>';
    
    if (!isset($menu)) $menu='';
    foreach (scandir(dirname(__FILE__)) AS $d)
    {
        
            if ($d[0]=='.') continue;
            if (!is_dir(__DIR__.'/'.$d)) continue;
            if ($d=='lib') continue;
	    
	    if (file_exists(__DIR__.'/'.$d.'/admin') && !Bootstrap::$main->isAdmin()) continue;
            
            $txt=$d;
            if (!isset($session[$d])) $session[$d]=0;
            
            if ($d=='users'){
                    
                $plus=$users-$session[$d];
                $session[$d]=$users;
                $txt.=' '.$users;
		if ($plus) $txt.=' (+'.$plus.')';
            }
            if ($d=='moneytransfer'){
                $event=new eventModel();
                $events=$event->getEventsToTransferMoney()?:[];
                $txt.=' '.count($events);
            }

            if ($d=='events'){
                $plus=$events-$session[$d];
                $session[$d]=$events;
		$txt.=' '.$events;
                if ($plus) {
		    if ($plus>0) $plus="+$plus";
		    $txt.=' ('.$plus.')';
		}
		if (!isset($session['guests'])) $session['guests']=0;
		$txt.=' / '.$guests;
		$plus=$guests-$session['guests'];
		$session['guests']=$guests;
		if ($plus) {
		    if ($plus>0) $plus="+$plus";
		    $txt.=' ('.$plus.')';
		}
	    }

            
	    $class=$d==$menu?'active':'';
            echo '<li class="'.$class.'"><a href="'.dirname($_SERVER['SCRIPT_NAME']).'/'.$d.'/">'.$txt.'</a></li>';
    }
    
    echo "</ul></div>";
    
   
