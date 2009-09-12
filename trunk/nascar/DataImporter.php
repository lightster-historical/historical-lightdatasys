<?php



require_once PATH_LIB . 'com/mephex/db/MySQL.php';



class DataImporter
{
    public static function importRaceResults($raceId, $file)
    {
        $raceId = intval($raceId);
        if($raceId <= 0)
            return false;

        $html = self::loadFile($file);

        if(self::isResults($html) && self::importResults($raceId, $html))
        {
            $type = NascarData::RESULT_UNOFFICIAL;

            if(preg_match('!<h2>.*?Unofficial.*?</h2>!i', $html) == 1)
                $type = NascarData::RESULT_UNOFFICIAL;
            else
                $type = NascarData::RESULT_OFFICIAL;
        }
        else if(self::isLineup($html) && self::importLineup($raceId, $html))
            $type = NascarData::RESULT_LINEUP;
        else if(self::isEntryList($html) && self::importEntryList($raceId, $html))
            $type = NascarData::RESULT_ENTRY_LIST;
        else if(self::isLiveLeaderboard($html) && self::importLiveLeaderboard($raceId, $html))
            $type = NascarData::RESULT_UNOFFICIAL;

        if($type !== false)
        {
            self::updateRace($raceId, $type);
            return $type;
        }

        return false;
    }

    protected static function loadFile($file)
    {
        return file_get_contents($file);
    }

