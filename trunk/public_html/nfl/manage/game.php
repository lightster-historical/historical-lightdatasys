<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/factory/Game.php';
//require_once PATH_LIB . 'com/lightdatasys/nascar/outputter/RaceListTableOutputter.php';

require_once PATH_LIB . 'com/mephex/data-object/responder/AbstractDefaultManageResponder.php';


class GameResponder extends MXT_DO_AbstractDefaultManageResponder
{
    protected $responder;


    public function init($args)
    {
        parent::init($args);

        $pageTitle = '';
        if($this->isEditMode())
            $this->getSiteResponder()->setPageTitle('Editing Game');
        else if($this->isCreateMode())
            $this->getSiteResponder()->setPageTitle('Create a Game');
        else
        {
            $this->getSiteResponder()->setPageTitle('Games');
        }
    }


    public function getItemsPerPage()
    {
        return 0;
    }

    protected function getDefaultSiteResponder()
    {
        return new NflResponder();
    }


    public function isReadAllowed()
    {
        return $this->getSiteResponder()->getPermission('com.lightdatasys.nfl', 'admin');
    }

    public function isWriteAllowed()
    {
        return $this->getSiteResponder()->getPermission('com.lightdatasys.nfl', 'admin');
    }


    public function getDataClass()
    {
        return LDS_FFB_GameClass::getSingleton();
    }

    public function getFormLanguageGroup()
    {
        return 'com.lightdatasys.nfl.game.form';
    }

    public function getListLanguageGroup()
    {
        return 'com.lightdatasys.game.game';
    }


    public function getIncludedFormFields()
    {
        return array
        (
            'awayTeam',
            'awayScore',
            'homeScore',
            'homeTeam',
            'gameTime'
        );
    }


    public function getIncludedListFields()
    {
        return array
        (
            'awayTeam',
            'awayScore',
            'homeScore',
            'homeTeam',
            'gameTime'
        );
    }


    public function getFilterFields()
    {
        return array
        (
            'week' => LDS_FFB_Week::getUsingId(3)
        );
    }

    public function getSortFields()
    {
        return array
        (
            'gameTime' => 'ASC'
        );
    }
}



?>
