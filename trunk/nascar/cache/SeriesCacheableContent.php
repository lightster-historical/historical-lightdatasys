<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';

require_once PATH_LIB . 'com/mephex/cache/CacheableContent.php';


class LDS_SeriesCacheableContent implements MXT_CacheableContent
{
    protected $responder;
    protected $series;


    public function __construct(NascarResponder $responder, $series)
    {
        $this->responder = $responder;
        $this->series = $series;
    }


    public function getResponder()
    {
        return $this->responder;
    }

    public function getSeriesArray()
    {
        return $this->series;
    }


    public function getContent()
    {
        $series = $this->getSeriesArray();

        $race = LDS_Race::getLastCompletedUsingSeasonId($series['seasonId']);

        $this->printFantasyResults($race);
        $this->printFantasyStandings($race);
        $this->printRaceResults($race);
        $this->printDriverStandings($race);
    }

    public function getContentLastUpdated()
    {
        $series = $this->getSeriesArray();
        return $series['lastUpdatedRace'];
    }

    public function getDirectory()
    {
        $series = $this->getSeriesArray();
        return 'com/lightdatasys/public_html/nascar/index.php';
    }

    public function getFileName()
    {
        $series = $this->getSeriesArray();
        return $series['keyname']. '.txt';
    }


    public function printFantasyResults(LDS_Race $race)
    {
        $season = $race->getSeason();
        $users = $season->getFantasyPlayers();

        $fantasyResults = $race->getFantasyResults();
        $fantasyPoints = $fantasyResults->getFantasyPointsUsingRace($race);
        $driverPoints = $fantasyResults->getDriverPointsUsingRace($race);

        if(!is_null($race) && count($fantasyPoints) > 0)// && $data->hasResults())
        {
            ?>
             <h4>
              <a href="fantasy/picks.php?raceId=<?php echo $race->getId(); ?>">
               <?php echo $race->isOfficial() ? 'Official' : 'Unofficial'; ?>
               Fantasy Results
              </a>
             </h4>
             <h5><?php echo $race->getName(); ?></h5>
             <div class="table-wide center">
              <table>
               <tr>
                <th>F</th>
                <th colspan="2">Player</th>
                <th>Pts</th>
                <th>Driver</th>
               </tr>
            <?php
            $leader = null;
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            $ranker = new MXT_Ranker();
            foreach($fantasyPoints as $userId => $points)
            {
                if(is_null($leader))
                    $leader = $userId;

                $rank = $ranker->getRank($points);

                if($rank > 5)
                    break;

                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                  <td><?php echo $rank; ?></td>
                <?php
                $users[$userId]->printCellSet();
                ?>
                  <td><?php echo $points; ?></td>
                  <td>
                <?php
                if($driverPoints[$userId] == $driverPoints[$leader])
                    echo $driverPoints[$userId];
                else
                    echo $driverPoints[$userId] - $driverPoints[$leader];
                ?>
                  </td>
                 </tr>
                <?php
            }
            ?>
               <tr>
                <th colspan="5">
                 <a href="fantasy/picks.php?raceId=<?php echo $race->getId(); ?>">Complete Fantasy Results</a>
                </th>
               </tr>
              </table>
             </div>
            <?php
        }
    }

