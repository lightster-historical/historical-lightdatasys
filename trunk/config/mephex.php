<?php


require_once PATH_ROOT . 'errors.php';



define('DEBUG', true);


HttpError::initInstance(new Errors());


?>
