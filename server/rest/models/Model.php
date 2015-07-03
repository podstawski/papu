<?php

include_once __DIR__.'/../class/Tools.php';

class Conn {
    protected $conn;
    protected $join='';
    
    protected function getConn()
    {
        if (!is_object($this->conn)) $this->conn=Bootstrap::$main->getConn();
        return $this->conn;
    }
    
    protected function clearRow(&$row,$hidden_fields=false)
    {
        foreach(array_keys($row) AS $k)
        {
            if (is_integer($k)) unset($row[$k]);
        }
        
        Tools::change_datetime($row,$hidden_fields);
    }
    
    public function fetchColumn($sql,$a=null)
    {
        $rows=$this->execute($sql,$a,true);
        $res=array();
        foreach($rows AS $row)
        {
            $res[]=$row[0];
        }
        return $res;
    }
    
    public function fetchRow($sql,$a=null,$hidden_fields=false)
    {
        $rows=$this->execute($sql,$a,true);
        if (!count($rows)) return false;
        
        foreach($rows AS $row)
        {
            $this->clearRow($row,$hidden_fields);
            return $row;
        }
    }
    
    
    public function fetchAll($sql,$a=null)
    {
        $rows=$this->execute($sql,$a,true);
        if (!count($rows)) return false;
        
        foreach ($rows AS &$row) $this->clearRow($row);
        
        return $rows;
    }    
    
    public function fetchOne($sql,$a=null)
    {
        $rows=$this->execute($sql,$a,true);
        if (!count($rows)) return false;
        foreach($rows AS $row)
        {
            return $row[0];
        }
    }
    
    public function execute($sql,$a=null,$query=false)
    {
        $token=$query?'db_reads':'db_writes';
        if (!$a) {
            if ($query)
                $q=$this->getConn()->query($sql);
            else
                $q=$this->getConn()->exec($sql);
                $res=$q;
        } else {
            $q=$this->getConn()->prepare($sql);
            $res=$q->execute($a);
        }
    
        //if (!$query) Bootstrap::$main->debug($sql,$a,$res);
        if (!isset(Bootstrap::$main->system[$token])) Bootstrap::$main->system[$token]=0;
        Bootstrap::$main->system[$token]++;
        
        if ($q===false) return false;
        if (!$query) return true;
        
        $res=array();
        foreach ($q AS $row)
        {
            $res[]=$row;
        }
        return $res;
    }

}

class Model {
    /**
     * @var Conn
     */     
    protected $conn;
    protected $savedData, $data;
    protected $_table = null;
    protected $_key = null;
    protected $_fields;
    
    public function __construct($data=null,$new=false) {
        $this->conn = new Conn();
        
        $fields=Bootstrap::$main->session('dbfields');

        $table=$this->getTable();
        if (!is_array($fields) || !isset($fields[$table]) || !count($fields[$table]) ) {
            
            $_data=Tools::memcache('db:'.$table.':fields');
            if (!$_data)
            {
                $db_name=Bootstrap::$main->session('db_name');
                $sql="SELECT column_name,data_type FROM information_schema.columns WHERE table_schema='".$db_name."' AND table_name='$table'";
                $f=$this->conn->fetchAll($sql);
                $a=[];
                foreach ($f AS $v) $a[$v['column_name']]=$v['data_type'];
                $_data=Tools::memcache('db:'.$table.':fields',$a);
            }
            $fields[$table]=$_data;  
            if (isset($fields[$table])) Bootstrap::$main->session('dbfields',$fields);
        }

        if (isset($fields[$table])) $this->_fields=$fields[$table];
        
        
        
        if ($data) {
            if (!is_array($data)) {
                $data=$this->get($data);
            }
        
            if (is_array($data)) {
                $this->load($data,$new);
            }

        }
          
        
    }
    
    public function conn($conn=null)
    {
        if ($conn) $this->conn=$conn;
        return $this->conn;
    }
    

