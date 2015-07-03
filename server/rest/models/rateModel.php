<?php
require_once __DIR__.'/Model.php';

class rateModel extends Model {
    protected $_table='rates';

    public function user_has_rated_event($event,$user=null)
    {
        if (!$user) $user=Bootstrap::$main->user['id'];
        
        $sql="SELECT count(*) FROM ".$this->_table." WHERE user=? AND event=?";
        
        return $this->conn->fetchOne($sql,[$user,$event]);
    }
    
    
    public function event($event,$family=false)
    {
        $sql="SELECT avg(overall) FROM ".$this->_table." WHERE event=?";
        $arg=[$event];
        if ($family) {
            $sql.= " OR event IN (SELECT id FROM events WHERE parent=?)";
            $arg[]=$event;
        } 
        return $this->conn->fetchOne($sql,$arg);        
    }
    
    public function user($user)
    {
        $sql="SELECT avg(overall) FROM ".$this->_table." WHERE host=?";
        return $this->conn->fetchOne($sql,[$user]);
    }
    
    public function event_count($event,$family=false)
    {
        $sql="SELECT count(*) FROM ".$this->_table." WHERE event=?";
        $arg=[$event];
        if ($family) {
            $sql.= " OR event IN (SELECT id FROM events WHERE parent=?)";
            $arg[]=$event;
        } 
        return $this->conn->fetchOne($sql,$arg);        
    }
    
    public function user_count($user)
    {
        $sql="SELECT count(*) FROM ".$this->_table." WHERE host=?";
        return $this->conn->fetchOne($sql,[$user]);
    }
    
    
    public function user_reviews($user,$limit=0,$offset=0)
    {
        
        $sql="SELECT * FROM ".$this->_table." WHERE host=? ORDER BY id DESC";

        if ($limit) $sql.=" LIMIT $limit";
        if ($offset) $sql.=" OFFSET $offset";
        

        return $this->conn->fetchAll($sql,[$user]);
    }
    
    public function event_reviews($event,$family=false,$limit=0,$offset=0)
    {
        
        $sql="SELECT * FROM ".$this->_table." WHERE event=?";

        $arg=[$event];
        if ($family) {
            $sql.= " OR event IN (SELECT id FROM events WHERE parent=?)";
            $arg[]=$event;
        }        
        
        $sql.=" ORDER BY id DESC";
        
        if ($limit) $sql.=" LIMIT $limit";
        if ($offset) $sql.=" OFFSET $offset";
        

        return $this->conn->fetchAll($sql,$arg);
    }    
}
