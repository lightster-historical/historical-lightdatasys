<?php

/*
require_once 'path.php';



require_once PATH_LIB . 'com/lightdatasys/nascar/NascarManageResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/outputter/RaceListTableOutputter.php';

require_once PATH_LIB . 'com/mephex/data-object/responder/AbstractDefaultManageResponder.php';


class RaceResponder extends MXT_DO_AbstractDefaultManageResponder
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
            $season = $filterValues['seasonId'];
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
    //

    public function getIncludedFormFields()
    {
        return array
        (
            'seasonId',
            'raceNo',
            'name',
            'trackId',
            'date',
            'stationId',
            'forPoints'
        );
    }


    public function getIncludedListFields()
    {
        return array
        (
            'date',
            'stationId',
            'name',
            'trackId'
        );
    }


    public function getFilterFields()
    {
        return array
        (
            'seasonId' => LDS_Season::getUsingId(3)
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
            'trackId'
        );
    }

    public function getListDisplayValueUsingFieldAndValue(MXT_DataField $field, $value)
    {
        $list = $this->getList();
        $context = $list->getContext();

        if($field->getKeyname() == 'date')
            return $value->format('M j, g:i a', $context->getValueOrDefault('timezone', 0));
        else if($field->getKeyname() == 'trackId')
            return $value->getName();
    }

    public function getListOutputter()
    {
        return new LDS_RaceListTableOutputter($this);
    }
}
*/



/*

require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarManageResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/DataImporter.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/mephex/form/outputter/DescriptiveFormOutputter.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/language/Language.php';


class RaceResponder extends NascarManageResponder
{
    protected $form;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);
        MXT_Language::pushLanguage('en');

        $this->form = new LDS_RaceForm($_SERVER['PHP_SELF'], $this->input, $this->getTimezone());
    }

    public function getPageTitle()
    {
        if($this->form->getValue('submit', 'raceId') > 0)
            return 'Edit Race';
        else
            return 'New Race';
    }


    public function post($args)
    {
        MXT_Language::pushGroup('com.lightdatasys.nascar.form.race');

        $race = $this->form->submit();
        if($race instanceof LDS_Race)
        {
            HttpHeader::forwardTo('index.php');
        }
        else
        {
            $this->get($args);
        }

        MXT_Language::popGroup();
    }


    public function get($args)
    {
        MXT_Language::pushGroup('com.lightdatasys.nascar.form.race');

        $db = Database::getConnection('com.lightdatasys.nascar');

        $this->printHeader();

        $this->form->printFormAsHTML(new MXT_DescriptiveFormOutputter());

        $this->printFooter();

        MXT_Language::popGroup();
    }


    public function printSelector()
    {
    }
}


*/
?>