    public function __call($name, $args)
    {
        $name = strtolower($name);
        

        if (substr($name, 0, 4) == 'find') {

            $sql = "SELECT * FROM " . $this->getTable();
            $one = false;
            $what = null;

            if ($name == 'find') {
                $what = $this->getKey();
            }

            if ($name == 'find_one') {
                $one = true;
                $what = $this->getKey();
            }

            if (substr($name, 0, 12) == 'find_one_by_') {
                $one = true;
                $what = substr($name, 12);
            }

            if (substr($name, 0, 8) == 'find_by_') {
                $what = substr($name, 8);
            }

            $where = array();
            foreach ($args AS $arg) {
                $where[] = "$what=?";
            }

            $sql .= ' WHERE (' . implode(' OR ', $where).')';
            
            $sql .= ' ORDER BY ' . $this->getKey();

            if ($what) {
                if ($one) {
                    $ret = $this->conn->fetchRow($sql,$args);
                    if ($ret)
                        $this->load($ret);

                    return $ret;
                }

                return $this->conn->fetchAll($sql,$args);
            }
        }
        
        Bootstrap::$main->result(array('name'=>$name),10);
    }
    
    public function __get($name) {
        $ret=null;
        
        if (isset($this->savedData[$name])) $ret=$this->savedData[$name];
        if (isset($this->data[$name])) $ret=$this->data[$name];
        
        if (is_string($ret)) $ret=trim($ret);
        return $ret;  
    }
    
    public function __set($k,$v) {
        $this->data[$k]=$v;
    }
    
    public function getTable() {
        if ($this->_table)
            return $this->_table;
        
        return str_replace('Model','', get_class($this));
    }

    public function getKey() {
        if ($this->_key)
            return $this->_key;

        return 'id';
    }
    
    public function get($id,$hidden_fields=false) {
        $sql="SELECT * FROM ".$this->getTable()." WHERE ".$this->getKey()."=?";
        $row=$this->conn->fetchRow($sql,array($id),$hidden_fields);
        if ($row) $this->load($row);
        return $row;
    }
    
    public function load ($data,$new=false) {
        
        if ($new) {
            $this->savedData=null;
            $this->data=null;
        }
        if (is_array($data)) foreach ($data AS $k=>$v) {
            if (!$new) $this->savedData[$k]=$v;
            $this->data[$k]=$v;
        }
    }
    
    public function select($where=[],$order='',$limit=0, $offset=0)
    {
        $sql="SELECT * FROM ".$this->getTable().' '.$this->join;
        
        $values=[];
        if (count($where))
        {
            $w=$this->where($where);
            $sql.=" WHERE ".$w['where'];
            $values=$w['values'];
        }
        
        if ($order) $sql.=" ORDER BY $order";
        if ($limit) $sql.=" LIMIT $limit";
        if ($offset) $sql.=" OFFSET $offset";
   
             
        return $this->conn->fetchAll($sql,$values);
    }
    
    protected static $counterer;
    
    public function save() {

        $key=$this->getKey();
    
        $inserts=array();
        $values=array();
        $sets=array();
        $pyt=array();
        $setvalues=array();
        $wheres=array();
        $wherevalues=array();
        
        
        
        
        foreach ($this->data AS $k=>$v) {
            
            if (!is_array($this->_fields) || !in_array($k,array_keys($this->_fields))) continue;
            
            if (!strlen($v) && !strstr(strtolower($this->_fields[$k]),'char') && !strstr(strtolower($this->_fields[$k]),'text')) $v=null;
            
            $inserts[]=$k;
            $values[]=$v;
            $pyt[]='?';
            
            if( isset($this->savedData[$k]) && !strlen($this->savedData[$k])) $this->savedData[$k]=null;
            if (is_array($this->savedData) && (!array_key_exists($k,$this->savedData) || $this->savedData[$k] !== $v)) {
                $sets[]="$k=?";
                $setvalues[]=strlen($v)?$v:null;
            }
            
            if (is_null($v)) 
                $wheres[]="$k IS NULL";
            else {
                $wheres[]="$k=?";
                $wherevalues[]=$v;
            }
        }
        
   
        
        if (isset($this->savedData[$this->getKey()])) {
         
            if (count($sets)) {
            
                $sql="UPDATE ".$this->getTable()." SET ".implode(',',$sets)." WHERE ".$this->getKey().'=?';
                
                $setvalues[]=$this->savedData[$this->getKey()];
                $res=$this->conn->execute($sql,$setvalues);
                
                    
                if ($res) {
                    foreach ($this->data AS $k=>$v) $this->savedData[$k]=$v;
                } else return false;
            }
            
        } else {   
            $sql="INSERT INTO ".$this->getTable()." (".implode(',',$inserts).") VALUES (".implode(',',$pyt).")";
            
            if (count($inserts)) {
                $res=$this->conn->execute($sql,$values);
        
                if ($res) {
                    $sql="SELECT ".$this->getKey()." FROM ".$this->getTable()." WHERE ".implode(' AND ',$wheres).' ORDER BY '.$this->getKey().' DESC';
                    $keyValue=$this->conn->fetchOne($sql,$wherevalues);
   
                    foreach ($inserts AS $i=>$k) $this->savedData[$k]=$values[$i];
                    $this->savedData[$key]=$keyValue;
                    $this->data[$key]=$keyValue;
                } else return false;
            }
            
        }
        $ret=$this->savedData;
        
        return $ret;
        
    }
    
