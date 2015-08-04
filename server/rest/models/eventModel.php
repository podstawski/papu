<?php
require_once __DIR__.'/Model.php';
require_once __DIR__.'/guestModel.php';

class eventModel extends Model {
    protected $_table='events';
    protected static $left_join_images="images.thumbnail AS img_thumbnail,images.url AS img_url,images.square AS img_square,images.full AS img_full";
    
    
    protected function start_condition()
    {
        $now=Bootstrap::$main->now;
        $start_condition="d_event_end>$now";
        
        $start_condition="(d_deadline>$now OR (d_event_end>$now AND bookafterdeadline>0 AND _guest_count>=max_guests))";
        
        return $start_condition;
    }
    
    public function find_on_url($user,$url,$except=0)
    {
        if ($user+0==0)
        {
            $user_model=new userModel();
            $u=$user_model->find_one_by_url($user);
            if ($u && isset($u['id'])) $user=$u['id'];
        }
        
        if (!$user) return false;
        
        $sql="SELECT ".$this->_table.".*,".self::$left_join_images;
        $sql.=" FROM ".$this->_table;
        $sql.=" LEFT JOIN images ON images.id=".$this->_table.".img";
        $sql.=" WHERE ".$this->_table.".user=? AND ".$this->_table.".url=?
                AND ".$this->_table.".parent IS NULL
                AND ".$this->_table.".id <>?";
        $args=[$user,$url,$except];
       
        $ret=$this->conn->fetchRow($sql,$args);
        $this->image_array($ret);
        return $ret;
    }
    
    protected function image_array(&$event)
    {
        $img=[];
        if (is_array($event))
        {
            foreach($event AS $k=>$v)
            {
                if (substr($k,0,4)=='img_')
                {
                    $img[substr($k,4)]=$v;
                }
            }
            if (count($img)) $event['img']=$img;
        }
    }
    

    public function get_for_user($user,$onlypublic=false)
    {
        $sql="SELECT * FROM ".$this->_table." WHERE user=?";
        if ($onlypublic) {
            $sql.=" AND active=1 AND (unlisted IS NULL OR unlisted=0)";
            $sql.=" ORDER BY d_event_start DESC";
        } else {
            $sql.=" ORDER BY ".Bootstrap::$main->now."-coalesce(d_change,d_create)";
        }
        
        return $this->conn->fetchAll($sql,[$user]);
    }
    
    public function get_future_user_public_events($user)
    {

        
        $sql="SELECT * FROM ".$this->_table." AS parents WHERE user=?";
        $sql.=" AND (unlisted IS NULL OR unlisted=0) AND parent IS NULL";
        $sql.=" AND ((".$this->start_condition()." AND active=1) OR id IN (SELECT parent FROM ".$this->_table." AS children WHERE children.parent=parents.id AND ".$this->start_condition()." AND active=1))";
        $sql.=" ORDER BY d_event_start";

        return $this->conn->fetchAll($sql,[$user]);
    }
    
    public function get_passed_user_public_events($user)
    {
        //AND (unlisted IS NULL OR unlisted=0)
        
        $sql="SELECT * FROM ".$this->_table." WHERE user=?";
        $sql.=" AND active=1";
        $sql.=" AND d_event_end<".Bootstrap::$main->now;
        $sql.=" AND id IN (SELECT event FROM guests WHERE event=".$this->_table.".id AND d_payment>0 AND d_cancel IS NULL)";
        $sql.=" ORDER BY d_event_start DESC";
        
        return $this->conn->fetchAll($sql,[$user]);        
    }
    
    public function get_passed_user_public_visits($user)
    {
        // AND (".$this->_table.".unlisted IS NULL OR ".$this->_table.".unlisted=0)
        
        $sql="SELECT ".$this->_table.".* FROM guests LEFT JOIN ".$this->_table." ON guests.event=".$this->_table.".id";
        $sql.=" WHERE guests.user=? AND d_payment>0 AND d_cancel IS NULL";
        $sql.=" AND ".$this->_table.".active=1 ";
        $sql.=" AND d_event_end<".Bootstrap::$main->now;
        $sql.=" ORDER BY d_event_start DESC";
        
        return $this->conn->fetchAll($sql,[$user]);
    }
    
