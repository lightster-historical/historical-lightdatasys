<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/factory/Season.php';
//require_once PATH_LIB . 'com/lightdatasys/nascar/outputter/RaceListTableOutputter.php';

require_once PATH_LIB . 'com/mephex/data-object/responder/AbstractDefaultManageResponder.php';


class SeasonResponder extends MXT_DO_AbstractDefaultManageResponder
{
    protected $responder;


    public function init($args)
    {
        parent::init($args);
        #MXT_Language::loadFile('com/lightdatasys/nfl');

        $pageTitle = '';
        if($this->isEditMode())
            $this->getSiteResponder()->setPageTitle('Editing ' . $this->getDataObject()->getYear());
        else if($this->isCreateMode())
            $this->getSiteResponder()->setPageTitle('Create a Season');
        else
        {
            $this->getSiteResponder()->setPageTitle('Seasons');
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
        return LDS_FFB_SeasonClass::getSingleton();
    }

    public function getFormLanguageGroup()
    {
        return 'com.lightdatasys.nfl.season.form';
    }

    public function getListLanguageGroup()
    {
        return 'com.lightdatasys.nfl.season';
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

    /*
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
    */
}



?>
