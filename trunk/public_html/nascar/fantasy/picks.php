<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/Driver.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPicks.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyResults.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Season.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Series.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';


class PicksResponder extends NascarResponder
{
    /*
    protected $seasonId;
    protected $seasonYear;
    protected $raceId;
    protected $raceNo;
    protected $raceName;
    protected $raceDate;
    protected $nascarComId;
    protected $drivers;
    protected $races;
    protected $ranks;
    protected $maxPurchase;
    protected $driversById;
    protected $weeklyResults;
    */
    protected $resultsAvail;

    public function getPageTitle()
    {
        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        $driverStandings = $race->getDriverStandings();
        $maxPickCount = $season->getMaxPickCount();
        $maxPurchase = $driverStandings->getMaxPurchase();
        $this->onLoad .= 'updateSelections(' . $maxPurchase . ', ' . $maxPickCount . ', true);';

        return $season->getYear() . ' ' . $series->getName()
            . ' Fantasy Picks for ' . $race->getName();
    }


    //INSERT INTO nascarFantPick (`raceId`, `userId`, `driverId`) SELECT fp.raceId, pu.userId, re.driverId FROM fantasyPick AS fp INNER JOIN player_user AS pu ON fp.playerId=pu.playerId INNER JOIN result AS re ON fp.raceId=re.raceId AND fp.carId=re.carId
    public function init($args, $cacheDir = null)
    {
        parent::init($args);

        $this->input->set('pick', IntegerInput::getInstance());
    }


    public function printExtendedHTMLHead()
    {
        parent::printExtendedHTMLHead();

        echo '<link rel="stylesheet" href="/nascar/style.css" />';
        echo '<script src="/nascar/picks-totaler.js" type="text/javascript"></script>';
    }


