<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/user/responders/PasswordChangeFormResponder.php';


class PasswordResponder extends LightDataSysResponder
{
    public function checkPermissions()
    {
        parent::checkPermissions();
        $this->checkPermission('com.lightdatasys', 'usercp');
    }


    public function printHeader()
    {
        parent::printHeader();
    }

    public function post($args)
    {
        $responder = new PasswordChangeFormResponder($args);
        $responder->init($args);
        $responder->post($args);

        $this->printHeader();
        $responder->get($args);
        $this->printFooter();
    }

    public function get($args)
    {

        $responder = new PasswordChangeFormResponder($args);
        $responder->init($args);

        $this->printHeader();
        $responder->get($args);
        $this->printFooter();
    }
}


?>
