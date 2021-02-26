<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final RegisterTest extends TestCase
{

    private $localhostURL = "http://localhost:8080/";

    private $registerURL = 'api_dailymotion/register/user';

    public function callPost($url, $data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->localhostURL.$url);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Expect:'));
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


    public function testRegisterUserSuccess()
    {
        $data = array(
            'email' => 'valid@email.fr',
            'password' => 'mysecretpassword',
            'password_confirmation' => 'mysecretpassword',
        );
        $response = $this->callPost($this->registerURL, $data);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(true), true);
    }

}
