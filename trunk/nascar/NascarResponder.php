<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
//require_once PATH_LIB . 'com/lightdatasys/nascar/NascarData.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/NascarPermissions.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Result.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Series.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class NascarResponder extends LightDataSysResponder
{
    const SERIES_CUP = 1;

    protected $data;
    protected $dataObjects;

    protected $race;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $db = Database::getConnection('com.lightdatasys');
        $conn = Database::setHash($db, 'com.lightdatasys.nascar');
        $conn->setTablePrefix('nascar');

        NascarPermissions::getInstance();

        $this->race = null;

        $this->input->set('raceId', IntegerInput::getInstance());
        $this->input->set('series', IntegerInput::getInstance());
        $this->input->set('season', IntegerInput::getInstance());
        $this->input->set('year', IntegerInput::getInstance());
        $this->input->set('race', IntegerInput::getInstance());

        /*
        $this->dataObjects = array();
            $seriesId = $this->input->get('series');
            $year = $this->input->get('year');
            $raceNo = $this->input->get('race');
        $this->data = $this->createDataObject($seriesId, $year, $raceNo);
        */
    }

    public function getRace()
    {
        if(is_null($this->race))
        {
            $raceId = $this->input->get('raceId');
            $seriesId = $this->input->get('series');
            $year = $this->input->get('year');
            $seasonId = $this->input->get('season');
            $raceNo = $this->input->get('race');

            if($raceId > 0)
                $this->race = LDS_Race::getUsingId($raceId);

            if(is_null($this->race))
            {
                if($seasonId > 0)
                {
                    $season = LDS_Season::getUsingId($seasonId);

                    if(!is_null($season))
                        $this->race = LDS_Race::getLastCompletedUsingSeason($season);
                }

                if(is_null($this->race))
                {
                    if($seriesId <= 0)
                        $seriesId = self::SERIES_CUP;

                    $this->race = LDS_Race::getUsingRaceNumYearAndSeriesId($raceNo, $year, $seriesId);
                    if(is_null($this->race))
                    {
                        $series = LDS_Series::getUsingId($seriesId);
                        if(is_null($series))
                            $series = LDS_Series::getUsingId(self::SERIES_CUP);

                        $season = LDS_Season::getUsingSeriesAndYear($series, $year);
                        if(is_null($season))
                            $season = LDS_Season::getUsingSeries($series);

                        $this->race = LDS_Race::getLastCompletedUsingSeason($season);
                    }
                }
            }
        }

        return $this->race;
    }

    public function getSeason()
    {
        return $this->getRace()->getSeason();
    }

    public function getSeries()
    {
        return $this->getRace()->getSeason()->getSeries();
    }


    public function checkPermissions()
    {
        parent::checkPermissions();
        $this->checkPermission('com.lightdatasys.nascar', 'read');
    }


    public function printExtendedHTMLHead()
    {
        parent::printExtendedHTMLHead();

        ?>
         <link rel="stylesheet" href="/nascar/style.css" />
        <?php
    }


    public function createDataObject($seriesId, $seasonYear, $raceNo)
    {
        if(!array_key_exists($seriesId, $this->dataObjects)
            || !array_key_exists($seasonYear, $this->dataObjects[$seriesId])
            || !array_key_exists($raceNo, $this->dataObjects[$seriesId][$seasonYear]))
        {
            $data = new NascarData($seriesId, $seasonYear, $raceNo);
            $this->dataObjects[$seriesId][$seasonYear][$raceNo] = $data;
        }

        return $this->dataObjects[$seriesId][$seasonYear][$raceNo];
    }

    public function isSeriesSelectable()
    {
        return true;
    }

    public function isSeasonSelectable()
    {
        return true;
    }

    public function isRaceSelectable()
    {
        return true;
    }



    public function printSelector()
    {
        /*
        $data = $this->data;

        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="selector">
        <?php
        $allSeries = $data->getAllSeries();
        if(count($allSeries) > 1 && $this->isSeriesSelectable())
        {
            ?>
              <dl>
               <dt>Series</dt>
               <dd>
                <select name="series">
                 <option value="0"></option>
            <?php
            foreach($allSeries as $series)
            {
                $selected = '';
                if($series->getId() == $this->seriesId)
                    $selected = ' selected="selected"';

                ?><option value="<?php echo $series->getId(); ?>"<?php echo $selected; ?>><?php echo htmlentities($series->getShortName()); ?></option><?php
            }
            ?>
                </select>
               </dd>
              </dl>
            <?php
        }

        $seasons = $data->getSeasons();
        if(count($seasons) > 1 && $this->isSeasonSelectable())
        {
            ?>
              <dl>
               <dt>Season</dt>
               <dd>
                <select name="year">
                 <option value="0"></option>
            <?php
            foreach($seasons as $seasonId => $year)
            {
                $selected = '';
                if($year == $this->seasonYear)
                    $selected = ' selected="selected"';

                ?><option value="<?php echo $year; ?>"<?php echo $selected; ?>><?php echo htmlentities($year); ?></option><?php
            }
            ?>
            </select>
           </dd>
          </dl>
        <?php
        }

        $races = $data->getRaces();
        if(count($races) > 1 && $this->isRaceSelectable())
        {
            ?>
             <dl>
              <dt>Race</dt>
              <dd>
                <select name="race">
                 <option value="0"></option>
            <?php
            $i = 1;
            foreach($races as $race)
            {
                $selected = '';
                if($i == $this->raceNo)
                    $selected = ' selected="selected"';

                ?><option value="<?php echo $i; ?>"<?php echo $selected; ?>><?php echo ($race['forPoints'] == '1' ? '' : '* ') . $i; ?> - <?php echo htmlentities($race['trackName']) . ': ' . htmlentities($race['name']); ?></option><?php

                $i++;
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
        //*/
    }

    public function addStatsNavigation()
    {
        $selected = $this->getSelectedNavItem();

        $item = new NavItem(0, 'results', 'Race Results', $selected, '/nascar/results.php', null, null, true);
        $item = new NavItem(0, 'driver-standings', 'Driver Standings', $selected, '/nascar/driver-standings.php', null, null, true);
        $item = new NavItem(0, 'fantasy-picks', 'Fantasy Picks', $selected, '/nascar/fantasy/picks.php', null, null, true);
        $item = new NavItem(0, 'fantasy-standings', 'Fantasy Standings', $selected, '/nascar/fantasy/standings.php', null, null, true);
        $item = new NavItem(0, 'fantasy-pick-stats', 'Pick Stats', $selected, '/nascar/fantasy/picks-stats.php', null, null, true);
    }


    public static function getDueDate(Date $raceDate)
    {
        $dueDate = new Date($raceDate);
        $dueDate->changeMinute(-5);

        return $dueDate;
    }
}



?>
