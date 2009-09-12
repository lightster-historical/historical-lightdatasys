<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
//require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/outputter/RaceListTableOutputter.php';

require_once PATH_LIB . 'com/mephex/data-object/responder/AbstractDefaultManageResponder.php';


class ScheduleResponder extends MXT_DO_AbstractDefaultManageResponder
{
    protected $responder;


    public function init($args)
    {
        parent::init($args);
        MXT_Language::loadFile('com/lightdatasys/nascar');

        $pageTitle = '';
        if($this->isEditMode())
            $this->getSiteResponder()->setPageTitle('Editing ' . $this->getDataObject()->getName());
        else if($this->isCreateMode())
            $this->getSiteResponder()->setPageTitle('Create a Race');
        else
        {
            $filter = $this->getFilter();
            $filterValues = $filter->getFilterValues();
            $season = $filterValues['season'];
            $this->getSiteResponder()->setPageTitle($season->getYear() . ' ' . $season->getSeries()->getName() . ' Schedule');
        }
    }


    public function getItemsPerPage()
    {
        return 0;
    }

    protected function getDefaultSiteResponder()
    {
        return new NascarResponder();
    }


    public function isWriteAllowed()
    {
        return $this->getSiteResponder()->getPermission('com.lightdatasys.nascar', 'admin');
    }


    public function getDataClass()
    {
        return LDS_RaceClass::getSingleton();
    }

    public function getFormLanguageGroup()
    {
        return 'com.lightdatasys.nascar.race.form';
    }

    public function getListLanguageGroup()
    {
        return 'com.lightdatasys.nascar.race';
    }


    /*
    public function getExcludedFormFields()
    {
        return array
        (
            'nascarComId',
            'laps',
            'qualifyingRainedOut',
            'official',
            'pickStatus'
        );
    }
    */

    public function getIncludedFormFields()
    {
        return array
        (
            'season',
            'raceNo',
            'name',
            'track',
            'date',
            'station',
            'forPoints'
        );
    }


    public function getIncludedListFields()
    {
        return array
        (
            'date',
            'station',
            'name',
            'track'
        );
    }


    public function getFilterFields()
    {
        return array
        (
            'season' => LDS_Season::getUsingId(3)
        );
    }

    public function getSortFields()
    {
        return array
        (
            'forPoints' => 'DESC',
            'date' => 'DESC'
        );
    }


    public function getCustomListDisplayValueFields()
    {
        return array
        (
            'date',
            'track'
        );
    }

    public function getListDisplayValueUsingFieldAndValue(MXT_AbstractDataField $field, $value)
    {
        $list = $this->getList();
        $context = $list->getContext();

        if($field->getKeyname() == 'date')
            return $value->format('M j, g:i a', $context->getValueOrDefault('timezone', 0));
        else if($field->getKeyname() == 'track')
            return $value->getName();
    }

    public function getListOutputter()
    {
        return new LDS_RaceListTableOutputter($this);
    }
}