    public function get_dates($id=null,$future=true,$active_only=true)
    {
        if (is_null($id)) $id=$this->id;
        
        $sql="SELECT id,d_event_start,d_event_end,d_deadline,max_guests,active FROM ".$this->_table." WHERE $id IN (id,parent)";
        if ($active_only) $sql.=" AND active>0";
        if ($future) $sql.=" AND ".$this->start_condition();
        $sql.=" ORDER BY d_event_start";
        return $this->conn->fetchAll($sql);
    }
    
    
    public function map($lat1,$lat2,$lng1,$lng2,$limit=0,$offset=0,$admin=false)
    {
        $sql="SELECT ".$this->_table.".*,".self::$left_join_images;
        $sql.=" ,users.url AS host_url";
        $sql.=" FROM ".$this->_table." LEFT JOIN images ON images.id=".$this->_table.".img";
        $sql.=" LEFT JOIN users ON users.id=events.user";
        $sql.=" WHERE ".$this->start_condition();
        if (!$admin) $sql.=" AND active=1 AND (".$this->_table.".unlisted IS NULL OR ".$this->_table.".unlisted=0)  AND ".$this->_table.".fullhouse IS NULL";
    
        $sql.=" AND events.lat BETWEEN ".$lat1." AND ".$lat2;
        $sql.=" AND events.lng BETWEEN ".$lng1." AND ".$lng2;

        $sql.=" ORDER BY d_event_start";
        if ($limit) $sql.=" LIMIT $limit";
        if ($offset) $sql.=" OFFSET $offset";
        
        //mydie($sql);
        $events=$this->conn->fetchAll($sql);
        if (is_array($events)) foreach ($events AS &$event) $this->image_array($event);
        return $events;


    }
    
    public function search($offset=0,$limit=0,$tags=null,$lat=null,$lng=null,$distance=null,$start=0,$end=0,$vip=null,$country=null)
    {
        $sql="SELECT ".$this->_table.".*,".self::$left_join_images;
        if ($lat && $lng && $distance)
        {
            $lat+=0;
            $lng+=0;
            $distance+=0;
            $sql.=",geo_distance(lat,lng,$lat,$lng) AS distance";
        }
        
        $sql.=" FROM ".$this->_table." LEFT JOIN images ON images.id=".$this->_table.".img";
        $sql.="\nWHERE active=1 AND ".$this->_table.".img>0
                AND ".$this->start_condition();
                
        if ($tags && count($tags)) $sql.=" AND id IN (SELECT event FROM tags WHERE event=".$this->_table.".id AND name IN ('".implode("','",$tags)."'))";

        if ($start) $sql.=" AND d_event_start>$start";
    
        if ($end && $end>$start) $sql.=" AND d_event_end<$end";
        
        if ($vip) $sql.=" AND _vip=".$vip;
        else $sql.=" AND (_vip IS NULL OR _vip>=0)";
    
        if ($lat && $lng && $distance)
        {
            
            $sql.=" AND lat BETWEEN ".($lat-$distance*0.9/100)." AND ".($lat+$distance*0.9/100);
            $sql.=" AND lng BETWEEN ".($lng-$distance*1.48/100)." AND ".($lng+$distance*1.48/100);
            $sql.=" AND geo_distance(lat,lng,$lat,$lng)<$distance";
        }
        
        if ($country) $sql.=" AND ".$this->_table.".country='$country'";


        $sql.=" AND (".$this->_table.".unlisted IS NULL OR ".$this->_table.".unlisted=0)  AND ".$this->_table.".fullhouse IS NULL";
        
    
        $order='d_event_start';
        
        if ($lat && $lng && $distance)
        {
            $order="10000*geo_distance(lat,lng,$lat,$lng)+(d_event_start-".Bootstrap::$main->now.")";
        }
        
        
        $sql.="\nORDER BY $order";
        if ($limit) $sql.=" LIMIT $limit";
        if ($offset) $sql.=" OFFSET $offset";
        
        //mydie($sql);
        $events=$this->conn->fetchAll($sql);
        if (is_array($events)) foreach ($events AS &$event) $this->image_array($event);
        return $events;
    }
    
