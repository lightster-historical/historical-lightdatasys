<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Track.php';

require_once PATH_LIB . 'com/mephex/data-object/class/AbstractDatabaseDataClass.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class LDS_TrackClass extends MXT_AbstractDatabaseDataClass
{
    protected static $singleton = null;


    public static function getSingleton()
    {
        return self::getSingletonUsingClassName(__CLASS__);
    }


    public function getDataObjectName()
    {
        return 'LDS_Track';
    }


    public function getTableName()
    {
        return 'Track';
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


    public function getClassFileName()
    {
        return __FILE__;
    }
}



?>
