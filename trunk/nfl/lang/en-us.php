<?php


MXT_Language::pushLanguage('en-us');



MXT_Language::pushGroup('com.lightdatasys.nfl.week');
{
    MXT_Language::setStatement('season.title', 'Season');
    MXT_Language::setStatement('submitFilter.title', 'Go');
    MXT_Language::setStatement('weekStart.title', 'Start of Week');
    MXT_Language::setStatement('weekEnd.title', 'End of Week');
    MXT_Language::setStatement('winWeight.title', 'Win Weight');

    MXT_Language::pushGroup('.form');
    {
        MXT_Language::setStatement('basic.title', 'Week Information');
        MXT_Language::setStatement('season.title', 'Season');
        MXT_Language::setStatement('weekStart.title', 'Start of Week');
        MXT_Language::setStatement('weekEnd.title', 'End of Week');
        MXT_Language::setStatement('winWeight.title', 'Win Weight');
        MXT_Language::setStatement('submit_save.title', 'Save');
        MXT_Language::setStatement('submit_create.title', 'Create');
        MXT_Language::setStatement('submit_cancel.title', 'Cancel');
    }
    MXT_Language::popGroup();
}
MXT_Language::popGroup();



MXT_Language::popLanguage();


?>
