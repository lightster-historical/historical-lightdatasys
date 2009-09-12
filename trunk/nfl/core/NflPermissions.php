<?php



require_once PATH_LIB . 'com/mephex/user/PermissionDomain.php';



class LDS_NflPermissions extends PermissionDomain
{
    protected static $instance = null;



    protected function __construct()
    {
        parent::__construct('com.lightdatasys.nfl');
    }


    protected function setKeyNames()
    {
        $this->setKeyName(0, 'read');
        $this->setKeyName(1, 'changePicks');
        $this->setKeyName(2, 'admin');
    }



    public static function getInstance($domain = null)
    {
        if(is_null(self::$instance))
            self::$instance = new LDS_NflPermissions();

        return self::$instance;
    }
}



?>
