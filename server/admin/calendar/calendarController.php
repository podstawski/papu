<?php
require_once __DIR__.'/../../rest/class/iCalReader/class.iCalReader.php';
require_once __DIR__.'/../../rest/controllers/eventController.php';




class calendarController extends eventController {
    protected $ical;
    protected static $future_days=30;
    protected $userstatus;
    
    protected function strtotime($t,$tz=null)
    {
        if (!$tz || substr($t,-1)=='Z') return strtotime($t);
        
        if ($tz && $tz+0==$tz) {
            $tz=round($tz/1800)*1800;
            return strtotime($t)-$tz;
        }
        $date=new DateTime($t, new DateTimeZone($tz));
        return $date->getTimestamp();
     
    }

    protected function timetostr($t)
    {
        return date('Y-m-d H:i',$t);
    }
    
    protected function processEvent($event,$start,$end,$update_allowed=true)
    {

        $e=$this->event()->find_one_by_ical_id($event['UID']);
        echo '&nbsp; ['.$event['UID'].' ';
        if ($e) {
            if (!$e['active'] && $update_allowed) {
                echo 'UPD';
                $deadlinetostart=$e['d_event_start']-$e['d_deadline'];
                $this->data=[
                    'name'=>stripslashes($event['SUMMARY']),
                    'event_start'=>$this->timetostr($start),
                    'event_end'=>$this->timetostr($end),
                    'deadline'=>$this->timetostr($start-$deadlinetostart)
                ];
                $this->id=$this->event()->id;
                $status=$this->put();
                echo $status['status']?1:0;
            } else {
                echo 'SKIP';

            }
            
        } else {
            $this->data=[
                'name'=>stripslashes($event['SUMMARY']),
                'event_start'=>$this->timetostr($start),
                'event_end'=>$this->timetostr($end),
                'about'=>stripslashes($event['DESCRIPTION']),
                'ical_id'=>$event['UID']
            ];

            echo 'NEW';
            $this->id=null;
            $status=$this->post();
            echo $status['status']?1:0;
        }
        echo '] ';
        return $this->event()->data();
    }
    
