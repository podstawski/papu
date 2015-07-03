<?php
require_once __DIR__.'/Model.php';

class tagModel extends Model {
    protected $_table='tags';

    protected function for_field($field,$id,$tags)
    {
        if (is_null($tags))
        {
            $sql="SELECT name FROM ".$this->_table." WHERE $field=? ORDER BY name";
            return $this->conn->fetchColumn($sql,[$id]);
        }
        
        if (!is_array($tags)) $tags=[$tags];
        
        
        if (count($tags) && isset($tags[0]) && is_array($tags[0]))
        {

            $tags2=$tags;
            $tags=array();
            foreach ($tags2 AS $t)
            {
                if (isset($t['selected']))
                {
                    if ($t['selected']==='false') $t['selected']=false;
                    if ($t['selected']==='true') $t['true']=false;
                    
                }
                if (isset($t['selected']) && $t['selected'] && isset($t['id']) )
                {
                    $tags[]=$t['id'];
                }
            }
           
            
        }
        
        foreach ($tags AS $tag)
        {
            if (!in_array($tag,Bootstrap::$main->tags)) return $tag;
        }
        
        $sql="DELETE FROM ".$this->_table." WHERE $field=? AND name NOT IN ('".implode("','",$tags)."')";
        $this->conn->execute($sql,[$id]);
    
        $current_tags=$this->for_field($field,$id,null);
        if (!is_array($current_tags)) $current_tags=[];
        $tags_to_add=array_diff($tags,$current_tags);
        
        foreach ($tags_to_add AS $tag)
        {
            $sql="INSERT INTO ".$this->_table." (name,$field) VALUES (?,?)";
            $args=[$tag,$id];
            $this->conn->execute($sql,$args);
        }

        return $tags;
    }
    
    
    public function for_user($user,$tags=null)
    {
        return $this->for_field('user',$user,$tags);
    }


    public function for_event($event,$tags=null)
    {
        return $this->for_field('event',$event,$tags);
    }
    
    
    public function user2event($user,$event)
    {
        $sql="INSERT INTO ".$this->_table." (name,event) SELECT name,? FROM ".$this->_table." WHERE user=?";
        $this->conn->execute($sql,[$event,$user]);
    }
}
