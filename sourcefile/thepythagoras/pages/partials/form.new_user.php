
<?php
       	$user_added = false;
        $game_added = false;
	$logged_in = is_logged_in(); 
?>




<section class="xxbg-gray-50 dark:bg-gray-900">
  <div class="max-w-screen-xl px-4 py-8 mx-auto lg:grid lg:gap-20 lg:py-16 lg:grid-cols-12">
  			<div class="flex-col justify-between hidden col-span-6 mr-auto lg:flex xl:mb-0">

					
					<?php
					if($add_site && !$game_added){ 
					?>
					<ul class="regnewtextnormal2">
						
							<li type="square">Receive High Quality Players & Ultra Targetted Traffic with <br/> High Conversion Rates.</li>
							<li type="square">Postback | Callback & Incentive systems to allow you <br/> reward your users 
										if they actually vote.</li>
							<li type="square">Advanced control panel which allow you to control all 
											your games under one global system.</li>
							<li type="square">Press | News Release & Video Trailer systems.</li>
							<li type="square">Unique gold based system with GiftShop.</li>
							<li type="square">Give away system.</li>
							<li type="square">Forum. </li><br/>
									<font color="#FC0">
									And Much More... <br/>
									<font style="font-size:24px;">Free!</font>
								</font>
						
							
					</ul>
					<?php } else { ?>
					
					<ul style="line-height: 11px;"> 

							<li type="square">Exclusive Resources.</li><br/>
							<li type="square">Discover top quality games.</li><br/>
							<li type="square">Rate them and make your opinion count.</li><br/>
							<li type="square">Make your vote count ten times more.</li><br/>
							<li type="square">Gain Gold & Exchange them for Gifts.</li><br/>
							<li type="square">Participate on Giveaways.</li><br/>
								<font color="#FC0">
									And Much More... <br/>
									<font style="font-size:24px;">Free!</font>
								</font><br/>

					</ul>
			
						
					<?php } ?>
					<p style="text-align:center;font-size:10px;">If you have any questions, do not hesitate and <a href="<?php echo $gc['path']['web_root'] ?>/contact"    
						target="_blank"><font color="#FC0">contact us</font></a>. 
						</p>
			</div>

			

			<!--- sep --->

			<div class="w-full col-span-6 mx-auto bg-white rounded-lg shadow dark:bg-gray-800 md:mt-0 sm:max-w-lg xl:p-0">
          		<div class="p-6 space-y-4 lg:space-y-6 sm:p-8">


						<form method="post" enctype="multipart/form-data" action="" onSubmit="return validate(this);">

					<section class="bg-white dark:bg-gray-900">
						<div class="py-8 px-4 mx-auto max-w-2xl lg:py-16">		


						<h2 class="mb-4 text-xl font-semibold leading-none text-gray-900 dark:text-white">Account details</h2>
						<div class="grid gap-4 mb-4 md:gap-6 md:grid-cols-2 sm:mb-8">
						


							<input type="hidden" name="extra" value="1" />

							<div>
								<label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
								<input type="text" name="username" id="username" class="required bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Username" required=""
								value="<?php echo $_POST['username']; ?>">
							</div>

							<div>
								<label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
								<input type="email" name="email" id="email" class="required req_email bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Username" required=""
								value="<?php echo $_POST['email']; ?>">
							</div>
							<div>
								<label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
								<input type="password" name="password" id="password" class="required bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="*********" required=""
								value="<?php echo $_POST['password']; ?>">
							</div>

							<div>
								<label for="password2" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm</label>
								<input type="password" name="password2" id="password2" class="required bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="*********" required=""
								value="<?php echo $_POST['password2']; ?>">
							</div>


						

							<div class="sm:col-span-2">				
								<div class="flex relative items-end gap-4">
									<div class="flex-grow">
										<script type="text/javascript">
											var gcaptcha_onloadCallback = function() {
											grecaptcha.enterprise.render('gcaptcha_element', {
												'sitekey' : '<?php echo $gc['api']['google']['recaptchav3_id']; ?>',
												'action': 'SIGNUP',
												});
											};
										</script>
										<div id="gcaptcha_element"></div>
										<script src="https://www.google.com/recaptcha/enterprise.js?onload=gcaptcha_onloadCallback&render=explicit" async defer></script>
									</div>
								</div>
								<?php if($captcha_error) { ?>
									<div class="text-sm text-red-400 self-start">
										<?php echo $captcha_error; ?>
									</div>
								<?php } ?>
							</div>


							<?php if($errors) { ?>
								<div class="sm:col-span-2">	
									<div class="text-sm font-semibold text-red-400 self-start">
										<?php echo "- ".implode("<br>- ",$errors); ?>
									</div>
								</div>
							<?php } ?>
							
							<div class="sm:col-span-2">
								<button name="submit_register" type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
									Sign up
								</button>
							</div>	





							</div>
						</div>
					</section>
						
						</form>

			
	


				</div>
			</div>
		

      </div>              
  </div>
</section>