<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/Driver.php';


class LDS_DriverStanding
{
    protected $driverId;

    protected $driver;

    protected $rank;

    protected $starts;
    protected $wins;
    protected $top5s;
    protected $top10s;
    protected $totalFinish;

    protected $points;
    protected $chasePenalties;


    protected function __construct()
    {
        $this->driverId = 0;

        $this->driver = null;

        $this->rank = null;

        $this->starts = null;
        $this->wins = null;
        $this->top5s = null;
        $this->top10s = null;
        $this->totalFinish = null;

        $this->points = null;
        $this->chasePenalties = null;
    }


    public function getDriver()
    {
        if(is_null($this->driver))
            $this->driver = LDS_Driver::getUsingId($this->driverId);

        return $this->driver;
    }

    public function getRank()
    {
        return $this->rank;
    }

    public function getStarts()
    {
        return $this->starts;
    }

    public function getWins()
    {
        return $this->wins;
    }

    public function getTop5s()
    {
        return $this->top5s;
    }

    public function getTop10s()
    {
        return $this->top10s;
    }

    public function getTotalFinish()
    {
        return $this->totalFinish;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function getChasePenalties()
    {
        return $this->chasePenalties;
    }


    public function setPoints($points)
    {
        $this->points = $points;
    }

    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    public function addStarts($starts)
    {
        $this->starts += $starts;
    }

    public function addWins($wins)
    {
        $this->wins += $wins;
    }

    public function addTop5s($top5s)
    {
        $this->top5s += $top5s;
    }

    public function addTop10s($top10s)
    {
        $this->top10s += $top10s;
    }

    public function addTotalFinish($totalFinish)
    {
        $this->totalFinish += $totalFinish;
    }

    public function addPoints($points)
    {
        $this->points += $points;
    }


    public static function constructUsingRow($row)
    {
        if($row)
        {
            $obj = new LDS_DriverStanding();
            $obj->initUsingRow($row);

            return $obj;
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $driverRow = array
            (
                'driverId' => Utility::getValueUsingKey($row, 'driverId'),
                'firstName' => Utility::getValueUsingKey($row, 'firstName'),
                'lastName' => Utility::getValueUsingKey($row, 'lastName'),
                'fontColor' => Utility::getValueUsingKey($row, 'fontColor'),
                'backgroundColor' => Utility::getValueUsingKey($row, 'backgroundColor'),
                'borderColor' => Utility::getValueUsingKey($row, 'borderColor')
            );
            //$this->driver = LDS_Driver::constructUsingRow($driverRow);
            $this->driverId = Utility::getValueUsingKey($row, 'driverId');
            $driverClass = LDS_DriverClass::getSingleton();
            $driverClass->queueObjectUsingId($this->driverId);

            $this->starts = Utility::getValueUsingKey($row, 'starts');
            $this->wins = Utility::getValueUsingKey($row, 'wins');
            $this->top5s = Utility::getValueUsingKey($row, 'top5s');
            $this->top10s = Utility::getValueUsingKey($row, 'top10s');
            $this->totalFinish = Utility::getValueUsingKey($row, 'totalFinish');

            $this->points = Utility::getValueUsingKey($row, 'points');
            $this->chasePenalties = Utility::getValueUsingKey($row, 'chasePenalties');
        }
    }
}



?>
