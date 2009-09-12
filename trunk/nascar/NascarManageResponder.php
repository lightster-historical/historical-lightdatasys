<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/NascarData.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/NascarPermissions.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class NascarManageResponder extends NascarResponder
{
    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);
    }


    public function checkPermissions()
    {
        parent::checkPermissions();
        $this->checkPermission('com.lightdatasys.nascar', 'admin');
    }
}



?>
