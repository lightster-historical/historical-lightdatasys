<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/aggregator/AggDatabaseParser.php';

require_once 'Mail.php';


class MailRemindersResponder extends LightDataSysResponder
{
    protected $factory;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $this->factory = null;
    }


    public function get($args)
    {
    }


    protected function getFactory()
    {
        if(is_null($this->factory))
        {
            $host = 'mail.lightdatasys.com';
            $username = 'commissioner+lightdatasys.com';
            $password = '***REMOVED***';

            $params = array
            (
                'host' => $host,
                'port' => 25,
                'auth' => false,
                //'username' => $username,
                //'password' => $password,
                'timeout' => 5,
                'debug' => true,
                'persist' => true
            );

            $this->factory = Mail::factory('smtp', $params);
        }

        return $this->factory;
    }

    protected function sendMessage($to, $from, $recipients, $subject, $body)
    {
        $factory = $this->getFactory();

        $headers = array
        (
            'To' => $to,
            'From' => $from,
            'Return-Path' => $from,
            'Reply-To' => $from,
            'Subject' => $subject
            'Bcc' => $recipients
        );
        return $factory->send($to, $headers, $body);
    }
}



?>
