<?php



require_once PATH_LIB . 'com/mephex/db/Query.php';
require_once PATH_LIB . 'com/mephex/user/PermissionDomain.php';



class LdsPermissions extends PermissionDomain
{
    protected static $instance = null;



    protected function __construct()
    {
        parent::__construct('com.lightdatasys');
    }

    protected function setKeyNames()
    {
        $this->setKeyName(0, 'read');
        $this->setKeyName(1, 'admin');
        $this->setKeyName(2, 'usercp');
    }


    public static function getInstance($class = null)
    {
        if(is_null(self::$instance))
            self::$instance = new LdsPermissions();

        return self::$instance;
    }
}



?>
