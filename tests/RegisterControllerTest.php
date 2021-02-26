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

    private function seedUser($email, $password, $code){
        $data = [$email, $password];
        $req = [
            "table"  => "users",
            "fields" => [
                'email',
                'password'
            ]
        ];
        $result = Model::insert($req, $data);
        $user_id = $result["data"];
        // seed email_verifications table
        $data2 = [$user_id, $code];
        $req2 = [
            "table"  => "email_verifications",
            "fields" => [
                'user_id',
                'validation_code'
            ]
        ];
        Model::insert($req2, $data2);
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

    // public function testDeleteOnlyGoodUserWhenVerifiesWithExpiredCode()
    // {
    //     $this->setGlobals();
    //     $req = [
    //         "fields" => ["*"],
    //         "from" => "users",
    //     ];
    //     $data = Model::select($req);
    //     $countUsersBefore = count($data);
    //     // create user and created_at so that code is expired
    //     $this->seedUser("my@email.com", "myPW", 1234, "2020-01-01 00:00:00");
    //     $this->seedUser("another@email.com", "myPW", 2345, date("Y-m-d H:i:s"));
    //     $req2 = [
    //         "fields" => ["*"],
    //         "from" => "users",
    //     ];
    //     $data2 = Model::select($req2);
    //     $this->assertEquals($count+2, count($data2));
    //     //mock the call by basic auth
    //     // $_SERVER['PHP_AUTH_USER'] = "my@email.com";
    //     // $_SERVER['PHP_AUTH_PW'] = "myPW";
    //     $GLOBALS["safeData"]->_post["validation_code"] = 1234;
    //     $c = new RegisterController("register/verify");
    //     $response = $c->verifyCode("my@email.com", "myPW");
    //     $body = $response["body"];
    //     $this->assertEquals("validation_code expired, user deleted", $body["error"]);
    //     //check that user was deleted and only him
    //     $req3 = [
    //         "fields" => ["*"],
    //         "from" => "users",
    //     ];
    //     $data3 = Model::select($req3);
    //     $this->assertEquals($count+1, count($data3));
    // }

    public function testCantVerifyUserWithInvalidCredentials()
    {
        $this->setGlobals();
        $req = [
            "fields" => ["*"],
            "from" => "users",
        ];
        $data = Model::select($req);
        $countUsersBefore = count($data);
        // create user and created_at so that code is expired
        $email = "my".rand(0,1000)."@email.com";
        $this->seedUser($email, "myTruePW", 1234);
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
        $c = new RegisterController();
        $response = $c->verifyCode($email, "myWrongPW");
        $body = $response["body"];
        $this->assertEquals("basic authentication failed", $body["error"]);
    }

}
