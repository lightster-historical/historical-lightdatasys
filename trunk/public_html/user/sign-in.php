<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/user/responders/SignInFormResponder.php';


class SignInResponder extends LightDataSysResponder
{
    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        if(!is_null($this->user))
            HttpHeader::forwardTo('/');
    }

    public function post($args)
    {
        $responder = new SignInFormResponder(null, true);
        $responder->init($args);
        $responder->post($args);

        $this->printHeader();
        $responder->get($args);
        $this->printFooter();
    }

    public function get($args)
    {
        $forwardTo = '/';
        if(array_key_exists('HTTP_REFERER', $_SERVER)
            && basename($_SERVER['HTTP_REFERER']) != 'sign-in.php')
            $forwardTo = $_SERVER['HTTP_REFERER'];

        $this->printHeader();

        $responder = new SignInFormResponder($forwardTo, true);
        $responder->init($args);
        $responder->get($args);

        $this->printFooter();
    }
}



?>
