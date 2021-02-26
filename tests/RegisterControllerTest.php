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

    private function seedUser($email, $password, $code, $created_at = null){
        if(!$created_at) $created_at = date("Y-m-d H:i:s");
        $data = [$email, hash("sha256", $password)];
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
        $data2 = [$user_id, $code, $created_at];
        $req2 = [
            "table"  => "email_verifications",
            "fields" => [
                'user_id',
                'validation_code',
                'created_at'
            ]
        ];
        Model::insert($req2, $data2);
        return $user_id;
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
        $req = [
            "fields" => ["*"],
            "from" => "users",
        ];
        $data = Model::select($req);
        $countUsersBefore = ($data["data"] != "")? count($data["data"]) : 0;
        $email = "random".rand(0, 1000)."@email.com";
        // check user doesn't exist
        $reqCheckUser = [
            "fields" => ["*"],
            "from" => "users",
            "where" => ["email ='$email'"]
        ];
        $dataCheck = Model::select($reqCheckUser);
        $count = ($dataCheck["data"] != "")? count($dataCheck["data"]) : 0;
        $this->assertEquals(0, $count);
        // create user and created_at so that code is expired
        $user_id = $this->seedUser($email, "myPW", 1234, "2020-01-01 00:00:00");
        // check user created
        $dataCheckNow = Model::select($reqCheckUser);
        $this->assertTrue($dataCheckNow["succeed"]);
        $countNow = ($dataCheckNow["data"] != "")? count($dataCheckNow["data"]) : 0;
        $this->assertEquals(1, $countNow);
        $another_email = "random".rand(0, 1000)."@email.com";
        $this->seedUser($another_email, "myPW", 2345, date("Y-m-d H:i:s"));
        $req2 = [
            "fields" => ["*"],
            "from" => "users",
        ];
        $data2 = Model::select($req2);
        $this->assertTrue($data2["succeed"]);
        $countUsersAfter = ($data2["data"] != "")? count($data2["data"]) : 0;
        $this->assertEquals($countUsersBefore+2, $countUsersAfter);
        $GLOBALS["safeData"]->_post["validation_code"] = 1234;
        $c = new RegisterController();
        $response = $c->verifyCode($email, "myPW");
        $body = $response["body"];
        $this->assertEquals("validation_code expired, user deleted", $body["error"]);
        //check that user was deleted and only him
        $req3 = [
            "fields" => ["*"],
            "from" => "users",
        ];
        $data3 = Model::select($req3);
        $this->assertEquals($countUsersAfter-1, count($data3["data"]));
    }

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