    public function getSlots($id=null)
    {
        if (is_null($id)) $id=$this->id;
        elseif ($id) $this->get($id);
        
        if (!$id) return false;
        
        if ($this->max_guests==10) return 10;
        $guest=new guestModel();
        return $this->max_guests - $guest->getGuestCount($id);
        
    }
    
    public function getGuests($id=null)
    {
        if (is_null($id)) $id=$this->id;
    
    
        $sql="SELECT *,guests.id AS guest_id
                FROM guests LEFT JOIN users ON users.id=guests.user
                WHERE event=$id";
        
        return $this->conn->fetchAll($sql);
    }


    public function allEventsForCountry($country)
    {
        $sql="SELECT events.url AS event_url,users.url AS host_url, max(events.d_change) AS d_change,min(events.id) AS event_id
            FROM events LEFT JOIN users ON users.id=events.user
            WHERE events.country=? AND events.active=1 AND (events.unlisted IS NULL OR events.unlisted=0)
            GROUP BY events.url,users.url";
            
        return $this->conn->fetchAll($sql,[$country]);
    }

    public function purge()
    {
        $sql="DELETE FROM events WHERE d_change IS NULL AND d_create<".(Bootstrap::$main->now - 3600);
        $this->conn->execute($sql);

        $sql="DELETE FROM events WHERE parent>0 AND d_event_end<".Bootstrap::$main->now."
                AND id NOT IN (SELECT event FROM guests WHERE event=events.id
                                AND d_payment IS NOT NULL)";
        $this->conn->execute($sql);

    }


    public function getEventsAfterDeadlineToCancel()
    {
        $now=Bootstrap::$main->now;
        $sql="SELECT id FROM events
                WHERE d_deadline<? AND d_event_start>? AND active=1 AND min_guests>1
                AND min_guests>(SELECT sum(persons) FROM guests WHERE event=events.id AND d_cancel IS NULL AND d_payment IS NOT NULL)";
        
        return $this->conn->fetchColumn($sql,[$now,$now]);
    }
    
    
    public function getLastEditedChildren($id=null)
    {
        if (!$id) $id=$this->id;
        
        $sql="SELECT * FROM events WHERE $id IN (id,parent)
                AND active=1
                ORDER BY d_change";
    
        return $this->conn->fetchAll($sql);
    }

    
    public function getCurrentEvents($tolerance=3600,$user=null)
    {
        if (is_null($user)) $user=Bootstrap::$main->user['id'];
        $now=Bootstrap::$main->now;
        
        $sql="(SELECT * FROM events WHERE user=? AND active=1
                AND d_event_start<? AND d_event_end>?)
            UNION ALL
                (SELECT events.* FROM guests
                LEFT JOIN events ON events.id=guests.event
                WHERE guests.user=?
                AND events.active=1
                AND events.d_event_start<? AND events.d_event_end>?)
                
            ORDER BY abs(d_event_start-$now)";
        
        $args=[$user,$now+$tolerance,$now-$tolerance,$user,$now+$tolerance,$now-3*$tolerance];
        
