<?php

class Mail
{

    protected $_email;
    protected $_token;

    public function __construct($email, $validation_code){
        $this->_email=$email;
        $this->_validation_code=$validation_code;
    }

    public function toMail(){
        $api_mail_url=$GLOBALS["api_mail_url"];;
        $ch = curl_init();
        $data = array(
            'email' => $this->_email,
            'validation_code' => $this->_validation_code
        );
        curl_setopt($ch, CURLOPT_URL, $api_mail_url);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Expect:'));
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response == "OK") {
            $responseData = json_decode($response, TRUE);
            return true;
        }
         else {
            return false;
        }
    }


}
