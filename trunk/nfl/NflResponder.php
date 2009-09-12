<?php


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/NflData.php';


class NflResponder extends LightDataSysResponder
{
    protected $data;


    protected $seasons;

    protected $seasonId;
    protected $seasonYear;

    protected $weekNo;
    protected $weekCount;

    protected $weeks;
    protected $currWeek;

    protected $teams;
    protected $teamsByDivision;

    protected $games;


    protected $results;

    protected $chaseRaceNo;
    protected $chaseDate;

    protected $standings;

    protected $users;

    protected $fantasyPicks;
    protected $totalDriverPoints;

    protected $weeklyResults;

    protected $points;
    protected $maxPoints;
    protected $minPoints;

    protected $maxPointsPerRace;
    protected $minPointsPerRace;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

		$db = Database::getConnection('com.lightdatasys');
        $conn = Database::setHash($db, 'com.lightdatasys.nfl');
        $conn->setTablePrefix('nfl');

        //NflPermissions::getInstance();
        MXT_Language::loadFile('com/lightdatasys/nfl');

        $this->input->set('season', IntegerInput::getInstance());
        $this->input->set('week', IntegerInput::getInstance());

        $this->data = new NflData($this->input->get('season'), $this->input->get('week'));

        $this->seasonId = $this->input->get('season');
        $this->weekNo = $this->input->get('week');
        $this->seasonYear = 0;

        $this->seasons = null;

        $this->weekCount = -1;

        $this->weeks = null;
        $this->currWeek = -1;

        $this->teams = null;
        $this->teamsByDivision = null;

        $this->games = null;




        $this->races = null;
        $this->completedRaceNo = 0;

        $this->raceId = 0;
        $this->raceName = null;
        $this->raceDate = null;
        $this->trackName = null;
        $this->nascarComId = null;

        $this->results = null;

        $this->chaseRaceNo = 0;
        $this->chaseDate = null;

        $this->standings = null;

        $this->users = null;

        $this->fantasyPicks = null;
        $this->totalDriverPoints = null;

        $this->weeklyResults = null;

        $this->points = null;
        $this->maxPoints = null;
        $this->minPoints = null;

        $this->maxPointsPerRace = -99999;
        $this->minPointsPerRace = 99999;
    }


    public function checkPermissions()
    {
        parent::checkPermissions();
        //$this->checkPermission('com.lightdatasys.nfl', 'read');
    }


    public function printExtendedHTMLHead()
    {
        parent::printExtendedHTMLHead();
    }

