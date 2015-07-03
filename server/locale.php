<?php

  $country='US';
  $url='https://www.jemyrazem.pl/';
  $lang='en';
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'beta'))
  {
    $country='AR';
    $url='http://beta.epapu.com/';
    $lang='es';
  }
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'webkameleon.com'))
  {
    $lang='pl';
  } 
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'www.epapu.com'))
  {
    $url='http://www.epapu.com/';
    $lang='en';
  }
  
 
