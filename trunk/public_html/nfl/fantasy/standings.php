<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/NFLTeam.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class StandingsResponder extends NFLResponder
{
    public function getPageTitle()
    {
        $data = $this->data;

        return parent::getPageTitle() . ' as of Week ' . $data->getWeekNumber();
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $data = $this->data;

        $this->printHeader();

        $seasonId = $data->getSeasonId();
        $weekNo = $data->getWeekNumber();

        $weeks = $data->getWeeks();

        $weekRow = $weeks[$weekNo - 1];
        $startDate = new Date($weekRow[1]);
        $endDate = new Date($weekRow[2]);

        $playerIds = array();
        $weeklyResults = array();
        $weeklyPicks = array();
        $query = new Query('SELECT p.playerId, w.weekId, '
            . 'SUM(IF((fp.teamId=g.homeId AND g.homeScore>g.awayScore) OR ('
            . 'fp.teamId=g.awayId AND g.awayScore>=g.homeScore), winWeight, 0)) AS points, '
            . 'COUNT(winWeight) AS potentialPoints '
            . 'FROM player AS p LEFT JOIN nflFantPick AS fp ON p.playerId=fp.playerId '
            . 'LEFT JOIN nflGame AS g ON fp.gameId=g.gameId '
            . 'LEFT JOIN nflWeek AS w ON g.weekId=w.weekId '
            . ' WHERE w.seasonId=' . $seasonId
            . ' GROUP BY g.weekId, p.playerId');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $weeklyResults[$row[1]][$row[0]] = $row[2];
            $weeklyPicks[$row[1]][$row[0]] = $row[3];
            $playerIds[$row[0]] = $row[0];
        }

        $players = array();
        if(count($playerIds) > 0)
        {
            $query = new Query('SELECT p.playerId, p.name, '
                . 'SUM(IF((fp.teamId=g.homeId AND g.homeScore>g.awayScore) OR ('
                . 'fp.teamId=g.awayId AND g.awayScore>=g.homeScore), winWeight, 0)) AS points, SUM(IF(g.awayScore>0 OR g.homeScore>0, winWeight, 0)) AS pointsPlayed, p.bgcolor '
                . 'FROM player AS p LEFT JOIN nflFantPick AS fp ON p.playerId=fp.playerId '
                . 'LEFT JOIN nflGame AS g ON fp.gameId=g.gameId '
                . 'LEFT JOIN nflWeek AS w ON g.weekId=w.weekId '
                . 'AND DATE(g.gameTime)<=\'' . $endDate->format('q') . '\' '
                . 'WHERE p.playerId IN (' . implode(',', $playerIds)
                . ')'
                //. ' AND DATE(g.gameTime)<=\'' . Date::now('q', 0) . '\''
                . ' AND seasonId=' . $seasonId . ' GROUP BY p.playerId ORDER BY points DESC, name ASC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $players[$row[0]] = $row;
            }
        }

        $now = new Date();

        $weekItems = array();
        $i = 1;
        $totalGames = 0;
        $totalPlayed = 0;
        $points = array();
		$maxPoints = array();
        $currWeek = 0;
        $weeks = array();
        $limit = '';
        if($weekNo > 0)
            $limit = ' LIMIT 0, ' . $weekNo;
        $query = new Query('SELECT w.weekId, weekStart, weekEnd, '
            . 'COUNT(g.gameId)*winWeight AS games, SUM(IF(awayScore IS NOT NULL AND homeScore IS NOT NULL, winWeight, 0)) AS gamesPlayed FROM ' . $db->getTable('Week') . ' AS w '
            . 'LEFT JOIN ' . $db->getTable('Game') . ' AS g '
            . 'ON DATE(gameTime) BETWEEN weekStart AND weekEnd WHERE seasonId=' . $seasonId
            . ' GROUP BY w.weekId ORDER BY weekStart ASC' . $limit);
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $weeks[] = $row;
            $totalGames += $row[3];
            $totalPlayed += $row[4];

            if(array_key_exists($row[0], $weeklyResults))
            {
                $points[] = $weeklyResults[$row[0]];
                $maxPoints[] = max($weeklyResults[$row[0]]);
                $minPoints[] = min($weeklyResults[$row[0]]);
            }
            else
            {
                $points[] = array();
                $maxPoints[] = 100;
                $minPoints[] = 0;
            }

            $weekStart = new Date($row[1]);
            if($weekStart->compareTo($now) < 0)
                $currWeek++;

            $weekItems[$i] = $i;
            $i++;
        }

        if($weekNo < 1 || !(1 <= $weekNo && $weekNo <= count($weeks)))
            $weekNo = max($currWeek, 1);

        $weekRow = $weeks[$weekNo - 1];
        $startDate = new Date($weekRow[1]);
        $endDate = new Date($weekRow[2]);


        ?>
         <div class="table-default">
          <table>
           <tr>
            <th rowspan="2">Rank</th>
            <th colspan="2" rowspan="2">Player</th>
            <th colspan="<?php echo count($weeks); ?>">Week</th>
            <th rowspan="2">Total</th>
            <th rowspan="2">%</th>
            <th rowspan="<?php echo 2 + count($players); ?>"></th>
            <th rowspan="2">Weeks<br />Won</th>
            <th rowspan="<?php echo 2 + count($players); ?>"></th>
            <th rowspan="2">GP</th>
            <th rowspan="2">Weighted<br />%</th>
           </tr>
           <tr>
        <?php

        foreach($weeks as $key => $week)
        {
            ?>
             <th style="width: 12px; "><?php echo $key + 1; ?></th>
            <?php
        }

        ?>
           </tr>
        <?php

        $color = new RolloverIterator(array('eeeeee', 'dddddd'));

        $rank = 0;
        $currPoint = -1;
        $skip = 0;
        foreach($players as $playerId => $player)
        {
			$weeksWon = 0;

            if($player[2] == $currPoint)
            {
                $skip++;
            }
            else
            {
                $rank += $skip + 1;
                $currPoint = $player[2];
                $skip = 0;
            }

            $name = explode(' ', $player[1]);
            $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));

            ?>
             <tr class="center" style="background-color: #<?php echo $color; ?>; ">
              <td class="center" style="width: 45px; "><?php echo $rank; ?></td>
              <td class="right" style="width: 65px; "><?php echo $name[0]; ?></td>
              <td class="center" style="color: #ffffff; background-color: #<?php echo $player[4]; ?>; "><?php echo $initials; ?></td>
            <?php
            $total = 0;
            $firstIncomplete = true;
            foreach($points as $key => $point)
            {

                if(array_key_exists($playerId, $point))
                {
                    if($maxPoints[$key] == $point[$playerId]
                        && $point[$playerId] != 0)
                        $weeksWon++;

                    $fontColor = hexdec('BB');
                    if($maxPoints[$key] - $minPoints[$key] == 0)
                        $fontColor = '000000';
                    else
                        $fontColor = max(0, intval($fontColor * (1 - (($point[$playerId] - $minPoints[$key]) / ($maxPoints[$key] - $minPoints[$key])))));
                    $fontColor = sprintf('%1$02X%1$02X%1$02X', $fontColor);

                    $style = 'color: #' . $fontColor . '; ';
                    if($maxPoints[$key] == $point[$playerId]
                            && $point[$playerId] != 0)
                        $style .= 'background-color: #ffff99; ';

                    echo '<td title="' . ($weeks[$key][4] == 0 ? '-' : (round(100 * $point[$playerId] / $weeks[$key][4], 1) . '%')) . '" style="' . $style . ' ">';
                    if($weekNo == $key + 1 && $weeks[$key][3] != $weeks[$key][4] && $point[$playerId] <= 0)
                    {
                        echo '(' . $weeklyPicks[$weeks[$key][0]][$playerId] . ')';
                    }
                    else
                    {
                        echo $point[$playerId];
                    }
                    //echo ' : ' .
                    echo '</td>';
                    $total += $point[$playerId];
                }
                else
                    echo '<td>&nbsp;</td>';
            }
            ?>
              <td class="center"><?php echo $total; ?></td>
              <td class="center"><?php echo $totalPlayed == 0 ? 0 : round(100 * $total / $totalPlayed, 1); ?>%</td>
              <td class="center"><?php echo $weeksWon; ?></td>
              <td class="center"><?php echo $player[3]; ?></td>
              <td class="center"><?php echo round(100 * $total / $player[3], 1); ?>%</td>
             </tr>
            <?php
        }

        ?>
          <tr style="background-color: #cccccc; ">
           <td colspan="<?php echo count($weeks) + 10; ?>"></td>
          </tr>
          <tr style="background-color: #<?php echo $color; ?>; ">
           <th class="right" colspan="3">Points</th>
        <?php

        foreach($weeks as $key => $week)
        {
            ?>
             <td class="center" style="width: 12px; "><?php echo $week[3]; ?></td>
            <?php
        }

        ?>
           <td class="center"><?php echo $totalGames; ?></td>
           <td colspan="6"></td>
          </tr>
          <tr style="background-color: #<?php echo $color; ?>; ">
           <th class="right" colspan="3">Played</th>
        <?php

        foreach($weeks as $key => $week)
        {
            ?>
             <td class="center" style="width: 12px; "><?php echo $week[4]; ?></td>
            <?php
        }

        ?>
           <td class="center"><?php echo $totalPlayed; ?></td>
           <td colspan="6"></td>
          </tr>
         </table>
         </div>
          <br style="clear: both; " />
        <?php


        $this->printFooter();
    }
}


?>
