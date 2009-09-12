<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/NFLTeam.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class FantasyPicksResponder extends NFLResponder
{
    private $playerId;


    public function init($args, $cacheDir = null)
    {
        parent::init($args);

        $db = Database::getConnection('com.lightdatasys.nfl');

        $this->input->set('season', IntegerInput::getInstance());
        $this->input->set('week', IntegerInput::getInstance());

        $this->playerId = 0;
        if($this->isLoggedIn())
        {
            $query = new Query('SELECT playerId FROM player_user WHERE userId='
                . IntegerInput::getInstance()->parseValue($this->user->getId()));
            $result = $db->execQuery($query);
            $row = $db->getRow($result);
            if($row)
                $this->playerId = $row[0];
        }
    }

    public function post($args)
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $seasonId = $this->input->get('season');
        $weekNo = $this->input->get('week');

        $this->input->set('game', IntegerInput::getInstance());

        $picks = $this->input->get('game');

        if(is_array($picks))
        {
            $games = IntegerInput::getInstance()->parseValue(array_keys($picks));

            $now = new Date();

            $editable = array();
            $query = new Query('SELECT gameId, gameTime FROM ' . $db->getTable('Game')
                . ' WHERE gameId IN (' . implode(',', $games) . ')');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $date = new Date($row[1]);
                $date->changeHour(-1);

                if($now->compareTo($date) < 0)
                    $editable[$row[0]] = $picks[$row[0]];
            }

            #echo '<pre>'; print_r($editable); exit;
            foreach($editable as $gameId => $pickId)
            {
                $query = new Query('REPLACE INTO ' . $db->getTable('FantPick')
                    . ' (`playerId`, `gameId`, `teamId`) VALUES (' . $this->playerId
                    . ', ' . $gameId . ', ' . $pickId . ')');
                $result = $db->execQuery($query);
            }
        }

        HttpHeader::forwardTo($_SERVER['PHP_SELF'] . '?season=' . $seasonId
            . '&week=' . $weekNo);
    }

    public function get($args)
    {
        $this->printHeader();

        $db = Database::getConnection('com.lightdatasys.nfl');

        $row = null;

        $timeZone = 0;
        if(!is_null($this->user))
            $timeZone = $this->user->getTimeZone();

        $now = new Date();

        $seasonId = $this->getSeasonId();
        $weekNo = $this->getWeekNumber();

        $weeks = $this->getWeeks();

        $weekRow = $weeks[$weekNo - 1];
        $startDate = new Date($weekRow[1]);
        $endDate = new Date($weekRow[2]);

        $this->printWeekSelector();


        ?>
                 <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <?php
        $playersWithPicks = 0;
        $player = array();
        $players = array();
        $query = new Query(/*'(SELECT DISTINCT p.playerId, p.name, p.bgcolor, u.userId, 0 AS count FROM user AS u '
			. 'INNER JOIN player_user AS pu ON u.userId=pu.userId '
			. 'INNER JOIN player AS p ON pu.playerId=p.playerId '
			. 'WHERE u.userId=' . $this->user->getId() . ') '
			. 'UNION (SELECT p.playerId, p.name, p.bgcolor, u.userId, COUNT(fp.gameId) AS count FROM user AS u '
			. 'INNER JOIN player_user AS pu ON u.userId=pu.userId '
			. 'INNER JOIN player AS p ON pu.playerId=p.playerId '
        	. 'INNER JOIN nflFantPick AS fp ON p.playerId=fp.playerId '
           . 'INNER JOIN nflGame AS g ON fp.gameId=g.gameId AND DATE(g.gameTime) BETWEEN \'' . $weekRow[1] . '\' AND \'' . $weekRow[2] . '\' '
			. 'WHERE u.userId=' . $this->user->getId() . ' GROUP BY p.playerId) '
			. 'UNION ('*/
             'SELECT p.playerId, p.name, p.bgcolor, pu.userId, COUNT(fp.gameId) AS count FROM player AS p '
        	. 'INNER JOIN nflFantPick AS fp ON p.playerId=fp.playerId '
            . 'INNER JOIN nflGame AS g ON fp.gameId=g.gameId '
            . 'INNER JOIN player_user AS pu ON pu.playerId=p.playerId '
            . ' WHERE ADDTIME(g.gameTime, \'-08:00:00\') BETWEEN '
            . '\'' . $weekRow[1] . ' 00:00:00\' AND ADDDATE(\'' . $weekRow[2] . ' 23:59:59\', INTERVAL 3 DAY) '
          	. 'GROUP BY p.playerId ORDER BY name');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $players[$row[0]] = $row;
            //if($row[3] == $this->user->getId())
            //    $player = $row;

            if($row[4] > 0)
                $playersWithPicks++;
        }


        //*
        ?>
          <style type="text/css">
           <!--
           td
           {
           	   padding: 5px;
           }
           -->
          </style>
          <h3>
            Fantasy Picks for Week <?php echo $weekNo; ?>
          </h3>
          <dl class="header-data">
           <dt>Current Date</dt>
           <dd><?php echo $now->format('l, F j, Y', $timeZone); ?></dd>
           <dt>Current Time</dt>
           <dd><?php echo $now->format('g:i a', $timeZone); ?></dd>
          </dl>
        <?php
