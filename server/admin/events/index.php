<?php

use google\appengine\api\users\User;
use google\appengine\api\users\UserService;
    
require_once __DIR__.'/../base.php';
require_once __DIR__.'/../../rest/models/eventModel.php';
require_once __DIR__.'/../../rest/models/userModel.php';
require_once __DIR__.'/../../rest/models/imageModel.php';
require_once __DIR__.'/../../rest/models/guestModel.php';

if (isset($_GET['offset']) || isset($_GET['userid'])) {

    if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
    {
        require_once 'google/appengine/api/users/User.php';
        require_once 'google/appengine/api/users/UserService.php';

        $mail = UserService::getCurrentUser()->getNickname();
        $user=new userModel();
        $u=$user->find_one_by_email(strtolower($mail));

        if (isset($u['id'])) {
            Bootstrap::$main->session('time_delta',$u['delta']);
        }
    }


    $limit=10;
    $event=new eventModel();
    $image=new imageModel();
    $user=new userModel();
    $guest=new guestModel();

    if (isset($_GET['userid'])) {
        $events=$event->select(['user'=>$_GET['userid']],'d_event_start')?:[];
    } else { 
        $events=$event->select(['active'=>1,'d_event_start'=>['>',Bootstrap::$main->now]],'d_event_start',$limit,$_GET['offset'])?:[];
    }
    
    foreach ($events AS &$e)
    {
        $e['user']=$user->get($e['user']);
        $e['img']=$image->get($e['img']);
        $e['link']=Bootstrap::$main->getConfig('app.root').$e['user']['url'].'/'.$e['url'];
        $e['start']=Bootstrap::$main->human_datetime_format($e['d_event_start']);
        $e['guests']=$guest->getGuestCount($e['id'])?:0;
    }
    header('Content-type: application/json; charset=utf8');
    die(json_encode($events,JSON_NUMERIC_CHECK));
}
$title='Events';
$menu='events';
include __DIR__.'/../head.php';
?>

<div>
    <ul id="events">
        
    </ul>
</div>

<script>
    var offset=0;
    function lazy_load() {
        var scroll_to = $('#lazyload');
        if (typeof(scroll_to.get(0))=='undefined') return;
    
        var hT = scroll_to.offset().top,
            hH = scroll_to.outerHeight(),
            wH = $(window).height(),
            wS = $(window).scrollTop();
    
        if (wS > (hT+hH-wH)){
            $('#lazyload').remove();
            load_events();
        }
        
    }
    
    
    function change_vip(event,vip) {

        var url='../../rest/event/'+event;
        var data={_vip:vip};
        
        $.ajax({
                url: url,
                type: 'PUT',
                dataType: 'json',
                data: data,
                success: function (data, textStatus, request) {
                    if (!data.status) {
                        alert(data.error.info);
                    }
                }
        });
        
    }
    
    function fill_selects()
    {
        $('#events select:empty').each (function () {
            //console.log($(this));
            $(this).append('<option value="0">Regular event</option>');
            $(this).append('<option value="1">VIP=1: paralaxa</option>');
            $(this).append('<option value="2">VIP=2: home results</option>');
            $(this).append('<option value="3">VIP=3: workshops</option>');
            $(this).append('<option value="4">VIP=4</option>');
            $(this).append('<option value="5">VIP=5</option>');
            $(this).append('<option value="-1">VIP=-1: no search</option>');
            
            var vip=$(this).attr('rel');
            if (vip!='null') $(this).val(vip);
            
            $(this).change(function() {
                change_vip($(this).attr('id'),$(this).val());
            });
        });
    }
    
    function load_events(u)
    {
        var url=parseInt(u)>0?'.?userid='+u:'.?offset='+offset;
        $.get(url,function (events) {
 
            for (var i=0;i<events.length;i++)
            {
                var link='<a href="'+events[i].link+'" target="_blank">';
                var html='<li class="row">';
                html+='<div class="col-md-2 col-sm-2">';
                html+=link+'<img class="event img-responsive" src="'+events[i].img.square+'"/></a>';
                html+='<img class="host" src="'+events[i].user.photo+'"/>';
                html+='</div><div class="col-md-8 col-sm-8">';
                html+='<h3>'+link+events[i].name+'</a></h3>';
                html+='<h4>by <a href="../?q='+events[i].user.url+'">'+events[i].user.firstname+' '+events[i].user.lastname+'</a></h4>';
                html+='<h5>'+events[i].city+': '+events[i].start+' (id='+events[i].id+', parent='+events[i].parent+')</h5>';
                html+='</div><div class="col-md-2 col-sm-2">';                
                html+='<select id="'+events[i].id+'" rel="'+events[i].vip+'"></select>';
                html+='<p class="unlisted'+events[i].unlisted+'"><i class="glyphicon glyphicon-eye-close"></i></p>';
                html+='<p class="guests">Guests: '+events[i].guests+'</p>';
                html+='</div>';
                html+='<div class="clearfix"></div>';
                html+='</li>';
                $('#events').append(html);
            }
            if (events.length>0) {
                offset+=events.length;
                $('#events').append('<li id="lazyload"></li>');
            }
            
            fill_selects();
        });
    }
    <?php if (isset($_GET['u'])): ?>
    $(function() {
        load_events(<?php echo $_GET['u']+0;?>);
    });
    <?php else: ?>
    $(load_events);
    $(window).scroll(lazy_load);
    <?php endif; ?>
</script>

<?php
    include __DIR__.'/../foot.php';

