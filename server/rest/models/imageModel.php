<?php
require_once __DIR__.'/Model.php';

class imageModel extends Model {
    protected $_table='images';

    
    public function getUsersCount($user)
    {
        $sql="SELECT count(*) FROM ".$this->_table." WHERE user=?";
        return $this->conn->fetchOne($sql,array($user));
    }
    
    public function getUsersImages($user,$label=false)
    {
        $sql="SELECT * FROM ".$this->_table." WHERE user=?";
        $args=[$user];
        
        if ($label) {
            $sql.=" AND id IN (SELECT image_labels.image FROM image_labels WHERE image_labels.image=".$this->_table.".id AND image_labels.name=?)";
            $args[]=$label;
        }
        
        $sql.=" ORDER BY d_taken DESC,d_uploaded DESC";
        
        return $this->conn->fetchAll($sql,$args);
    }

    public function getEventImages($event)
    {
        $sql="SELECT * FROM image_labels LEFT JOIN ".$this->_table." ON ".$this->_table.".id=image_labels.image";
        $sql.=" WHERE image_labels.event";
        
        if (is_array($event)) $sql.=" IN (".implode(',',$event).")";
        elseif ($event+0==$event) $sql.="=$event";
        else $sql.=" IN ($event)";
        
        //$sql.=" ORDER BY d_taken DESC,d_uploaded DESC";
        $sql.=" ORDER BY image_labels.id";

        
        return $this->conn->fetchAll($sql);
    }
    
    
    public function getLabels($id=null)
    {
        if (is_null($id)) $id=$this->id;
    
        $sql="SELECT COALESCE(events.name,image_labels.name)
                FROM image_labels
                LEFT JOIN events ON image_labels.event=events.id
                WHERE image=?
                ORDER BY COALESCE(events.name,image_labels.name)";
        
        $labels=$this->conn->fetchColumn($sql,[$id]);
        if (is_array($labels)) foreach($labels AS $i=>$label) $labels[$i]=Tools::translate($label);
        return $labels;
    }

    public function setLabels($labels,$id=null)
    {
        if (is_null($id)) $id=$this->id;
        if (!$labels) return;
        
        if (!is_array($labels)) $labels=explode(',',$labels);
        
        
        foreach ($labels AS $key=>$label)
        {
            $args=array($id,$label);
            
            if ("$key"=='event' && $label+0==$label) {
                
                $c=$this->conn->fetchOne('SELECT count(*) FROM image_labels WHERE image=? AND event=?',$args);
                if ($c) continue;
                
                $sql="INSERT INTO image_labels (image,event) VALUES (?,?)"; 
                $this->conn->execute($sql,$args);                
            } else {
  
                $c=$this->conn->fetchOne('SELECT count(*) FROM image_labels WHERE image=? AND name=?',$args);
                if ($c) continue;

                $sql="INSERT INTO image_labels (image,name) VALUES (?,?)"; 
                $this->conn->execute($sql,$args);
            }
            
        }
    }
    
    public function addEvent($event,$id=null)
    {
        if (is_null($id)) $id=$this->id;
        
        $c=$this->conn->fetchOne('SELECT count(*) FROM image_labels WHERE image=? AND event=?',[$id,$event]);
        if ($c) return true;
        
        $sql="INSERT INTO image_labels (image,event) VALUES (?,?)"; 
        return $this->conn->execute($sql,[$id,$event]);    
    }
    
    public function removeEvent($event,$id=null)
    {
        if (is_null($id)) $id=$this->id;

        $sql="DELETE FROM image_labels WHERE image=? AND event=?"; 
        return $this->conn->execute($sql,[$id,$event]);            
    }    
}
