<?php

require_once __DIR__ . '/../lib/Doctrine/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

use google\appengine\api\users\User;
use google\appengine\api\users\UserService;
use google\appengine\api\cloud_storage\CloudStorageTools;

if (isset($_SERVER['REMOTE_ADDR'])) {
    $title='Migration';
    $menu='migrate';
    include __DIR__.'/../base.php';
    include __DIR__.'/../head.php';
}

if (isset($_SERVER['HTTP_HOST']))
{
    if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
    {
        require_once 'google/appengine/api/users/User.php';
        require_once 'google/appengine/api/users/UserService.php';

        $google_user = UserService::getCurrentUser();        
        $config_file=str_replace('~','-',$_SERVER['APPLICATION_ID']);
    }
    else
    {
        $config_file=strtolower($_SERVER['HTTP_HOST']);
    }
} else {
    $config_file='local';
    if (isset($argv[1])) $_REQUEST['ver']=$argv[1];
}
$f=__DIR__.'/../../rest/config/application.json';
if (isset($_SERVER['REMOTE_ADDR'])) echo "Configuration from $f<br/>";
$config=json_decode(file_get_contents($f),true);



$f=__DIR__.'/../../rest/config/'.$config_file.'.json';

if (file_exists($f))
{
    if (isset($_SERVER['REMOTE_ADDR'])) echo "Configuration from $f<br/>";
    $config=array_merge($config,json_decode(file_get_contents($f),true));
}
else
{
    die('No file: '.$f."\n");
}

try {
    
    $dsn=explode(';dbname=',$config['db.dsn']);
    $dbname=$dsn[1];
    $user=$config['db.user'];
    $pass=$config['db.pass'];
    if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
    {
        $user='root';
        $pass=null;
    }
    if (isset($_SERVER['REMOTE_ADDR'])) {echo 'DSN: ';print_r($dsn);echo '<br/>';}
    $db=new PDO($dsn[0],$user,$pass);
    $conn = Doctrine_Manager::connection($db);
    $dbtable=$conn->fetchOne("SELECT schema_name FROM information_schema.schemata WHERE schema_name='".$dbname."'");
    if (!$dbtable) {
        $sql="CREATE DATABASE `".$dbname."` CHARACTER SET utf8;
                CREATE USER '".$config['db.user']."'@'localhost' IDENTIFIED BY '".$config['db.pass']."';
                GRANT ALL PRIVILEGES ON ".$dbname.".* TO '".$config['db.user']."'@'%' WITH GRANT OPTION;
        ";
        $db->exec($sql);
    }
    $conn->close();
    
    $db = new PDO($config['db.dsn'],$config['db.user'],$config['db.pass']);
    $conn = Doctrine_Manager::connection($db);
    
    
    
    if (isset($google_user)) echo 'user: '.$google_user->getNickname().'<br>';
    
    
    
    $migration = new Doctrine_Migration(__DIR__ . '/classes', $conn);
    $migration->setTableName('doctrine_migration_version');

    if (isset($_REQUEST['ver']) && (!isset($google_user) || $google_user->getNickname()=='piotr.podstawski@gammanet.pl')) $version = 0+ intval($_REQUEST['ver']);
    else {
        $classesKeys = array_keys($migration->getMigrationClasses());
        $version = 0+array_pop($classesKeys);
    }
    
    if (isset($_SERVER['HTTP_HOST'])) echo '<h1>';
    if ($migration->getCurrentVersion() == $version) {
        echo 'Database at version ' . $version . PHP_EOL;
    } else {
        $migration->migrate($version);
        
        echo 'Migrated succesfully to version ' . $migration->getCurrentVersion() . PHP_EOL;
    }
    if (isset($_SERVER['HTTP_HOST'])) echo '</h1>';

    
    if (isset($_SERVER['SERVER_SOFTWARE']) && strstr(strtolower($_SERVER['SERVER_SOFTWARE']),'engine'))
    {
        require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
        $path='gs://'.CloudStorageTools::getDefaultGoogleStorageBucketName().'/sql';
        if (file_exists($path))
        {
            foreach (scandir($path) as $file) {
                $f=$path.'/'.$file;
                echo 'Execute '.$file.PHP_EOL;
                $conn->execute(file_get_contents($f));
            }
        }
    }
    
    if (isset($_REQUEST['sql']) && (!isset($google_user) || $google_user->getNickname()=='piotr.podstawski@gammanet.pl'))
    {
        $r=$conn->execute($_REQUEST['sql']);
        print_r($r);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    die($e->getMessage());
}

if (!isset($_SERVER['HTTP_HOST'])) return;
?>
<?php if (!isset($google_user) || $google_user->getNickname()=='piotr.podstawski@gammanet.pl'): ?>
<form method="get" style="padding:2em;">
    db.ver=<input type="text" size="4" name="ver" placeholder="ver" value="<?php echo $version?>"/>
    
    
    <br/><br/><textarea style="width:100%; height:100px" name="sql" placeholder="sql"><?php if (isset($_REQUEST['sql'])) echo $_REQUEST['sql'];?></textarea>
    
    
    <br/><br/><input type="submit" value="go!"/>
    
</form>
<?php endif; ?>
<?php
    if (isset($_SERVER['REMOTE_ADDR'])) include __DIR__.'/../foot.php';