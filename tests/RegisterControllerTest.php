<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class RegisterControllerTest extends TestCase
{

    private function setGlobals(){
        $GLOBALS["db"] = [
            "host"      => 'mysql',
            "user"      => 'root',
            "password"  => 'root',
            "database"  => 'dailymotion'
        ];
        $GLOBALS["envProd"]=false;
        $GLOBALS["safeData"]=new Security([]);
        $GLOBALS["api_mail_url"] = 'http://localhost/api_mail_server/sendmail';
        Model::init();
    }

    private function seedUser($email = null, $password = null, $id = null, $code = null, $created_at = null){
        $random_email = ($email)? $email : 'random_email'.rand(0, 1000).'@email.com';
        $random_pw = ($password)? $password : 'random_pw'.rand(0, 1000);
        $data = [$random_email, $random_pw];
        $req = [
            "table"  => "users",
            "fields" => [
                'email',
                'password'
            ]
        ];
        Model::insert($req, $data);
        //If id given, seed email_verifications table
        if($id){
            $data2 = [$id, $code, $created_at];
            $req2 = [
                "table"  => "email_verifications",
                "fields" => [
                    'user_id',
                    'validation_code',
                    'created_at'
                ]
            ];
        Model::insert($req2, $data2);
        }
    }

    public function testCreateUserWithoutData()
    {
        $this->setGlobals();
        $c = new RegisterController("register/user");
        $response = $c->createUser();
        $body = $response["body"];
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals("missing data", $body["error"]);
        $this->assertEquals("400", $response["code"]);
    }

    public function testDeleteOnlyGoodUserWhenVerifiesWithExpiredCode()
    {
        $this->setGlobals();
        $c = new RegisterController("register/user");
        $req = [
            "fields" => ["*"],
            "from" => "users",
        ];
        $data = Model::select($req);
        $countUsersBefore = count($data);
        // create user and created_at so that code is expired
        $this->seedUser("my@email.com", "myPW", 1000, 1234, "2020-01-01 00:00:00");
        $this->seedUser();
        $req2 = [
            "fields" => ["*"],
            "from" => "users",
        ];
        $data2 = Model::select($req2);
        $this->assertEquals($count+2, count($data2));
        //mock the call by basic auth
        // $_SERVER['PHP_AUTH_USER'] = "my@email.com";
        // $_SERVER['PHP_AUTH_PW'] = "myPW";
        $GLOBALS["safeData"]->_post["validation_code"] = 1234;
        $c = new RegisterController("register/verify");
        $response = $c->verifyCode("my@email.com", "myPW");
        $body = $response["body"];
        $this->assertEquals("validation_code expired, user deleted", $body["error"]);
        //check that user was deleted and only him
        $req3 = [
            "fields" => ["*"],
            "from" => "users",
        ];
        $data3 = Model::select($req3);
        $this->assertEquals($count+1, count($data3));
    }

}
