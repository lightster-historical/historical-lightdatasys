<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';


class DebugResponder extends LightDataSysResponder
{
    public function get($args)
    {
        $this->printHeader();

        $arr = array('a' => array('b' => 2));
        #var_dump($user);
        var_dump($this->user);
        ob_start();
        print_r(ob_get_status());
        var_dump($this->user->getPermission('com.lightdatasys', 'usercp'));
        var_dump($this->user->getPermission('com.lightdatasys', 'read'));
        var_dump($this->user->getPermission('com.lightdatasys', 'readWhileClosed'));
        var_dump($this->user->getPermission('com.lightdatasys', 'changePicks'));
        #var_dump($user);
        #var_dump(SamplePermissionGroup::getInstance());

        $this->printFooter();
    }
}



?>
