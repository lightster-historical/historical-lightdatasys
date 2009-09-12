<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Result.php';

require_once PATH_LIB . 'com/mephex/data-object/class/AbstractDatabaseDataClass.php';
require_once PATH_LIB . 'com/mephex/data-object/io/DatabaseDataClassReader.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class LDS_ResultClass extends MXT_AbstractDatabaseDataClass
{
    protected static $singleton = null;


    public static function getSingleton()
    {
        return self::getSingletonUsingClassName(__CLASS__);
    }


    public function getDataObjectName()
    {
        return 'LDS_Result';
    }


    public function getTableName()
    {
        return 'Result';
    }

    public function getDbConnection()
    {
        return Database::getConnection('com.lightdatasys.nascar');
    }


    public function initFields()
    {
        $fields = parent::initFields();

        $fields->addForeignObjectFields('driver', 'driverId', 'LDS_DriverClass');
        //$fields['driverId']->setDataType(new MXT_DataObjectType(LDS_DriverClass::getSingleton(), '', false));
        $fields->replace(new MXT_IntegerDataField($this, 'points'));

        return $fields;
    }


    public function getClassFileName()
    {
        return __FILE__;
    }


    public function constructUsingRow(array $row)
    {
        Utility::getValueUsingKey($row, 'resultId');
        Utility::getValueUsingKey($row, 'car');
        $raceId = Utility::getValueUsingKey($row, 'raceId');
        $driverId = Utility::getValueUsingKey($row, 'driverId');
        Utility::getValueUsingKey($row, 'start');
        Utility::getValueUsingKey($row, 'finish');
        Utility::getValueUsingKey($row, 'ledLaps');
        Utility::getValueUsingKey($row, 'ledMostLaps');
        Utility::getValueUsingKey($row, 'points');
        Utility::getValueUsingKey($row, 'penalties');
        
        $raceClass = LDS_RaceClass::getSingleton();
        $driverClass = LDS_DriverClass::getSingleton();
        
        $raceClass->queueObjectUsingId($raceId);
        $driverClass->queueObjectUsingId($driverId);

        return $this->getReader()->constructUsingRow($row);
    }
}



?>
