<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
require_once PATH_LIB . 'com/mephex/user/User.php';


class SignOutResponder extends LightDataSysResponder
{
    public function get($args)
    {
        User::clearActiveUser();
        HttpHeader::forwardTo('/index.php');
    }
}



?>
