<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';

require_once PATH_LIB . 'com/mephex/util/StringUtil.php';


class GeneratePasswordEmailsResponder extends LightDataSysResponder
{
    public function get($args)
    {
        $this->printHeader();

        $mailer = $this->getMailer();

        $lineEnding = "\n";

        $from = 'commissioner@lightdatasys.com';
        $subject = 'Fantasy Football \'09 - Lightdatasys Account Information';

        $db = Database::getConnection('com.mephex.user');
        $query = new Query('SELECT * FROM ' . $db->getTable('user') . ' AS user '
            . ' LEFT JOIN player_user AS pl_user ON user.userId=pl_user.userId '
            . ' LEFT JOIN player AS player ON pl_user.playerId=player.playerId '
            . ' WHERE password=\'\' AND email IS NOT NULL LIMIT 1');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $id = $row['userId'];
            $name = $row['name'];
            $username = $row['username'];
            $email = 'lightster+devtest@gmail.com';
            $to = $name . ' <' . $email . '>';

            $password = MXT_StringUtil::generateRandomUnambiguousString(8);
            $hashedPassword = md5(md5($password) . $row['securityHash']);

            $pwQuery = new Query('UPDATE ' . $db->getTable('user')
                . ' SET `password`=\'' . addslashes($hashedPassword)
                . '\' WHERE userId=' . intval($id));
            $pwResult = $db->execQuery($pwQuery);

            if(!$pwResult)
            {
                echo 'Something went wrong with ' . $name;
                exit;
            }

            $message  = $name . ',' . $lineEnding
                . $lineEnding
                . 'You recently expressed interest in taking part in '
                . 'Lightdatasys fantasy football. Below is account '
                . 'information that will allow you to sign into the '
                . 'Lightdatasys web site (http://lightdatasys.com).'
                . $lineEnding . $lineEnding
                . 'Username: ' . $username . $lineEnding
                . 'Password: ' . $password . $lineEnding
                . $lineEnding
                . 'You may change your password in the "Preferences" section '
                . 'of the Lightdatasys web site.' . $lineEnding
                . $lineEnding
                . 'Be on the look out for another email with more information '
                . 'about the first week of Lightdatasys fantasy football. '
                . 'If you do not receive the week 1 email prior to Tuesday, '
                . 'September 7, please let the Commissioner know by emailing '
                . '<commissioner@lightdatasys.com>. The week 1 email gives '
                . 'information regarding the league rules and verifies that you '
                . 'are on the Lightdatasys fantasy football mailing list.'
                . $lineEnding . $lineEnding
                . 'If at anytime you wish to no longer receive emails, '
                . 'or would like to change the email address that you receive '
                . 'emails regarding fantasy football, please inform '
                . 'the Commissioner via email.' . $lineEnding
                . $lineEnding
                . '--------------------------------' . $lineEnding
                . 'Matt Light' . $lineEnding
                . 'Commissioner of Fantasy Sports' . $lineEnding
                . 'Lightdatasys' . $lineEnding
                . 'commissioner@lightdatasys.com' . $lineEnding;

            $mailer->sendMessage($to, $from, $subject, $message, $from);

            echo 'Sent to ' . $name . '<br />';
        }

        $this->printFooter();
    }
}



?>
