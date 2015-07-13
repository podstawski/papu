<?php

        $part = substr($_SERVER['REQUEST_URI'], 1+strlen(dirname($_SERVER['SCRIPT_NAME'])));
        if ($pos = strpos($part, '?')) $part = substr($part, 0, $pos);
        $part=preg_replace('~/+~','/',$part);
        $parts = explode('/', $part);

	if ($parts[0] && file_exists(__DIR__.'/'.$parts[0].'/index.php'))
	{
		die(include(__DIR__.'/'.$parts[0].'/index.php'));
	}

	$admin_path=$_SERVER['REQUEST_URI'];
	if ($pos = strpos($admin_path, '?')) $admin_path = substr($admin_path, 0, $pos);
	if ($admin_path[strlen($admin_path)-1]!='/') $admin_path.='/';
	
	include __DIR__.'/base.php';
	require_once __DIR__.'/../rest/models/userModel.php';
	require_once __DIR__.'/../rest/models/eventModel.php';
	require_once __DIR__.'/../rest/controllers/userController.php';	
	
	if (Bootstrap::$main->isAdmin() && isset($_GET['pretend'])) {
		$id=null;
		$data=[];
		if ($_GET['pretend']+0>0) {
			$id=$_GET['pretend'];
		} elseif (strstr($_GET['pretend'],'@')) {
			$data['email']=$_GET['pretend'];
		} else {
			$data['url']=$_GET['pretend'];
		}
		
		$user=new userController($id,$data);
		$user->get_pretend();
		Tools::log('pretend',['admin'=>Bootstrap::$main->user['email'],'id'=>$id,'data'=>$data]);
		header('location: '.Bootstrap::$main->getConfig('app.root').'profile');
		die();
	}
	
	if (Bootstrap::$main->isAdmin() && isset($_GET['vip'])) {
		$vip=explode(',',$_GET['vip']);
		$model=new userModel($vip[0]);
		$model->_vip=$vip[1]+0;
		$model->save();
	}
	
	
	$us=false;
	if (Bootstrap::$main->isAdmin() && isset($_GET['q']))
	{
		$model=new userModel();
		if ($_GET['q']+0>0) {
			$us=$model->get($_GET['q']);
		} elseif (strstr($_GET['q'],'@')) {
			$us=$model->find_one_by_email(trim(strtolower($_GET['q'])));
		} else {
			$us=$model->find_one_by_url(trim($_GET['q']));
		}
		
	}
	

?>

<?php
	$title='';
	include __DIR__.'/head.php';
	
	//mydie();
?>

<?php if(Bootstrap::$main->isAdmin()): ?>
<form style="margin: 2em;" action="<?php echo $admin_path;?>">
	<input name="q" value="<?php if (isset($_GET['q'])) echo $_GET['q'];?>" placeholder="user: id, email or url" /><input type="submit" value="search"/>
</form>
<?php endif; ?>


<?php if($us): ?>

<?php
	$event=new eventModel();
	$count=0+$event->count(['user'=>$us['id']]);
?>

<ul id="events">
	<li class="row">
		<div class="col-md-2 col-sm-2">
			<a href="<?php echo Bootstrap::$main->getConfig('app.root').$us['url'];?>" target="_blank">
				<img style="width:100px" src="<?php echo $us['photo'];?>"/>
			</a>
		</div>
		<div class="col-md-8 col-sm-8">
			<a href="<?php echo Bootstrap::$main->getConfig('app.root').$us['url'];?>" target="_blank">
				<h3><?php echo $us['firstname'].' '.$us['lastname'];?></h3>
			</a>
			
			<h4><a href="events/?u=<?php echo $us['id'];?>">Events (<?php echo $count;?>)</a></h4>
		</div>
		
		<div class="col-md-2 col-sm-2">
			<select onchange="location.href=location.href+'&vip=<?php echo $us['id'];?>,'+this.value">
				<option value="0">Regular user</option>
				<option value="1" <?php if ($us['vip']==1) echo 'selected';?>>VIP=1 OK CHEF</option>
				<option value="2" <?php if ($us['vip']==2) echo 'selected';?>>VIP=2 OK BAR</option>
				<option value="3" <?php if ($us['vip']==3) echo 'selected';?>>VIP=3</option>
				<option value="4" <?php if ($us['vip']==4) echo 'selected';?>>VIP=4</option>
				<option value="5" <?php if ($us['vip']==5) echo 'selected';?>>VIP=5</option>
			</select>
			<br/><br/>
			<a href="<?php echo $_SERVER['REQUEST_URI'];?>&pretend=<?php echo $us['id'];?>">
				Become <?php echo $us['firstname'].' '.$us['lastname'];?>
			</a>
		</div>
	</li>
</ul>

<?php endif; ?>

<?php
	
	include __DIR__.'/foot.php';



