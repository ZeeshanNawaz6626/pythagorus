
<?php 
require_once('../config.php'); 
only_by_permalink();

require_once($gc['path']['root'].'/lib/paypal/paypal.inc.php');

require_once($gc['path']['root_partials'].'/header.php'); 

?>

<br>
<br>

<div class="flex gap-4 m-4 p-4">
<div class="font-bold rounded bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white" >
<?php

    $paypal = new paypal();
    $paypal->price = $gc['prem_aicreds_price'];
    $paypal->ipn = $gc['path']['web_root'].'/lib/paypal_ipn.php'; //full web address to IPN script
    $paypal->enable_payment(); //one-time payment
    $paypal->add('currency_code', 'USD');
    $paypal->add('business', $gc['business']); //your paypal email address
    $paypal->add('item_name', $gc['prem_aicreds_item_name']);
    $paypal->add('item_number', $gc['prem_aicreds_item_number']);
    $paypal->add('quantity', 1);
    $paypal->add('return', $gc['path']['web_root'].'/paypal_success_aicreds');
    $paypal->add('cancel_return', $gc['path']['web_root']);

    $paypal->add('on0', 'Userid');
    $paypal->add('os0', $_SESSION['user']['id']);

?>
Purchase 10,000 Ai usage credits for $<?php echo $gc['prem_aicreds_price']; ?>

<?php
    $paypal->set_button('<div class="paypal_button"><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
    <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></div>');
    $paypal->output_form();
    ?>





    <input type="hidden" name="on0" value="Userid">
    <input name="os0" type="hidden" value="<?php echo $_SESSION['user']['id']; ?>" maxlength="200">
</div>
<div class="font-bold rounded bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white" >
<?php

    $paypal = new paypal();
    $paypal->price = $gc['prem_aicreds10_price'];
    $paypal->ipn = $gc['path']['web_root'].'/lib/paypal_ipn.php'; //full web address to IPN script
    $paypal->enable_payment(); //one-time payment
    $paypal->add('currency_code', 'USD');
    $paypal->add('business', $gc['business']); //your paypal email address
    $paypal->add('item_name', $gc['prem_aicreds10_item_name']);
    $paypal->add('item_number', $gc['prem_aicreds10_item_number']);
    $paypal->add('quantity', 1);
    $paypal->add('return', $gc['path']['web_root'].'/paypal_success_aicreds');
    $paypal->add('cancel_return', $gc['path']['web_root']);

    $paypal->add('on0', 'Userid');
    $paypal->add('os0', $_SESSION['user']['id']);

?> 
Purchase 100,000 Ai usage credits for $<?php echo $gc['prem_aicreds10_price']; ?>

<?php
    $paypal->set_button('<div class="paypal_button"><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
    <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></div>');
    $paypal->output_form();
    ?>


   




    <input type="hidden" name="on0" value="Userid">
    <input name="os0" type="hidden" value="<?php echo $_SESSION['user']['id']; ?>" maxlength="200">
</div>
</div>


<br/><br/>
       Tokens are fragments of words, 10000 tokens are about 7000 words. Each AI request varies in size, and will be charged a corresponding number of cresits proportional to its size.
       <br><br>
       You may purchase a credit pack multiple times, they will be incremented on your account for later use.



<?php 
require_once $gc['path']['root_partials'].'/footer.php';
require_once($gc['path']['root'] . '/output.php');
?>

