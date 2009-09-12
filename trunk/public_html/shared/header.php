<?php
$keys = Navigation::getKeysFromFileName($_SERVER['PHP_SELF']);
?>
 <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title><?php echo $this->getDocumentTitle(); ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" href="/shared/style.css" />
  <script src="/shared/global.js" type="text/javascript"></script>
  <?php echo $this->printExtendedHTMLHead(); ?>
 </head>
 <?php echo $this->printOpenBodyTag(); ?>
  <div id="outer-container">
  <div id="middle-container">
  <div id="inner-container">
   <div id="container">
   <div id="header-container">
    <div id="header">
     <div id="logo">
      <h1>Lightdatasys</h1>
     </div>
     <div id="header-content">
      <p id="welcome-message">
       <?php $responder->printWelcomeMessage(); ?>
      </p>
     </div>
    </div>
    <div class="nav-container">
     <div class="nav">
      <ul style="float: right; ">
       <?php
       $responder->printNav($responder->getGlobalNav());
       $responder->printHelpNavItem();
       ?>
      </ul>
      <ul>
       <?php
       $responder->printNav($responder->getMainNav());
       ?>
      </ul>
      <br style="clear: both; " />
     </div>
    </div>
   </div>
   <div id="content-container">
    <div id="page-header">
     <?php
     $responder->printNavHierarchy();
     $responder->printPageTitle();
     $responder->printPageNav();
     $responder->printSelector();
     ?>
    </div>
    <div id="content">
