<?php

class Security
{

    public $_post = [];
    public $_url  = [];

    public function __construct($args) {
        //Get URL
        $this->_url = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL);
        $this->_url = explode("/", $this->_url);
        $this->_url = array_slice($this->_url, 2);
        //Get all POST
        if (isset($args["post"])){
          $this->_post = filter_input_array(INPUT_POST, $args["post"]);
        }
    }

    public function postEmpty(){
        return empty($this->_post);
    }

}
