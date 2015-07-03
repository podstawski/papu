<?php
require_once __DIR__.'/Model.php';

class userModel extends Model {
    protected $_table='users';

    public function ical_users()
    {
        $sql="SELECT * FROM ".$this->_table." WHERE ical<>''";
        return $this->conn->fetchAll($sql);
    }
    
    public function get_refered_user_ids($user_id=null) {
        if (is_null($user_id)) $user_id=$this->id;
        $sql="SELECT id FROM users WHERE ref_user=?";
        return $this->conn->fetchColumn($sql,[$user_id]);
    }
    
    public function get_user_for_password_reset($hash)
    {
        $sql="SELECT * FROM users WHERE md5hash=?
                AND (_password_reminder_expire IS NULL OR _password_reminder_expire<?)";
        
        return $this->conn->fetchRow($sql,[$hash,Bootstrap::$main->now]);
    }
    
    public function generate_passowrd_reset_hash($expire)
    {
        $hash=md5(time().'JemyRazem'.rand(99999,10000000));
        $sql="UPDATE users SET _password_reminder_hash='$hash',
            _password_reminder_expire=$expire
            WHERE id=".$this->id.";
            ";
        $this->conn->execute($sql);
        return $hash;
    }
    
    public function get_user_on_password_hash($md5hash,$reminder_hash)
    {
        $sql="SELECT * FROM users WHERE md5hash=? AND _password_reminder_hash=?
                AND _password_reminder_expire>=?";
        
        return $this->conn->fetchRow($sql,[$md5hash,$reminder_hash,Bootstrap::$main->now]);        
    }
}