    public function processUserEvents()
    {
        
        $user=$this->data;
        Bootstrap::$main->user = $user;
        echo '<hr size="1"><b>Processing '.$user['url'].' (delta='.$user['delta'].')</b><br/>';
        
        $this->userstatus=true;
        $this->ical = new ICal($user['ical']);
        $events = $this->ical->events();
        if (!is_array($events)) return;
        echo count($events).' events<br/>';
        
        Tools::log('calendar',['url'=>$user['url'],'count'=>count($events)]);
        
        
        foreach ($events AS &$event)
        {
            $tz=$user['delta'];
            foreach (array_keys($event) AS $k)
            {
                if ($pos=strpos($k,'TZID='))
                {
                    $tz=substr($k,$pos+5);
                    if ($pos=strpos($tz,';')) $tz=substr($tz,0,$pos);
                    break;
                }
            }
            $start=$this->strtotime($event['DTSTART'],$tz);
            $end=$this->strtotime($event['DTEND'],$tz);
            
            if (isset($event['RRULE'])) {
                $dates=$this->parseRrule($start,$event['RRULE'],$tz);

                if (is_array($dates) && count($dates))
                {
                    $duration=$end-$start;
                    $start=$dates[0];
                    $end=$start+$duration;
                    $e=$this->processEvent($event,$start,$end,false);
                    
                    echo $event['SUMMARY'].' ['.$this->timetostr($start).'] ... seq='.count($dates).'<br/>';
                    Tools::log('calendar',$event['SUMMARY'].' ['.$this->timetostr($start).'] ... seq='.count($dates));
                    
                    echo '&nbsp; &nbsp;';
                    foreach ($dates AS $dd) echo '&nbsp; &raquo; '.$this->timetostr($dd);
                    echo '<br/>';
                    
                    if (isset($e['id']) && $e['id'])
                    {
                        $parent=$e['id'];
                        $deadlinetostart=$e['d_event_start']-$e['d_deadline'];
                        $this->event()->get($parent);
                        $parent_active=$this->event()->active;
                        
                        $edates=$this->event()->get_dates($parent,true,false);
                        if (!is_array($edates)) $edates=[];
                        
                        
                       
                        $edates2=[];
                        foreach ($edates AS $edate)
                        {
                               
                            if (isset($edate['d_event_start'])) $edates2[$edate['d_event_start']]=$edate;
                        }
                        
                        
                        
                        foreach (array_intersect($dates,array_keys($edates2)) AS $d)
                        {
                            if (!$edates2[$d]['active'])
                            {
                                $this->event()->get($edates2[$d]['id']);                                
                                $this->event()->d_event_end=$d+$duration;
                                if (!$this->event()->parent) $this->event()->name=$event['SUMMARY'];
                                
                                $this->event()->save();
                            }
                        }
                        
                        
                        
                        foreach (array_diff($dates,array_keys($edates2)) AS $d)
                        {
                            $this->id=null;
                            $this->data=['parent'=>$parent];
                            $e=$this->post();
                            
                            if (isset($e['calendar']['id']))
                            {
                                $this->event()->get($e['calendar']['id']);
                                $this->event()->d_event_start=$d;
                                $this->event()->d_event_end=$d+$duration;
                                $this->event()->d_deadline=$d-$deadlinetostart;
                                $this->event()->active = $parent_active;
                                $this->event()->ical_id = 'p-'.$event['UID'];
                                $this->event()->save();
                            }
                        }
                        
                        foreach (array_diff(array_keys($edates2),$dates) AS $d)
                        {
                            if ($edates2[$d]['id']==$parent) continue;
                            if ($edates2[$d]['active']) continue;
                            if ($edates2[$d]['d_event_start']<Bootstrap::$main->now) continue;
                        
                            $this->event()->remove($edates2[$d]['id']);
                        }
                        
                        
                    }
                    

                }
                
                
            } else {
                if ($start<Bootstrap::$main->now) continue;
                $this->processEvent($event,$start,$end);
                echo $event['SUMMARY'].' ['.$this->timetostr($start).']<br/>';
                Tools::log('calendar',$event['SUMMARY'].' ['.$this->timetostr($start).']');
            }
            
            if (!$this->userstatus) break;
        }
        
    }
    
    
    protected function parseRrule($start,$rule,$tz)
    {
        $params = explode(';', $rule);
        $data=['interval'=>1,'count'=>9999999];
        foreach ($params as $param) {
            list($name, $value) = explode('=', $param);
            switch ($name) {
                case 'UNTIL':
                    $data['until'] = $this->strtotime($value,$tz);
                    break;
                case 'FREQ':
                    $data['freq'] = $this->translateFrequency($value);
                    break;
                case 'INTERVAL':
                    $data['interval'] = $value;
                    break;
                case 'COUNT':
                    $data['count'] = intval($value);
                    break;
                case 'WKST':
                    //$data['wkst'] = $data['translateWeekday($value);
                    break;
                case 'BYSECOND':
                    $data['bysecond'] = explode(',', $value);
                    break;
                case 'BYMINUTE':
                    $data['byminute'] = explode(',', $value);
                    break;
                case 'BYHOUR':
                    $data['byhour'] = explode(',', $value);
                    break;
                case 'BYDAY':
                    $data['byday'] = $this->translateDay(explode(',', $value));
                    break;
                case 'BYMONTHDAY':
                    $data['bymonthday'] = explode(',', $value);
                    break;
                case 'BYYEARDAY':
                    $data['byyearday'] = explode(',', $value);
                    break;
                case 'BYWEEKNO':
                    $data['byweekno'] = explode(',', $value);
                    break;
                case 'BYMONTH':
                    $data['bymonth'] = explode(',', $value);
                    break;
                case 'BYSETPOS':
                    $data['bysetpos'] = explode(',', $value);
                    break;
            }
        }
        $dates=[];
        $ts=$start;
        
        $future=Bootstrap::$main->now+self::$future_days*24*3600;
        if (isset($data['until']) && $data['until']<$future) $future=$data['until'];
        while ($ts<=$future)
        {
            if (isset($data['byday']) && is_array($data['byday'])) {
                $ts_dow=date('w',$ts);
                foreach($data['byday'] AS $dow)
                {
                    $delta=$dow-$ts_dow;
                    if ($delta) {
                        if ($delta>0) $delta="+$delta";
                        $ts2=strtotime("$delta day",$ts);
                        if ($ts2<=$future && $ts2>$start) $dates[]=$ts2;
                    } else {
                        if ($ts<=$future) $dates[]=$ts;
                    }
                    if (count($dates)>=$data['count']) break 2;
                }
            } else {
                $dates[]=$ts;
                if (count($dates)>=$data['count']) break;
            }
            
            $plus='+'.$data['interval'].' '.$data['freq'];
            $ts=strtotime($plus,$ts);            
            
            
        }
        
        sort($dates);
        
        while (isset($dates[0]) && $dates[0]<Bootstrap::$main->now) array_shift($dates);
        
        
        //echo '<pre>'.print_r($data,1).'</pre>'; foreach ($dates AS $date) echo date('d-m-Y H:i (w)',$date).'<br>';
        
        return $dates;
    }
    
    protected function translateDay($value) {
        if (is_array($value)) {
            $days=[];
            foreach ($value AS $day) $days[]=$this->translateDay($day);
            return $days;
        }
        $days=['SU','MO','TU','WE','TH','FR','SA'];
        return array_search($value,$days);
    }

    protected function translateFrequency($value) {
        switch ($value) {
            case 'YEARLY': return 'year'; break;
            case 'MONTHLY': return 'month'; break;
            case 'WEEKLY': return 'week'; break;
            case 'DAILY': return 'day'; break;
            case 'HOURLY': return 'hour'; break;
            case 'MINUTELY': return 'minute'; break;
            case 'SECONDLY': return 'second'; break;
        }
    }
    
    
    protected function error($id=0,$ctx=null)
    {
        if (!is_null($this->error_trap)) return;
        
        require_once __DIR__.'/../../rest/class/Error.php';
        $result['error']=Error::e($id);
        if (!is_null($ctx)) $result['ctx']=$ctx;
        
        echo 'Error: '.json_encode($result).'<br/>';
        Tools::log('calendar',$result);
        $this->userstatus=false;
    }
    
}