    public function printFantasyStandings(LDS_Race $race)
    {
        $season = $race->getSeason();
        $users = $season->getFantasyPlayers();

        $fantasyResults = $race->getFantasyResults();
        $fantasyTotalPoints = $fantasyResults->getTotalFantasyPoints();

        if(!is_null($race) && count($fantasyTotalPoints) > 0 && $race->isForPoints())// && $data->hasResults())
        {
            ?>
             <h4>
              <a href="fantasy/standings.php?raceId=<?php echo $race->getId(); ?>">
               <?php echo $race->isOfficial() ? 'Official' : 'Unofficial'; ?>
               Fantasy Standings
              </a>
             </h4>
             <h5><?php echo $race->getName(); ?></h5>
             <div class="table-wide center">
              <table>
               <tr>
                <th>Rank</th>
                <th colspan="2">Player</th>
                <th>Points</th>
               </tr>
            <?php
            $leader = null;
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            $ranker = new MXT_Ranker();
            foreach($fantasyTotalPoints as $userId => $points)
            {
                if(is_null($leader))
                    $leader = $userId;

                $rank = $ranker->getRank($points);

                if($rank > 5)
                    break;

                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                  <td><?php echo $rank; ?></td>
                <?php
                $users[$userId]->printCellSet();
                ?>
                  <td>
                <?php
                if($fantasyTotalPoints[$userId] == $fantasyTotalPoints[$leader])
                    echo $fantasyTotalPoints[$userId];
                else
                    echo $fantasyTotalPoints[$userId] - $fantasyTotalPoints[$leader];
                ?>
                  </td>
                 </tr>
                <?php
            }
            ?>
               <tr>
                <th colspan="5">
                 <a href="fantasy/standings.php?raceId=<?php echo $race->getId(); ?>">Complete Fantasy Standings</a>
                </th>
               </tr>
              </table>
             </div>
            <?php
        }
    }

    public function printRaceResults(LDS_Race $race)
    {
        $results = $race->getResults()->getByRank();
        if(!is_null($race) && count($results) > 0)// && $data->hasResults())
        {
            ?>
             <h4>
              <a href="results.php?raceId=<?php echo $race->getId(); ?>">
               <?php echo $race->isOfficial() ? 'Official' : 'Unofficial'; ?>
               Results
              </a>
             </h4>
             <h5><?php echo $race->getName(); ?></h5>
             <div class="table-wide center">
              <table>
               <tr>
                <th>F</th>
                <th>S</th>
                <th>#</th>
                <th colspan="2">Driver</th>
               </tr>
            <?php
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            foreach($results as $result)
            {
                if($result->getFinish() > 5)
                    break;

                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                  <td><?php echo $result->getFinish(); ?></td>
                  <td><?php echo $result->getStart(); ?></td>
                  <td><?php echo $result->getCar(); ?></td>
                <?php
                $result->getDriver()->printCellSet();
                ?>
                 </tr>
                <?php
            }
            ?>
               <tr>
                <th colspan="5">
                 <a href="results.php?raceId=<?php echo $race->getId(); ?>">Complete Results</a>
                </th>
               </tr>
              </table>
             </div>
            <?php
        }
    }

    public function printDriverStandings(LDS_Race $race)
    {
        $season = $race->getSeason();
        $standings = $race->getDriverStandings()->getByRank();
        if(!is_null($race) && count($standings) > 0)
        {
            $date = $race->getDate();
            ?>
             <h4>
              <a href="driver-standings.php?season=<?php echo $season->getId(); ?>">
               <?php echo $race->isOfficial() ? 'Official' : 'Unofficial'; ?>
               Driver Standings
              </a>
             </h4>
             <h5><?php echo $race->getName(); ?></h5>
             <div class="table-wide center">
              <table>
               <tr>
                <th>Rank</th>
                <th colspan="2">Driver</th>
                <th>Points</th>
                <th>Wins</th>
               </tr>
            <?php

            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));

            $leader = $standings[0];
            foreach($standings as $standing)
            {
                if($standing->getRank() > 5)
                    break;

                ?>
                 <tr class="center <?php echo $rowStyle; ?>">
                  <td><?php echo $standing->getRank(); ?></td>
                <?php
                $standing->getDriver()->printCellSet();
                ?>
                  <td>
                <?php
                if($standing->getRank() == 1)
                    echo $standing->getPoints();
                else
                    echo ($standing->getPoints() - $leader->getPoints());
                ?>
                  </td>
                  <td><?php echo $standing->getWins(); ?></td>
                 </tr>
                <?php
            }
            ?>
               <tr>
                <th colspan="5">
                 <a href="driver-standings.php?raceId=<?php echo $race->getId(); ?>">Complete Driver Standings</a>
                </th>
               </tr>
              </table>
             </div>
             <br class="clear" />
            <?php
        }
    }
}



?>
