<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';

require_once PATH_LIB . 'com/mephex/cache/CacheableContent.php';


class LDS_ScheduleCacheableContent implements MXT_CacheableContent
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

        $this->printSchedule($race);
    }

    public function getContentLastUpdated()
    {
        $series = $this->getSeriesArray();
        return $series['lastUpdatedPicks'];
    }

    public function getDirectory()
    {
        $series = $this->getSeriesArray();
        return 'com/lightdatasys/public_html/nascar/index.php/' . $series['keyname'];
    }

    public function getFileName()
    {
        $responder = $this->getResponder();

        $userId = 0;
        if(!is_null($responder->getUser()))
            $userId = $responder->getUser()->getId();

        return $userId . '.txt';
    }


    public function printSchedule(LDS_Race $race)
    {
        $responder = $this->getResponder();

        $season = $race->getSeason();
        $races = LDS_Race::getAfterRace($race, 5);

        $user = $responder->getUser();

        ?>
         <h4><a href="schedule.php?season=<?php echo $season->getId(); ?>">Schedule</a></h4>
         <div class="table-wide center" style="font-size: .9em; ">
          <table>
           <tr>
            <th style="width: 25px; ">Saved</th>
            <th style="width: 65px; ">Picks Due</th>
            <th>Race</th>
           </tr>
        <?php

        if(count($races) > 0)
        {
            LDS_FantasyPicks::getAllUsingRaces($races);

            foreach($races as $race)
            {
                $season = $race->getSeason();
                $fantasyPicks = $race->getFantasyPicks()->getPicksByUserId();

                $now = new Date();
                $date = '';
                $dueDate = NascarResponder::getDueDate($race->getDate());
                $dueSoonDate = new Date($dueDate);
                $dueSoonDate->changeDay(-1);
                $weekAgo = new Date();
                $weekAgo = $weekAgo->changeDay(7);
                if($dueDate->compareTo($weekAgo) < 0)
                    $date .= $responder->getDate($dueDate, 'l');
                else
                    $date .= $responder->getDate($dueDate, 'F j');
                $date .= '<br />' . $responder->getDate($dueDate, 'g:i a');

                $pickStatus = false;
                if(!is_null($user) && !is_null($fantasyPicks)
                    && array_key_exists($user->getId(), $fantasyPicks)
                    && $fantasyPicks[$user->getId()] > 0)
                    $pickStatus = true;

                $picksColor = 'inherit';
                if($pickStatus && $season->getMaxPickCount() == count($fantasyPicks[$user->getId()]))
                    $picksColor = '#66ff66';
                else if($dueSoonDate->compareTo($now) <= 0 || $pickStatus)
                    $picksColor = '#ff6666';
                else if($race->getPickStatus() >= 1)
                    $picksColor = '#ffff66';

                if($race->isForPoints() != '1')
                    $nonPointsEvent = true;

                $checked = '';
                if($pickStatus && $season->getMaxPickCount() == count($fantasyPicks[$user->getId()]))
                    $checked = ' checked="checked"'

                ?>
                 <tr style="background-color: <?php echo $picksColor; ?>">
                  <td><input type="checkbox" name="nil[]"<?php echo $checked; ?> onclick="javascript: return false;" /></td>
                <?php

                if($race->getPickStatus())
                {
                    ?>
                     <td>
                      <a href="fantasy/picks.php?raceId=<?php echo $race->getId(); ?>" style="color: black; "><?php echo $date; ?></a>
                     </td>
                    <?php
                }
                else
                {
                    ?>
                     <td><?php echo $date; ?></td>
                    <?php
                }

                ?>
                  <td>
                   <b><?php echo $race->getName(); ?></b><br />
                   <?php echo $race->getTrack()->getShortName(); ?>
                  </td>
                </tr>
               <?php
            }
        }
        ?>
           <tr>
            <th colspan="3">
             <a href="schedule.php?season=<?php echo $season->getId(); ?>">Complete Schedule</a>
            </th>
           </tr>
          </table>
         </div>
        <?php
    }
}



?>
