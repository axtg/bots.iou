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


// Log output
function checkJSON($chatID,$update)
{
	$myFile = "log.txt";
	$updateArray = print_r($update,TRUE);
	$fh = fopen($myFile, 'a') or die("can't open file");
	fwrite($fh, $chatID ."\n\n");
	fwrite($fh, $updateArray."\n\n");
	fclose($fh);
}

?>