    public function post($args)
    {
        $race = $this->getRace();
        if(!$race->getPickStatus())
        {
            $this->get($args);
            exit;
        }

        $db = Database::getConnection('com.lightdatasys.nascar');

        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        $driverStandings = $race->getDriverStandings();
        $driverStandingsByDriverId = $driverStandings->getByDriverId();

        $maxPickCount = $season->getMaxPickCount();
        $maxPurchase = $driverStandings->getMaxPurchase();

        $now = new Date();
        $date = self::getDueDate($race->getDate());

        if(!is_null($this->user) && $now->compareTo($date) <= 0)
        {
            $args['errors'] = array();
            $args['messages'] = array();

            $tempPicks = $this->input->get('pick', IntegerInput::getInstance());

            if(count($tempPicks) > 0)
            {
                $total = 0;
                $picks = array();
                foreach($tempPicks as $driverId)
                {
                    if(array_key_exists($driverId, $driverStandingsByDriverId))
                    {
                        $total += $driverStandingsByDriverId[$driverId]->getPoints();
                    }
                    $picks[] = $driverId;
                }

                if(count($picks) <= $maxPickCount && $total <= $maxPurchase)
                {
                    if(!is_null($race))
                    {
                        if(count($picks) > 0)
                        {
                            $noChangePicks = array();
                            $query = new Query('SELECT driverId FROM nascarFantPick AS fp'
                                . ' INNER JOIN nascarRace AS r ON fp.raceId=r.raceId'
                                . ' INNER JOIN user AS u ON fp.userId=u.userId'
                                . ' WHERE r.raceId=' . $race->getId()
                                . ' AND u.userId=' . $this->user->getId()
                                . ' AND fp.deletedTime IS NULL'
                                . ' AND fp.driverId IN (' . implode(',', $picks) . ')'
                                . ' ORDER BY name ASC');
                            $result = $db->execQuery($query);
                            while($row = $db->getRow($result))
                            {
                                $noChangePicks[$row[0]] = $row[0];
                            }

                            $noChangeWhere = '';
                            if(count($noChangePicks) > 0)
                                $noChangeWhere = ' AND driverId NOT IN (' . implode(',', $noChangePicks) . ')';

                            $query = new Query('UPDATE '
                                . $db->getTable('FantPick')
                                . ' SET deletedTime=\'' . $now->format('q Q')
                                . '\' WHERE raceId=' . $race->getId() . ' AND userId='
                                . $this->user->getId()
                                . ' AND deletedTime IS NULL'
                                . $noChangeWhere);
                            $db->execQuery($query);

                            $values = '';
                            $comma = '';
                            foreach($picks as $pick)
                            {
                                if(!array_key_exists($pick, $noChangePicks))
                                {
                                    $values .= $comma . '(' . $race->getId() . ',' . $this->user->getId() .
                                        ',' . $pick . ',\'' . $now->format('q Q') . '\')';
                                    $comma = ',';
                                }
                            }

                            if($values != '')
                            {
                                $query = new Query('INSERT INTO ' . $db->getTable('FantPick')
                                    . ' (`raceId`, `userId`, `driverId`, `addedTime`) VALUES '
                                    . $values);
                                $db->execQuery($query);
                            }

                            if(count($picks) == $maxPickCount)
                                $args['messages'][] = 'You chose ' . count($picks) . ' drivers.';
                            else
                                $args['errors'][] = 'You chose ' . count($picks) . ' drivers. This is fewer than the ' . $maxPickCount . ' allowed drivers.';

                            $args['messages'][] = 'The drivers you chose cost $' . $total . ' of the $' . $maxPurchase . ' allowed.';
                        }
                        else
                        {
                            $args['errors'][] = 'You did not pick any drivers.';
                        }
                    }
                    else
                    {
                        $args['errors'][] = 'There was an unknown error. Please try again.';
                    }
                }
                else
                {
                    if(count($picks) > $maxPickCount)
                        $args['errors'][] = 'You chose ' . count($picks) . ' drivers. This is more than the ' . $maxPickCount . ' allowed drivers.';
                    else if(count($picks) < $maxPickCount)
                        $args['errors'][] = 'You chose ' . count($picks) . ' drivers. This is fewer than the ' . $maxPickCount . ' allowed drivers.';

                    if($total > $maxPurchase)
                        $args['errors'][] = 'The drivers you chose cost $' . $total . '. This is more than the $' . $maxPurchase . ' allowed.';
                }
            }
            else
            {
                if(count($tempPicks) == 0)
                    $args['errors'][] = 'You did not pick any drivers.';
                else if(count($tempPicks) < $maxPickCount)
                    $args['errors'][] = 'You chose ' . count($tempPicks) . ' drivers. This is fewer than the ' . $maxPickCount . ' allowed drivers.';
            }
        }

        $this->get($args);
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        $driverStandings = $race->getDriverStandings();

        $maxPickCount = $season->getMaxPickCount();
        $maxPurchase = $driverStandings->getMaxPurchase();

        $now = new Date();

        $this->printHeader();

        if(!is_null($race))
        {
            ?>
             <dl class="header-data">
              <dt>Season</dt>
              <dd><?php echo $season->getYear(); ?> <?php echo $series->getName(); ?></dd>
              <dt>Race</dt>
              <dd>
               <?php echo $race->getNumber() . ' &ndash; ' . $race->getName(); ?>
            <?php
            if(!$race->isForPoints())
                echo ' <em>(non-points event)</em>';
            ?>
              </dd>
              <dt>Track</dt>
              <dd><?php echo $race->getTrack()->getName(); ?></dd>
              <dt>Current Date</dt>
              <dd><?php echo $this->printDate($now, 'l, F j, Y, g:i a'); ?></dd>
              <dt>Due Date</dt>
              <dd><?php echo $this->printDate($race->getDate(), 'l, F j, Y, g:i a'); ?></dd>
             </dl>
            <?php
        }
        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="picksForm">
        <?php

        if($race->hasResults())
        {
            $this->printFantasyResults();
        }
        else if($now->compareTo($race->getDate()) < 0)
        {
            if(!$race->getPickStatus())
            {
                ?>
                 <div class="tip-message">
                  Picks cannot yet be made for this race. The previous race must be declared official before picks will open for this race.
                 </div>
                <?php
            }
            else if(!$this->isLoggedIn())
            {
                ?>
                 <div class="tip-message">
                  Please sign in to make your picks.
                 </div>
                <?php
            }
            else
                $this->printFantasyPicker($args);
        }
        else if($now->compareTo($race->getDate()) > 0)
        {
            $this->printFantasyPicks();
        }

        ?>
          <input type="hidden" name="raceId" value="<?php echo $race->getId(); ?>" />
         </form>
        <?php

        $this->printFooter();
    }


