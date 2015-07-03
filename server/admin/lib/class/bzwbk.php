<?php



class bzwbk {
    
    protected $wbk,$tab,$events,$events_ids;
    const account='53109014630000000046204445';
    
    public function __construct()
    {
        $this->wbk="3220106\r\n";
        $this->tab=[];
        $this->events=[];
        
    }
    
    public function add($e)
    {
        if (!$e['user']['payment']) {
            $this->events_ids[$e['id']] = 0;
            Tools::observe('no-payment',$e);
            return false;
        }
        $payment=preg_replace('/[^0-9]/','',$e['user']['payment']);
        if (strlen($payment)!=26) {
            $this->events_ids[$e['id']] = 0;
            Tools::observe('no-payment',$e);
            return false;
        }
        if (!isset($this->tab[$payment])) {
            $this->tab[$payment]=[
                'total'=>0,
                'events'=>[],
                'city'=>str_replace('|','.',$e['user']['city']),
                'address'=>str_replace('|','.',$e['user']['address']),
                'postal'=>str_replace('|','.',$e['user']['postal']),
                'name'=>str_replace('|','.',$e['user']['firstname'].' '.$e['user']['lastname'])
                
            ];
        }
        $this->tab[$payment]['total']+=$e['total'];
        $this->tab[$payment]['events'][]=$e['event']['url'].'/'.substr($e['event']['event_start'],0,10).'/'.$e['persons'].'x'.$e['event']['host_price'].$e['event']['currency'];
        $this->events[]=$e['event']['id'];
        $this->events_ids[$e['id']] = time();
    }
    
    public function complete($file)
    {
        if (!count($this->tab)) return;
        foreach($this->tab AS $account=>$t)
        {
            $this->wbk.='1|'.self::account.'|'.$account.'|'.substr($t['name'],0,40);
            $this->wbk.='|'.substr($t['city'],0,40).'|'.substr($t['address'],0,40).'|'.substr($t['postal'],0,6);
            $this->wbk.='|'.$t['total'].'|1|JemyRazem:'.implode(',',$t['events']).'||';
            $this->wbk.="\r\n";
            
            echo $t['total'].' &raquo; '.$account.' ('.$t['name'].')'.'<br/>';
        }
        $wbk=iconv('UTF-8','Windows-1250',$this->wbk);
        
        foreach($this->events_ids AS $id=>$timestamp)
        {
            $event=new eventModel($id);
            $event->d_transfer=$timestamp;
            $event->save();
        }
        Tools::save($file,$wbk);
        return $wbk;            
    }
}