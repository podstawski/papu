<?php
    chdir(__DIR__);

    $url='https://docs.google.com/spreadsheet/ccc?key=1w9Le5-gXaU8xoTL-qAx-QOhOFl24Pphv29ORqFcOL2Q&output=csv';

    $cmd="wget -q -O csv \"$url\"";
    //echo "$cmd\n";
    system($cmd);
	//die();
    
    $data=file_get_contents('csv');
    //unlink ('csv');

    
    function przecinek2strumien($data)
    {
	static $len;
	
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
	
	while ($data[strlen($data)-1]=='|') $data=substr($data,0,strlen($data)-1);
	
	if (!$len) $len=substr_count($data,'|');
	
	if (substr_count($data,'|')!=$len)
	{
	    $data=str_replace('||','|',$data);

	}
	
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
            if (!isset($header[$j])) {print_r($line); die("J:$j\n".$data[$i]."\n".przecinek2strumien($data[$i]));}
            $langs[$header[$j]][$label]=$line[$j];
        }
    }
    //system("git pull ".realpath(__DIR__.'/..'));

    $js="'use strict';\nangular.module('gettext').run(['gettextCatalog', function (gettextCatalog) {\n";
    
    foreach ($langs AS $lang=>$data)
    {
        if (!trim($lang)) continue;
        echo "Lang: $lang, ".count($data)." phrases.\n";
        file_put_contents(__DIR__.'/../server/rest/langs/'.$lang.'.ser',serialize($data));
        $js.="gettextCatalog.setStrings(\"$lang\",".json_encode($data).");\n";
    }
    $js.="}]);";
    
    file_put_contents(__DIR__.'/../app/scripts/translations.js',$js);
    
    //system("git commit -m lang ".__DIR__.'/../server/rest/langs '.__DIR__.'/../app/scripts/translations.js');
    //system("git push origin master");
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
    
