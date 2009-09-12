<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/core/Input.php';
require_once PATH_LIB . 'com/mephex/db/MySQL.php';
require_once PATH_LIB . 'com/mephex/framework/CacheableResponder.php';
require_once PATH_LIB . 'com/mephex/nav/Navigation.php';
require_once PATH_LIB . 'com/mephex/nav/NavItem.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/user/responders/SignInFormResponder.php';


class AdminResponder extends LightDataSysResponder
{
    protected $bodyAttributes;
    protected $onLoad;

    protected $navItems;
    protected $startTime;

    protected $user;
    protected $cookie;

    protected $playerName;
    protected $playerShortName;
    protected $playerColor;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);
    }


    public function checkPermissions()
    {
        parent::checkPermissions();
        $this->checkPermission('com.lightdatasys', 'admin');
    }


    public function printExtendedHTMLHead()
    {
        parent::printExtendedHTMLHead();
    }
}


?>
