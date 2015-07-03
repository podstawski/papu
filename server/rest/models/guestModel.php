<?php
require_once __DIR__.'/Model.php';

class guestModel extends Model {
    protected $_table='guests';


    public function getGuestCount($event,$paid=true)
    {
        $sql="SELECT sum(persons) FROM ".$this->_table." WHERE event=?";
        if ($paid) $sql.=" AND d_payment IS NOT NULL AND d_cancel IS NULL";
        
        return $this->conn()->fetchOne($sql,[$event]);
    }
    
    public function getGuests($event,$paid=true)
    {
        $sql="SELECT * FROM ".$this->_table." WHERE event=?";
        if ($paid) $sql.=" AND d_payment IS NOT NULL AND d_cancel IS NULL";
        
        return $this->conn()->fetchAll($sql,[$event]);
    }    
    
    public function getForUser($user)
    {
        $now=Bootstrap::$main->now;
        $sql="SELECT ".$this->_table.".*, events.name, events.d_event_start,events.d_event_end,events.url AS event_url,users.url AS user_url,events.id AS event_id,events.d_deadline AS d_deadline
                FROM ".$this->_table."
                LEFT JOIN events ON ".$this->_table.".event=events.id
                LEFT JOIN users ON events.user=users.id
            WHERE ".$this->_table.".user=?
            ORDER BY
                coalesce(d_cancel,0),
                $now - coalesce(d_payment,$now),
                abs(events.d_event_start - $now)";
        return $this->conn()->fetchAll($sql,[$user]);
    }
    
    public function getCanceledGuests()
    {
        $sql="SELECT * FROM ".$this->_table." WHERE d_payment IS NOT NULL
            AND d_cancel IS NOT NULL AND d_cancel_commit IS NULL";
            
        return $this->conn()->fetchAll($sql);
    }
    
    public function getPersons4host($user_id,$free=true)
    {
        $now=Bootstrap::$main->now;
        $sql="SELECT sum(guests.persons) FROM events LEFT JOIN guests ON guests.event=events.id
                WHERE events.user=? AND events.d_event_start < $now
                AND guests.d_payment IS NOT NULL AND guests.d_cancel IS NULL";
        $sql.=$free?" AND (guests.host_price=0 || guests.host_price IS NULL)":" AND guests.host_price>0";
  

        return 0+$this->conn->fetchOne($sql,[$user_id]);
    }
    
    public function getPersons4guest($user_id,$free=true)
    {
        $now=Bootstrap::$main->now;
        $sql="SELECT sum(guests.persons) FROM guests LEFT JOIN events ON guests.event=events.id
                WHERE guests.user=? AND events.d_event_start < $now
                AND guests.d_payment IS NOT NULL AND guests.d_cancel IS NULL";
        $sql.=$free?" AND (guests.guest_price=0 || guests.guest_price IS NULL)":" AND guests.guest_price>0";
  
        return 0+$this->conn->fetchOne($sql,[$user_id]);
    }
    
    public function getGuestsForAllEvents($event,$user=null)
    {
        if (is_null($user)) $user=Bootstrap::$main->user['id'];
        
        $sql="SELECT guests.*,events.d_event_start,events.d_event_end
                FROM guests
                LEFT JOIN events ON events.id=guests.event
                WHERE guests.user=? AND (events.id=? OR events.parent=?)
                ORDER BY guests.id";
                

        return $this->conn()->fetchAll($sql,[$user,$event,$event]); 
    }

}
