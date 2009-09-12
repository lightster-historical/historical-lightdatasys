
        <?php
        $responder->printDebugInfo();
        ?>
       </div>
      </div>
      <div class="footer">
       <div class="nav-footer-container">
        <div class="nav">
         <ul>
          <?php
          $responder->printNav($responder->getFooterNav());
          ?>
         </ul>
         <ul style="float: right; ">
          <li>
           <a href="http://lightdatasys.com">
            Copyright &#0169; 2007 &#8211; <?php echo Date::now('Y'); ?> Lightdatasys
           </a>
          </li>
         </ul>
         <br class="clear" />
        </div>
       </div>
       </div>
       <?php
       $responder->printDebugStats();
       ?>
      </div>
     </div>
    </div>
   </div>
  </body>
 </html>