    public function printFantasyResults()
    {
        $now = new Date();

        $race = $this->getRace();
        $season = $this->getSeason();

        $users = $season->getFantasyPlayers();
        uasort($users, array($this, 'comparePlayersByFantasyPoints'));
        $drivers = $race->getResults()->getByRank();

        $allPicks = $race->getFantasyPicks()->getPicks();
        $fantasyResults = $race->getFantasyResults();
        $fantasyPoints = $fantasyResults->getFantasyPointsUsingRace($race);
        $driverPoints = $fantasyResults->getDriverPointsUsingRace($race);

        ?>
         <div class="table-default center">
          <table class="fantasy-results">
           <tr>
            <th>Race<br />Position</th>
            <th colspan="2" style="width: 156px;">Driver</th>
            <th>Race<br />Points</th>
            <th>Race<br />Picks</th>
            <th colspan="<?php echo count($users); ?>">Players</th>
           </tr>
        <?php

        $leader = $drivers[0];
        $ranker = new MXT_Ranker();
        $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
        foreach($drivers as $driver)
        {
            if($now->compareTo($race->getDate()) >= 0
                && (!array_key_exists($driver->getDriver()->getId(), $allPicks)
                || count($allPicks[$driver->getDriver()->getId()]) <= 0))
                continue;

            $rank = $ranker->getRank($driver->getPoints());

            ?>
             <tr class="<?php echo $rowStyle; ?>">
              <td><?php echo $driver->getFinish(); ?></td>
            <?php

            $accessory = '';
            if($this->isLoggedIn() && array_key_exists($driver->getDriver()->getId(), $allPicks)
                && in_array($this->user->getId(), $allPicks[$driver->getDriver()->getId()]))
                $accessory = 'x';

            $driver->getDriver()->printCellSet($accessory);

            echo '<td>' . $driver->getPoints() . '</td>';
            echo '<td>' . count($allPicks[$driver->getDriver()->getId()]) . '</td>';

            if(array_key_exists($driver->getDriver()->getId(), $allPicks))
            {
                foreach($users as $userId => $user)
                {
                    if(in_array($userId, $allPicks[$driver->getDriver()->getId()]))
                    {
                        $player = $users[$userId];

                        $player->printInitialsCell();
                    }
                    else if($this->isLoggedIn() && $userId == $this->user->getId())
                        echo '<td style="background-color: #ffffff; ">&nbsp;</td>';
                    else
                        echo '<td>&nbsp;</td>';
                }
            }
            echo '</tr>';
        }

        ?>
          <tr style="background-color: #cccccc; ">
           <td colspan="<?php echo count($users) + 5; ?>"></td>
          </tr>
          <tr class="<?php echo $rowStyle; ?>">
           <th class="right" colspan="5">Driver Points</th>
        <?php
        foreach($users as $userId => $user)
        {
            if($this->isLoggedIn() && $userId == $this->user->getId())
                echo '<td style="background-color: #ffffff; ">';
            else
                echo '<td>';
            echo $driverPoints[$userId] . '</td>';
        }
        ?>
          </tr>
          <tr class="<?php echo $rowStyle; ?>">
           <th class="right" colspan="5">Fantasy Ranking</th>
        <?php
        $ranker = new MXT_Ranker();
        foreach($users as $userId => $user)
        {
            $rank = $ranker->getRank($driverPoints[$userId]);

            if($this->isLoggedIn() && $userId == $this->user->getId())
                echo '<td style="background-color: #ffffff; ">';
            else
                echo '<td>';
            echo $rank . '</td>';
        }
        ?>
          </tr>
          <tr class="<?php echo $rowStyle; ?>">
           <th class="right" colspan="5">Fantasy Points</th>
        <?php
        foreach($users as $userId => $user)
        {
            if($this->isLoggedIn() && $userId == $this->user->getId())
                echo '<td style="background-color: #ffffff; ">';
            else
                echo '<td>';

            if(is_array($fantasyPoints) && array_key_exists($userId, $fantasyPoints))
                echo $fantasyPoints[$userId] . '</td>';
            else
                echo '&nbsp;</td>';
        }
        ?>
           </tr>
          </table>
         </div>
         <br style="clear: both; " />
        <?php
    }

