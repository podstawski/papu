<?php
    chdir(__DIR__);

    $url='https://docs.google.com/spreadsheet/ccc?key=0Amv2idDALj8-dFlIZlM2MmhZRzB0TzI3Y0xiS1Z2dWc&output=csv';

    $cmd="wget -q -O csv \"$url\"";
    //echo "$cmd\n";
    system($cmd);
    
    $data=file_get_contents('csv');
    //unlink ('csv');

    for ($i=0;$i<20;$i++)
    {
        $data=preg_replace('/,"([^"]+),([^"]+)",*/',',"\\1ZJEBANY_PRZECINEK\\2",',$data);
        //echo "$data\n\n";
    }


    $data=str_replace('"','',$data);
    $data=str_replace(',','|',$data);
    $data=str_replace('ZJEBANY_PRZECINEK',',',$data);
    
    
    $data=explode("\n",$data);
    $header=explode("|",$data[0]);
    $langs=array();
    for($i=1;$i<count($data);$i++)
    {
        $line=explode("|",$data[$i]);
        
        $label=$line[0];
        for ($j=1;$j<count($line);$j++)
        {
            if (!$line[$j]) continue;
            //if (!isset($header[$j])) {print_r($line); die("J:$j");}
            $langs[$header[$j]][$label]=$line[$j];
        }
    }
    
    
    foreach ($langs AS $lang=>$data)
    {
        if (!$lang) continue;
        file_put_contents(__DIR__.'/../server/rest/config/countries/'.$lang.'.json',json_encode($data));
    }
    
    

    
    
    