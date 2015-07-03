<?php
require_once __DIR__.'/../base.php';

use google\appengine\api\cloud_storage\CloudStorageTools;

$base=__DIR__.'/../../../media/log';
if (Bootstrap::$main->appengine)
{
    require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
    $base='gs://'.CloudStorageTools::getDefaultGoogleStorageBucketName().'/log';
}

$ts=time()-24*3600;
$year=date('Y',$ts);
$month=date('m',$ts);
$day=date('d',$ts);

foreach (scandir($base) AS $component)
{
    echo "Starting $component<br/>";
    if ($component[0]=='.') continue;
    if (substr($component,-1)=='/') $component=substr($component,0,strlen($component)-1);
    $dir="$base/$component/$year/$month/$day";
    
    echo "&nbsp; Scaning dir $dir<br/>";
    if (!file_exists($dir))
    {
        echo "&nbsp; &nbsp; does not exist<br/>";
        continue;
    }
    
    $log='';
    foreach(scandir($dir) AS $f)
    {
        if ($f[0]=='.') continue;
        $log.=file_get_contents("$dir/$f");
        unlink("$dir/$f");
    }
    file_put_contents("$base/$component/$year/$month/$day.txt",$log);
    @unlink($dir);
    @rmdir($dir);
}