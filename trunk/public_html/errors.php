<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
require_once PATH_LIB . 'com/mephex/core/Input.php';
require_once PATH_LIB . 'com/mephex/db/MySQL.php';
require_once PATH_LIB . 'com/mephex/db/Query.php';
require_once PATH_LIB . 'com/mephex/framework/ErrorPage.php';
require_once PATH_LIB . 'com/mephex/user/User.php';


class Errors extends LightDataSysResponder implements InputParser, InputValidator
{
    //*
    public function get($args)
    {
        $this->printHeader();

        $this->input->set('error', $this, $this);
        $code = $this->input->get('error');

        if($code == 500)
            $code = 404;

        echo $code . ' Error - ' . HttpHeader::getStatusName($code);

        $this->printFooter();
    }
    //*/

    public function isValid($value)
    {
        return is_numeric($value);
    }

    public function parseValue($value)
    {
        return intval($value);
    }
}


?>