    public function printFantasyPicks()
    {
        $now = new Date();

        $race = $this->getRace();
        $season = $this->getSeason();

        $users = $season->getFantasyPlayers();

        $drivers = null;
        if($race->hasResults() || $race->hasEntryList() || $race->hasLineup())
        {
            $results = $race->getResults()->getByRank();
            $drivers = array();
            foreach($results as $result)
                $drivers[$result->getDriver()->getId()] = $result->getDriver();
        }
        if(is_null($drivers))
            $drivers = LDS_Driver::getAll();
        usort($drivers, array($this, 'compareDriversByName'));

        $allPicks = $race->getFantasyPicks()->getPicks();

        $driverStandings = $race->getDriverStandings();
        $driverStandingsByDriverId = $driverStandings->getByDriverId();

        $fantasyResults = $race->getFantasyResults();
        $fantasyPoints = $fantasyResults->getFantasyPointsUsingRace($race);
        $driverPoints = $fantasyResults->getDriverPointsUsingRace($race);

        ?>
         <div class="table-default">
          <table class="fantasy-results">
           <tr>
            <th>Price</th>
            <th colspan="2" style="width: 156px;">Driver</th>
            <th>Race Picks</th>
            <th colspan="<?php echo count($users); ?>">Players</th>
           </tr>
        <?php

        $rowStyle = new RolloverIterator(array('row-a', 'row-b'));

        $leader = $drivers[0];
        $lastPoints = -1;
        $tied = 0;
        $lastRank = 0;
        foreach($drivers as $driver)
        {
            if($now->compareTo($race->getDate()) >= 0
                && (!array_key_exists($driver->getId(), $allPicks)
                || count($allPicks[$driver->getId()]) <= 0))
                continue;

            echo '<tr class="' . $rowStyle . '">';

            if($this->isLoggedIn() && array_key_exists($driver->getId(), $allPicks)
                && in_array($this->user->getId(), $allPicks[$driver->getId()]))
                echo '<td style="background: #' . $users[$this->user->getId()]->getBackgroundColor() . '; color: #ffffff;">';
            else
                echo '<td>';

            $points = 0;
            if(array_key_exists($driver->getId(), $driverStandingsByDriverId))
                $points = $driverStandingsByDriverId[$driver->getId()]->getPoints();
            echo '$' . $points . '</td>';

            $accessory = '';
            if($this->isLoggedIn() && array_key_exists($driver->getId(), $allPicks)
                && in_array($this->user->getId(), $allPicks[$driver->getId()]))
                $accessory = 'x';

            $driver->printCellSet($accessory);

            echo '<td class="center">' . count($allPicks[$driver->getId()]) . '</td>';
            if(array_key_exists($driver->getId(), $allPicks))
            {
                foreach($users as $userId => $user)
                {
                    if(in_array($userId, $allPicks[$driver->getId()]))
                    {
                        $player = $users[$userId];

                        $player->printInitialsCell();
                    }
                    else if($this->isLoggedIn() && $userId == $this->user->getId())
                        echo '<td style="background-color: #ffffff; ">&nbsp;</td>';
                    else
                        echo '<td>&nbsp;</td>';
                }
            }

            echo '</tr>';
        }
        ?>
          </table>
         </div>
         <br class="clear" />
        <?php
    }


