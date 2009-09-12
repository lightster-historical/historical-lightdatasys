



    public function getSeasons()
    {
        if(is_null($this->seasons))
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $this->seasons = array();
            $query = new Query('SELECT seasonId, year FROM '
                . $db->getTable('Season') . ' ORDER BY year DESC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $this->seasons[$row[0]] = $row[1];
            }
        }

        return $this->seasons;
    }


    public function getSeasonId()
    {
        if($this->seasonId <= 0)
            $this->getSeasonInformation();

        return $this->seasonId;
    }

    public function getSeasonYear()
    {
        if($this->seasonYear <= 0)
            $this->getSeasonInformation();

        return $this->seasonYear;
    }

    public function getSeasonInformation()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $row = false;
        if($this->seasonYear > 0)
        {
            $query = new Query('SELECT seasonId, year FROM ' . $db->getTable('Season')
                . ' WHERE year=' . $this->seasonYear);
            $result = $db->execQuery($query);
            $row = $db->getRow($result);
        }
        if(!$row)
        {
            $query = new Query('SELECT seasonId, year FROM ' . $db->getTable('Season')
                . ' ORDER BY year DESC LIMIT 1');
            $result = $db->execQuery($query);
            $row = $db->getRow($result);
        }

        $this->seasonId = $row[0];
        $this->seasonYear = $row[1];
    }


    public function getRaceNumber()
    {
        if($this->raceNo <= 0)
        {
            $this->loadRaces();
        }

        return $this->raceNo;
    }

    public function getCompletedRaceNumber()
    {
        if($this->completedRaceNo <= 0)
        {
            $this->loadRaces();
        }

        return $this->completedRaceNo;
    }

    public function getRaces()
    {
        if(is_null($this->races))
        {
            $this->loadRaces();
        }

        return $this->races;
    }

    public function loadRaces($date = null)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $currRace = 0;
        $this->races = array();

        $where  = ' WHERE r.seasonId=' . $this->getSeasonId();
        if(!is_null($date))
            $where .= ' AND r.date<=\'' . $date->format('q Q') . '\'';

        $query = new Query('SELECT r.raceId, r.`date`, t.shortName AS trackName, COUNT(re.raceId) FROM '
            . $db->getTable('Race') . ' AS r LEFT JOIN ' . $db->getTable('Track')
            . ' AS t ON r.trackId=t.trackId LEFT JOIN ' . $db->getTable('Race')
            . ' AS re ON r.raceId=re.raceId ' . $where . ' GROUP BY r.raceId ORDER BY `date` ASC');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $this->races[] = $row;

            $raceStart = new Date($row[1]);
            if(is_null($date) || $raceStart->compareTo($date) < 0)
            {
                $currRace++;
            }

            if((!is_null($date) && $raceStart->compareTo($date) < 0)
                || $raceStart->compareTo(new Date()) < 0)
                $this->completedRaceNo = $currRace;
        }

        if(!(1 <= $this->raceNo && $this->raceNo <= count($this->races)))
            $this->raceNo = $currRace;
    }


    public function getRaceId()
    {
        if($this->raceId == 0)
            $this->loadRaceInformation();

        return $this->raceId;
    }

    public function getRaceName()
    {
        if(is_null($this->raceName))
            $this->loadRaceInformation();

        return $this->raceName;
    }

    public function getRaceDate()
    {
        if(is_null($this->raceDate))
            $this->loadRaceInformation();

        return $this->raceDate;
    }

    public function getTrackName()
    {
        if(is_null($this->trackName))
            $this->loadRaceInformation();

        return $this->trackName;
    }

    public function getNascarComId()
    {
        if($this->nascarComId == 0)
            $this->loadRaceInformation();

        return $this->nascarComId;
    }

    public function loadRaceInformation()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName, nascarComId FROM '
            . $db->getTable('Race') . ' AS r LEFT JOIN '
            . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
            . 'WHERE seasonId=' . $this->getSeasonId() . ' ORDER BY date LIMIT '
            . max((intval($this->getRaceNumber())-1), 0) . ',1');
        $result = $db->execQuery($query);
        $row = $db->getRow($result);

        if($row)
        {
            $this->raceId = $row[0];
            $this->raceName = $row[1];
            $this->raceDate = $row[2];
            $this->trackName = $row[3];
            $this->nascarComId = $row[4];
        }
    }


    public function getChaseRaceNumber()
    {
        if($this->chaseRaceNo != $raceNo)
            $this->loadChaseInformation();

        return $this->chaseDate;
    }

    public function getChaseDate()
    {
        if(is_null($this->chaseDate))
            $this->loadChaseInformation();

        return $this->chaseDate;
    }

    public function loadChaseInformation($raceNo = 26)
    {
        $raceNo = IntegerInput::getInstance()->parseValue($raceNo) - 1;
        if($raceNo < 0)
            $raceNo = 0;

        $db = Database::getConnection('com.lightdatasys.nascar');

        $query = new Query('SELECT date FROM ' . $db->getTable('Race')
            . ' WHERE seasonId=' . $this->getSeasonId()
            . ' ORDER BY date LIMIT ' . $raceNo . ',1');
        $result = $db->execQuery($query);
        $row = $db->getRow($result);

        $this->chaseDate = $row[0];
        $this->chaseRaceNo = $raceNo;
    }


    public function getDriverStandings($zeroPointDrivers = false)
    {
        if(is_null($this->standings))
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $seasonId = $this->getSeasonId();
            $raceNo = $this->getRaceNumber();
            $raceDate = $this->getRaceDate();
            $chaseDate = $this->getChaseDate();

            $whereChase = '';
            if($chaseDate != '')
                $whereChase = ' AND ra.date<=\'' . $chaseDate . '\'';

            $this->standings = array();
            $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color, d.background, d.border, COUNT(ra.raceId) starts, '
                . 'SUM(IF(finish=1,1,0)) wins, SUM(IF(finish<=5,1,0)) top5s, '
                . 'SUM(IF(finish<=10,1,0)) top10s, SUM(re.finish) AS totalFinish, '
                . 'SUM(IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)+penalties) AS points, '
                . 'cp.penalty AS chasePenalties '
                . 'FROM nascarDriver AS d '
                . 'INNER JOIN nascarResult AS re ON d.driverId=re.driverId '
                . 'INNER JOIN nascarRace AS ra ON re.raceId=ra.raceId '
                . 'LEFT JOIN nascarChasePenalty AS cp ON cp.seasonId=ra.seasonId '
                . 'AND cp.driverId=d.driverId '
                . 'WHERE ra.seasonId=' . $seasonId . ' AND DATE(ra.date)<=\''
                . $raceDate . '\'' . $whereChase . ' '
                . 'GROUP BY d.driverId ORDER BY points DESC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $standing = new Standing($row[0], $row[1], $row[2], $row[3]
                    , $row[4], $row[5], $row[6], $row[7], $row[8], $row[9]
                    , $row[10], $row[11], $row[12]);
                $this->standings[$row[0]] = $standing;
            }

            if($zeroPointDrivers)
            {
                $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color, d.background, d.border FROM nascarDriver AS d ORDER BY lastName, firstName');
                $result = $db->execQuery($query);
                while($row = $db->getRow($result))
                {
                    if(!array_key_exists($row[0], $this->standings))
                    {
                        $standing = new Standing($row[0], $row[1], $row[2], $row[3]
                            , $row[4], $row[5], 0, 0, 0, 0, 0, 0, 0);
                        $this->standings[$row[0]] = $standing;
                    }
                }
            }

            if($raceNo >= $this->chaseRaceNo)
            {
                $i = 0;
                foreach($this->standings as $driverId => $driver)
                {
                    if($i < 12)
                        $this->standings[$driverId]->points =
                            5000 + (10 * $driver->wins) - $driver->chasePenalties;
                    else
                        break;
                    $i++;
                }

                $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color, d.background, d.border, COUNT(ra.raceId) starts, '
                . 'SUM(IF(finish=1,1,0)) wins, SUM(IF(finish<=5,1,0)) top5s, '
                . 'SUM(IF(finish<=10,1,0)) top10s, SUM(re.finish) AS totalFinish, '
                . 'SUM(IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)+penalties) AS points '
                . 'FROM nascarDriver AS d '
                . 'INNER JOIN nascarResult AS re ON d.driverId=re.driverId '
                . 'INNER JOIN nascarRace AS ra ON re.raceId=ra.raceId '
                . 'WHERE ra.seasonId=' . $seasonId . ' AND DATE(ra.date)<=\''
                . $raceDate . '\' AND ra.date>\'' . $chaseDate . '\''
                . 'GROUP BY d.driverId ORDER BY points DESC');
                $result = $db->execQuery($query);
                while($row = $db->getRow($result))
                {
                    if(array_key_exists($row[0], $this->standings))
                    {
                        $standing = $this->standings[$row[0]];

                        $standing->starts += $row[6];
                        $standing->wins += $row[7];
                        $standing->top5s += $row[8];
                        $standing->top10s += $row[9];
                        $standing->totalFinish += $row[10];
                        $standing->points += $row[11];
                    }
                    else
                    {
                        $standing = new Standing($row[0], $row[1], $row[2], $row[3]
                            , $row[4], $row[5], $row[6], $row[7], $row[8], $row[9]
                            , $row[10], $row[11], 0);
                        $this->standings[$row[0]] = $standing;
                    }
                }
            }
        }

        function sortStandings($a, $b)
        {
            if($a->points == $b->points)
                return 0;
            else
                return $a->points < $b->points ? 1 : -1;
        }
        usort($this->standings, 'sortStandings');

        return $this->standings;
    }


    public function getRaceResults()
    {
        if(is_null($this->results))
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $this->results = array();
            $query = new Query('SELECT re.car, re.driverId, '
                . 'd.firstName, d.lastName, d.color, d.background, d.border, '
                . 're.start, re.finish, re.ledLaps, re.ledMostLaps, '
                . 'IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0) AS points, '
                . 're.penalties FROM ' . $db->getTable('Result') . ' AS re '
                . 'INNER JOIN ' . $db->getTable('Driver') . ' AS d '
                . 'ON re.driverId=d.driverId '
                . 'WHERE raceId=' . $this->getRaceId() . ' ORDER BY re.finish ASC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $raceResult = new Result($row[0], $row[1], $row[2], $row[3], $row[4]
                    , $row[5], $row[6], $row[7], $row[8], $row[9], $row[10]
                    , $row[11], $row[12]);
                $this->results[] = $raceResult;
            }
        }

        return $this->results;
    }


    public function getFantasyPicks()
    {
        if(is_null($this->fantasyPicks))
            $this->loadFantasyPicks(true);

        return $this->fantasyPicks;
    }

    public function getTotalDriverPoints()
    {
        if(is_null($this->totalDriverPoints))
            $this->loadFantasyPicks(true);

        return $this->totalDriverPoints;
    }

    public function loadFantasyPicks($overall)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        if($overall)
            $whereRace = ' r.date<=\'' . $this->getRaceDate() . '\' ';
        else
            $whereRace = ' r.raceId=' . $this->getRaceId() . ' ';

        $this->fantasyPicks = array();
        $this->totalDriverPoints = array();
        $query = new Query('SELECT r.raceId, fp.userId, '
            . ' SUM(IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)) AS points'
            . ' FROM nascarFantPick AS fp'
            . ' INNER JOIN nascarRace AS r ON fp.raceId=r.raceId'
            . ' INNER JOIN user AS u ON fp.userId=u.userId'
            . ' LEFT JOIN nascarResult AS re ON r.raceId=re.raceId AND fp.driverId=re.driverId'
            . ' WHERE ' . $whereRace . ' AND seasonId=' . $this->getSeasonId()
            . ' GROUP BY r.raceId, fp.userId'
            . ' ORDER BY points DESC'
            );
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $this->fantasyPicks[$row[0]][$row[1]] = $row[2];

            if(!array_key_exists($row[1], $this->totalDriverPoints))
                $this->totalDriverPoints[$row[1]] = 0;

            $this->totalDriverPoints[$row[1]] += $row[2];
        }
    }


    public function getMaxPointsPerRace()
    {
        if(is_null($this->weeklyResults))
            $this->loadFantasyResults(true);

        return $this->maxPointsPerRace;
    }

    public function getMinPointsPerRace()
    {
        if(is_null($this->weeklyResults))
            $this->loadFantasyResults(true);

        return $this->minPointsPerRace;
    }

    public function &getFantasyResults()
    {
        if(is_null($this->weeklyResults))
            $this->loadFantasyResults(true);

        return $this->weeklyResults;
    }

    public function loadFantasyResults()
    {
        $fantasyPicks = $this->getFantasyPicks();
        $this->weeklyResults = array();
        foreach($fantasyPicks as $raceId => $fantasyPick)
        {
            $points = -1;
            $rank = 0;
            $tied = 1;
            foreach($fantasyPick as $userId => $result)
            {

                if($points == $result)
                {
                    $tied++;
                }
                else
                {
                    $rank += $tied;
                    $tied = 1;
                    $points = $result;
                }

                $this->weeklyResults[$raceId][$userId] = $rank;
            }

            $max = 100;
            foreach($this->weeklyResults[$raceId] as $userId => $rank)
            {
                //*
                $pts = 0;
                if($rank == 1 && $points <= 0)
                    $pts = 185 - $max;
                else if($rank == 1)
                    $pts = 185;
                else if($rank <= 6)
                    $pts = 150 + (6 - $rank) * 5;
                else if($rank <= 11)
                    $pts = 130 + (11 - $rank) * 4;
                else if($rank <= 43)
                    $pts = 34 + (43 - $rank) * 3;

                $pts -= 185 - $max;
                //*/

                /*
                $pts = 0;
                switch($rank)
                {
                    case 1: $pts = 10; break;
                    case 2: $pts = 8; break;
                    case 3: $pts = 6; break;
                    case 4: $pts = 5; break;
                    case 5: $pts = 4; break;
                    case 6: $pts = 3; break;
                    case 7: $pts = 2; break;
                    case 8: $pts = 1; break;
                }
                //*/

                $this->maxPointsPerRace = max($this->maxPointsPerRace, $pts);
                if($pts != 0)
                    $this->minPointsPerRace = min($this->minPointsPerRace, $pts);

                $this->weeklyResults[$raceId][$userId] = $pts;
            }
        }
    }



    public function getFantasyPoints()
    {
        if(is_null($this->points))
            $this->loadFantasyPoints();

        return $this->points;
    }

    public function &getFantasyMaxPoints()
    {
        if(is_null($this->maxPoints))
            $this->loadFantasyPoints();

        return $this->maxPoints;
    }

    public function &getFantasyMinPoints()
    {
        if(is_null($this->minPoints))
            $this->loadFantasyPoints();

        return $this->minPoints;
    }

    public function loadFantasyPoints()
    {
        $this->points = array();
        $this->maxPoints = array();
        $this->minPoints = array();

        $this->getFantasyResults();
        foreach($this->weeklyResults as $raceId => $results)
        {
            $this->maxPoints[$raceId] = max($results);
            $this->minPoints[$raceId] = min($results);

            foreach($this->weeklyResults[$raceId] as $userId => $pts)
            {
                if(!array_key_exists($userId, $this->points))
                {
                    $this->points[$userId] = array();
                }

                if($this->maxPoints[$raceId] == 0)
                    $this->points[$userId][$raceId] = 0;
                else
                    $this->points[$userId][$raceId] = $pts;
            }
        }

        $this->getFantasyPlayers();
        foreach($this->users as $userId => $user)
        {
            $this->users[$userId][4] = 0;
            if(array_key_exists($userId, $this->points))
                $this->users[$userId][4] = array_sum($this->points[$userId]);
        }

        function compareUsersByPoints($a, $b)
        {
            $aSum = $a[4];
            $bSum = $b[4];

            if($aSum < $bSum)
                return 1;
            else if($aSum > $bSum)
                return -1;
            else
                return 0;
        }
        uasort($this->users, 'compareUsersByPoints');
    }


    public function &getFantasyPlayers()
    {
        if(is_null($this->users))
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $this->users = array();
            $query = new Query('SELECT u.userId, u.username, p.name, p.bgcolor FROM user AS u '
                . 'INNER JOIN player_user AS pu ON u.userId=pu.userId '
                . 'INNER JOIN player AS p ON pu.playerId=p.playerId '
                . 'INNER JOIN nascarFantPick AS fp ON u.userId=fp.userId '
                . 'INNER JOIN nascarRace AS ra ON fp.raceId=ra.raceId '
                . 'WHERE seasonId=' . $this->getSeasonId()
                . ' GROUP BY userId ORDER BY name');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $this->users[$row[0]] = $row;
            }
        }

        return $this->users;
    }
