<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';

require_once PATH_LIB . 'com/mephex/cache/CacheableContent.php';


class LDS_LineupPicksCacheableContent implements MXT_CacheableContent
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

        $race = LDS_Race::getCurrentUsingSeasonId($series['seasonId']);

        $this->printRaceLineup($race);
        $this->printFantasyPicks($race);
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
        return $series['keyname']. '_lineup.txt';
    }


    public function printRaceLineup(LDS_Race $race)
    {
        $results = $race->getResults()->getByRank();
        if($race->hasLineup() && $race->getDate()->compareTo(new Date()) >= 0)
        {
            ?>
             <h4>
              <a href="results.php?raceId=<?php echo $race->getId(); ?>">
               Lineup
              </a>
             </h4>
             <h5><?php echo $race->getName(); ?></h5>
             <div class="table-wide center">
              <table>
               <tr>
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
                 <a href="results.php?raceId=<?php echo $race->getId(); ?>">Complete Lineup</a>
                </th>
               </tr>
              </table>
             </div>
            <?php
        }
    }


    public function printFantasyPicks(LDS_Race $race)
    {
        $now = new Date();

        if($race->getDate()->compareTo(new Date()) < 0 && $race->hasLineup())
        {
            $season = $race->getSeason();
            $players = $season->getFantasyPlayers();

            $maxDrivers = $season->getMaxPickCount();

            $drivers = $race->getResults()->getByDriverId();
            //$allPicks = $race->getFantasyPicks()->getPicksByUserId();
            $allPicks = $this->getAllPicksByUserId($race);

            ?>
             <h4>
              <a href="fantasy/picks.php?raceId=<?php echo $race->getId(); ?>">
               Fantasy Picks
              </a>
             </h4>
             <h5><?php echo $race->getName(); ?></h5>
             <div class="table-wide center">
              <table>
               <tr>
                <th>Plyr</th>
                <th colspan="<?php echo $maxDrivers; ?>">Drivers</th>
               </tr>
            <?php

            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));

            foreach($players as $player)
            {
                if(array_key_exists($player->getId(), $allPicks))
                {
                    $picks = $allPicks[$player->getId()];

                    ?>
                     <tr>
                    <?php

                    $player->printInitialsCell();

                    foreach($picks as $pick)
                    {
                        if(array_key_exists($pick, $drivers))
                        {
                            $driver = $drivers[$pick];
                            $driver->getDriver()->printMiniCellSet($driver->getCar());
                        }
                    }

                    ?>
                     </tr>
                    <?php
                }
            }

            ?>
               <tr>
                <th colspan="<?php echo 1 + $maxDrivers; ?>">
                 <a href="fantasy/picks.php?raceId=<?php echo $race->getId(); ?>">Complete Fantasy Picks</a>
                </th>
               </tr>
              </table>
             </div>
            <?php
        }
    }

    protected function getAllPicksByUserId(LDS_Race $race)
    {
        $season = $race->getSeason();
        $players = $season->getFantasyPlayers();

        $maxDrivers = $season->getMaxPickCount();

        $drivers = $race->getResults()->getByDriverId();
        $allPicksByDriverId = $race->getFantasyPicks()->getPicks();
        $allPicksByPlayerId = $race->getFantasyPicks()->getPicksByUserId();

        $cellsOpen = array_fill(0, $maxDrivers, count($players));
        $linesFilled = 0;
        $maxLines = $maxDrivers;

        $ordered = $this->getDefaultPicksArray($race);
        $notPlaced = array();
        $pickCounts = $this->getPickCountByDriverId($race);
        foreach($pickCounts as $driverId => $count)
        {
            $found = false;
            $currLine = 0;
            while(!$found && $currLine < $maxLines)
            {
                if($count <= $cellsOpen[$currLine])
                {
                    $failure = false;
                    $tempOrder = $ordered;
                    foreach($allPicksByDriverId[$driverId] as $playerId)
                    {
                        if(is_null($tempOrder[$playerId][$currLine]))
                            $tempOrder[$playerId][$currLine] = $driverId;
                        else
                        {
                            $failure = true;
                        }
                    }

                    if(!$failure)
                    {
                        $ordered = $tempOrder;
                        $found = true;
                        $cellsOpen[$currLine] -= $count;
                    }
                    else
                    {
                    }
                }

                ++$currLine;
            }

            if(!$found)
            {
                $notPlaced[] = $driverId;
            }
        }

        foreach($allPicksByPlayerId as $playerId => $picks)
        {
            $currLine = 0;
            foreach($picks as $driverId)
            {
                if(in_array($driverId, $notPlaced))
                {
                    while(!is_null($ordered[$playerId][$currLine]) && $currLine < $maxLines)
                        ++$currLine;

                    $ordered[$playerId][$currLine] = $driverId;
                    ++$currLine;
                }
            }
        }

        foreach($notPlaced as $driverId)
        {
            $currLine = 0;
            foreach($allPicksByDriverId[$driverId] as $playerId)
            {
                if(is_null($tempOrder[$playerId][$currLine]))
                    $ordered[$playerId][$currLine] = $driverId;
                else
                {
                    $failure = true;
                }
            }
        }

        return $ordered;
    }

    protected function getDefaultPicksArray(LDS_Race $race)
    {
        $season = $race->getSeason();
        $players = $season->getFantasyPlayers();

        $maxDrivers = $season->getMaxPickCount();

        $allPicksByPlayerId = $race->getFantasyPicks()->getPicksByUserId();

        $picks = array();
        $template = array_fill(0, 5, null);
        foreach($players as $player)
        {
            $playerId = $player->getId();
            if(array_key_exists($playerId, $allPicksByPlayerId)
                && count($allPicksByPlayerId[$playerId]) > 0)
            {
                $picks[$playerId] = $template;
            }
        }

        return $picks;
    }

    protected function getPickCountByDriverId(LDS_Race $race)
    {
        $season = $race->getSeason();
        $players = $season->getFantasyPlayers();

        $allPicksByUserId = $race->getFantasyPicks()->getPicksByUserId();

        $pickCounts = array();
        foreach($players as $player)
        {
            if(array_key_exists($player->getId(), $allPicksByUserId))
            {
                $picks = $allPicksByUserId[$player->getId()];
                foreach($picks as $pick)
                {
                    if(!array_key_exists($pick, $pickCounts))
                        $pickCounts[$pick] = 1;
                    else
                        $pickCounts[$pick]++;
                }
            }
        }

        arsort($pickCounts, SORT_NUMERIC);

        return $pickCounts;
    }
}



?>
