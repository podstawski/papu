<?php
require_once __DIR__.'/Controller.php';
require_once __DIR__.'/eventController.php';
require_once __DIR__.'/userController.php';
require_once __DIR__.'/../models/eventModel.php';


class bookController extends Controller {
    protected $_event;

    /**
     * @return eventModel
     */    
    protected function event()
    {
	if (is_null($this->_event)) $this->_event=new eventModel();
	return $this->_event;
    }

    public function get()
    {
	$book=Bootstrap::$main->session('book');
	if (isset($book['event']['id'])) {
	    
	    $event=new eventModel($book['event']['id']);
	    $book['event']['fb_friend_required']=0;
	    if ( isset($book['event']['fb_friends']) && $book['event']['fb_friends'])
		$book['event']['fb_friend_required']=$this->require_fb_friend($event->user,false);
	    
	    
	    $book['event']['free_slots']=$event->getSlots();
	    return $this->status($book);
	}
	
	$this->error(71);
    }
    
    
    public function post()
    {
	$this->check_input();
	
	if (!(0+$this->data('event'))) $this->error(31);
	
	$event=$this->event()->get($this->data('event'));
	
	if ($event['id']!=$this->data('event')) $this->error(31);
	
	if (!(0+$this->data('persons'))) $this->error(33);
	
	$eventController=new eventController();
	$userController=new userController();
	
	$user=new userModel();
	$user->get($event['user']);
	$event=$eventController->public_data($event,true);
	$event['id']=$this->data('event');
	$event['host']=$userController->public_data($user->data(),true);
	
	$event['free_slots']=$this->event()->getSlots();
	
	$data=['event'=>$event,'persons'=>$this->data('persons')];
	
	if ($event['fb_friends']) Bootstrap::$main->session('fb_friends',1);
	
	return $this->status(Bootstrap::$main->session('book',$data));
    }
    
}
