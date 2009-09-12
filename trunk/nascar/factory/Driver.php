<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Driver.php';

require_once PATH_LIB . 'com/mephex/data-object/class/AbstractDatabaseDataClass.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class LDS_DriverClass extends MXT_AbstractDatabaseDataClass
{
    protected static $singleton = null;


    public static function getSingleton()
    {
        return self::getSingletonUsingClassName(__CLASS__);
    }


    public function getDataObjectName()
    {
        return 'LDS_Driver';
    }


    public function getTableName()
    {
        return 'Driver';
    }

    public function getDbConnection()
    {
        return Database::getConnection('com.lightdatasys.nascar');
    }


    /*
    public function initFields()
    {
        $fields = parent::initFields();

        $fields['categoryId']->setDataType(new MXT_DataObjectType(ML_CategoryClass::getSingleton($this->getTimezone()), '', false));

        return $fields;
    }
    */


    public function getSelectAllSQL()
    {
        return $this->getGeneralSelectSQL()
            . ' ORDER BY mt.lastName ASC, mt.firstName ASC';
    }


    /*
    public function getObjectUsingId($id)
    {
        //$this->getAllObjects();
        //debug_print_backtrace();exit;

        return parent::getObjectUsingId($id);
    }
    */


    public function getClassFileName()
    {
        return __FILE__;
    }
}



?>
