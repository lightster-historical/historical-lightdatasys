<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyResults.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPlayer.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class StandingsResponder extends NascarResponder
{
    protected $last5Weeks;
    protected $beforeLast5Weeks;



    public function getPageTitle()
    {
        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        return $season->getYear() . ' ' . $series->getName()
            . ' Fantasy Standings as of ' . $race->getName();
    }


    public function get($args)
    {
        $currRace = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();
        $races = $season->getRaces();

        $this->printHeader();

        $users = $season->getFantasyPlayers();
        if(count($users) > 0)
        {
            $raceCount = 5;

            $startNo = $currRace->getNumber() - $raceCount;
            $endNo = $startNo + $raceCount;
            if($startNo <= 2)
                $startNo = 1;

            $fantasyResults = $currRace->getFantasyResults();

            $totalDriverPoints = $fantasyResults->getTotalDriverPoints();
            $maxDriverPoints = $fantasyResults->getMaxDriverPoints();
            $minDriverPoints = $fantasyResults->getMinDriverPoints();

            $totalFantasyPoints = $fantasyResults->getTotalFantasyPoints();
            $maxFantasyPoints = $fantasyResults->getMaxFantasyPoints();
            $minFantasyPoints = $fantasyResults->getMinFantasyPoints();

            $raceColumnCount = min(count($races), (($startNo > 1 ? 1 : 0) + $endNo - $startNo + 1));

            ?>
             <dl class="header-data">
              <dt>Season</dt>
              <dd><?php echo $season->getYear(); ?> <?php echo $series->getName(); ?></dd>
              <dt>Race</dt>
              <dd><?php echo $currRace->getNumber() . ' &ndash; ' . $currRace->getName(); ?></dd>
              <dt>Track</dt>
              <dd><?php echo $currRace->getTrack()->getName(); ?></dd>
              <dt>Date</dt>
              <dd><?php echo $this->printDate($currRace->getDate(), 'l, F j, Y, g:i a'); ?></dd>
             </dl>
             <div class="table-default center">
              <table id="standings-table">
               <tr>
                <th rowspan="2">Rank</th>
                <th colspan="2" rowspan="2">Player</th>
                <th colspan="<?php echo $raceColumnCount; ?>">Race</th>
                <th rowspan="2">Total</th>
                <th rowspan="2">Behind<br />Leader</th>
                <th rowspan="2">Behind<br />Next</th>
                <th rowspan="2">Weeks<br />Won</th>
                <th colspan="5" rowspan="1">Driver Points</th>
               </tr>
               <tr>
            <?php
            for($i = 1; $i <= count($races); $i++)
            {
                if($startNo <= $i && $i <= $endNo)
                {
                    ?>
                     <th style="width: 12px; "><?php echo $i; ?></th>
                    <?php
                }
                else if($startNo - 1 == $i)
                {
                    ?>
                     <th>1&ndash;<?php echo $i; ?></th>
                    <?php
                }
            }
            ?>
               <th>800+</th>
               <th>750+</th>
               <th>700+</th>
               <th>Max</th>
               <th>Total</th>
              </tr>
            <?php

            $fantasyLeader = null;
            $prev = null;
            $showedEliminated = false;
            $ranker = new MXT_Ranker();
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            foreach($totalFantasyPoints as $userId => $totalFantasyPoint)
            {
                $user = $users[$userId];

                if(is_null($fantasyLeader))
                    $fantasyLeader = $user;

                $plus700 = 0;
                $plus750 = 0;
                $plus800 = 0;
                $maxDriver = 0;

                $rank = $ranker->getRank($totalFantasyPoint);

                $behindLeader = $totalFantasyPoint - $totalFantasyPoints[$fantasyLeader->getId()];
                if(!array_key_exists($user->getId(), $totalDriverPoints))
                    $totalDriverPoints[$user->getId()] = 0;

                $driverDiff = $totalDriverPoints[$user->getId()] - $totalDriverPoints[$fantasyLeader->getId()];

                if(is_null($prev))
                    $behindPrev = 0;
                else
                    $behindPrev = $totalFantasyPoint - $totalFantasyPoints[$prev->getId()];

                $weeksWon = 0;

                //*
                if(!$showedEliminated
                    && (max($maxFantasyPoints) - min($minFantasyPoints)) * ($season->getRaceCount() - $currRace->getNumber()) < -$behindLeader)
                {
                    ?>
                     <tr>
                      <th colspan="<?php echo $raceColumnCount + 12; ?>" style="text-align: center; background-color: #cccccc; font-size: .7em; font-weight: bold; font-variant: small-caps; ">
                       v v v &nbsp;&nbsp;&nbsp; Mathematically Eliminated &nbsp;&nbsp;&nbsp; v v v
                      </th>
                     </tr>
                    <?php

                    $showedEliminated = true;
                }
                //*/

                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                  <td style="width: 45px; "><?php echo $rank; ?></td>
                <?php
                $user->printCellSet();

                $subTotal = 0;
                $total = 0;
                $races = $season->getRaces();
                foreach($races as $race)
                {
                    $raceNum = $race->getNumber() - 1;
                    $raceId = $race->getId();

                    $fantasyPoints = $fantasyResults->getFantasyPointsUsingRaceAndPlayer($race, $user);
                    $driverPoints = $fantasyResults->getDriverPointsUsingRaceAndPlayer($race, $user);

                    if($raceNum < $currRace->getNumber())
                    {
                        if($driverPoints >= 700)
                            $plus700++;
                        if($driverPoints >= 750)
                            $plus750++;
                        if($driverPoints >= 800)
                            $plus800++;

                        $maxDriver = max($maxDriver, $driverPoints);
                    }

                    if($startNo <= $raceNum + 1 && $raceNum + 1 <= $endNo)
                    {
                        $total += $subTotal;
                        $subTotal = 0;

                        if(!is_null($fantasyPoints))
                        {
                            if($maxFantasyPoints[$raceId] == $fantasyPoints
                                && $fantasyPoints != 0)
                                $weeksWon++;

                            $fontColor = hexdec('BB');
                            if($maxFantasyPoints[$raceId] - $minFantasyPoints[$raceId] == 0)
                                $fontColor = '000000';
                            else
                                $fontColor = sprintf('%1$02X%1$02X%1$02X'
                                    , max(0, intval($fontColor * (1 - (($driverPoints - $minDriverPoints[$raceId]) / ($maxDriverPoints[$raceId] - $minDriverPoints[$raceId]))))));

                            $style = 'color: #' . $fontColor . '; ';
                            if($maxFantasyPoints[$raceId] == $fantasyPoints
                                && $fantasyPoints != 0)
                                $style .= 'background: #ffff99; ';

                            echo "<td style=\"$style\" title=\"$driverPoints\">$fantasyPoints</td>";
                            $total += $fantasyPoints;
                        }
                        else
                            echo '<td>&nbsp;</td>';
                    }
                    else if($startNo - 1 == $raceNum + 1)
                    {
                        if(!is_null($fantasyPoints))
                        {
                            if($maxFantasyPoints[$raceId] == $fantasyPoints
                                && $fantasyPoints != 0)
                                $weeksWon++;

                            $subTotal += $fantasyPoints;
                        }

                        ?>
                         <td><?php echo $subTotal; ?></td>
                        <?php
                    }
                    else if($raceNum + 1 == $endNo + 1)
                    {
                        //echo '<td>&nbsp;</td>';
                    }
                    else if($raceNum + 1 < $startNo || $endNo < $raceNum + 1)
                    {
                        if(!is_null($fantasyPoints))
                        {
                            if($maxFantasyPoints[$raceId] == $fantasyPoints
                                && $fantasyPoints != 0)
                                $weeksWon++;

                            $subTotal += $fantasyPoints;
                        }
                    }
                }

                ?>
                  <td><?php echo $fantasyResults->getTotalFantasyPointsUsingPlayer($user); ?></td>
                  <td><?php echo $behindLeader == 0 ? '--' : $behindLeader; ?></td>
                  <td><?php echo $behindPrev == 0 ? '--' : $behindPrev; ?></td>
                  <td><?php echo $weeksWon; ?></td>
                  <td><?php echo $plus800; ?></td>
                  <td><?php echo $plus750; ?></td>
                  <td><?php echo $plus700; ?></td>
                  <td><?php echo $maxDriver; ?></td>
                  <td><?php echo $fantasyResults->getTotalDriverPointsUsingPlayer($user); ?></td>
                 </tr>
                <?php

                $prev = $user;
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
              Fantasy standings have not yet been posted. Please check back after the first race.
             </div>
            <?php
        }

        $this->printFooter();
    }
}



?>
