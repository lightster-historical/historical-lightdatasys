<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/NFLTeam.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class ScheduleResponder extends NflResponder
{
    public function get($args)
    {
        $data = $this->data;

        $now = new Date();

        $seasonId = $data->getSeasonId();
        $weekNo = $data->getWeekNumber();

        $weeks = $data->getWeeks();

        $weekRow = $weeks[$weekNo - 1];
        $startDate = new Date($weekRow[1]);
        $endDate = new Date($weekRow[2]);


        $this->printHeader();

        //$this->printSelectorMenu($seasonId, $weekItems, $weekNo);


        if($weekRow)
        {
            $lastDate = '';

            $color = new RolloverIterator(array('eeeeee', 'dddddd'));

            $teams = $data->getTeams();

            $games = $data->getGames();
            foreach($games as $game)
            {
                $row = &$game;
                $date = new Date($row[1], 0);

                if($this->getDate($date, 'q') != $lastDate)
                {
                    if($lastDate != '')
                    {
                        echo '</table></div>';
                        echo '<br class="clear" />';
                    }
                    ?>
                    <div class="table-default" style="margin-bottom: 3px; ">
		             <table>
                      <tr>
                       <th colspan="3" style="background-color: #ffffff; "><?php echo $this->printDate($date, 'l, F j, Y'); ?></th>
                      </tr>
                    <?php

                    $lastDate = $this->getDate($date, 'q');
                    $lastTime = '';
                }

                if($this->getDate($date, 'g:i a', false) != $lastTime)
                {
                    $lastTime = $this->getDate($date, 'g:i a');

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
                      <td colspan="3" style="background-color: #999999; height: 3px; font-size: 1px; padding: 0; margin: 0; "></td>
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
            echo '</div>';
        }
        else
        {
            echo 'No upcoming week found';
        }
        echo '<br class="clear" />';

        $this->printFooter();
    }
}


?>
