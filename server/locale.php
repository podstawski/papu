<?php

  $country='US';
  $url='http://www.epapu.com/';
  $lang='en';
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'beta'))
  {
    $country='AR';
    $url='http://beta.epapu.com/';
    $lang='es';
  }
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'webkameleon.com'))
  {
    $lang='es';
  }

  if (strstr(strtolower($_SERVER['HTTP_HOST']),'pudel.webkameleon.com'))
  {
    $url='http://pudel.webkameleon.com:9006/';
    
  }   
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'www.epapu.com'))
  {
    $url='http://www.epapu.com/';
    $lang='en';
  }
  
 
