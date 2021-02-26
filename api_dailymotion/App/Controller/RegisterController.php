<?php

class RegisterController
{

    protected $_url;

    public function __construct($url){
        $this->_url=$url;
    }

    public function createUser(){
        $safeData=$GLOBALS["safeData"];
        if (!$safeData->postEmpty()){
            // if missing email or password
            if(!isset($safeData->_post["email"])){
                return  [
                    "code" => "400",
                    "body" => ["error" => "missing email"]
                ];
            }
            // if non validated email
            if(!($safeData->_post["email"])){
                return  [
                    "code" => "400",
                    "body" => ["error" => "non valid email"]
                ];
            }
            if(!isset($safeData->_post["password"])){
                return  [
                    "code" => "400",
                    "body" => ["error" => "missing password"]
                ];
            }
            if(!isset($safeData->_post["password_confirmation"])){
                return  [
                    "code" => "400",
                    "body" => ["error" => "missing password_confirmation"]
                ];
            }
            $email = $safeData->_post["email"];
            $password = $safeData->_post["password"];
            $password_confirmation = $safeData->_post["password_confirmation"];
            if($password != $password_confirmation){
                return  [
                    "code" => "400",
                    "body" => ["error" => "password and password_confirmation not matching"]
                ];
            }
            // check if email is not used yet
            if(!$this->emailFree($email)){
                return  [
                    "code" => "400",
                    "body" => ["error" => "an account already exists with this email"]
                ];
            }
            // create a new User
            $user = $this->saveUser($email, hash("sha256", $password));
            if(!$user["succeed"]){
                return  [
                    "code" => "400",
                    "body" => ["error" => "database error - user could not be created"]
                ];
            }
            // create a 4 digits code
            $validation_code = mt_rand(1000,9999);
            $code = $this->saveCode($user["data"], $validation_code);
            if(!$code["succeed"]){
                return  [
                    "code" => "400",
                    "body" => ["error" => "database error - user created but verification_code could not be stored"]
                ];
            }
            // send an email to the user with validation_code
            require_once "App/Mail.php";
            $mail = (new Mail($email, $validation_code))->toMail();
            if($mail){
                // return new User
                return [
                    "body" => [
                        "success" => "new user created, 4-digits code sent by email for verification",
                        "id" => $user["data"]
                    ]
                ];
            }
            // delete user ??
            return  [
                "code" => "400",
                "body" => ["error" => "smtp error - user created but email could not be sent"]
            ];
        }
        // if no data in POST
        return  [
            "code" => "400",
            "body" => ["error" => "missing data"]
        ];
    }

    public function saveUser($email, $password){
            $data = [$email, $password];
            $req = [
                "table"  => "users",
                "fields" => [
                    'email',
                    'password'
                ]
            ];
            return Model::insert($req, $data);
    }

    public function saveCode($user_id, $validation_code){
        $data = [$user_id, $validation_code];
        $req = [
            "table"  => "email_verifications",
            "fields" => [
                'user_id',
                'validation_code'
            ]
        ];
        return Model::insert($req, $data);
    }



    public function verifyCode(){
        $safeData=$GLOBALS["safeData"];
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return  [
                "code" => "400",
                "body" => ["error" => "missing credentials for Basic Auth"]
            ];
        }
        $email = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        if (!$safeData->postEmpty()){
            // if missing validation_code
            if(!isset($safeData->_post["validation_code"])){
                return  [
                    "code" => "400",
                    "body" => ["error" => "missing validation_code"]
                ];
            }
            $validation_code = $safeData->_post["validation_code"];
            // check if email and password match
            if($user_id = $this->authenticate($email, $password)){
                // check if validation_code is valid
                $valid_code = $this->validateCode($user_id, $validation_code);
                // if validated
                if($valid_code[0]){
                    return  [
                        "body" => ["success" => "email verified"]
                    ];
                }
                // if not validated, return error message
                return [
                    "code" => "400",
                    "body" => ["error" => $valid_code[1]]
                ];
            }
            // if wrong authentification
            return  [
                "code" => "401",
                "body" => ["error" => "basic authentification failed"]
            ];

        }
        // if no data in POST
        return  [
            "code" => "400",
            "body" => ["error" => "missing data"]
        ];
    }

    public function emailFree($email){
        $req = [
            "fields" => ["*"],
            "from" => "users",
            "where" => ["email ='$email'"]
        ];
        $data = Model::select($req);
        //return true if not empty or false otherwise
        return empty($data["data"]);
    }

    public function authenticate($email, $password){
        $req = [
            "fields" => ["*"],
            "from" => "users",
            "where" => ["email ='$email'"],
            "limit" => 1
        ];
        $data = Model::select($req);
        // if a user was found with that email
        if ($data["succeed"] && isset($data["data"][0])){
            // if password matches return user id
            if(hash("sha256", $password) == $data["data"][0]["password"]){
                return $data["data"][0]["id"];
            };
            // if wrong password
            return false;
        }
        // if no user was found
        else {
            return false;
        }
    }

    public function validateCode($user_id, $validation_code){
        $req = [
            "fields" => ["*"],
            "from" => "email_verifications",
            "where" => ["user_id ='$user_id'"],
            "limit" => 1
        ];
        $data = Model::select($req);
        // if user was found with validation_code check codes match
        if ($data["succeed"] && isset($data["data"][0])){
            if($validation_code == $data["data"][0]["validation_code"]){
                //check validation_code created less than a minute ago
                $created_at = $data["data"][0]["created_at"];
                $expiration_time = date('Y-m-d H:i:s',strtotime('+60 seconds',strtotime($created_at)));
                if($expiration_time >= date('Y-m-d H:i:s')){
                    // update is_verified for this user
                    $update = $this->updateVerified($user_id);
                    if(!$update["succeed"]){
                        return [false, "valid code but database issue when updating user"];
                    }
                    $delete_code = $this->deleteCode($user_id);
                    return [true, "valid code"];
                }
                // if validation_code expired delete user and code
                $delete_code = $this->deleteCode($user_id);
                $delete_user = $this->deleteUser($user_id);
                if(!$delete_code["succeed"] && !$delete_user["succeed"]){
                    return [false, "validation_code expired but database issue when deleting user and code"];
                }
                return [false, "validation_code expired, user deleted"];
            }
            // if wrong validation_code
            return [false, "wrong validation_code"];
        }
        // if no user was found
        return [false, "user doesn't have a validation_code in database"];
    }

    public function updateVerified($id){
        $req = [
            "table"  => "users",
            "fields" => ["is_verified"],
            "where" => ["id = ".$id],
            "limit" => 1
        ];
        return Model::update($req, [true]);
    }

    public function deleteCode($user_id){
       $req = [
            "from"  => "email_verifications",
            "where" => ["user_id = ".$user_id]
        ];
        return Model::delete($req);
    }

    public function deleteUser($id){
       $req = [
            "from"  => "users",
            "where" => ["id = ".$id]
        ];
        return Model::delete($req);
    }



}
