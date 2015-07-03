<?php


require_once __DIR__.'/../../../rest/controllers/Controller.php';

class fakturownia extends Controller {
    

    static $api_token='KziQi5tsF05liULNYvYB/gammanetzoo';
    static $departament='111967';
    static $product='2236412';
    static $description='Zwolniono z VAT na podstawie art. 43 ust. 1 pkt 40 ustawy o podatku VAT. Kompensata - pobrano z wpływów';
    static $url='https://gammanetzoo.fakturownia.pl/invoices.json';
    static $url_mail='https://gammanetzoo.fakturownia.pl/invoices/{id}/send_by_email.json?api_token={api_token}';
    
    
    public function invoice($e)
    {

        $invoice=['kind'=>'vat','department_id'=>self::$departament,'description'=>self::$description];
        
        if ($e['user']['_payment_id']) {
            $invoice['client_id']=$e['user']['_payment_id'];
        } else {
            $invoice['buyer_name']=$e['user']['firstname'].' '.$e['user']['lastname'];
            $invoice['buyer_street']=$e['user']['address'];
            $invoice['buyer_post_code']=$e['user']['postal'];
            $invoice['buyer_city']=$e['user']['city'];
            $invoice['buyer_email']=$e['user']['email'];
        }
        
        $invoice['positions']=[];
        $total=0;
        foreach ($e['commision'] AS $amount=>$quantity)
        {
            $total+=$quantity*$amount;
            $invoice['positions'][]=array('product_id'=>self::$product,'quantity'=>$quantity,'total_price_gross'=>$amount*$quantity,'tax'=>'zw');
        }
        $invoice['paid']=$total;
        
        $req=['api_token'=>self::$api_token,'invoice'=>$invoice];
        
        $data=json_encode($req,JSON_NUMERIC_CHECK);
        
        $resp=$this->req(self::$url,$data,'POST','Content-Type: application/json; charset=utf8');
        
        $result=json_decode($resp,true);
        
        if (!isset($result['id']))
        {
            echo "Invoice could not be issued!<br/>";
            return null;
        }
        
        $mail=self::$url_mail;
        $mail=str_replace('{id}',$result['id'],$mail);
        $mail=str_replace('{api_token}',self::$api_token,$mail);
        
        $mail_res=$this->req($mail,[]);
        
        //mydie(json_decode($mail_res),$mail);
        return $result['client_id'];
    }
    

}