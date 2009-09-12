<?php


MXT_Language::pushLanguage('en-us');



MXT_Language::pushGroup('com.lightdatasys.nascar.race');
{
    MXT_Language::setStatement('season.title', 'Season');
    MXT_Language::setStatement('submitFilter.title', 'Go');
    MXT_Language::setStatement('track.title', 'Track');
    MXT_Language::setStatement('date.title', 'Date');
    MXT_Language::setStatement('name.title', 'Race Name');
    MXT_Language::setStatement('stationId.title', 'TV');
    MXT_Language::setStatement('edit.title', 'Edit');

    MXT_Language::pushGroup('.form');
    {
        MXT_Language::setStatement('basic.title', 'Race Information');
        MXT_Language::setStatement('track.title', 'Track');
        MXT_Language::setStatement('season.title', 'Season');
        MXT_Language::setStatement('raceNo.title', 'Race Number');
        MXT_Language::setStatement('date.title', 'Date/Time');
        MXT_Language::setStatement('date.description', 'Using your account timezone');
        MXT_Language::setStatement('name.title', 'Race');
        MXT_Language::setStatement('station.title', 'TV Station');
        MXT_Language::setStatement('submit_save.title', 'Save');
        MXT_Language::setStatement('submit_create.title', 'Create');
        MXT_Language::setStatement('submit_cancel.title', 'Cancel');

        MXT_Language::pushGroup('.forPoints');
        {
            MXT_Language::setStatement('title', 'For Points?');
            MXT_Language::setStatement('true', 'Yes');
            MXT_Language::setStatement('false', 'No');
        }
        MXT_Language::popGroup();
    }
    MXT_Language::popGroup();
}
MXT_Language::popGroup();



MXT_Language::popLanguage();


?>