/*
require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Season.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class ScheduleResponder extends NascarResponder
{
    public function getPageTitle()
    {
        $series = $this->getSeries();
        $season = $this->getSeason();

        $title = $season->getYear() . ' ' . $series->getName() . ' Schedule';

        return $title;
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $series = $this->getSeries();
        $season = $this->getSeason();
        $races = $season->getRaces();

        $this->printHeader();

        if(count($races) > 0)
        {
            ?>
             <dl class="header-data">
              <dt>Season</dt>
              <dd><?php echo $season->getYear(); ?> <?php echo $series->getName(); ?></dd>
             </dl>
             <div class="table-default">
              <table>
               <tr>
                <th>Date</th>
                <th>TV</th>
                <th>Race</th>
                <th>Track</th>
                <th>Results</th>
                <th>Standings</th>
                <th colspan="2" style="width: 120px; ">Fantasy</th>
               </tr>
            <?php

            $now = new Date();
            $forPoints = true;
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            $raceNo = 1;
            $maxResultsType = max(LDS_Race::RESULT_UNOFFICIAL
                , LDS_Race::RESULT_OFFICIAL);
            foreach($races as $race)
            {
                if($forPoints && !$race->isForPoints())
                {
                    $forPoints = false;
                    ?>
                     <tr>
                      <th colspan="8"><em>Non-points Events</em></th>
                     </tr>
                    <?php
                }

                $selectedStyle = '';
                //if($race['raceId'] == $race->getId())
                //    $selectedStyle = ' style="background-color: #ffffcc; "';

                //$qs = '?series=' . $series->getId() . '&amp;year=' . $season->getSeasonYear() . '&amp;race=' . $raceNo;
                $qs = '?raceId=' . $race->getId();

                $date = $race->getDate();
                $tvStation = '';
                if(!is_null($race->getTvStation()))
                    $tvStation = $race->getTvStation()->getName();
                ?>
                 <tr class="center <?php echo $rowStyle; ?>"<?php echo $selectedStyle; ?>>
                  <td><?php echo $this->printDate($date, 'M j, g:i a'); ?></td>
                  <td><?php echo $tvStation; ?></td>
                  <td><?php echo $race->getName(); ?></td>
                  <td><?php echo $race->getTrack()->getName(); ?></td>
                <?php
                if($race->getResultsType() != 0)
                {
                    $resultStatus = '';
                    if($race->hasEntryList())
                        $resultStatus = 'Entry List';
                    else if($race->hasLineup())
                        $resultStatus = 'Lineup';
                    else if($race->hasResults())
                    {
                        if($race->isOfficial())
                            $resultStatus = 'Official';
                        else
                            $resultStatus = 'Unofficial';
                    }
                    ?>
                     <td>
                      <a href="results.php<?php echo $qs; ?>"><?php echo $resultStatus; ?></a>
                     </td>
                     <td>
                    <?php

                    if($race->isForPoints()
                        && ($race->getResultsType() == LDS_Race::RESULT_OFFICIAL
                        || $race->getResultsType() == LDS_Race::RESULT_UNOFFICIAL))
                    {
                        $official = $race->isOfficial() ? 'Official' : 'Unofficial';
                        ?>
                         <a href="driver-standings.php<?php echo $qs; ?>"><?php echo $official; ?></a>
                        <?php
                    }
                    else
                    {
                        ?>
                         --
                        <?php
                    }

                    ?>
                     </td>
                     <td>
                      <a href="fantasy/picks.php<?php echo $qs; ?>">Picks</a>
                     </td>
                     <td>
                    <?php

                    if($race->isForPoints())
                    {
                        ?>
                         <a href="fantasy/standings.php<?php echo $qs; ?>">Standings</a>
                        <?php
                    }
                    else
                    {
                        ?>
                         --
                        <?php
                    }

                    ?>
                     </td>
                    <?php
                }
                else
                {
                    ?>
                     <td>&nbsp;</td>
                     <td>&nbsp;</td>
                    <?php
                    if($race->getPickStatus() >= '1'
                        || $now->compareTo($race->getDate()) > 0)
                    {
                        ?>
                         <td><a href="fantasy/picks.php<?php echo $qs; ?>">Picks</a></td>
                         <td>
                        <?php

                        if($race->isForPoints())
                        {
                            ?>
                             <a href="fantasy/standings.php<?php echo $qs; ?>">Standings</a>
                            <?php
                        }
                        else
                        {
                            ?>
                             --
                            <?php
                        }

                        ?>
                         </td>
                        <?php
                    }
                    else
                    {
                        ?>
                         <td>&nbsp;</td>
                         <td>&nbsp;</td>
                        <?php
                    }
                    ?>
                    <?php
                }

                ?>
                 </tr>
                <?php
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
              The schedule has not yet been posted. Please check back later.
             </div>
            <?php
        }

        $this->printFooter();
    }



    public function printSelector()
    {
        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="selector">
        <?php
        $seasons = LDS_Season::getAll();
        if(count($seasons) > 1)
        {
            ?>
              <dl>
               <dt>Season</dt>
               <dd>
                <select name="season">
            <?php
            foreach($seasons as $season)
            {
                $selected = '';
                if($season->getId() == $this->getSeason()->getId())
                    $selected = ' selected="selected"';

                ?>
                 <option value="<?php echo $season->getId(); ?>"<?php echo $selected; ?>>
                  <?php echo htmlentities($season->getYear()); ?>
                  <?php echo htmlentities($season->getSeries()->getName()); ?>
                 </option>
                <?php
            }
            ?>
                </select>
               </dd>
              </dl>
            <?php
        }
        ?>
          <dl>
           <dd>&nbsp;</dd>
           <dt><input type="submit" value="Go" /></dt>
          </dl>
         </form>
        <?php
    }
}
*/



?>
