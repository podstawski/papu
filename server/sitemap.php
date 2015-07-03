<?php
  include __DIR__.'/admin/base.php';

  require_once __DIR__.'/rest/models/eventModel.php';
  require_once __DIR__.'/rest/models/rateModel.php';
  require_once __DIR__.'/rest/controllers/cityController.php';
  
  function sitemap_date($t)
  {
    return date('c',$t);
    
  }
  include __DIR__.'/locale.php';
  $sitemap=[];
  $sitemap[]=['loc'=>$url,'priority'=>1,'lastmod'=>sitemap_date(strtotime(date('Y-m-d')))];
  
  $city=new cityController();
  $cities=$city->get($country);
  
  $rate=new rateModel();
  
  if (isset($cities['cities'])) {
    foreach ($cities['cities'] AS $city)
    {
      $sitemap[]=['loc'=>$url.'events/'.$city['lat'].'/'.$city['lng'].'/'.round($city['distance']).'/'.urlencode($city['name']),'priority'=>0.9,'lastmod'=>sitemap_date(strtotime(date('Y-m-d')))];
      
    }
  }
  
  
  $event=new eventModel();
  $events=$event->allEventsForCountry($country);

  $hosts=[];
  foreach ($events AS $e)
  {
    $date=$e['d_change']?:strtotime(date('Y-m-d'));
    $reviews=$rate->event_reviews($e['event_id'],true,1)?:[];
    if (isset($reviews[0]) && $reviews[0]['d_create']>$date) $date=$reviews[0]['d_create'];
    $sitemap[]=['loc'=>$url.$e['host_url'].'/'.$e['event_url'],'priority'=>0.8,'lastmod'=>sitemap_date($date)];
    
    if (isset($hosts[$e['host_url']])) $hosts[$e['host_url']]=max($hosts[$e['host_url']],$date);
    else $hosts[$e['host_url']]=$date;
  }
  
  foreach($hosts AS $host=>$date)
  {
    $sitemap[]=['loc'=>$url.$host,'priority'=>0.7,'lastmod'=>sitemap_date($date)];
    
  }
  
  if (isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REQUEST_URI'])) {
    Tools::log('bots',['agent'=>$_SERVER['HTTP_USER_AGENT'],'uri'=>$_SERVER['REQUEST_URI']]);
  }
  
  header('Content-type: application/xml; charset=utf-8');
  
  
?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">  
<?php foreach($sitemap AS $url): ?>
  <url>
    <loc><?php echo $url['loc']?></loc>
    <lastmod><?php echo $url['lastmod']?></lastmod>
    <priority><?php echo $url['priority']?></priority>
  </url>
<?php endforeach; ?>
</urlset>

  