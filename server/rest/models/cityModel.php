<?php
require_once __DIR__.'/Model.php';

class cityModel extends Model {
    protected $_table='cities';

    
    public function country($country='')
    {
        $where=null;
        $sql="SELECT * FROM ".$this->_table." LEFT JOIN images ON images.id=".$this->_table.".img";
        $sql.=" WHERE img IS NOT NULL ";
        if ($country) {
            $sql.=" AND country=?";
            $where=[$country];
        }
        $sql.=" ORDER BY name";
        
        
        return $this->conn->fetchAll($sql,$where);
    }
    
    public function getByLocation($lat,$lng)
    {
        $sql="SELECT * FROM ".$this->_table." WHERE geo_distance(lat,lng,?,?)<distance
                ORDER BY geo_distance(lat,lng,?,?)";
        return $this->conn->fetchAll($sql,[$lat,$lng,$lat,$lng]);
    }
}
