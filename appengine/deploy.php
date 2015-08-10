<?php

    $dir=realpath(__DIR__.'/../dist');
    $server=realpath(__DIR__.'/../server');
    $dest=__DIR__.'/tmp';
    
    system('rm -rf '.$dest);
    @mkdir($dest,0755);
    
    $yaml=file_get_contents(isset($argv[1]) && $argv[1]=='www'?__DIR__.'/app.www':__DIR__.'/app.beta');
    $cron=file_get_contents(__DIR__.'/cron.yaml');
    $ini=file_get_contents(__DIR__.'/php.ini');
    
    if (!file_exists(__DIR__.'/email.txt')) die("Brak pliku email.txt\n");
    $mail=trim(file_get_contents(__DIR__.'/email.txt'));
    
    
    $files=array();
    foreach (scandir($dir) AS $f)
    {
        if ($f[0]=='.') continue;
        
        $cmd="cp -Rp $dir/$f $dest";
        $files[]=$f;
        system($cmd);
        
        if (is_dir("$dir/$f"))
        {
            $yaml.="\n- url: /$f\n  static_dir: $f\n";
        }
        else
        {
	    if ($f=='index.html') continue;
            $yaml.="\n- url: /$f\n  static_files: $f\n  upload: $f\n";
	    //if ($f=='index.html') $yaml.="  login: admin\n";
        }
    }
    $yaml.=file_get_contents(__DIR__.'/app.yaml.post');

    file_put_contents("$dest/app.yaml",$yaml);
    file_put_contents("$dest/cron.yaml",$cron);
    file_put_contents("$dest/php.ini",$ini);

    foreach (scandir($server) AS $f)
    {
        if ($f[0]=='.') continue;
        $cmd="cp -HRp $server/$f $dest";
        $files[]=$f;
        system($cmd);        
    }
    system("rm $dest/i18n/[b-z]* $dest/i18n/a[a-m]* $dest/i18n/a[o-z]*");


    system('git pull  >/dev/null');

    $dst=isset($argv[1]) && $argv[1]=='www'?'www':'beta';
    $dir=explode('/',__DIR__);
    file_put_contents(__DIR__.'/log.txt',date('Y-m-d H:i:s').' '.$dir[2].' -> '.$dst."\n",FILE_APPEND);
    $cmd="/opt/google/appengine/appcfg.py --no_cookies -e $mail update $dest";
    system('git commit -m deploy '.__DIR__.' 2>/dev/null');
    system('git push origin master');
    system($cmd);
    //system('rm -rf '.$dest);
    
    
    