        return $this->conn->fetchAll($sql,$args);
        
    }
    
    public function getEventsToRate($tolerance=86400)
    {
        $now=Bootstrap::$main->now;
        $sql="SELECT events.*,guests.user AS guest FROM events
                LEFT JOIN guests ON guests.event=events.id
                WHERE active=1 AND d_event_end<? AND d_event_end>?
                AND guests.d_payment IS NOT NULL AND guests.d_cancel IS NULL
                AND guests.user NOT IN (SELECT user FROM rates WHERE rates.event=events.id)
            ";
        

        return $this->conn->fetchAll($sql,[$now,$now-$tolerance]);
    }
    
    
    public function getEventsToPay($tolerance=86400,$tolernace2=600)
    {
        $now=Bootstrap::$main->now;
        $sql="SELECT events.*,guests.user AS guest,guests.d_create AS d_guest_create
                FROM events
                LEFT JOIN guests ON guests.event=events.id
                WHERE active=1 AND events.price>0 
                AND d_event_start>? AND guests.d_create>? AND guests.d_create<? 
                AND guests.d_payment IS NULL AND guests.d_cancel IS NULL
            ";
        

        return $this->conn->fetchAll($sql,[$now,$now-$tolerance-$tolernace2,$now-$tolernace2]);
    }
    
    public function lastHints($lat=null,$lng=null,$user=null)
    {
        if (is_null($lat)) $lat=$this->lat;
        if (is_null($lng)) $lng=$this->lng;
        if (is_null($user)) $user=Bootstrap::$main->user['id'];
        
        $sql="SELECT hints FROM events WHERE user=? AND abs(lat-?)<0.05 AND abs(lng-?)<0.05
                AND hints<>'' ORDER BY id DESC LIMIT 1";
                

        return $this->conn->fetchOne($sql,[$user,$lat,$lng]);
        
    }
    
    public function lastEvent($user=null)
    {
        if (is_null($user)) $user=Bootstrap::$main->user['id'];
        
        $sql="SELECT * FROM events WHERE user=? ORDER BY d_change DESC LIMIT 1";
        return $this->conn->fetchRow($sql,[$user]);
    }
    
    public function getEventsToTransferMoney($event_id=0)
    {
        $now=Bootstrap::$main->now;
        $sum="(SELECT sum(persons) FROM guests WHERE event=events.id AND d_cancel IS NULL AND d_payment IS NOT NULL AND host_price>0)";
        $sql="SELECT id,country,user,
                $sum AS persons
                FROM events 
                WHERE d_event_end<? AND active=1 AND (d_transfer IS NULL OR events.id=?) 
                AND $sum>0
            ";
        
        return $this->conn->fetchAll($sql,[$now,$event_id]);
    }
    
    public function get_passed_public_events($country=null,$offset=0,$limit=0)
    {
        $where=null;
        
        $sql="SELECT * FROM ".$this->_table." WHERE active=1";
        $sql.=" AND (_vip IS NULL OR _vip>=0)";
        $sql.=" AND d_event_end<".Bootstrap::$main->now;
        $sql.=" AND id IN (SELECT event FROM guests WHERE event=".$this->_table.".id AND d_payment>0 AND d_cancel IS NULL)";
        
        if ($country) {
            $sql.=" AND country=?";
            $where=[$country];
        }
        
        $sql.=" ORDER BY d_event_end DESC";

        if ($limit) $sql.=" LIMIT $limit";
        if ($offset) $sql.=" OFFSET $offset";
        
        return $this->conn->fetchAll($sql,$where);        
    }
    
    
    public function lastMaxGuests($user=null)
    {

        if (is_null($user)) $user=Bootstrap::$main->user['id'];
        
        $sql="SELECT max_guests FROM events WHERE user=? 
                ORDER BY d_change DESC LIMIT 1";
                

        return $this->conn->fetchOne($sql,[$user]);
        
    }
    
    public function updateFam($update,$id=null)
    {
        if (!$id) $id=$this->id;
        if (!$id) return;
        
        $set=[];
        $fields=[];
        foreach($update AS $k=>$v)
        {
            $fields[]="$k=?";
            $set[]=$v;
        }
        $sql='UPDATE events SET '.implode(',',$fields).' WHERE id=? OR parent=?';
        $set[]=$id;
        $set[]=$id;
        
        return $this->conn->execute($sql,$set);
    }
}
