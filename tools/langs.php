<?php
    chdir(__DIR__);

    $url='https://docs.google.com/spreadsheet/ccc?key=0Amv2idDALj8-dFU0QWF5bi1LT1BRSGpHNmwxZGI2anc&output=csv';

    $cmd="wget -q -O csv \"$url\"";
    //echo "$cmd\n";
    system($cmd);
	//die();
    
    $data=file_get_contents('csv');
    //unlink ('csv');

    
    function przecinek2strumien($data)
    {
        $last_data=$data;
        while(true)
        {
            $data=preg_replace('/,"([^"]+),([^"]+)",*/',',"\\1ZJEBANY_PRZECINEK\\2",',$data);
            if ($last_data==$data) break;
            $last_data=$data;
        }
    
    
        $data=str_replace('"','',$data);
        $data=str_replace(',','|',$data);
        $data=str_replace('ZJEBANY_PRZECINEK',',',$data);
        
        return $data;
    }
    

    
    
    $data=explode("\n",$data);
    $header=explode("|",przecinek2strumien($data[0]));
    $langs=array();
    for($i=1;$i<count($data);$i++)
    {
        $line=explode("|",przecinek2strumien($data[$i]));
        
        $label=$line[0];
        for ($j=1;$j<count($line);$j++)
        {
            if (!$line[$j]) continue;
            //if (!isset($header[$j])) {print_r($line); die("J:$j");}
            $langs[$header[$j]][$label]=$line[$j];
        }
    }
    system("git pull ".realpath(__DIR__.'/..'));

    $js="'use strict';\nangular.module('gettext').run(['gettextCatalog', function (gettextCatalog) {\n";
    
    foreach ($langs AS $lang=>$data)
    {
        if (!$lang) continue;
        echo "Lang: $lang, ".count($data)." phrases.\n";
        file_put_contents(__DIR__.'/../server/rest/langs/'.$lang.'.ser',serialize($data));
        $js.="gettextCatalog.setStrings(\"$lang\",".json_encode($data).");\n";
    }
    $js.="}]);";
    
    file_put_contents(__DIR__.'/../app/scripts/translations.js',$js);
    
    system("git commit -m lang ".__DIR__.'/../server/rest/langs '.__DIR__.'/../app/scripts/translations.js');
    system("git push origin master");
    $cmd="cd ..; grunt nggettext_extract";
    system ($cmd);
    $po=file_get_contents(__DIR__.'/../po/template.pot');
    
    $pos=strpos($po,'msgid ');
    
    while(($pos=strpos($po,'msgid "'))!==false)
    {
 
        $po=substr($po,$pos+7);
        $pos=strpos($po,'"');
        $txt=substr($po,0,$pos);
        if (!$txt) continue;
        if (isset($langs['en'][$txt])) continue;
        echo "$txt\n";
    }
    