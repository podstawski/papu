<?php


class Ics {
    public static function invitation($e,$h,$g,$created,$updated)
    {
        return self::_ics($e,$h,$g,$created,$updated,'REQUEST','CONFIRMED');
    }
    
    public static function cancelation($e,$h,$g,$created,$updated)
    {
        return self::_ics($e,$h,$g,$created,$updated,'CANCEL','CANCELLED');
    }
    
    
    protected static function _ics($e,$h,$g,$created,$updated,$method,$status)
    {
        if ($g['email']==$h['email']) $h['email']='jemyrazem@jemyrazem.pl';
        
        $ics="BEGIN:VCALENDAR\nPRODID:-//Gammanet//JemyRazem calendar 1.0//EN\n";
        $ics.="VERSION:2.0\nCALSCALE:GREGORIAN\nMETHOD:$method\nBEGIN:VEVENT\n";
        $ics.="DTSTART:".self::zulu_date($e['event_start'])."\n";
        $ics.="DTEND:".self::zulu_date($e['event_end'])."\n";
        $ics.="DTSTAMP:".self::zulu_date($e['event_start'])."\n";
        $ics.="ORGANIZER;CN=".$h['firstname'].' '.$h['lastname'].":mailto:".$h['email']."\n";
        $ics.="UID:".md5($e['url'].'-'.$h['url'])."@jemyrazem.pl\n";
        $ics.="ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE\n";
        $ics.=" ;CN=".$g['firstname'].' '.$g['lastname'];
        $ics.=";X-NUM-GUESTS=0:mailto:".$g['email']."\n";
        $ics.="CREATED:".self::zulu_date($created)."\n";
        $ics.="DESCRIPTION:".str_replace("\n",' ',$e['about'])."\n";
        $ics.="LAST-MODIFIED:".self::zulu_date($updated)."\n";
        $ics.="LOCATION:".$e['address']."\\,".$e['postal'].' '.$e['city']."\n";
        $ics.="SEQUENCE:0\nSTATUS:$status\n";
        $ics.="SUMMARY:".$e['name']."\n";
        $ics.="TRANSP:OPAQUE\nEND:VEVENT\nEND:VCALENDAR";
        
        return $ics;        
    }
    
    
    protected static function zulu_date($d)
    {
	return preg_replace('/[^0-9TZ]/','',$d);
    }
}