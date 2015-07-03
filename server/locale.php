<?php

  $country='PL';
  $url='https://www.jemyrazem.pl/';
  $lang='en';
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'jemyrazem'))
  {
    $country='PL';
    $url='https://www.jemyrazem.pl/';
    $lang='pl';
  }
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'beta'))
  {
    $country='PL';
    $url='http://beta.jemyrazem.pl/';
    $lang='pl';
  }
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'webkameleon.com'))
  {
    $lang='pl';
  } 
  
  if (strstr(strtolower($_SERVER['HTTP_HOST']),'pudel.webkameleon'))
  {
    $url='http://pudel.webkameleon.com:9003/';
    $lang='es';
  }
  
 