/*

    public function getSeasonId()
    {
        if($this->seasonId <= 0)
            $this->loadSeasons();

        return $this->seasonId;
    }

    public function getSeasonYear()
    {
        if($this->seasonYear <= 0)
            $this->loadSeasons();

        return $this->seasonYear;
    }

    public function getWeekNumber()
    {
        if($this->weekNo <= 0)
            $this->loadSeasons();

        return $this->weekNo;
    }

    public function getWeekCount()
    {
        if($this->weekCount <= 0)
            $this->loadSeasons();

        return $this->weekCount;
    }

    public function getSeasons()
    {
        if(is_null($this->seasons))
            $this->loadSeasons();

        return $this->seasons;
    }

    public function loadSeasons()
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $selectedSeason = null;
        $this->seasons = array();

        $query = new Query('SELECT s.seasonId, s.year, COUNT(w.weekId) FROM '
            . $db->getTable('Season') . ' AS s INNER JOIN ' . $db->getTable('Week')
            . ' AS w ON s.seasonId=w.seasonId GROUP BY w.seasonId ORDER BY year DESC');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            if(is_null($selectedSeason) || $row[0] == $this->seasonId)
                $selectedSeason = $row;

            $this->seasons[$row[0]] = $row[1];
        }

        $this->seasonId = $selectedSeason[0];
        $this->seasonYear = $selectedSeason[1];
        $this->weekCount = $selectedSeason[2];
        $this->weekNo = min($this->weekNo, $this->weekCount);
        if($this->weekNo <= 0)
            $this->weekNo = $this->getCurrentWeek();
    }


    public function getWeeks()
    {
        if(is_null($this->weeks))
            $this->loadWeeks();

        return $this->weeks;
    }

    public function getCurrentWeek()
    {
        if($this->currWeek <= 0)
            $this->loadWeeks();

        return $this->currWeek;
    }

    public function loadWeeks()
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $this->currWeek = 0;
        $this->weeks = array();
        $query = new Query('SELECT weekId, weekStart, weekEnd, winWeight FROM '
            . $db->getTable('Week') . ' WHERE seasonId=' . $this->getSeasonId()
            . ' ORDER BY weekStart ASC');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $this->weeks[] = $row;

            $weekStart = new Date($row[1]);
            $weekStart->changeDay(-1);
            if($weekStart->compareTo(new Date()) < 0)
                $this->currWeek++;
        }
    }


    public function getTeams()
    {
        if(is_null($this->teams))
            $this->loadTeams();

        return $this->teams;
    }

    public function getTeamsByDivision()
    {
        if(is_null($this->teamsByDivision))
            $this->loadTeams();

        return $this->teamsByDivision;
    }

    public function loadTeams()
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $weeks = $this->getWeeks();
        $weekRow = $weeks[$this->getWeekNumber() - 1];

        $this->teams = array();
        $this->teamsByDivision = array();
        $query = new Query('SELECT t.teamId, t.location, t.mascot, t.conference, t.division,'
            . ' SUM(IF(g.awayScore IS NOT NULL AND g.homeScore IS NOT NULL AND DATE(gameTime)<=\'' . $weekRow[2] . '\' AND ((t.teamId=g.awayId AND g.awayScore>g.homeScore) OR (t.teamId=g.homeId AND g.homeScore>g.awayScore)), 1, 0)) AS wins,'
            . ' SUM(IF(g.awayScore IS NOT NULL AND g.homeScore IS NOT NULL AND DATE(gameTime)<=\'' . $weekRow[2] . '\' AND ((t.teamId=g.awayId AND g.awayScore<g.homeScore) OR (t.teamId=g.homeId AND g.homeScore<g.awayScore)), 1, 0)) AS losses,'
            . ' SUM(IF(g.awayScore IS NOT NULL AND g.homeScore IS NOT NULL AND DATE(gameTime)<=\'' . $weekRow[2] . '\' AND g.awayScore=g.homeScore, 1, 0)) AS ties,'
            . ' fontColor, background, borderColor'
            . ' FROM ' .  $db->getTable('Team') . ' AS t'
            . ' INNER JOIN ' . $db->getTable('Game') . ' AS g ON t.teamId=g.awayId OR t.teamId=g.homeId'
            . ' INNER JOIN ' . $db->getTable('Week') . ' AS w ON DATE(g.gameTime) BETWEEN w.weekStart AND w.weekEnd '
            . ' WHERE DATE(g.gameTime)<=\'' . $weekRow[2] . '\' AND w.seasonId=' . $this->getSeasonId() . ' GROUP BY t.teamId ORDER BY wins DESC, ties DESC, losses ASC');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $this->teams[$row[0]] = $row;
            $this->teamsByDivision[$row[3]][$row[4]][] = $row;
        }
    }


    public function getGames()
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        if(is_null($this->games))
        {
            $this->games = array();

            $weeks = $this->getWeeks();
            $query = new Query('SELECT '
                . 'gameId, gameTime, awayId, homeId, awayScore, homeScore '
                . 'FROM ' . $db->getTable('Game') . ' '
                . 'WHERE DATE(gameTime) BETWEEN '
                . '\'' . $weeks[$this->getWeekNumber() - 1][1] . '\' AND \'' . $weeks[$this->getWeekNumber() - 1][2] . '\' '
                . 'ORDER BY gameTime');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $this->games[] = $row;
            }
        }

        return $this->games;
    }*/


    public function printSelector()
    {
        $data = $this->data;

        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="selector">
          <dl>
           <dt>Season</dt>
           <dd>
            <select name="season">
        <?php
        foreach($data->getSeasons() as $id => $year)
        {
            $selected = '';
            if($id == $data->getSeasonYear())
                $selected = ' selected="selected"';

            ?><option value="<?php echo $year; ?>"<?php echo $selected; ?>><?php echo $year; ?></option><?php
        }
        ?>
            </select>
           </dd>
          </dl>
          <dl>
           <dt>Week</dt>
           <dd>
            <select name="week">
        <?php
        $weekCount = $data->getWeekCount();
        for($i = 1; $i <= $weekCount; $i++)
        {
            $selected = '';
            if($i == $data->getWeekNumber())
                $selected = ' selected="selected"';

            ?><option value="<?php echo $i; ?>"<?php echo $selected; ?>><?php echo $i; ?></option><?php
        }
        ?>
            </select>
           </dd>
          </dl>
          <dl>
           <dd>&nbsp;</dd>
           <dt><input type="submit" value="Go" /></dt>
          </dl>
          <br style="clear: both; " />
         </form>
        <?php
    }
}


?>
