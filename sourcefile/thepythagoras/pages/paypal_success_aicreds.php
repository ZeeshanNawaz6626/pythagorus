<?php

require_once('../config.php'); 
only_by_permalink();

require_once($gc['path']['root_partials'].'/header.php'); 


//https://browsermmorpg.com/paypal_success_aicreds?PayerID=GKXV7JU4G3SLU&st=Completed&tx=53V54508995279934&cc=USD&amt=5.00&payer_email=paypal%40magicduel.com&payer_id=GKXV7JU4G3SLU&payer_status=VERIFIED&first_name=Manuel&last_name=Tanase&txn_id=53V54508995279934&mc_currency=USD&mc_fee=0.47&mc_gross=5.00&protection_eligibility=ELIGIBLE&payment_fee=0.47&payment_gross=5.00&payment_status=Completed&payment_type=instant&handling_amount=0.00&shipping=0.00&item_name=10%2C000%20AI%20Credits&item_number=A10K&quantity=1&txn_type=web_accept&option_name1=Userid&option_selection1=12277&payment_date=2023-01-27T00%3A29%3A13Z&receiver_id=W2M636ZLB7EH8&notify_version=UNVERSIONED&verify_sign=ASq7O0o8rhj1A-0cIW4sTwmwAwawADp6phpG8q6SCv-qayai4okdgIao


$pop_notification = "SUCCESS: Thank you for your purchase. Your AI credits will show up on your account in the next minutes. If for some reason you do not see the purchased credits in the next hour, please contact us.";
?>



<?php

require_once($gc['path']['root_partials'].'/footer.php'); 
require_once($gc['path']['root'].'/output.php');?>