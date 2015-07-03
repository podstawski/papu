<?php
return;

use google\appengine\api\cloud_storage\CloudStorageTools;

class FileSessionHandler
{
    private $savePath;
    private $name;

    private function id2file($id)
    {
        //if (isset($_COOKIE[$this->name]) && $_COOKIE[$this->name]) $id=$_COOKIE[$this->name]; 
        return "$this->savePath/sess_$id.txt";
    }
    
    public function open($savePath, $sessionName)
    {
        $this->name=$sessionName;
        if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
        {
            require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
            $this->savePath='gs://'.CloudStorageTools::getDefaultGoogleStorageBucketName().'/sessions';
        }
        else
        {
            $this->savePath = $savePath;
            if (!is_dir($this->savePath)) {
                mkdir($this->savePath, 0777);
            }
        }
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        
        return (string)@file_get_contents($this->id2file($id));
    }

    public function write($id, $data)
    {
        return file_put_contents($this->id2file($id), $data) === false ? false : true;
    }

    public function destroy($id)
    {
        $file = $this->id2file($id);
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime)
    {
        $maxlifetime=3600*24;
        if (!file_exists($this->savePath)) return;
        foreach (scandir($this->savePath) as $file) {
            $f=$this->savePath.'/'.$file;

            if (substr($file,0,5)=='sess_' && filemtime($f) + $maxlifetime < time() && file_exists($f)) {
                unlink($f);
            }
        }

        return true;
    }
    
    public function create_sid()
    {
        if (isset($_COOKIE[$this->name]) && $_COOKIE[$this->name]) return $_COOKIE[$this->name];
        
        while (true)
        {
            $id=md5(time().rand(10000,99999));
            if (!file_exists($this->id2file($id))) break; 
        }

        return $id;
    }
}

$handler = new FileSessionHandler();
session_set_save_handler(
    array($handler, 'open'),
    array($handler, 'close'),
    array($handler, 'read'),
    array($handler, 'write'),
    array($handler, 'destroy'),
    array($handler, 'gc')
    );
