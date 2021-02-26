<?php

class Model {

    //PDO instance
    private static $_db;

    public static function init(){
        $envProd=$GLOBALS["envProd"];
        $my_db=$GLOBALS["db"];
        self::$_db = new PDO('mysql:host='.$my_db["host"].';dbname='.$my_db["database"].';charset=utf8', $my_db["user"], $my_db["password"]);
        self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (!$envProd) self::$_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        unset($my_db);
    }

    public static function request($sql, $data=NULL, $insert=false){
        try {
            if ($data == NULL){
                $result = self::$_db->query($sql);
                $data = $result->fetchAll();
            }
            else {
                $result = self::$_db->prepare($sql);
                $result->execute($data);
                //store result
            }
            if ($insert){
                $data= self::$_db->lastInsertId();
            }
            //close request
            $result->closeCursor();
            //if no result
            if (empty($data)) $data="";

            return [
                "succeed" => true,
                "data"    => $data
            ];
        }
        catch(Exception $e) {
            print_r($e);
            return [
                "succeed" => false,
                "data"    => $e
            ];
        }
    }

    // build an sql SELECT query from args array
    public static function select($args){
        //add all fields to be selected
        $req = 'SELECT '.implode(", ", $args["fields"]);
        //add db table
        $req .= ' FROM '.$args["from"];
        //add optional thing
        if (isset($args["join"])) $req .= ' INNER JOIN ' .$args["join"];
        if (isset($args["on"])) $req.= ' ON ' .$args["on"];
        if (isset($args["where"])) $req .= ' WHERE ' .implode(" AND ", $args["where"]);
        if (isset($args["order"])) $req .= " ORDER BY ".$args["order"];
        if (isset($args["limit"])) $req .= " LIMIT ".$args["limit"];
        //launch query and return result
        return self::request($req);
    }

    // build an sql UPDATE query from args array
    public static function update($args, $data){
        $req = 'UPDATE '.$args["table"];
        $req .= ' SET '.implode("=? , ", $args["fields"])."=?";
        $req .= ' WHERE '.implode(" AND ", $args["where"]);
        if (isset($args["limit"])) $req .= " LIMIT ".$args["limit"];
        //launch query and return result
        return self::request($req, $data);
    }

    // build an sql INSERT query from args array
    public static function insert($args, $data){
        $req = 'INSERT INTO '.$args["table"];
        $req .= ' ('.implode(", ", $args["fields"]).")";
        $req .= ' VALUES ( ?';
        $i = 1;
        while (isset($args["fields"][$i])){
            $req .= " , ?";
            $i++;
        }
        $req .= " )";
        //launch query and return result
        return self::request($req, $data, true);
    }

    // build an sql DELETE query from args array
    public static function delete($args){
        $req = 'DELETE FROM '.$args["from"];
        if (isset($args["where"])) $req .= ' WHERE ' .implode(" AND ", $args["where"]);
        //launch query and return result
        try {
            $result = self::$_db->query($req);
            return [
                "succeed" => true
            ];
        }
        catch(Exception $e) {
            print_r($e);
            return [
                "succeed" => false,
                "data"    => $e
            ];
        }
    }

}