$currPlayer = null;
        if($weekRow)
        {
            $lastDate = null;
            $lastTime = '';

            $color = new RolloverIterator(array('eeeeee', 'dddddd'));
            $nColor = new RolloverIterator(array('ffcccc', 'eebbbb'));

            $picks = array();
            $query = new Query('SELECT g.gameId, fp.playerId, teamId FROM nflFantPick AS fp'
                . ' INNER JOIN nflGame AS g ON fp.gameId=g.gameId'
                . ' INNER JOIN player AS p ON fp.playerId=p.playerId'
                . ' WHERE ADDTIME(gameTime, \'-08:00:00\') BETWEEN '
                . '\'' . $weekRow[1] . ' 00:00:00\' AND ADDDATE(\'' . $weekRow[2] . ' 23:59:59\', INTERVAL 3 DAY) '
                . ' ORDER BY name ASC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $picks[$row[0]][$row[2]][] = $row[1];
            }

            $teams = $this->getTeams();

            $query = new Query('SELECT '
                . 'g.gameId, gameTime, awayId, homeId, awayScore, homeScore, fp.teamId AS pickId '
                . 'FROM ' . $db->getTable('Game') . ' AS g '
                . 'LEFT JOIN ' . $db->getTable('FantPick') . ' AS fp '
                . 'ON g.gameId=fp.gameId AND fp.playerId=' . $this->playerId
                . ' WHERE ADDTIME(gameTime, \'-08:00:00\') BETWEEN '
                . '\'' . $weekRow[1] . ' 00:00:00\' AND ADDDATE(\'' . $weekRow[2] . ' 23:59:59\', INTERVAL 3 DAY) '
                . 'ORDER BY gameTime');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $date = new Date($row[1], 0);
                $date->changeHour(-1);

                if(is_null($lastDate) || $date->format('q', $timeZone) != $lastDate->format('q', $timeZone))
                {
                    if($lastDate != '')
                    {
                        echo '</table><br />';
                        if(isset($lastDate) && !is_null($lastDate) && $lastDate->compareTo(new Date()) > 0)
                        {
                            echo '<div class="selector">';
                            if($this->isLoggedIn())
                            {
                                ?>
                                  <dl>
                                   <dt>
                                    <input type="submit" class="submit" name="save_picks" value="Save" />
                                    <input type="hidden" name="week" value="<?php echo $weekNo; ?>" />
                                    <em style="color: #cc0000; font-weight: bold; ">Save your picks!</em>
                                   </dt>
                                  </dl>
                                <?php
                            }
                            else
                            {
                                ?>
                                  <dl>
                                   <dt>
                                    Sign in to save your picks.
                                   </dt>
                                  </dl>
                                <?php
                            }
                            echo '<br style="clear: both; " />';
                            echo '</div>';
                            echo '<br />';
                        }
                    }
                    ?>
		             <table style="">
                      <tr>
                       <th colspan="<?php echo 3 + count($players); ?>" style="background-color: #ffffff; "><?php echo $date->format('l, F j, Y', $timeZone); ?></th>
                      </tr>
                    <?php

                    $lastDate = $date;
                    $lastTime = '';
                }

                if($date->format('g:i a', $timeZone) != $lastTime)
                {
                    $lastTime = $date->format('g:i a', $timeZone);

                    ?>
                     <tr>
                      <th colspan="<?php echo 3 + count($players); ?>" style="text-align: left; "><?php echo $lastTime; ?></th>
                     </tr>
                    <?php
                }
                else
                {
                	?>
                    <tr>
                     <td style="background-color: #000000; padding: 0px; height: 1px; " colspan="<?php echo 3 + $playersWithPicks; ?>"></td>
                    </tr>
                 	<?php
                }

                $notPicked = false;
                if($now->compareTo($date) < 0 && $row[6] != $row[2] && $row[6] != $row[3])
                    $notPicked = true;

                ?>
                 <tr style="background-color: #<?php echo $color; ?>; ">
                <?php

                 $fontColor = $teams[$row[2]][8];
                 if(trim($fontColor) == '')
                     $fontColor = '#000000';
                 $background = $teams[$row[2]][9];
                 if(trim($background) == '')
                     $background = '#ffffff';
                 $borderColor = $teams[$row[2]][10];
                 if(trim($borderColor) == '')
                     $borderColor = '#000000';

                if($this->isLoggedIn() && $now->compareTo($date) < 0)
                {
                    $awaySelected = '';
                    if($row[6] == $row[2])
                        $awaySelected = ' checked="checked"';

                    ?>
                     <td style="width: 40px; ">&nbsp;<input type="radio" name="game[<?php echo $row[0]; ?>]" value="<?php echo $row[2]; ?>" id="game<?php echo $row[0]; ?>_team<?php echo $row[2]; ?>"<?php echo $awaySelected; ?> />&nbsp;</td>
                    <?php
                }
                else
                {
                    $style = '';
                    $value = '';
                    if($row[4] == '' || $row[5] == '')
                    {
                        $style = ' style="width: 30px; "';
                        if($row[6] == $row[2])
                            $value = 'x';
                        else
                            $value = '';
                    }
                    else if($row[4] >= $row[5])
                    {
                        if($row[6] == $row[2])
                            $style = ' style="color: #ffffff; background-color: #00cc00; width: 30px; "';
                        else
                            $style = ' style="color: #009900; width: 30px; "';
                        $value = $weekRow[3];
                    }
                    else
                    {
                        if($row[6] == $row[2])
                            $style = ' style="color: #ffffff; background-color: #cc0000; width: 30px; "';
                        else
                            $style = ' style="color: #990000; width: 30px; "';
                        $value = '0';
                    }
                    ?>
                     <td class="center"<?php echo $style; ?>>&nbsp;<?php echo $value; ?>&nbsp;</td>
                    <?php
                }

                ?>
                 <td style="background: <?php echo $borderColor; ?>; width: 10px; ">&nbsp;</td>
                 <td style="width: 220px; background: <?php echo $background; ?>; color: <?php echo $fontColor; ?>; ">
                  <label for="game<?php echo $row[0]; ?>_team<?php echo $row[2]; ?>">
                   &nbsp;<?php echo $teams[$row[2]][1] . ' ' . $teams[$row[2]][2]; ?>
                   (<?php echo $teams[$row[2]][5] . '-' . $teams[$row[2]][6] . '-' . $teams[$row[2]][7]; ?>)
                  </label>
                 </td>
                <?php


                if($date->compareTo($now) < 0)
                {
                    //if(array_key_exists($row[2], $picks))
                    //{
                    	foreach($players as $playerId => $player)
                    	{
                            $name = explode(' ', $player[1]);
                            $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));

                            if(array_key_exists($row[0], $picks) && array_key_exists($row[2], $picks[$row[0]]) && in_array($playerId, $picks[$row[0]][$row[2]]))
                            {
                                if($row[4] != '' && $row[5] != '' && $row[4] < $row[5])
                                    echo '<td style="color: #' . $player[2] . '; width: 20px; text-align: center; " title="' . $player[1] . '">' . $initials . '</td>';
                                else
                                    echo '<td style="color: #ffffff; background-color: #' . $player[2] . '; width: 20px; text-align: center; " title="' . $player[1] . '">' . $initials . '</td>';
                            }
                            else if($this->isLoggedIn() && $playerId == $currPlayer[0])
	                        	echo '<td style="background-color: #ffffff; width: 20px; text-align: center; ">&nbsp;</td>';
                            else
                        		echo '<td style="width: 20px; text-align: center; ">&nbsp;</td>';
                        }
                    //}
                }
                else
                {
                    	foreach($players as $playerId => $player)
                    	{
                            $name = explode(' ', $player[1]);
                            $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));

                          //  if(array_key_exists($row[2], $picks) && in_array($playerId, $picks[$row[2]]))
                            if($player[4] > 0)
                            {
                                if(array_key_exists($row[0], $picks) && ((array_key_exists($row[2], $picks[$row[0]]) && in_array($playerId, $picks[$row[0]][$row[2]]))
                                    || (array_key_exists($row[3], $picks[$row[0]]) && in_array($playerId, $picks[$row[0]][$row[3]]))))
                                {
                                    if($this->isLoggedIn() && $playerId == $currPlayer[0])
                                    {
                                        if(array_key_exists($row[2], $picks[$row[0]]) && in_array($playerId, $picks[$row[0]][$row[2]]))
                                            echo '<td style="color: #ffffff; background-color: #' . $player[2] . '; width: 20px; text-align: center; " title="' . $player[1] . '">' . $initials . '</td>';
                                        else
                                            echo '<td style="background-color: #ffffff; width: 20px; text-align: center; ">&nbsp;</td>';
                                    }
                                    else
                                        echo '<td style="color: #ffffff; background-color: #' . $player[2] . '; width: 20px; text-align: center; " rowspan="2" title="' . $player[1] . '">' . $initials . '</td>';
                                }
                                else if($this->isLoggedIn() && $playerId == $currPlayer[0])
                                    echo '<td style="background-color: #ffffff; width: 20px; text-align: center; " rowspan="2">&nbsp;</td>';
                                else
                                    echo '<td style="width: 20px; text-align: center; " rowspan="2">&nbsp;</td>';
                            }
	                        /*else if($playerId == $currPlayer[0])
	                        	echo '<td style="background-color: #ffffff; width: 20px; text-align: center; ">&nbsp;</td>';
                        	else
                        		echo '<td style="width: 20px; text-align: center; ">&nbsp;</td>';*/
                        }
                }


                ?>
                 </tr>
                 <tr style="background-color: #<?php echo $color; ?>; ">
                <?php

                 $fontColor = $teams[$row[3]][8];
                 if(trim($fontColor) == '')
                     $fontColor = '#000000';
                 $background = $teams[$row[3]][9];
                 if(trim($background) == '')
                     $background = '#ffffff';
                 $borderColor = $teams[$row[3]][10];
                 if(trim($borderColor) == '')
                     $borderColor = '#000000';

                if($this->isLoggedIn() && $now->compareTo($date) < 0)
                {
                    $homeSelected = '';
                    if($row[6] == $row[3])
                        $homeSelected = ' checked="checked"';


                    ?>
                     <td style="width: 40px; ">&nbsp;<input type="radio" name="game[<?php echo $row[0]; ?>]" value="<?php echo $row[3]; ?>" id="game<?php echo $row[0]; ?>_team<?php echo $row[3]; ?>"<?php echo $homeSelected; ?> />&nbsp;</td>
                    <?php
                }
                else
                {
                    $style = '';
                    $value = '';
                    if($row[4] == '' || $row[5] == '')
                    {
                        $style = ' style="width: 30px; "';
                        if($row[6] == $row[3])
                            $value = 'x';
                        else
                            $value = '';
                    }
                    else if($row[5] >= $row[4])
                    {
                        if($row[6] == $row[3])
                            $style = ' style="color: #ffffff; background-color: #00cc00; width: 30px; "';
                        else
                            $style = ' style="color: #009900; width: 30px; "';
                        $value = $weekRow[3];
                    }
                    else
                    {
                        if($row[6] == $row[3])
                            $style = ' style="color: #ffffff; background-color: #cc0000; width: 30px; "';
                        else
                            $style = ' style="color: #990000; width: 30px; "';
                        $value = '0';
                    }
                    ?>
                     <td class="center"<?php echo $style; ?>>&nbsp;<?php echo $value; ?>&nbsp;</td>
                    <?php
                }

                ?>
                  <td style="background: <?php echo $borderColor; ?>; width: 10px; ">&nbsp;</td>
                  <td style="background: <?php echo $background; ?>; color: <?php echo $fontColor; ?>; ">
                   <label for="game<?php echo $row[0]; ?>_team<?php echo $row[3]; ?>">
                    &nbsp;<?php echo $teams[$row[3]][1] . ' ' . $teams[$row[3]][2]; ?>
                    (<?php echo $teams[$row[3]][5] . '-' . $teams[$row[3]][6] . '-' . $teams[$row[3]][7]; ?>)
                   </label>
                  </td>
                <?php

                if($now->compareTo($date) < 0)
                {
                    if($this->isLoggedIn())
                    {
                        $name = explode(' ', $currPlayer[1]);
                        $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));

                      //  if(array_key_exists($row[2], $picks) && in_array($playerId, $picks[$row[2]]))
                        if($currPlayer[4] > 0)
                        {
                            if(array_key_exists($row[0], $picks) && (((array_key_exists($row[2], $picks[$row[0]]) && in_array($currPlayer[0], $picks[$row[0]][$row[2]]))
                                || (array_key_exists($row[3], $picks[$row[0]]) && in_array($currPlayer[0], $picks[$row[0]][$row[3]])))))
                            {
                                if(array_key_exists($row[3], $picks[$row[0]]) && in_array($currPlayer[0], $picks[$row[0]][$row[3]]))
                                    echo '<td style="color: #ffffff; background-color: #' . $currPlayer[2] . '; width: 20px; text-align: center; " title="' . $currPlayer[1] . '">' . $initials . '</td>';
                                else
                                    echo '<td style="background-color: #ffffff; width: 20px; text-align: center; ">&nbsp;</td>';
                            }
                        }
                    }
                }
                else
                {
                    //if(array_key_exists($row[3], $picks))
                    //{
                    	foreach($players as $playerId => $player)
                    	{
                            $name = explode(' ', $player[1]);
                            $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));

                            if(array_key_exists($row[0], $picks) && array_key_exists($row[3], $picks[$row[0]]) && in_array($playerId, $picks[$row[0]][$row[3]]))
                            {
                                if($row[4] != '' && $row[5] != '' && $row[4] > $row[5])
                                    echo '<td style="color: #' . $player[2] . '; width: 20px; text-align: center; " title="' . $player[1] . '">' . $initials . '</td>';
                                else
                                    echo '<td style="color: #ffffff; background-color: #' . $player[2] . '; width: 20px; text-align: center; " title="' . $player[1] . '">' . $initials . '</td>';
	                        }
                            else if($this->isLoggedIn() && $playerId == $currPlayer[0])
	                        	echo '<td style="background-color: #ffffff; width: 20px; text-align: center; ">&nbsp;</td>';
                        	else
                        		echo '<td style="width: 20px; text-align: center; ">&nbsp;</td>';
                        }
                    //}
                }

                ?>
                 </tr>
                <?php
            }

            //*
            echo '</table>';
            echo '<br />';
            echo '<div class="selector">';

            if(isset($date) && !is_null($date) && $date->compareTo(new Date()) > 0)
            {
                if($this->isLoggedIn())
                {
                    ?>
                      <dl>
                       <dt>
                        <input type="submit" class="submit" name="save_picks" value="Save" />
                        <input type="hidden" name="week" value="<?php echo $weekNo; ?>" />
                        <em style="color: #cc0000; font-weight: bold; ">Save your picks!</em>
                       </dt>
                      </dl>
                    <?php
                }
                else
                {
                    ?>
                      <dl>
                       <dt>
                        Sign in to save your picks.
                       </dt>
                      </dl>
                    <?php
                }
            }
            else
            {
                ?>
                  <dl>
                   <dt>
                    <em style="font-weight: bold; ">This week is complete.</em>
                   </dt>
                  </dl>

                <?php
            }
            echo '<br style="clear: both; " />';
            echo '</div>';
	    //*/
            ?>
             </form>
            <?php
        }
        else
        {
            echo 'No upcoming week found';
        }
        echo '<br />';

        $this->printFooter();
    }
}


?>
