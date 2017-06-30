<?php

// Send message
function sendMessage($chatID, $reply, $keyboard="") 
{
	$sendto = API_URL."sendMessage?chat_id=$chatID&reply_markup=$keyboard&parse_mode=markdown&text=".urlencode($reply);
	@file_get_contents($sendto);
}


// Calculator
function calculate_string($v=1) 
{
    $compute = create_function("", "return (" . $v . ");" );
    return 0 + $compute();
}

?>