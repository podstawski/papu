<?php
require_once __DIR__.'/Controller.php';

class tagController extends Controller {
 
    public function get()
    {
	return $this->status(Tools::tags());
    }
    
}
