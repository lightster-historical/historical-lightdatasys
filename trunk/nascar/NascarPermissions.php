<?php



require_once PATH_LIB . 'com/mephex/user/PermissionDomain.php';



class NASCARPermissions extends PermissionDomain
{
    protected static $instance = null;
    
    
    
    protected function __construct()
    {
        parent::__construct('com.lightdatasys.nascar');
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
            self::$instance = new NASCARPermissions();
        
        return self::$instance;
    }
}



?>
