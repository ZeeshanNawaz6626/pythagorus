

     <?php if(isset($_SESSION['user'])){ ?>
          <!-- end of mt-11 -->
     <?php } ?>    

     <footer class="flex flex-wrap w-full bg-black/[.49] flex-col">
          <div class="topFoot border-b border-white/[.20]">
               <div class="container mx-auto flex justify-between items-center Logo py-5">
                    <a href="/"><img class="w-[70px]" src="<?php echo $gc['path']['web_root']; ?>/images/logo.png" alt="Pythagoras AI Oracle"></a>
                    <div class="footNav">
                         <ul>
                              <li><a href="<?php echo $gc['path']['web_root']; ?>/v2/pages/info/privacy.php">Terms of Conditions</a></li>
                              <li><a href="<?php echo $gc['path']['web_root']; ?>/v2/pages/info/privacy.php">Privacy Policy</a></li>
                         </ul>                         
                    </div>
               </div>
          </div>  
          <div class="botmFoot">
               <div class="container mx-auto text-center py-5">
                    <p>Copyright 2024 All Rights Are Reserved PYTHAGORAS AI</p>
               </div>
          </div>   
     </footer>

     <div id="layers" class="w-full h-full fixed top-0 left-0 right-0 overflow-hidden z-0">
       <div class="layer layer-1"></div>
       <div class="layer layer-2"></div>
       <div class="layer layer-3"></div>
       <div class="layer layer-4"></div>
       <div class="layer layer-5"></div>
       <div class="layer layer-6"></div>
       <div class="layer layer-7"></div>
     </div>

</div>

     <script src="<?php echo $gc['path']['web_root']; ?>assets/js/custom-script.js"></script>

</body>
</html>
