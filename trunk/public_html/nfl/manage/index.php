<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/NFLTeam.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class IndexResponder extends NflResponder
{
    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $this->printHeader();

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
        //$this->printSelectorMenu($seasonId, $weekItems, $weekNo);


        ?>
          <style type="text/css">
           <!--
           td
           {
           	   padding: 5px;
           }
           -->
          </style>
         <h3>Week <?php echo $weekNo; ?> Schedule</h3>
          <dl class="header-data">
           <dt>Current Date</dt>
           <dd><?php echo $now->format('l, F j, Y', $timeZone); ?></dd>
           <dt>Current Time</dt>
           <dd><?php echo $now->format('g:i a', $timeZone); ?></dd>
          </dl>
        <?php

        if($weekRow)
        {
            $lastDate = '';

            $color = new RolloverIterator(array('eeeeee', 'dddddd'));

            $teams = $this->getTeams();

            $games = $this->getGames();
            foreach($games as $game)
            {
                $row = &$game;
                $date = new Date($row[1], 0);

                if($date->format('q', $timeZone) != $lastDate)
                {
                    if($lastDate != '')
                    {
                        echo '</table><br />';
                    }
                    ?>
		             <table>
                      <tr>
                       <th colspan="3" style="background-color: #ffffff; "><?php echo $date->format('l, F j, Y', $timeZone); ?></th>
                      </tr>
                    <?php

                    $lastDate = $date->format('q', $timeZone);
                    $lastTime = '';
                }

                if($date->format('g:i a', $timeZone) != $lastTime)
                {
                    $lastTime = $date->format('g:i a', $timeZone);

                    ?>
                     <tr>
                      <th colspan="3" style="text-align: left; "><?php echo $lastTime; ?></th>
                     </tr>
                    <?php
                }
                else
                {
                	?>
                    <tr>
                     <td style="background-color: #000000; padding: 0px; height: 1px; " colspan="3"></td>
                    </tr>
                 	<?php
                }


                 $fontColor = $teams[$row[2]][8];
                 if(trim($fontColor) == '')
                     $fontColor = '#000000';
                 $background = $teams[$row[2]][9];
                 if(trim($background) == '')
                     $background = '#ffffff';
                 $borderColor = $teams[$row[2]][10];
                 if(trim($borderColor) == '')
                     $borderColor = '#000000';

                ?>
                 <tr style="background-color: #<?php echo $color; ?>;<?php echo ($row[4] != '' && $row[5] != '' && $row[4] > $row[5]) ? ' font-weight: bold;' : ''; ?> ">
                 <td style="text-align: center; width: 30px; "><?php echo $row[4]; ?></td>
                 <td style="background: <?php echo $borderColor; ?>; width: 10px; ">&nbsp;</td>
                 <td style="width: 220px; background: <?php echo $background; ?>; color: <?php echo $fontColor; ?>; ">&nbsp;<?php echo $teams[$row[2]][1] . ' ' . $teams[$row[2]][2]; ?>
                  (<?php echo $teams[$row[2]][5] . '-' . $teams[$row[2]][6] . '-' . $teams[$row[2]][7]; ?>)
                 </td>
                 </tr>
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

                 ?>
                 <tr style="background-color: #<?php echo $color; ?>;<?php echo ($row[4] != '' && $row[5] != '' && $row[5] > $row[4]) ? ' font-weight: bold;' : ''; ?> ">
                  <td style="text-align: center; width: 30px;"><?php echo $row[5]; ?></td>
                  <td style="background: <?php echo $borderColor; ?>; width: 10px; ">&nbsp;</td>
                  <td style="background: <?php echo $background; ?>; color: <?php echo $fontColor; ?>; ">
                   &nbsp;<?php echo $teams[$row[3]][1] . ' ' . $teams[$row[3]][2]; ?>
                   (<?php echo $teams[$row[3]][5] . '-' . $teams[$row[3]][6] . '-' . $teams[$row[3]][7]; ?>)
                  </td>
                  </tr>
                 <?php
            }

            echo '</table>';
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
