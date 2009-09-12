<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/mephex/aggregator/AggDatabaseItem.php';
require_once PATH_LIB . 'com/mephex/core/DateRange.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class IndexResponder extends NascarResponder
{
    protected $lastUpdated;


    public function getPageTitle()
    {
        $data = $this->data;

        return $data->getRaceName();
    }


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);//PATH_LIB . 'com/lightdatasys/cache/nascar/fantasy');

        $this->lastUpdated = null;

        $data = $this->data;

        $this->addCacheArg($data->getSeriesShortName());
        $this->addCacheArg($data->getSeasonYear());
        $this->addCacheArg($data->getRaceNumber());
    }


    public function getContentLastUpdated()
    {
        if(is_null($this->lastUpdated))
        {
            $data = $this->data;
            $this->lastUpdated = $data->getRaceLastUpdated();
        }

        return $this->lastUpdated;
    }



    public function printHeader()
    {
        parent::printHeader();

        $data = $this->data;

        $now = new Date();

        $raceId = $data->getRaceId();
        $date = $data->getRaceDate();

        $data->loadFantasyPicks(false);
        $fantasyPicks = $data->getFantasyPicks();

        $newsDate = new Date($date);
        $newsDate->changeDay(2);
        $items = AggDatabaseItem::getItems($data->getSeriesFeedName(), 5, 0, new DateRange(null, $newsDate));

        ?>
         <div id="nascar-sidebar" style="width: 395px; float: right; padding: 0; margin: 0; border: 0px dashed #000000; overflow: visible; ">
           <h3>Upcoming Events</h3>
        <?php
        $upcoming = NascarData::getUpcomingRaces($data->getSeasonYear(), 6, $date);
        $pickCounts = NascarData::getPickCountByRaceIds(array_keys($upcoming), is_null($this->user) ? 0 : $this->user->getId());
        if(count($upcoming) > 0)
        {
            ?>
             <div class="table-default" style="font-size: .9em; ">
              <table>
               <tr>
                <th style="width: 60px; ">Series</th>
                <th style="min-width: 140px; ">Race</th>
                <th style="width: 100px; ">Picks Due</th>
               </tr>
            <?php
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            foreach($upcoming as $race)
            {
                $dueDate = self::getDueDate(new Date($race['date']));
                $dueDateSoon = new Date($dueDate);
                $dueDateSoon->changeHour(-24);

                $pickStatus = false;
                if(array_key_exists($race['raceId'], $pickCounts))
                    $pickStatus = true;

                $picksColor = 'inherit';
                if($pickStatus && $race['maxPickCount'] == $pickCounts[$race['raceId']])
                    $picksColor = '#ccffcc';
                else if($dueDateSoon->compareTo($now) <= 0 || $pickStatus)
                    $picksColor = '#ffcccc';
                else if($race['pickStatus'] >= 1)
                    $picksColor = '#ffffcc';

                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                  <td class="center"><?php echo $race['shortName']; ?></td>
                  <td><?php echo $race['name']; ?></td>
                  <td class="center" style="background-color: <?php echo $picksColor; ?>">
                <?php
                if($race['pickStatus'] >= 1)
                {
                    ?>
                     <a href="picks.php?series=<?php echo $race['seriesId']; ?>&amp;year=<?php echo $race['year']; ?>&amp;race=<?php echo $race['raceNo']; ?>">
                    <?php
                    $this->printDate($dueDate, 'M j, g:i a');
                    ?>
                     </a>
                    <?php
                }
                else
                    $this->printDate($dueDate, 'M j, g:i a');
                ?>
                  </td>
                 </tr>
                <?php
            }
            ?>
              </table>
             </div>
             <br class="clear" />
            <?php
        }
        else
        {
            ?>
             <div style="font-size: .9em; border: 1px solid #000000; padding: 5px; ">
              There are no upcoming events for the <?php echo $data->getSeasonYear(); ?> <?php echo $data->getSeriesName(); ?> season.
             </div>
            <?php
        }

        ?>
         </div>
        <?php
    }


    public function createCache($args)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $data = $this->data;

        $maxRowCount = 12;

        $now = new Date();

        $raceId = $data->getRaceId();
        $date = $data->getRaceDate();

        $data->loadFantasyPicks();
        $fantasyPicks = $data->getFantasyPicks();

        ?>
         <div style="border: 0px dashed #000000; margin-right: 405px; ">
        <?php
        $raceId = $data->getRaceId();
        $raceName = $data->getRaceName();
        $date = $data->getRaceDate();
        $raceTrack = $data->getTrackName();
        $data->loadRaces(new Date());
        $raceNo = $data->getRaceNumber();
        $results = $data->getRaceResults();

        if(count($results) > 0)
        {
            $standings = $data->getDriverStandings();
            ?>
            <div class="table-default center" style="float: left; margin: 0 5px 5px 0; ">
             <h3 style="margin-top: 7px; ">Fantasy Results</h3>
             <table class="fantasy-results">
              <tr>
               <th>F</th>
               <th colspan="2">Player</th>
               <th>DPts</th>
              </tr>
            <?php

            $rowColor = new RolloverIterator(array('eeeeee', 'dddddd'));

            $weeklyResults = $data->getFantasyResults();

            $points = $data->getFantasyPoints();
            $maxPoints = $data->getFantasyMaxPoints();
            $minPoints = $data->getFantasyMinPoints();

            $users = $data->getFantasyPlayers();

            $userDriverPoints = array();
            $userFantasyPoints = array();
            foreach($users as $userId => $user)
            {
                $userDriverPoints[$userId] = 0;
                $userFantasyPoints[$userId] = 0;
            }

            $allPicks = array();
            $query = new Query('SELECT fp.userId, driverId FROM nascarFantPick AS fp'
                . ' INNER JOIN nascarRace AS r ON fp.raceId=r.raceId'
                . ' INNER JOIN user AS u ON fp.userId=u.userId'
                . ' WHERE r.raceId=' . $raceId
                . ' AND fp.deletedTime IS NULL'
                . ' ORDER BY name ASC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $allPicks[$row[1]][] = $row[0];
            }

            $drivers = $data->getRaceResults();
            $leader = $drivers[0];
            $lastPoints = -1;
            $tied = 0;
            $lastRank = 0;
            foreach($drivers as $driver)
            {
                if(array_key_exists($driver->driverId, $allPicks))
                {
                    foreach($users as $userId => $user)
                    {
                        if(in_array($userId, $allPicks[$driver->driverId]))
                        {
                            $player = $users[$userId];

                            $userDriverPoints[$userId] += $driver->points;

                            $name = explode(' ', $player[2]);
                            $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));
                            if(count($name) > 1)
                                $initials .= substr($name[1], 0, 1);
                        }
                    }
                }
            }
            arsort($userDriverPoints);

            $rank = 0;
            $currPoint = -1;
            $skip = 0;
            foreach($userDriverPoints as $userId => $points)
            {
                $user = $users[$userId];
                $player = $users[$userId];

                if($user[4] == $currPoint)
                {
                    $skip++;
                }
                else
                {
                    $rank += $skip + 1;
                    $currPoint = $user[4];
                    $skip = 0;
                }

                ?>
                 <tr style="background: #<?php echo $rowColor; ?>; ">
                  <td><?php echo $rank; ?></td>
                <?php
                $name = explode(' ', $player[2]);
                $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));
                if(count($name) > 1)
                    $initials .= substr($name[1], 0, 1);

                ?>
                  <td class="right" style="width: 65px; "><?php echo $name[0]; ?></td>
                <?php
                echo '<td style="color: #ffffff; background-color: #' . $player[3] . '; " title="' . $player[2] . '">' . $initials . '</td>';
                echo '<td>';
                echo $userDriverPoints[$userId] . '</td>';
                ?>
                 </tr>
                <?php
            }
            ?>
              <tr>
               <th style="background-color: #ffffff; " colspan="4"><a href="fantasy/picks.php?series=<?php echo $this->seriesId; ?>&year=<?php echo $this->seasonYear; ?>&race=<?php echo $this->raceNo; ?>">Complete Results</a></th>
              </tr>
             </table>
            </div>
            <div class="table-default center">
            <?php
            $date = $data->getRaceDate();

            $points = array();

            $raceCount = 5;

            $startNo = $raceNo - $raceCount;
            $endNo = $startNo + $raceCount;
            if($startNo <= 2)
                $startNo = 1;
            //if($endNo == 35)
            //    $endNo = 36;

            $fantasyPicks = $data->getFantasyPicks();
            $totalDriverPoints = $data->getTotalDriverPoints();
            $weeklyResults = $data->getFantasyResults();

            $raceColumnCount = min(count($data->getRaces()), (($startNo > 1 ? 1 : 0) + ($endNo < count($data->getRaces()) ? 0 : 0) + $endNo - $startNo + 1));

            ?>
             <h3 style="margin-top: 7px; ">Fantasy Standings</h3>
              <table id="standings-table">
               <tr>
                <th>Rank</th>
                <th colspan="2">Player</th>
                <th>Behind</th>
               </tr>
            <?php

            #echo '<pre>'; print_r($weeklyResults);

            $points = $data->getFantasyPoints();
            $maxPoints = $data->getFantasyMaxPoints();
            $minPoints = $data->getFantasyMinPoints();

            $color = new RolloverIterator(array('eeeeee', 'dddddd'));

            $fantasyLeader = null;
            $prev = null;

            $lastWeeks = array();
            $beforeLastWeeks = array();

            $raceCount = count($data->getRaces());
            for($i = 1; $i <= $raceCount; $i++)
            {
                $lastWeeks[$i] = array();
                $beforeLastWeeks[$i] = array();

                foreach($users as $userId => $user)
                {
                    $lastWeeks[$i][$userId] = 0;
                    $beforeLastWeeks[$i][$userId] = 0;
                }
            }

            $ranks = array();

            $weekRaced = false;

            $showedEliminated = false;

            $rank = 0;
            $currPoint = -1;
            $skip = 0;
            $rowNum = 0;
            foreach($users as $userId => $user)
            {
                if(is_null($fantasyLeader))
                    $fantasyLeader = $user;

                $plus700 = 0;
                $plus750 = 0;
                $plus800 = 0;
                $maxDriver = 0;

                $name = explode(' ', $user[2]);
                $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));
                if(count($name) > 1)
                    $initials .= substr($name[1], 0, 1);

                if($user[4] == $currPoint)
                {
                    $skip++;
                }
                else
                {
                    $rank += $skip + 1;
                    $currPoint = $user[4];
                    $skip = 0;
                }

                $ranks[$userId] = $rank;

                $behindLeader = $user[4] - $fantasyLeader[4];
                $driverDiff = $totalDriverPoints[$user[0]] - $totalDriverPoints[$fantasyLeader[0]];

                if(is_null($prev))
                    $behindPrev = 0;
                else
                    $behindPrev = $user[4] - $prev[4];

                $weeksWon = 0;

                if(!$showedEliminated
                    && ($data->getMaxPointsPerRace() - $data->getMinPointsPerRace()) * (36 - min($data->getCompletedRaceNumber(), $data->getRaceNumber())) < -$behindLeader)
                {
                    ?>
                     <tr>
                      <th colspan="4" style="text-align: center; background-color: #cccccc; font-size: .7em; font-weight: bold; font-variant: small-caps; ">
                       v v v &nbsp;&nbsp;&nbsp; Mathematically Eliminated &nbsp;&nbsp;&nbsp; v v v
                      </th>
                     </tr>
                    <?php

                    $showedEliminated = true;
                }

                ?>
                 <tr class="center" style="background-color: #<?php echo $color; ?>; ">
                  <td class="center" style="width: 45px; "><?php echo $rank; ?></td>
                  <td class="right" style="width: 65px; "><?php echo $name[0]; ?></td>
                  <td class="center" style="color: #ffffff; background-color: #<?php echo $user[3]; ?>; "><?php echo $initials; ?></td>
                <?php
                $subTotal = 0;
                $total = 0;
                $races = $data->getRaces();
                foreach($races as $raceNum => $race)
                {
                    $raceId = $race['raceId'];

                    if($raceNum < $raceNo)
                    {
                        if($fantasyPicks[$race['raceId']][$userId] >= 700)
                            $plus700++;
                        if($fantasyPicks[$race['raceId']][$userId] >= 750)
                            $plus750++;
                        if($fantasyPicks[$race['raceId']][$userId] >= 800)
                            $plus800++;

                        $maxDriver = max($maxDriver, $fantasyPicks[$race['raceId']][$userId]);
                    }

                    if(array_key_exists($userId, $points)
                        && array_key_exists($raceId, $points[$userId])
                        && $points[$userId][$raceId] != 0)
                    {
                        for($i = 1; $i <= count($races); $i++)
                        {
                            if(1 <= $raceNum + 1 && $raceNum + 1 <= $endNo - ($i))
                            {
                                $beforeLastWeeks[$i][$userId] += $points[$userId][$raceId];
                            }
                            else
                            {
                                $lastWeeks[$i][$userId] += $points[$userId][$raceId];
                            }
                        }
                    }

                    if($startNo <= $raceNum + 1 && $raceNum + 1 <= $endNo)
                    {
                        $total += $subTotal;
                        $subTotal = 0;

                        if(array_key_exists($userId, $points)
                            && array_key_exists($raceId, $points[$userId]))
                        {
                            if($maxPoints[$raceId] == $points[$userId][$raceId]
                                && $points[$userId][$raceId] != 0)
                                $weeksWon++;

                            if($raceNum + 1 == $raceNo && $points[$userId][$raceId] != 0)
                                $weekRaced = true;

                            $fontColor = hexdec('BB');
                            if($maxPoints[$raceId] - $minPoints[$raceId] == 0)
                                $fontColor = '000000';
                            else
                                $fontColor = sprintf('%1$02X%1$02X%1$02X'
                                    , max(0, intval($fontColor * (1 - (($points[$userId][$raceId] - $minPoints[$raceId]) / ($maxPoints[$raceId] - $minPoints[$raceId]))))));

                            $style = 'color: #' . $fontColor . '; ';
                            if($maxPoints[$raceId] == $points[$userId][$raceId]
                                && $points[$userId][$raceId] != 0)
                                $style .= 'background: #ffff99; ';

                            #echo '</span>';
                            $total += $points[$userId][$raceId];
                        }
                    }
                    else if($startNo - 1 == $raceNum + 1)
                    {
                        if(array_key_exists($userId, $points)
                            && array_key_exists($raceId, $points[$userId])
                            && $points[$userId][$raceId] != 0)
                        {
                            if($maxPoints[$raceId] == $points[$userId][$raceId]
                                && $points[$userId][$raceId] != 0)
                                $weeksWon++;

                            $subTotal += $points[$userId][$raceId];
                        }

                    }
                    else if($raceNum + 1 == $endNo + 1)
                    {
                        //echo '<td>&nbsp;</td>';
                    }
                    else if($raceNum + 1 < $startNo || $endNo < $raceNum + 1)
                    {
                        if(array_key_exists($userId, $points)
                            && array_key_exists($raceId, $points[$userId])
                            && $points[$userId][$raceId] != 0)
                        {
                            if($maxPoints[$raceId] == $points[$userId][$raceId]
                                && $points[$userId][$raceId] != 0)
                                $weeksWon++;

                            $subTotal += $points[$userId][$raceId];
                        }
                    }
                }

                ?>
                  <td class="center"><?php echo $behindLeader == 0 ? '--' : $behindLeader; ?></td>
                 </tr>
                <?php

                $prev = $user;
            }

            ?>
               <tr>
                <th style="background-color: #ffffff; " colspan="4"><a href="fantasy/standings.php?series=<?php echo $this->seriesId; ?>&year=<?php echo $this->seasonYear; ?>&race=<?php echo $this->raceNo; ?>">Complete Standings</a></th>
               </tr>
              </table>
             </div>
            <?php
        }
        else
        {
            ?>
             <div class="tip-message">
              Results for this event have not yet been posted.
             </div>
            <?php
        }
        ?>
         </div>
        <?php
    }
}



?>
