<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/NFLTeam.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class StandingsResponder extends NFLResponder
{
    public function get($args)
    {
        $data = $this->data;

        $this->printHeader();

        $seasonId = $data->getSeasonId();
        $weekNo = $data->getWeekNumber();

        $weeks = $data->getWeeks();

        $weekRow = $weeks[$weekNo - 1];
        $startDate = new Date($weekRow[1]);
        $endDate = new Date($weekRow[2]);

        ?>
        <?php

        if($weekRow)
        {
            echo '<table style="border: 0; ">';

            $lastDate = '';

            $color = new RolloverIterator(array('eeeeee', 'dddddd'));

            $teams = $data->getTeamsByDivision();

            ?>
             <tr>
              <th style="background-color: #ffffff; " width="49%">AFC</th>
              <th style="background-color: #ffffff; "></th>
              <th style="background-color: #ffffff; " width="49%">NFC</th>
             </tr>
            <?php

            $divisions = array('North', 'East', 'South', 'West');

            if(array_key_exists('AFC', $teams) && array_key_exists('NFC', $teams))
            {
                $color = new RolloverIterator(array('eeeeee', 'dddddd'));
                foreach($divisions as $division)
                {
                    $rowCount = max(count($teams['AFC'][$division])
                        , count($teams['NFC'][$division]));

                    ?>
                     <tr>
                      <td>
                       <div class="table-default">
                       <table>
                        <tr>
                         <th colspan="5"><?php echo $division; ?></th>
                        </tr>
                        <tr>
                         <th width="220" class="right" colspan="2">Team</th>
                         <th width="30">W</th>
                         <th width="30">L</th>
                         <th width="30">T</th>
                        </tr>
                    <?php
                    for($i = 0; $i < $rowCount; $i++)
                    {
                        $afcTeam = $teams['AFC'][$division][$i];
                        $nfcTeam = $teams['NFC'][$division][$i];

                        $rowColor = $color->getValue();

                        ?>
                         <tr style="background-color: #<?php echo $rowColor; ?>; ">
                          <td style="background: <?php echo $afcTeam[10]; ?>; width: 10px; ">&nbsp;</td>
                          <td class="right" style="background: <?php echo $afcTeam[9]; ?>; color: <?php echo $afcTeam[8]; ?>; "><?php echo $afcTeam[1] . ' ' . $afcTeam[2]; ?>&nbsp;</td>
                          <td class="center"><?php echo $afcTeam[5]; ?></td>
                          <td class="center"><?php echo $afcTeam[6]; ?></td>
                          <td class="center"><?php echo $afcTeam[7]; ?></td>
                         </tr>
                        <?php
                    }
                    ?>
                       </table>
                       </div>
                      </td>
                      <td></td>
                      <td>
                       <div class="table-default">
                       <table>
                        <tr>
                         <th colspan="5"><?php echo $division; ?></th>
                        </tr>
                        <tr>
                         <th width="220" class="right" colspan="2">Team</th>
                         <th width="30">W</th>
                         <th width="30">L</th>
                         <th width="30">T</th>
                        </tr>
                    <?php

                    for($i = 0; $i < $rowCount; $i++)
                    {
                        $afcTeam = $teams['AFC'][$division][$i];
                        $nfcTeam = $teams['NFC'][$division][$i];

                        $rowColor = $color->getValue();

                        ?>
                         <tr style="background-color: #<?php echo $rowColor; ?>; ">
                          <td class="right" style="background: <?php echo $nfcTeam[10]; ?>; width: 10px; ">&nbsp;</td>
                          <td class="right" style="background: <?php echo $nfcTeam[9]; ?>; color: <?php echo $nfcTeam[8]; ?>; "><?php echo $nfcTeam[1] . ' ' . $nfcTeam[2]; ?>&nbsp;</td>
                          <td class="center"><?php echo $nfcTeam[5]; ?></td>
                          <td class="center"><?php echo $nfcTeam[6]; ?></td>
                          <td class="center"><?php echo $nfcTeam[7]; ?></td>
                         </tr>
                        <?php
                    }
                    ?>
                       </table>
                       </div>
                      </td>
                     </tr>
                    <?php
                }
            }

            ?>
             </table>
            <?php
        }

        $this->printFooter();
    }
}


?>