    public function printFantasyPicker($args)
    {
        $race = $this->getRace();
        $season = $this->getSeason();

        $users = $season->getFantasyPlayers();

        $drivers = null;
        if($race->hasResults() || $race->hasEntryList() || $race->hasLineup())
        {
            $results = $race->getResults()->getByRank();
            $drivers = array();
            foreach($results as $result)
                $drivers[$result->getDriver()->getId()] = $result->getDriver();
        }
        if(is_null($drivers))
            $drivers = LDS_Driver::getAll();
        usort($drivers, array($this, 'compareDriversByName'));

        $allPicks = $race->getFantasyPicks()->getPicksByUserId();
        $picks = $this->input->get('pick');
        if(is_null($picks) || count($picks) <= 0)
        {
            $picks = array();
            if(array_key_exists($this->user->getId(), $allPicks))
                $picks = $allPicks[$this->user->getId()];
        }

        $driverStandings = $race->getDriverStandings();
        $driverStandingsByDriverId = $driverStandings->getByDriverId();

        $maxPickCount = $season->getMaxPickCount();
        $maxPurchase = $driverStandings->getMaxPurchase();

        ?>
         <div class="tip-message">
          Choose <?php echo $maxPickCount; ?> drivers. You have $<?php echo $maxPurchase; ?> to spend.
          <br /><br />
        <?php
        if($race->hasLineup())
            echo 'The listed drivers are on the <b>lineup</b> for this race. The start position of each driver is listed below.';
        else if($race->hasEntryList())
            echo 'The listed drivers are on the <b>entry list</b> for this race.';
        else
            echo 'Some of the drivers listed on this page may not be participating in this race. This list is <b>not</b> the lineup nor is it the entry list.';
        ?>
         </div>
        <?php
        if(array_key_exists('errors', $args) && count($args['errors']) > 0)
        {
            ?>
             <div class="error-message">
              The following error(s) occurred during processing:
              <ul>
            <?php
            foreach($args['errors'] as $message)
            {
                echo '<li>' . $message . '</li>';
            }
            ?>
              </ul>
             </div>
            <?php
        }

        if(array_key_exists('messages', $args) && count($args['messages']) > 0)
        {
            ?>
             <div class="info-message">
              Your picks have been saved.
              <ul>
            <?php
            foreach($args['messages'] as $message)
            {
                echo '<li>' . $message . '</li>';
            }
            ?>
              </ul>
             </div>
            <?php
        }

        ?>
         <div style="background-color: #eeeeee; margin: 3px 0; padding: 7px; border: 1px solid #999999; ">
         <div style="float: left; height: 300px; width: 300px; overflow: auto; overflow-y: scroll; overflow-x: hidden; padding-right: 16px; border: 1px solid #000000; ">
           <table id="picksTable" class="fantasy-results" style="border: 0; width: 100%; ">
        <?php

        $rowColor = new RolloverIterator(array('eeeeee', 'dddddd'));

        $results = $race->getResults()->getByDriverId();
        foreach($drivers as $driver)
        {
            $checked = '';
            if(in_array($driver->getId(), $picks))
                $checked .= ' checked="checked"';

            echo '<tr style="background: #' . $rowColor . '; ">';

            $accessory = '<input type="checkbox" id="driver_' . $driver->getId() . '" name="pick[]" onClick="javascript: return updateSelections(' . $maxPurchase . ', ' . $maxPickCount . ', false); " value="' . $driver->getId() . '"' . $checked . ' style="margin: 0 4px; " />';

            if($race->hasLineup())
            {
                if(array_key_exists($driver->getId(), $results))
                    echo '<td>' . $results[$driver->getId()]->getStart() . '</td>';
                else
                    echo '<td>?</td>';
            }
            $driver->printCellSet($accessory, true);


            if($this->isLoggedIn() && in_array($driver->getId(), $picks))
                echo '<td width="65" style="background: #' . $users[$this->user->getId()]->getBackgroundColor() . '; color: #ffffff;">';
            else
                echo '<td width="65" style="background: #dddddd; ">';

            $points = 0;
            if(array_key_exists($driver->getId(), $driverStandingsByDriverId))
                $points = $driverStandingsByDriverId[$driver->getId()]->getPoints();
            echo '$' . $points . '</td>';

            echo '</tr>';
        }
        ?>
           </table>
          </div>
          <input type="submit" name="save" value="Save Picks" style="margin: 10px; "/>
          <div id="selections">
          </div>
          <br class="clear" />
         </div>
        <?php
    }


    public function comparePlayersByFantasyPoints(LDS_FantasyPlayer $a, LDS_FantasyPlayer $b)
    {
        $race = $this->getRace();
        $fantasyResults = $race->getFantasyResults();

        $aPoints = $fantasyResults->getFantasyPointsUsingRaceAndPlayer($race, $a);
        $bPoints = $fantasyResults->getFantasyPointsUsingRaceAndPlayer($race, $b);

        if($aPoints > $bPoints)
            return -1;
        else if($aPoints < $bPoints)
            return 1;
        return 0;
    }

    public function compareDriversByName(LDS_Driver $a, LDS_Driver $b)
    {
        $cmp = strcmp($a->getLastName(), $b->getLastName());
        if($cmp == 0)
            return strcmp($a->getLastName(), $b->getLastName());
        return $cmp;
    }

    public function compareResultsByDriverName(LDS_Result $a, LDS_Result $b)
    {
        return $this->compareDriversByName($a->getDriver(), $b->getDriver());
    }
}



?>
