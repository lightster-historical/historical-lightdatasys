<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nfl/factory/Week.php';
//require_once PATH_LIB . 'com/lightdatasys/nascar/outputter/RaceListTableOutputter.php';

require_once PATH_LIB . 'com/mephex/data-object/responder/AbstractDefaultManageResponder.php';


class WeekResponder extends MXT_DO_AbstractDefaultManageResponder
{
    protected $responder;


    public function init($args)
    {
        parent::init($args);

        $pageTitle = '';
        if($this->isEditMode())
            $this->getSiteResponder()->setPageTitle('Editing Week');
        else if($this->isCreateMode())
            $this->getSiteResponder()->setPageTitle('Create a Week');
        else
        {
            $this->getSiteResponder()->setPageTitle('Weeks');
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
        return LDS_FFB_WeekClass::getSingleton();
    }

    public function getFormLanguageGroup()
    {
        return 'com.lightdatasys.nfl.week.form';
    }

    public function getListLanguageGroup()
    {
        return 'com.lightdatasys.nfl.week';
    }


    public function getIncludedFormFields()
    {
        return array
        (
            'season',
            'weekStart',
            'weekEnd',
            'winWeight'
        );
    }


    public function getIncludedListFields()
    {
        return array
        (
            'weekStart',
            'weekEnd',
            'winWeight'
        );
    }


    public function getFilterFields()
    {
        return array
        (
            'season' => LDS_FFB_Season::getUsingId(3)
        );
    }

    public function getSortFields()
    {
        return array
        (
            'weekStart' => 'DESC'
        );
    }


    /*
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
