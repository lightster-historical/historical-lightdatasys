<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/cache/MultiKeyInstanceCache.php';


class IndexResponder extends LightDataSysResponder
{
    public function get($args)
    {
        $this->printHeader();

        $displayName = 'Guest';
        if($this->isLoggedIn())
            $displayName = '<em>' . $this->user->getUserName() . '</em>';

        //$rssParser = RSSDatabaseParser::parseFeedById(1);
        //$rssParser = RSSDatabaseParser::parseFeedById(2);

        /*$items = RSSDatabaseItem::getItems(array('nascar_cup', 'nfl'));
        foreach($items as $item)
        {
            echo '<a href="' . $item->getLink() . '" target="_blank">' . $item->getTitle() . '</a>'
                . ' -- ' . $item->getPublishDate()->format('q Q') . '<br />';
        }*/

        /*
        echo '<pre>';
        $key = array('test', 'abc');
        $cache = new MXT_MultiKeyInstanceCache(count($key));
        if($cache->containsKey($key))
            echo "Contains key\n";
        else
            echo "Does not contain key\n";
        print_r($cache);
        $cache->add($key, 'blah');
        if($cache->containsKey($key))
            echo "Contains key\n";
        else
            echo "Does not contain key\n";
        print_r($cache);
        $cache->replace($key, 'yep');
        print_r($cache);
        $key = array('asdf', '123');
        if($cache->containsKey($key))
            echo "Contains key\n";
        else
            echo "Does not contain key\n";
        print_r($cache);
        $cache->replace($key, 'yep');
        print_r($cache);
        echo '</pre>';
        */

        /*
        $access = '19R0QPKQQ61ABQWYF4G2';
        $secret = 'dKnx1Ts6kZH7uCIaJXZNi98HBpPPr10tU90sauBZ';

        $params = array
        (
            'Service' => 'AWSECommerceService',
            'AWSAccessKeyId' => $access,
            'Operation' => 'ItemLookup',
            'IdType' => 'UPC',
            'ItemId' => '024543554646',
            'SearchIndex' => 'DVD',
            'ResponseGroup' => 'ItemAttributes,Offers,Images,Reviews',
            'Version' => '2009-01-06',
            'Timestamp' =>
                //'2009-01-01T12:00:00Z'
                gmdate('Y-m-d') . 'T' . gmdate('H:i:s') . 'Z'
        );
        ksort($params);
        print_r($params);

        $rawParams = array();
        foreach($params as $key => $val)
        {
            $rawParams[] = urlencode($key) . '=' . urlencode($val);
        }

        echo '<br />';
        $qs = implode('&', $rawParams);
        echo $qs;

        $toSign  = "GET\n";
        $toSign .= "webservices.amazon.com\n";
        $toSign .= "/onca/xml\n";
        $toSign .= $qs;

        echo '<pre>';
        echo $toSign;
        echo '</pre>';

        $sig = urlencode(base64_encode(hash_hmac('sha256', $toSign, $secret, true)));
        echo $sig . '<br />';

        $url = 'http://webservices.amazon.com/onca/xml?'
            . $qs . '&Signature=' . $sig;
        echo '<a href="' . $url . '" target="_blank">Go</a><br />';
        */


        ?>
         <h3>Hello <?php echo $displayName; ?>!</h3>
        <?php
        if(!$this->isLoggedIn())
        {
            ?>
             Please <a href="/user/sign-in.php">sign in</a>.
            <?php
        }
        ?>
         Here are some quick links:
         <ul>
          <li><a href="/nascar">NASCAR</a></li>
          <li><a href="/nfl/fantasy/picks.php">Fantasy Football Picks</a></li>
          <li><a href="/nfl/fantasy/standings.php">Fantasy Football Standings</a></li>
         </ul>
        <?php
        /*
         <div style="position: relative; height: 400px; ">
          <div style="width: 20px; height: 300px; background: #66ff66; position: absolute; left: 0; bottom: 50px; ">
          </div>
          <div style="width: 20px; height: 100px; background: #ff6666; position: absolute; left: 20px; bottom: 50px; ">
          </div>
         </div>
         */

        $this->printFooter();
    }
}



?>