    protected static function updateRace($raceId, $type)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $query = new Query('UPDATE ' . $db->getTable('Race')
            . ' SET lastUpdated=\'' . Date::now('q Q') . '\', official=' . $type
            . ' WHERE raceId=' . $raceId);
        $db->execQuery($query);
    }


    public static function importDriverEntryList($raceId, $text)
    {
        $drivers = preg_split('/(\r|\n)+/', $text);
        print_r($drivers);

        $rawResults = array();
        foreach($drivers as $driver)
        {
            $result = array();
            $result['driver'] = $driver;

            $rawResults[] = $result;
        }

        $return = self::storeResults($raceId, $rawResults);

        if($return !== false)
        {
            self::updateRace($raceId, NascarData::RESULT_ENTRY_LIST);
            return true;
        }

        return false;
    }




    protected static function isResults($html)
    {
        return strpos($html, '<h1>RESULTS</h1>') === false ? false : true;
    }

    protected static function importResults($raceId, $html)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        // get the table rows that hold the entry list
        preg_match_all('!<tbody id="cnnDataBody">(.*?)</tbody>!s', $html, $rows, PREG_SET_ORDER);
        if(count($rows) <= 0)
            return false;
        $html = $rows[0][1];

        // split the table rows up
        preg_match_all('!<tr>(.*?)</tr>!s', $html, $rows, PREG_SET_ORDER);
        if(count($rows) <= 0)
            return false;

        $results = array();
        // loop through the drivers' results
        foreach ($rows as $row)
        {
            $result = array();

            // read the result for the driver
            preg_match_all('!<td.*?>(.*?)</td>!s', $row[1], $stats, PREG_SET_ORDER);

            // column 0 is the starting position
            $result['finish'] = trim(strip_tags($stats[0][1]));

            // column 1 is the starting position
            $result['start'] = trim(strip_tags($stats[1][1]));

            // column 2 is the car number
            $result['car'] = trim(strip_tags($stats[2][1]));

            // column 3 is the driver
            $result['driver'] = trim(strip_tags($stats[3][1]));
            // strip any non alpha characters (such as the rookie star *)
            preg_match('!([a-zA-Z\-\'\. ]*)!', $result['driver'], $driver);
            $result['driver'] = trim($driver[1]);

            // column 5 is the total points and bonus points awarded
            // (used for determining laps led)
            $points = explode('/', trim($stats[6][1]));
            if(array_key_exists(1, $points))
            {
                $result['ledLaps'] = ($points[1] >= 5 ? 1 : 0);
                $result['ledMostLaps'] = ($points[1] >= 10 ? 1 : 0);
            }

            $results[] = $result;
        }

        self::storeResults($raceId, $results);

        return true;
    }



    protected static function isLineup($html)
    {
        return strpos($html, '<h1>RACE LINEUP</h1>') === false ? false : true;
    }

    protected static function importLineup($raceId, $html)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        // get the table rows that hold the entry list
        preg_match_all('!<tbody id="cnnDataBody">(.*?)</tbody>!s', $html, $rows, PREG_SET_ORDER);
        if(count($rows) <= 0)
            return false;
        $html = $rows[0][1];

        // split the table rows up
        preg_match_all('!<tr>(.*?)</tr>!s', $html, $rows, PREG_SET_ORDER);
        if(count($rows) <= 0)
            return false;

        $results = array();
        // loop through the drivers' results
        foreach ($rows as $row)
        {
            $result = array();

            // read the result for the driver
            preg_match_all('!<td.*?>(.*?)</td>!s', $row[1], $stats, PREG_SET_ORDER);

            // column 0 is the starting position
            $result['start'] = trim(strip_tags($stats[0][1]));
            $result['finish'] = $result['start'];

            // column 1 is the car number
            $result['car'] = trim(strip_tags($stats[1][1]));

            // column 2 is the driver
            $result['driver'] = trim(strip_tags($stats[2][1]));
            // strip any non alpha characters (such as the rookie star *)
            preg_match('!([a-zA-Z\-\'\. ]*)!', $result['driver'], $driver);
            $result['driver'] = trim($driver[1]);

            $results[] = $result;
        }

        self::storeResults($raceId, $results);

        return true;
    }



    protected static function isEntryList($html)
    {
        return strpos($html, '<h1>ENTRY LIST</h1>') === false ? false : true;
    }

    protected static function importEntryList($raceId, $html)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        // get the table rows that hold the entry list
        preg_match_all('!<tbody id="cnnDataBody">(.*?)</tbody>!s', $html, $rows, PREG_SET_ORDER);
        if(count($rows) <= 0)
            return false;
        $html = $rows[0][1];

        // split the table rows up
        preg_match_all('!<tr>(.*?)</tr>!s', $html, $rows, PREG_SET_ORDER);
        if(count($rows) <= 0)
            return false;

        $results = array();
        // loop through the drivers' results
        foreach ($rows as $row)
        {
            $result = array();

            // read the result for the driver
            preg_match_all('!<td.*?>(.*?)</td>!s', $row[1], $stats, PREG_SET_ORDER);

            // column 0 is the car number
            $result['car'] = trim(strip_tags($stats[0][1]));

            // column 1 is the driver
            $result['driver'] = trim(strip_tags($stats[1][1]));
            // strip any non alpha characters (such as the rookie star *)
            preg_match('!([a-zA-Z\-\'\. ]*)!', $result['driver'], $driver);
            $result['driver'] = trim($driver[1]);

            $results[] = $result;
        }

        self::storeResults($raceId, $results);

        return true;
    }



    protected static function isLiveLeaderBoard($html)
    {
        return strpos($html, '<h5>LIVE LEADERBOARD') === false ? false : true;
    }

    protected static function importLiveLeaderboard($raceId, $html)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        preg_match_all('!var eventDataFileURL = \'(.*?)\';!s', $html, $dataFile, PREG_SET_ORDER);
        $dataFile = 'http://www.nascar.com' . $dataFile[0][1];

        if(!($html = @file_get_contents($dataFile)))
            return false;

        $find = 'E\|.*';
        preg_match_all('!' . $find . '!', $html, $eventInfo, PREG_SET_ORDER);
        $eventInfo = explode('|', $eventInfo[0][0]);
        $laps = $eventInfo[6];

        // read the results
        $find = 'R\|.*';
        preg_match_all('!' . $find . '!', $html, $rows, PREG_SET_ORDER);
        if(count($rows) <= 0)
            return false;

        $results = array();
        // loop through the drivers' results
        foreach ($rows as $row)
        {
            $result = array();

            $stats = explode('|', $row[0]);

            if($stats[0] == 'R')
            {
                $result['finish'] = trim(strip_tags($stats[4]));

                $result['start'] = trim(strip_tags($stats[3]));

                $result['car'] = trim(strip_tags($stats[2]));

                $result['driver'] = trim(strip_tags($stats[8] . ' ' . $stats[9]));
                // strip any non alpha characters (such as the rookie star *)
                preg_match('!([a-zA-Z\-\'\. ]*)!', $result['driver'], $driver);
                $result['driver'] = trim($driver[1]);

                $result['ledLaps'] = ($stats[12] >= 5 ? 1 : 0);
                $result['ledMostLaps'] = (0);

                $results[] = $result;
            }
        }

        self::storeResults($raceId, $results);

        return true;
    }



    protected static function storeResults($raceId, &$rawResults)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $driverIdsByName = self::getDriverIdsByName($rawResults);

        $valueSets = array();
        foreach($rawResults as $rawResult)
        {
            if(!array_key_exists('driver', $rawResult))
                continue;

            if(array_key_exists($rawResult['driver'], $driverIdsByName))
            {
                $driverId = $driverIdsByName[$rawResult['driver']];

                $valueSets[] = self::generateResultValueSet($raceId, $driverId, $rawResult);
            }
        }

        if(count($valueSets) > 0)
        {
            $query = new Query('DELETE FROM ' . $db->getTable('Result')
                . ' WHERE raceId=' . $raceId);
            $db->execQuery($query);

            $query = new Query('INSERT INTO ' . $db->getTable('Result')
                . ' (`raceId`, `driverId`, `car`, `start`, `finish`,'
                . ' `ledLaps`, `ledMostLaps`) VALUES '
                . implode(',', $valueSets));
            if($db->execQuery($query))
                return true;
        }

        return false;
    }


    protected static function generateResultValueSet($raceId, $driverId, $rawResult)
    {
        $car = '\'\'';
        if(array_key_exists('car', $rawResult))
            $car = '\'' . addslashes($rawResult['car']) . '\'';

        $start = 0;
        if(array_key_exists('start', $rawResult))
            $start = intval($rawResult['start']);

        $finish = 0;
        if(array_key_exists('finish', $rawResult))
            $finish = intval($rawResult['finish']);

        $ledLaps = 0;
        if(array_key_exists('ledLaps', $rawResult))
            $ledLaps = intval($rawResult['ledLaps']);

        $ledMostLaps = 0;
        if(array_key_exists('ledMostLaps', $rawResult))
            $ledMostLaps = intval($rawResult['ledMostLaps']);

        return '(' . $raceId . ',' . $driverId . ',' . $car. ',' . $start
            . ',' . $finish. ',' . $ledLaps. ',' . $ledMostLaps . ')';
    }

    protected static function &getDriverIdsByName(&$rawResults)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $drivers = array();
        foreach($rawResults as $rawResult)
        {
            if(array_key_exists('driver', $rawResult)
                && strtolower(substr($rawResult['driver'], 0, 3)) != 'tba')
                $drivers[] = '\'' . addslashes(trim($rawResult['driver'])) . '\'';
        }

        if(count($drivers) <= 0)
            return false;

        $driverIdsByName = array();
        $query = new Query('SELECT driverId, CONCAT(firstName, " ", lastName)'
            . ' FROM ' . $db->getTable('Driver') . ' WHERE'
            . ' CONCAT(firstName, " ", lastName) IN ('
            . implode(',', $drivers) . ')');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $driverIdsByName[$row[1]] = $row[0];
        }

        $drivers = array();
        $unknownDrivers = array();
        foreach($rawResults as $rawResult)
        {
            if(!array_key_exists('driver', $rawResult))
                continue;

            if(!array_key_exists($rawResult['driver'], $driverIdsByName)
                && strtolower(substr($rawResult['driver'], 0, 3)) != 'tba')
            {
                $driver = explode(' ', $rawResult['driver']);
                $firstName = $driver[0];
                unset($driver[0]);
                $lastName = implode(' ', $driver);

                $unknownDrivers[] = '(\'' . addslashes($firstName) . '\',\''
                     . addslashes($lastName) . '\')';
                $drivers[] = '\'' . addslashes(trim($rawResult['driver'])) . '\'';
            }
        }

        if(count($unknownDrivers) > 0)
        {
            $query = new Query('INSERT INTO ' . $db->getTable('Driver')
                . ' (`firstName`, `lastName`) VALUES '
                . implode(',', $unknownDrivers));
            $db->execQuery($query);

            $query = new Query('SELECT driverId, CONCAT(firstName, " ", lastName)'
                . ' FROM ' . $db->getTable('Driver') . ' WHERE'
                . ' CONCAT(firstName, " ", lastName) IN ('
                . implode(',', $drivers) . ')');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $driverIdsByName[$row[1]] = $row[0];
                echo 'Created driver \'' . $row[1] . '\'<br />';
            }
        }

        return $driverIdsByName;
    }
}



?>