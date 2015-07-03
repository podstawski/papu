<?php
require_once __DIR__.'/Model.php';

class paymentModel extends Model {
    protected $_table='payments';

    public function getForGuest($guest)
    {
        $sql="SELECT * FROM ".$this->_table." WHERE guest=?";
        
        return $this->conn()->fetchAll($sql,[$guest]);
    }
    
    
    public function getAllForChannel($channel,$datetime,$status=null)
    {
        $sql="SELECT *,payments.id AS payment_id,payments.d_create AS d_payment_create
                FROM payments
                LEFT JOIN guests ON payments.guest=guests.id
                LEFT JOIN events ON guests.event=events.id
            WHERE channel=? AND payments.d_create>?";
        $arg=[$channel,$datetime];
        if (!is_null($status)) {
            $sql.=" AND payments.status=?";
            $arg[]=$status;
        }
   
        return $this->conn()->fetchAll($sql,$arg);
    }
}
