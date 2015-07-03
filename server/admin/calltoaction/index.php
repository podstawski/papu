<?php
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

require_once __DIR__.'/../base.php';
require_once __DIR__.'/../../rest/models/userModel.php';

$title='Call to action';
$menu='calltoaction';
include __DIR__.'/../head.php';

function call_error($txt)
{
    echo '<h1 style="color:red; margin:0.7em">'.$txt.'</h1>';
}

function call_url($txt)
{
    echo '<pre style="margin:3em; width:77%; font-weight:bold">'.$txt.'</pre>';
    
}

if (isset($_GET['_redirect'])) {

    if (substr($_GET['_redirect'],0,strlen(Bootstrap::$main->getConfig('app.root')))!=Bootstrap::$main->getConfig('app.root')) {
        call_error('Url should begin from: '.Bootstrap::$main->getConfig('app.root'));
    } elseif (!isset($_GET['_referer']) || !$_GET['_referer'] || strlen($_GET['_referer'])>30) {
        call_error('Referer should be given and has max 30 characters');
    } else {
        $redirect=urlencode($_GET['_redirect']);
        
        $root=Bootstrap::$main->getRoot();
        $pos=strpos($root,'admin');
        if ($pos) {
            $root=substr($root,0,$pos);
            $root.='rest/';
        }
        
        $url=Bootstrap::$main->getConfig('protocol').'://'.$_SERVER['HTTP_HOST'].$root.'user/'.$_GET['_login'];
        $url.='?redirect='.$redirect;
        
        $referer=['u'=>1,'s'=>$_GET['_referer']];
        if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
        {
            require_once 'google/appengine/api/users/User.php';
            require_once 'google/appengine/api/users/UserService.php';
            
            $mail = UserService::getCurrentUser()->getNickname();
            $user=new userModel();
            $u=$user->find_one_by_email(strtolower($mail));
    
            if (isset($u['id'])) {
                $referer['u']=$u['id'];
            }
        
        }
        $url.='&referer='.urlencode(base64_encode(json_encode($referer)));
    
        
        call_url($url);
    }

}

?>
<form method="GET" style="margin:3em">
    <input type="radio" name="_login" <?php if (!isset($_GET['_login']) || $_GET['_login']=='facebook') echo 'checked';?> value="facebook"/> Login Facebook
    &nbsp; | &nbsp;
    <input type="radio" name="_login" <?php if (isset($_GET['_login']) && $_GET['_login']=='google') echo 'checked';?> value="google"/> Login Google
    
    <br/><br/>
    <input name="_redirect" placeholder="Redirect full URL"/ style="width: 80%" value="<?php if (isset($_GET['_redirect'])) echo $_GET['_redirect']; ?>">
    <br/><br/>
    <input name="_referer" placeholder="referer, e.g. fb-campain"/ style="width: 80%" value="<?php if (isset($_GET['_referer'])) echo $_GET['_referer']; ?>">
    <br/><br/>
    <input type="submit" value="Generate link"/>
</form>


<?php

include __DIR__.'/../foot.php';
