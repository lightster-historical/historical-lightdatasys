<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/mephex/aggregator/AggDatabaseItem.php';
require_once PATH_LIB . 'com/mephex/core/DateRange.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class IndexResponder extends NflResponder
{
    public function getPageTitle()
    {
        $data = $this->data;

        return 'Week ' . $data->getWeekNumber();
    }


    public function get($args, $cacheDir = null)
    {
        $db = Database::getConnection('com.lightdatasys.nfl');
        $data = $this->data;

        $this->printHeader();

        $now = new Date();

        //$raceId = $this->getRaceId();
        //$raceDate = $this->getRaceDate();
        $date = new Date();

        $dueDate = new Date($date);
        $dueDate->changeHour(-1);
        $dueDateSoon = new Date($dueDate);
        $dueDateSoon->changeHour(-24);

        //$fantasyPicks = $this->getFantasyPicks();
        $pickStatus = false;
        /*if(array_key_exists($raceId, $fantasyPicks)
            && array_key_exists($this->user->getId(), $fantasyPicks[$raceId]))
            $pickStatus = true;*/

        $items = AggDatabaseItem::getItems('nfl', 5);//, 0, new DateRange(null, $date));

        ?>
         <div id="nascar-sidebar" style="width: 25%; float: right; padding: 0; margin: 0; border: 0px dashed #000000; ">
          <div id="nascar-notify-picks">
           <h3>Fantasy Picks</h3>
        <?php
        $picksColor = '#ffffcc';
        if($pickStatus)
            $picksColor = '#ccffcc';
        else if($now->compareTo($dueDateSoon) <= 0)
            $picksColor = '#ffcccc';
        ?>
           <div style="font-size: .9em; border: 1px solid #000000; padding: 0; background: <?php echo $picksColor; ?>; ">
            <div style="padding: 5px; ">
             Due by <em><?php echo $this->getDate($dueDate, 'M j, Y, g:i a'); ?></em><br />
             <a href=""><?php echo ($pickStatus ? 'Change' : 'Make'); ?> your picks</a>
            </div>
           </div>
          </div>
        <?php

        if(count($items) > 0)
        {
            ?>
             <div id="nfl-news">
              <h3>News</h3>
              <div style="font-size: .9em; border: 1px solid #000000; padding: 0; ">
               <ul style="list-style-type: none; margin: 5px; padding: 0; ">
            <?php
            foreach($items as $item)
            {
                ?>
                 <li style="margin: 5px 0; padding: 0; ">
                  <a href="<?php echo $item->getLink(); ?>" target="_blank"><?php echo $item->getTitle(); ?></a><br />
                  <?php echo $this->getDate($item->getPublishDate(), null, true); ?>
                 </li>
                <?php
            }
            ?>
               </ul>
               <div style="margin: 5px 0 0 0; padding: 5px; background: #bbccdd; ">
                <!--<a href="">More news</a><br />-->
                (provided by <a href="http://nfl.com">NFL.com</a>)
               </div>
              </div>
             </div>
            <?php
        }

        ?>
         </div>
         <div style="border: 0px dashed #000000; margin-right: 26%; ">
          Congratulations to Jeanine Maga&ntilde;a and Jimmie Johnson on their wins!
          <div style="float: left; width: 49%; ">
           left
          </div>
          <div style="float: left; width: 49%; ">
           <div id="">
        <?php
            /*$raceId = $this->getRaceId();
            $raceName = $this->getRaceName();
            $raceDate = $this->getRaceDate();
            $date = new Date($raceDate);
            $raceTrack = $this->getTrackName();


            ?>
             <h3>Race Results</h3>
             <table>
              <tr>
               <th>Finish</th>
               <th>Start</th>
               <th>Car</th>
               <th colspan="2">Driver</th>
              </tr>
            <?php

            $color = new RolloverIterator(array('eeeeee', 'dddddd'));

            $results = $this->getRaceResults();
            $rowCount = 0;
            foreach($results as $result)
            {
                $border = $result->borderColor;
                $background = $result->backgroundColor;
                $fontColor = $result->fontColor;

                echo '<tr style="background-color: #' . $color . '; ">';
                echo '<td class="center">' . $result->finish . '</td>';
                echo '<td class="center">' . $result->start . '</td>';
                echo '<td class="center">#' . $result->car . '</td>';

                $style = 'padding: 3px; ';
                if(!empty($border))
                    $style .= 'background: ' . $border . '; ';
                if(!empty($style))
                    $style = ' style="' . $style . '"';
                echo '<td' . $style . '>&nbsp;</td>';

                $style = 'padding: 3px 6px; text-align: right; ';
                if(!empty($border))
                    $style .= 'border: ' . $border . '; ';
                if(!empty($background))
                    $style .= 'background: ' . $background . '; ';
                if(!empty($fontColor))
                    $style .= 'color: ' . $fontColor . '; ';
                if(!empty($style))
                    $style = ' style="' . $style . '"';
                echo '<td' . $style . '>';
                echo $result->firstName . ' ' . $result->lastName . '</td>';
                echo '</tr>';

                $rowCount++;

                if($rowCount >= 5)
                    break;
            }
            echo '</table>';*/
        ?>
            <a href="">Complete Race Results</a>
           </div>
          </div>
          <br style="clear: both; ">
         </div>
        <?php

        $this->printFooter();
    }
}



?>
