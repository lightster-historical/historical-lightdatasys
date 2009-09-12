<?php


require_once 'path.php';


require_once PATH_LIB . 'com/mephex/framework/HttpResponder.php';


class PageResponder extends HttpResponder
{
    public function get($args)
    {
        phpinfo();
    }
}


?>
