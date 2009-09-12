<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class DriverStandingsResponder extends NascarResponder
{
    public function getPageTitle()
    {
        $race = $this->getRace();
        $season = $this->getSeason();

        if($race->hasResults() && $race->isForPoints())
        {
            $official = $race->isOfficial() ? 'Official' : 'Unofficial';
            return $official . ' ' . $season->getYear() . ' Driver Standings';
        }
        else
        {
            return 'Driver Standings';
        }
    }


    public function get($args)
    {
        $race = $this->getRace();
        $series = $this->getSeries();
        $season = $this->getSeason();

        $standings = $race->getDriverStandings()->getByRank();

        $prevRaces = LDS_Race::getBeforeRace($race, 1);
        $prevStandings = null;
        if(count($prevRaces) > 0)
        {
            $prevRace = $prevRaces[0];
            $prevStandings = $prevRace->getDriverStandings()->getByDriverId();
        }

        $this->printHeader();

        if(count($standings) > 0)
        {
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
             <div class="table-default">
              <table>
               <tr>
                <th>Rank</th>
                <th>+/-</th>
                <th colspan="2">Driver</th>
                <th>Points</th>
                <th>Behind</th>
                <th>Starts</th>
                <th>Wins</th>
                <th>Top<br />5s</th>
                <th>Top<br />10s</th>
                <th>Avg.<br />Finish</th>
               </tr>
            <?php

            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));

            $leader = $standings[0];
            foreach($standings as $standing)
            {
                $prevRank = '--';
                if(!is_null($prevStandings))
                {
                    if(array_key_exists($standing->getDriver()->getId(), $prevStandings))
                    {
                        $pRank = $prevStandings[$standing->getDriver()->getId()]->getRank();
                        if($pRank != $standing->getRank())
                            $prevRank = sprintf('%+d', ($pRank - $standing->getRank()));
                    }
                }

                ?>
                 <tr class="center <?php echo $rowStyle; ?>">
                  <td><?php echo $standing->getRank(); ?></td>
                  <td><?php echo $prevRank; ?></td>
                <?php
                $standing->getDriver()->printCellSet();
                ?>
                  <td><?php echo $standing->getPoints(); ?></td>
                  <td>
                <?php
                if($standing->getRank() == 1)
                    echo '--';
                else
                   echo ($standing->getPoints() - $leader->getPoints());
                ?>
                  </td>
                  <td><?php echo $standing->getStarts(); ?></td>
                  <td><?php echo $standing->getWins(); ?></td>
                  <td><?php echo $standing->getTop5s(); ?></td>
                  <td><?php echo $standing->getTop10s(); ?></td>
                  <td>
                   <?php echo number_format($standing->getTotalFinish() / $standing->getStarts(), 2); ?>
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
             <div class="tip-message">
              Driver standings have not yet been posted. Please check back after the first race.
             </div>
            <?php
        }

        $this->printFooter();
    }
}



?>
