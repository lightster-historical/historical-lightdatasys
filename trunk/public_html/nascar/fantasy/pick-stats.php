<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/DriverStanding.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class PickStatsResponder extends NascarResponder
{
    public function getPageTitle()
    {
        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        return $season->getYear() . ' ' . $series->getName()
            . ' Pick Stats as of ' . $race->getName();
    }


    public function get($args, $cacheDir = null)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        $this->printHeader();

        if(!is_null($race))
        {
            $whereUser = '';

            $this->standings = array();
            $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color AS fontColor, d.background AS backgroundColor, d.border AS borderColor, COUNT(fp.driverId) starts, '
                . 'SUM(IF(finish=1,1,0)) wins, SUM(IF(finish<=5,1,0)) top5s, '
                . 'SUM(IF(finish<=10,1,0)) top10s, SUM(re.finish) AS totalFinish, '
                . 'SUM(IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)+penalties)/COUNT(ra.raceId) AS points '
                . 'FROM nascarDriver AS d '
                . 'INNER JOIN nascarResult AS re ON d.driverId=re.driverId '
                . 'INNER JOIN nascarFantPick AS fp ON d.driverId=fp.driverId AND re.raceId=fp.raceId AND fp.deletedTime IS NULL '
                . 'INNER JOIN nascarRace AS ra ON re.raceId=ra.raceId '
                . 'WHERE ra.seasonId=' . $season->getId() . ' AND DATE(ra.date)<=\''
                . $race->getDate()->format('q Q') . '\' AND ra.forPoints=1 ' . $whereUser
                . 'GROUP BY d.driverId ORDER BY points DESC');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $row['chasePenalties'] = null;

                $standing = LDS_DriverStanding::constructUsingRow($row);
                $this->standings[] = $standing;
            }
            $drivers = $this->standings;

            ?>
             <dl class="header-data">
              <dt>Season</dt>
              <dd><?php echo $season->getYear(); ?> <?php echo $series->getName(); ?></dd>
              <dt>Race</dt>
              <dd><?php echo $race->getNumber() . ' &ndash; ' . $race->getName(); ?></dd>
              <dt>Track</dt>
              <dd><?php echo $race->getTrack()->getName(); ?></dd>
              <dt>Date</dt>
              <dd><?php echo $this->printDate($race->getDate(), 'l, F j, Y, g:i a'); ?></dd>
             </dl>
             <div class="table-default center">
              <table>
               <tr>
                <th>Rank</th>
                <th colspan="2">Driver</th>
                <th>Average<br />Points</th>
                <th>Picks</th>
                <th>Win<br />%</th>
                <th>Top 5<br />%</th>
                <th>Top 10s<br />%</th>
                <th>Average<br />Finish</th>
               </tr>
            <?php

            $leader = $drivers[0];
            $ranker = new MXT_Ranker();
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            foreach($drivers as $driver)
            {
                $rank = $ranker->getRank($driver->getPoints());

                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                  <td><?php echo $rank; ?></td>
                  <?php $driver->getDriver()->printCellSet(); ?>
                <?php

                echo '<td>' . number_format($driver->getPoints(), 2) . '</td>';
                echo '<td>' . $driver->getStarts() . '</td>';
                echo '<td>' . number_format($driver->getWins() / $driver->getStarts() * 100, 2) . '</td>';
                echo '<td>' . number_format($driver->getTop5s() / $driver->getStarts() * 100, 2) . '</td>';
                echo '<td>' . number_format($driver->getTop10s() / $driver->getStarts() * 100, 2) . '</td>';
                echo '<td>' . number_format($driver->getTotalFinish() / $driver->getStarts(), 2) . '</td>';

                echo '</tr>';
            }

            ?>
              </table>
             </div>
             <br style="clear: both; " />
            <?php
        }
        else
        {
            ?>
             <div class="tip-message">
              Pick stats have not yet been posted. Please check back after the first race.
             </div>
            <?php
        }


        $this->printFooter();
    }
}



?>
