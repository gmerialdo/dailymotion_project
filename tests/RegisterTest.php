<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class RegisterTest extends TestCase
{

    private $URL_localhost = "http://localhost:8080";

    private $URL_register = '/api_dailymotion/register/user';

    public function testRegisterUserSuccess()
    {
        // create our http client (Guzzle)
        $client = new Client(['base_uri' => $this->URL_localhost]);

        $data = [
            'email' => 'valid@email.fr',
            'password' => 'mysecretpassword',
            'password_confirmation' => 'mysecretpassword',
        ];
        $response = $client->post($this->URL_register, [
            'form_params' => $data
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(true), true);
        $this->assertArrayHasKey('body', $data);
        $body = $data['body'];
        $this->assertArrayHasKey('success', $body);
    }

}
