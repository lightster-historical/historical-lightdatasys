<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Result.php';
/*
require_once PATH_LIB . 'com/lightdatasys/nascar/Driver.php';


class LDS_Result
{
    protected $car;

    protected $driverId;

    protected $driver;

    protected $start;
    protected $finish;

    protected $ledLaps;
    protected $ledMostLaps;

    protected $points;
    protected $penalties;


    protected function __construct()
    {
        $this->car = null;

        $this->driverId = 0;

        $this->driver = null;

        $this->start = null;
        $this->finish = null;

        $this->ledLaps = null;
        $this->ledMostLaps = null;

        $this->points = null;
        $this->penalties = null;
    }


    public function getCar()
    {
        return $this->car;
    }

    public function getDriver()
    {
        if(is_null($this->driver))
            $this->driver = LDS_Driver::getUsingId($this->driverId);

        return $this->driver;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getFinish()
    {
        return $this->finish;
    }

    public function getLedLaps()
    {
        return $this->ledLaps;
    }

    public function getLedMostLaps()
    {
        return $this->ledMostLaps;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function getPenalties()
    {
        return $this->penalties;
    }


    public static function constructUsingRow($row)
    {
        if($row)
        {
            $obj = new LDS_Result();
            $obj->initUsingRow($row);

            return $obj;
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $this->car = Utility::getValueUsingKey($row, 'car');

            $driverRow = array
            (
                'driverId' => Utility::getValueUsingKey($row, 'driverId'),
                'firstName' => Utility::getValueUsingKey($row, 'firstName'),
                'lastName' => Utility::getValueUsingKey($row, 'lastName'),
                'fontColor' => Utility::getValueUsingKey($row, 'fontColor'),
                'backgroundColor' => Utility::getValueUsingKey($row, 'backgroundColor'),
                'borderColor' => Utility::getValueUsingKey($row, 'borderColor')
            );
            $this->driverId = Utility::getValueUsingKey($row, 'driverId');
            //$this->driver = LDS_Driver::constructUsingRow($driverRow);

            $this->start = Utility::getValueUsingKey($row, 'start');
            $this->finish = Utility::getValueUsingKey($row, 'finish');
            $this->ledLaps = Utility::getValueUsingKey($row, 'ledLaps');
            $this->ledMostLaps = Utility::getValueUsingKey($row, 'ledMostLaps');
            $this->points = Utility::getValueUsingKey($row, 'points');
            $this->penalties = Utility::getValueUsingKey($row, 'penalties');
        }
    }
}
*/


?>