    public function clear() {
        $this->data=null;
        $this->savedData=null;
    }
    
    public function data($saved=true) {
        return $saved?$this->savedData:$this->data;
    }
    
    public function remove($id=null) {
        
        if (is_null($id)) $id=$this->id;
        $sql="DELETE FROM ".$this->getTable()." WHERE ".$this->getKey()."=?";
        
        return $this->conn->execute($sql,array($id));

    }

    public function __toString()
    {
        return get_class($this);
    }
    
    
    
    public function begin()
    {
        return $this->conn->beginTransaction();
    }
    
    public function commit()
    {
        return $this->conn->commit();
    }
    
    
    public function rollback()
    {
        return $this->conn->rollback();
    }
    
    
    public function getAll($limit=0,$offset=0)
    {
        $sql="SELECT * FROM ".$this->_table." ORDER BY id";
        if ($limit) $sql.=" LIMIT $limit";
        if ($offset) $sql.=" OFFSET $offset";
        
        return $this->conn->fetchAll($sql);    
    }
    
    
    public function count($where=[])
    {
        $sql="SELECT count(*) FROM ".$this->getTable().' '.$this->join;
        if (count($where))
        {
            $w=$this->where($where);
            $sql.=" WHERE ".$w['where'];
            return $this->conn->fetchOne($sql,$w['values']); 
        }
                
        return $this->conn->fetchOne($sql);
    }
    

    public function join($field,$table,$tablefield,$join='left')
    {
        switch(strtolower($join))
        {
            case 'right':
                $this->join.=' RIGHT';
                break;
            case 'left':
                $this->join.=' LEFT';
                break;
            default:
                $this->join.=' INNER';
                break;
        }
        $this->join.=' JOIN '.$table.' ON '.$this->getTable().'.'.$field.'='.$table.'.'.$tablefield;
  
 
        return $this;
        
    }

    protected function where($where)
    {
        $where_v=array();
        $where_k=array();
        
        foreach ($where AS $k=>$v)
        {
            if (is_array($v) && count($v)==2)
            {
                $where_v[]=$v[1];
                $where_k[]=$k.$v[0]."?";                    
            }
            else
            {
                if (is_null($v)) $where_k[]="$k IS NULL";
                else {
                    $where_v[]=$v;
                    $where_k[]="$k=?";
                }
            }
        }
        
        return ['where'=>implode(' AND ',$where_k), 'values'=>$where_v];

    
    }

    public function sum($field,$where=[])
    {
        $sql="SELECT sum($field) FROM ".$this->getTable().' '.$this->join;
        $where_v=array();
        if (count($where))
        {
            $w=$this->where($where);
            $sql.=" WHERE ".$w['where'];
            return $this->conn->fetchOne($sql,$w['values']); 
        }
                
        return $this->conn->fetchOne($sql);
    }

    
}
