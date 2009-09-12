<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPicks.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/NewsCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/LineupPicksCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/ScheduleCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/SeriesCacheableContent.php';

require_once PATH_LIB . 'com/mephex/core/DateRange.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/cache/ContentCache.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class IndexResponder extends NascarResponder
{
    protected $cache;

    protected $lastUpdated;

    protected $upcoming;
    protected $pickCounts;


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');
        $dbAgg = Database::getConnection('com.mephex.aggregator');

        $this->input->set('force');

        $this->printHeader();

        $this->cache = new MXT_ContentCache(PATH_LIB . 'com/lightdatasys/data/cache');

        $now = new Date();
        $today = new Date($now->format('q') . ' 00:00:00');

        $season = $this->getSeason();

        $series = array();
        $query = new Query('SELECT series.seriesId, series.keyname, series.shortName AS seriesName,'
            . ' season.seasonId, series.feedName,'
            . ' MAX(race.lastUpdated) AS lastUpdatedRace,'
            . ' MAX(fantPick.addedTime) AS lastUpdatedPicksAdded,'
            . ' MAX(fantPick.deletedTime) AS lastUpdatedPicksDeleted,'
            . ' feed.pubDate AS lastUpdatedNews'
            . ' FROM ' . $db->getTable('Series') . ' AS series'
            . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
            . ' ON series.seriesId=season.seriesId'
            . ' LEFT JOIN ' . $db->getTable('Race') . ' AS race'
            . ' ON season.seasonId=race.seasonId'
            . ' LEFT JOIN ' . $db->getTable('FantPick') . ' AS fantPick'
            . ' ON race.raceId=fantPick.raceId'
            . ' LEFT JOIN ' . $dbAgg->getTable('Feed') . ' AS feed'
            . ' ON series.feedName=feed.keyName'
            //. ' INNER JOIN ' . $dbAgg->getTable('Item') . ' AS feedItem'
            //. ' ON feed.feedId=feedItem.feedId'
            . ' WHERE season.year=' . $season->getYear() . ' GROUP BY series.seriesId');
        $result = $db->execQuery($query);
        while($oneSeries = $db->getAssoc($result))
        {
            $lastUpdatedRace = new Date($oneSeries['lastUpdatedRace']);
            $lastUpdatedNews = new Date($oneSeries['lastUpdatedNews']);
            $lastUpdatedNews = Date::getMax($lastUpdatedNews, $today);
            $lastUpdated = Date::getMax($lastUpdatedRace, $lastUpdatedNews);

            $lastUpdatedPicksAdded = new Date($oneSeries['lastUpdatedPicksAdded']);
            $lastUpdatedPicksDeleted = new Date($oneSeries['lastUpdatedPicksDeleted']);
            $lastUpdatedPicks = Date::getMax($lastUpdatedPicksAdded
                , $lastUpdatedPicksDeleted, $lastUpdatedRace);

            $oneSeries['lastUpdated'] = $lastUpdated;
            $oneSeries['lastUpdatedRace'] = $lastUpdatedRace;
            $oneSeries['lastUpdatedNews'] = $lastUpdatedNews;
            $oneSeries['lastUpdatedPicks'] = $lastUpdatedPicks;
            $series[$oneSeries['keyname']] = $oneSeries;
        }

        ?>
        <div class="nascar-main">
          <div class="series-first">
           <?php $this->printSeries($series['cup']); ?>
          </div>
          <div class="series-middle">
           <?php $this->printSeries($series['national']); ?>
          </div>
          <div class="series-last">
           <?php $this->printSeries($series['truck']); ?>
          </div>
         </div>
         <br class="clear" />
        <?php

        $this->printFooter();
    }

    public function getCache()
    {
        return $this->cache;
    }


    public function printSeries($series)
    {
        ?>
         <h3><?php echo $series['seriesName']; ?></h3>
        <?php
        $this->printContent($series);
    }

    public function printContent($series)
    {
        $cache = $this->getCache();

        $scheduleCacheable = new LDS_ScheduleCacheableContent($this, $series);
        $lineupCacheable = new LDS_LineupPicksCacheableContent($this, $series);
        $newsCacheable = new LDS_NewsCacheableContent($this, $series);
        $seriesCacheable = new LDS_SeriesCacheableContent($this, $series);

        if($this->input->get('force') != '')
        {
            echo $cache->createCache($scheduleCacheable);
            echo $cache->createCache($lineupCacheable);
            echo $cache->createCache($newsCacheable);
            echo $cache->createCache($seriesCacheable);
        }
        else
        {
            echo $cache->get($scheduleCacheable);
            echo $cache->get($lineupCacheable);
            echo $cache->get($newsCacheable);
            echo $cache->get($seriesCacheable);
        }
    }


    public function getUpcomingEvents($date)
    {
        $data = $this->data;

        if(is_null($this->upcoming))
        {
            $this->upcoming = NascarData::getUpcomingRaces($date->format('Y'), 20, $date);
        }

        return $this->upcoming;
    }

    public function getPickCounts($upcoming)
    {
        if(is_null($this->pickCounts))
        {
            $this->pickCounts = NascarData::getPickCountByRaceIds(array_keys($upcoming), is_null($this->user) ? 0 : $this->user->getId());
        }

        return $this->pickCounts;
    }
}









?>
