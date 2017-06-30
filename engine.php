<?php 
// Include basics
include 'config.php';


/*
 *
 * INITIATE SCRIPT
 *
 */

// Read incoming info to decoded JSON array
$content 		= file_get_contents("php://input");
$update 		= json_decode($content, true);
$zero			= 0;
$one 			= 1;

// Set variables for webhook request
if(isset($update["message"]["chat"]["id"]) && !empty($update["message"]["chat"]["id"]))
{
	$chatID 	= $update["message"]["chat"]["id"];						// In what chat group?
	$title 		= $update["message"]["chat"]["title"];					// With what chat group title?
	$fromID		= $update["message"]["from"]["id"]; 					// Who? 
	$firstname 	= $update["message"]["from"]["first_name"];				// Let's be friendly
	$lastname 	= $update["message"]["from"]["last_name"];				// cont'd (opt)
	$nickname 	= $update["message"]["from"]["username"];				// cont'd (opt)
	$message 	= $update["message"]["text"];							// What command?
}

// Set variables for callback request
elseif(isset($update["callback_query"]) && !empty($update["callback_query"]["data"])) 
{
	$chatID		= $update['callback_query']['message']['chat']['id'];		// In what chat group?
	$title		= $update['callback_query']['message']['chat']['title'];	// With what chat group title?
	$fromID		= $update['callback_query']["from"]["id"]; 					// Who? 
	$firstname 	= $update['callback_query']["from"]["first_name"];			// Let's remain friendly
	$message	= $update['callback_query']['data'];						// Get callback command
}

// Get current userID
$user = $dbh->prepare("SELECT userID 
						FROM sb_users
						WHERE telegramID=:telegramID AND chatID=:chatID");
$user->bindValue(':telegramID', $fromID, PDO::PARAM_INT);
$user->bindValue(':chatID', $chatID, PDO::PARAM_INT);
$user->execute();
$userID = $user->fetchColumn(0);


/* 
 *
 * SANITY CHECKS
 *
 */

// Not a valid request
if(is_null($chatID)) {
	die("Not a valid request");
}


/* 
 *
 * COMMAND ROUTING
 * Three types here, anything 1-to-1 ($chatID>0), group ($chatID<0) or both ($chatID<>0).
 *
 */ 

/*
 * All commands that work for individual chats ($chatID>0)
 */
if($chatID>0) 
{

	//
	// I1. Command /start or /settlehelp
	//     To show some basic instructions how to use this bot
	//
	if(stripos($message, '/start') !== false || stripos($message, '/settlehelp') !== false) 
	{
		// [MSG_FAIL]
		$reply	= "Hi $firstname!\n";
		$reply .= "If you chat with me individually, you can list your transactions made in any group ";
		$reply .= "without bothering others `(/payments)`. Or set your IBAN for others to refer to `/setiban`.";
		
		// Set processed and send message
		$indprocessed = 1;
		sendMessage($chatID, $reply);
		
		exit();
	}

	//
	// I2. Command /paid
	//     Throw appropriate message when trying to add in individual chat
	//
	elseif(stripos($message, '/paid') !== false) 
	{
		$reply = "To be transparant ".ucfirst($firstname).", you can only add transactions within a chat group.";
		
		// Set processed and send message
		$indprocessed = 1;
		sendMessage($chatID, $reply);
		
		exit();
	}
}



/*
 * All commands that work for group chats ($chatID<0)
 */
elseif($chatID<0) 
{

	// 
	// G1. Command /start or /settlehelp
	//     To show some basic instructions how to use this bot
	//
	if(stripos($message, '/start') !== false || stripos($message, '/settlehelp') !== false) 
	{
		// [MSG_SUCCESS]
		$reply  = "Hi there $firstname!\n\n";
		$reply .= "I am listening in on this chat group for certain commands. ";
		$reply .= "My aim is to make money settlements between friends as easy as possible.";
		sendMessage($chatID, $reply);
		sleep(3);

		$reply  = "For example, type `/paid 5.25` to add 5.25 as an expense made by you. ";
		$reply .= "Then type `/payments` to see all of your *unsettled* transactions. ";
		$reply .= "Should a chat group member not be included in a current settlement? Exclude him or her ";
		$reply .= "using the `/ignore` command. Or change someone's share if a '+1' is involved using `/plus`.\n";
		$reply .= "Ultimately, you can propose to settle open expenses using `/settle`.\n\n\u{1F44B} Bye!";
		sendMessage($chatID, $reply);
		exit();
	} 

	// 
	// G2. Command /paid
	//     To have a group chat member add an individual payment made (positive or negative)
	//
	elseif(stripos($message, '/paid') !== false) 
	{
		// Check if user is not currently excluded
		$exc = $dbh->prepare("SELECT distinct u.excluded 
								FROM sb_users u 
								WHERE u.chatID=:chatID AND u.telegramID=:telegramID");
		$exc->bindValue(':chatID', $chatID, PDO::PARAM_INT);
		$exc->bindValue(':telegramID', $fromID, PDO::PARAM_INT);
		$exc->execute();
		
		if($exc->fetchColumn(0)==1) 
		{
			$reply = "Can't do ".ucfirst($firstname)."!\nYou are currently excluded from settlements in this group.";
		}
		//
		else
		{
			// Set variables
			$amount	= preg_replace("/[^0-9-+,.]/", "", $message); 	// Remove anything non-numeric, allow negative
			$amount = str_replace(',', '.', $amount);				// Replace , with . for number_format

			// Check if amount was mentioned with /paid command
			if(isset($amount) && !empty($amount)) 
			{
				// Calculate if needed
				$amount = calculate_string($amount);

				// Get existing IBAN if any
				$iban = $dbh->prepare("SELECT iban 
										FROM sb_users
										WHERE telegramID=:telegramID");
				$iban->bindParam(':telegramID', $fromID, PDO::PARAM_INT);
				$iban->execute();
				$iban = (verify_iban($iban->fetchColumn(0))?$iban->fetchColumn(0):NULL);

				// Do database insert/ update query for user in this group
				$stmt = $dbh->prepare("INSERT INTO sb_users (telegramID, chatID, title, firstname, lastname, nickname, iban) 
										VALUES (:telegramID, :chatID, :title, :firstname, :lastname, :nickname, :iban)
										ON duplicate key 
										UPDATE userID=LAST_INSERT_ID(userID), telegramID=:telegramID, title=:title, iban=:iban");
				$stmt->bindParam(':telegramID', $fromID, PDO::PARAM_INT);
				$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);
				$stmt->bindParam(':title', $title, PDO::PARAM_STR);
				$stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
				$stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
				$stmt->bindParam(':nickname', $nickname, PDO::PARAM_STR);
				$stmt->bindParam(':iban', $iban, PDO::PARAM_STR);
				$stmt->execute(); 
				$userID = $dbh->lastInsertId();

				// Do database query for transaction
				$stmt = $dbh->prepare("INSERT INTO sb_transactions (userID, amount) 
										VALUES (:userID, :amount)");
				$stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
				$stmt->bindParam(':amount', $amount);

				// Calculate old amount (before insert execution)
				$sum_query = $dbh->prepare("SELECT SUM(t.amount) AS sum  
											FROM sb_users u, sb_transactions t
											WHERE u.userID=:userID AND u.userID=t.userID AND u.chatID=:chatID AND t.settled=:settled");
				$sum_query->bindParam(':userID', $userID, PDO::PARAM_INT);
				$sum_query->bindParam(':chatID', $chatID, PDO::PARAM_INT);
				$sum_query->bindParam(':settled', $zero, PDO::PARAM_INT);
				$sum_query->execute();
				$old_amount = $sum_query->fetchColumn();

				// Execute the database statement
				if($stmt->execute()) 
				{
					// [MSG_SUCCESS] Build success reply to Telegram
					$reply  = "\u{2714} *".number_format($amount, 2)."* for $firstname, ";
					$reply .= "now totalling at *".number_format(($old_amount+$amount), 2)."*";
				}
			}

			// If no amount mentioned
			else
			{
				// [MSG_FAIL] If we couldn't derive an amount
				$reply	= "Did you mention an amount just now?\n\u{1F50E} I couldn't find it. Try using `/paid 7.50` or even `/paid 5+12.50+8`";
			}
		}

		// Execute
		sendMessage($chatID, $reply);
		exit();
	}


	// 
	// G3. Command /getiban
	//     To show all entered IBANs for this group
	//
	elseif (stripos($message, '/getiban') !== false) 
	{

		// Do database query
		$ibans = $dbh->query("SELECT distinct u.userID AS uid, u.firstname, u.iban 
								FROM sb_users u
								WHERE u.chatID=$chatID AND u.iban IS NOT NULL");

		// Build success reply to Telegram
		if($ibans->rowCount()>0) 
		{
			// [MSG_SUCCESS]
			$reply = ucfirst($firstname).", these are the IBANs I have on file:";
			foreach ($ibans as $row) {
				$reply .= "\n- ".ucfirst($row['firstname']).": `".strtoupper($row['iban']."`");
			}
		} 
		else 
		{ 
			// [MSG_FAIL]
			$reply = "\u{1F3DC} I have *no* IBANs on file for this chat group."; 
		}

		$keyb 	= array('inline_keyboard' => array(
					array(
						array('text'=>'Mark all transactions as settled', 'callback_data'=>'/reset')
					)
				)
			);

		$replyMarkup = json_encode($keyb);

		// Execute
		sendMessage($chatID, $reply, $replyMarkup);
		exit();
	}


	// 
	// G4. Command /settle or /suggest
	//     To summarize the open settlements and suggest payments
	//
	elseif (stripos($message, '/settle') !== false || stripos($message, '/suggest') !== false) 
	{
		// Do database select queries (for both /settle, /suggest)
		// GRAND SUM (single value)
		$gs = $dbh->prepare("SELECT SUM(t.amount) as sum 
							FROM sb_users u, sb_transactions t 
							WHERE u.userID=t.userID AND u.excluded=:excluded AND u.chatID=:chatID AND t.settled=:settled");
		$gs->bindValue(':excluded', $zero, PDO::PARAM_INT);
		$gs->bindValue(':settled', $zero, PDO::PARAM_INT);
		$gs->bindValue(':chatID', $chatID, PDO::PARAM_INT);
		$gs->execute();
		$grand_sum = $gs->fetchColumn(0);

		if($grand_sum!=0) 
		{
			// MEMBER COUNT (array)
			$mem = $dbh->prepare("SELECT u.telegramID, u.plus
									FROM sb_users u
									WHERE u.excluded=:excluded AND u.chatID=:chatID");
			$mem->bindValue(':excluded', $zero, PDO::PARAM_INT);
			$mem->bindValue(':chatID', $chatID, PDO::PARAM_INT);
			$mem->execute();
			$rsMembers 	= $mem->fetchAll(PDO::FETCH_ASSOC);

			// Calculation(s)
			$members = 0;
			foreach ($rsMembers as $value) 
			{
				$members = $members+$value['plus'];
			}
			$should_have = ($grand_sum/$members);

			//
			// G4A. Command /settle
			//      To ...
			//
			if(stripos($message, '/settle') !== false) 
			{
				// [MSG_SUCCESS] Populate Telegram messages
				$reply  = "Together, you spend `".number_format($grand_sum,2)."`.\n";
				$reply .= "If I include $members people in this calculation, ";
				$reply .= "per person you should have paid `".number_format($should_have,2)."`.\n";

				$debtsum = array();
				foreach ($rsMembers as $row) 
				{

					// PER MEMBER (array)
					$per = $dbh->prepare("SELECT u.firstname, u.plus, SUM(t.amount) as sumpm 
											FROM sb_users u, sb_transactions t
											WHERE u.userID=t.userID AND u.telegramID=:telegramID AND u.chatID=:chatID AND (t.settled IS NULL OR t.settled=:settled)");

					$per->bindValue(':telegramID', $row['telegramID'], PDO::PARAM_INT);
					$per->bindValue(':chatID', $chatID, PDO::PARAM_INT);
					$per->bindValue(':settled', $zero, PDO::PARAM_INT);
					$per->execute();
					$permember	= $per->fetch();
					$debtsum[]	= array('firstname'=>$permember['firstname'], 
										'plus'=>$permember['plus'], 
										'sumpm'=>$permember['sumpm']);

					// Set appropriate message (plus or minus)
					$pp 	= number_format(($permember['sumpm']-($should_have*$permember['plus'])),2);
					$plus 	= ($permember['plus']>1?" (+".($permember['plus']-1).")":"");

					// If negative balance, thus too little paid
					if($pp<0) 
					{
						$reply .= "\n\u{1F538} ".ucfirst($permember['firstname'])."$plus paid `".number_format($permember['sumpm'],2)."` (`$pp`)";
					}
					// If positive balance, thus excess paid
					elseif ($pp>0) 
					{

						$reply .= "\n\u{1F539} ".ucfirst($permember['firstname'])."$plus paid `".number_format($permember['sumpm'],2)."` (`+$pp`)";
					}
					// If exactly enough
					elseif ($pp==0) 
					{
						$reply .= "\n\u{25AB} ".ucfirst($permember['firstname'])."$plus paid exactly `".number_format($permember['sumpm'],2)."`.";
					}
					$pp=null;

				}

				// Share outcome
				sendMessage($chatID, $reply);
				sleep(3);

				// [MSG_SUCCESS] Ask for next step, provide inline keyboard
				$reply 	 = "*So what is next?*";
				$reply 	.= "\nNeed help to decide who pays what to whom? ";
				$reply 	.= "Maybe in- or exclude a group chat member from this settlement? ";
				$reply  .= "Or add a '+1' for someone?";
				$keyb 	= array('inline_keyboard' => array(
								array(
									array('text'=>'Suggest payments', 'callback_data'=>'/suggest'),
									array('text'=>'In- or exclude person', 'callback_data'=>'/ignore')
								),
								array(
									array('text'=>"Add a %2B1", 'callback_data'=>'/plus'),
									array('text'=>'Show IBAN', 'callback_data'=>'/getiban'),
									array('text'=>'Mark settled', 'callback_data'=>'/reset')
									)
					        )
					    );

				$replyMarkup = json_encode($keyb);

				// Send options
				sendMessage($chatID, $reply, $replyMarkup);
				exit();
			}


			//
			// G4B. Command /suggest
			//      To ...
			//
			elseif (stripos($message, '/suggest') !== false) 
			{
				// Get (again!) sum of payments
				$debt = array();
				foreach ($rsMembers as $row) 
				{

					// PER MEMBER (array)
					$per = $dbh->prepare("SELECT u.firstname, u.plus, SUM(t.amount) as sumpm 
											FROM sb_users u, sb_transactions t
											WHERE u.userID=t.userID AND u.telegramID=:telegramID AND u.chatID=:chatID AND (t.settled IS NULL OR t.settled=:settled)");

					$per->bindValue(':telegramID', $row['telegramID'], PDO::PARAM_INT);
					$per->bindValue(':chatID', $chatID, PDO::PARAM_INT);
					$per->bindValue(':settled', $zero, PDO::PARAM_INT);
					$per->execute();
					$permember	= $per->fetchAll();

					// Calculate the excess or debt per user
					foreach($permember as $row) 
					{
						$val 	= ($row['sumpm']-($should_have*$row['plus']));	// Dif paid vs should have
						$user 	= rand(100, 999).$row['firstname'];				// What user?
						if($val!=0) 
						{
							$debt[$user] = $val;								// Set array with telegramID as key
						}
						asort($debt);											// Sort higest debt to lowest
					}
				}

				// [MSG_SUCCESS]
				$reply = "\u{1F4A1} Okay, this is what I feel would be easiest:\n\n";

				// Loop through debtors, calculate efficient settlement
				$i=0;
				if(count($debt)>1) 
				{
					while ($i < $members) 
					{
						// If largest excess > largest debtor
						if(round((max($debt)+min($debt)),2)>0)
						{
							// Find and set array keys and values, max()=excessor; min()=debtor
							$key 		= array_search(max($debt), $debt);
							$pos 		= array_search(min($debt), $debt);
							$receiver	= array_keys($debt, $debt[$key]);
							$sender		= array_keys($debt, $debt[$pos]);

							// Calculate remainder (only after having found key (!))
							$debt[$key] = $debt[$key]+min($debt);

							// Messaging
							$reply 		.= substr(ucfirst($sender[0]), 3)." pays `".number_format((min($debt)*-1),2);
							$reply 		.= "` to ".substr(ucfirst($receiver[0]), 3);
							$reply 		.= ", who then still is to receive `".number_format($debt[$key], 2)."`.\n";

							// Now the message is parsed, we can drop the "used" array
							unset($debt[$pos]);
						}
						// If largest excess < largest debtor (needs split) or last loop
						elseif(round((max($debt)+min($debt)),2)<=0)
						{
							// Find and set array keys and values
							$key 		= array_search(min($debt), $debt);
							$pos 		= array_search(max($debt), $debt);
							$sender		= array_keys($debt, $debt[$key]);	// min() owner = sender
							$receiver	= array_keys($debt, $debt[$pos]);	// max() owner = receiver

							// Calculate remainder (only after having found key (!))
							$debt[$key]	= $debt[$key]+max($debt);

							// [MSG_SUCCESS]
							$reply .= substr(ucfirst($sender[0]), 3)." pays `".number_format(max($debt),2);
							$reply .= "` to ".substr(ucfirst($receiver[0]), 3).".\n";

							// No more use for the array we used last
							unset($debt[$pos]);

							// [MSG_SUCCESS] If last loop specifically
							if(count($debt)<=1)
							{
								$reply .= "\n\u{1F44C} All done!";
								break;
							}

							
						}
						$i++;
					}
				} 
				else 
				{
					$reply = "\u{3297} You all paid the same amount!";
				}
				// Share outcome
				sendMessage($chatID, $reply);
				sleep(3);

				// [MSG_SUCCESS] Propose next step
				$reply 	 = "Does this calculation need adjustment? You can still in- or exclude a person, ";
				$reply  .= "or add a '+1'. Otherwise, make the transfer and mark these as settled.";
				$keyb 	 = array('inline_keyboard' => array(
								array(
									array('text'=>'In- or exclude person', 'callback_data'=>'/ignore'),
									array('text'=>"Add a %2B1", 'callback_data'=>'/plus')
								),
								array(
									array('text'=>'Show IBAN', 'callback_data'=>'/getiban'),
									array('text'=>'Mark as settled', 'callback_data'=>'/reset')
					            )
					        )
					    );

				$replyMarkup = $keyb;
				$encodedMarkup = json_encode($replyMarkup);

				// Send options
				sendMessage($chatID, $reply, $encodedMarkup);
				exit();
			}
		} 

		// If there is nothing to settle
		elseif ($grand_sum==0) 
		{
			$reply = "\u{1F4A9} There is nothing to settle!";
			sendMessage($chatID, $reply, $replyMarkup);
			exit();
		}
	}


	//
	// G5. Command /reset
	//     To set all open settlement amounts to settled=1
	//
	if(stripos($message, '/reset') !== false) 
	{

		// Do database query
		$stmt = $dbh->prepare("UPDATE sb_users u, sb_transactions t
								SET settled=:settled, excluded=:excluded
								WHERE u.userID=t.userID AND u.chatID=:chatID");
		$stmt->bindParam(':settled', $one, PDO::PARAM_INT);
		$stmt->bindParam(':excluded', $zero, PDO::PARAM_INT);
		$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);

		// Execute database statement
		if($stmt->execute())
		{
			// [MSG_SUCCESS] Build success reply to Telegram
			$reply  = "Excellent! Everyone has paid their share \u{1F4B8}";
			$reply .= "You can start adding new expenses. If needed, remember to re-include ";
			$reply .= "any previously excluded group chat members.";
		}

		// Execute
		sendMessage($chatID, $reply);
		exit();
	} 


	//
	// G6. Command /ignore
	//     To set a group chat member as ignored for next settlement
	//
	if(stripos($message, '/ignore') !== false) 
	{
		// Set variables
		$user 		= str_replace('/ignore', '', $message);
		$user 		= trim($user);
		
		// List available users, no user specified
		if (strlen($user)==0) 
		{
			// Do database query
			$stmt = $dbh->prepare("SELECT distinct telegramID, firstname, excluded 
									FROM sb_users
									WHERE chatID=:chatID");
			$stmt->bindValue(':chatID', $chatID, PDO::PARAM_INT);
			$stmt->execute();

			foreach($stmt as $row)
			{
				$fname 		=($row['excluded']==1?"[X] ".$row['firstname']:$row['firstname']);
				$options[]	=['text'=>urlencode($fname),'callback_data'=>'/ignore '.$row['telegramID']];
			}

			$reply 			 = "Select a group chat member that you would like to in- or exclude from ";
			$reply 			.= "the current settlement. Use /plus if you would like to attribute someone a '+1'\n";

			sendMessage($chatID, $reply, json_encode(['inline_keyboard'=>array_chunk($options,3)]));
			exit();
		}
		// Run when a user is specified
		elseif(is_numeric($user) && strlen($user)>=1) 
		{
			// Check existance of user
			$stmt = $dbh->prepare("SELECT distinct firstname, userID, excluded 
									FROM sb_users 
									WHERE telegramID=:telegramID AND chatID=:chatID 
									LIMIT 1");
			$stmt->bindValue(':telegramID', $user, PDO::PARAM_INT);
			$stmt->bindValue(':chatID', $chatID, PDO::PARAM_INT);
			$stmt->execute();
			$member 	= $stmt->fetch(PDO::FETCH_ASSOC);
			$userID		= $member['userID'];
			$excluded	= ($member['excluded']>0?0:1);
			
			// Update excluded for this user in this chat to 1
			$stmt = $dbh->prepare("UPDATE sb_users 
									SET excluded=:excluded 
									WHERE userID=:userID AND chatID=:chatID");
			$stmt->bindParam(':excluded', $excluded, PDO::PARAM_INT);
			$stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
			$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);

			// Execute database statement
			if($stmt->execute())
			{
				// Draft message for new excluded
				if ($excluded==1) 
				{	
					$reply  = ucfirst($member['firstname'])." is now excluded from this and future settlements. ";
					$reply .= "To include ".ucfirst($member['firstname'])." again, toggle the same command.";
				}
				// Draft message for reinstated (included) member
				elseif ($excluded==0) 
				{
					$reply  = ucfirst($member['firstname'])." is now again included in group settlements.";
				}
				$keyb 	= array('inline_keyboard' => array(
								array(
									array('text'=>'Recalculate settlement', 'callback_data'=>'/settle'),
									array('text'=>'In- or exclude others', 'callback_data'=>'/ignore')
					            )
					        )
					    );

				$replyMarkup = $keyb;
				$encodedMarkup = json_encode($replyMarkup);
			}
			
			// Send options
			sendMessage($chatID, $reply, $encodedMarkup);
			exit();
		}
	} 


	//
	// G7. Command /plus
	//     To add a virtual group chat member for next settlement
	//
	if(stripos($message, '/plus') !== false) 
	{

		// Set variables
		$plusID = preg_replace("/[^0-9]/", "", $message);

		if(!empty($plusID) && is_numeric($plusID) && $plusID>0)
		{
			// Get current number of plus's
			$gs = $dbh->prepare("SELECT plus 
								 FROM sb_users 
								 WHERE telegramID=:telegramID AND chatID=:chatID");
			$gs->bindValue(':telegramID', $plusID, PDO::PARAM_INT);
			$gs->bindValue(':chatID', $chatID, PDO::PARAM_INT);
			$gs->execute();
			$plusnr = ($gs->fetchColumn(0)+1);
			$plusnr = ($plusnr>5?1:$plusnr);

			// Do database query
			$stmt = $dbh->prepare("UPDATE sb_users 
									SET plus=:plus 
									WHERE telegramID=:telegramID AND chatID=:chatID");
			$stmt->bindParam(':plus', $plusnr, PDO::PARAM_INT);
			$stmt->bindParam(':telegramID', $plusID, PDO::PARAM_INT);
			$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);

			// Execute database statement
			$stmt->execute();
		}

		// If no ID specified just yet, show list of user options
		$stmt = $dbh->prepare("SELECT distinct telegramID, firstname, plus 
								FROM sb_users 
								WHERE excluded=:excluded AND chatID=:chatID");
		$stmt->bindValue(':excluded', $zero, PDO::PARAM_INT);
		$stmt->bindValue(':chatID', $chatID, PDO::PARAM_INT);
		$stmt->execute();

		// Loop through all members and create individual buttons
		foreach($stmt as $row)
		{
			$options[]	=['text'=>urlencode($row['firstname'])." (%2B".($row['plus']-1).")",'callback_data'=>'/plus '.$row['telegramID']];
		}

		// [MSG_SUCCESS]
		$reply 	= (empty($plusID)?"*Select group member*\n":"Ready? Then redo /settle. ");
		if(empty($plusID))
		{
			// Set reply message when no ID is set
			$reply  	.= "Make a group member pay for someone _not_ in this chat.\n\n";
			$reply 		.= "Add up to four '+1's per person, click a name to add one, ";
			$reply 		.= "five clicks resets the count to 0. Or /settle instead.";
		}
		
		// Send options
		sendMessage($chatID, $reply, json_encode(['inline_keyboard'=>array_chunk($options,3)]));
		exit();
	} 


	// 
	// G2. Command /settlehi
	//     To allow a new member to join settlement without using /paid
	//
	elseif(stripos($message, '/settlehi') !== false) 
	{
		// Get existing IBAN if any
		$iban = $dbh->prepare("SELECT iban 
								FROM sb_users
								WHERE telegramID=:telegramID");
		$iban->bindParam(':telegramID', $fromID, PDO::PARAM_INT);
		$iban->execute();
		$iban = (verify_iban($iban->fetchColumn(0))?$iban->fetchColumn(0):NULL);
		
		// Do database query for user
		$stmt = $dbh->prepare("INSERT INTO sb_users (telegramID, chatID, title, firstname, lastname, nickname, iban) 
								VALUES (:telegramID, :chatID, :title, :firstname, :lastname, :nickname, :iban)
								ON duplicate key 
								UPDATE userID=LAST_INSERT_ID(userID), title=:title, firstname=:firstname, lastname=:lastname, nickname=:nickname, iban=:iban");
		$stmt->bindParam(':telegramID', $fromID, PDO::PARAM_INT);
		$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);
		$stmt->bindParam(':title', $title, PDO::PARAM_STR);
		$stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
		$stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
		$stmt->bindParam(':nickname', $nickname, PDO::PARAM_STR);
		$stmt->bindParam(':iban', $iban, PDO::PARAM_STR);

		// Execute the database statement
		if($stmt->execute()) 
		{
			// [MSG_SUCCESS] Build success reply to Telegram
			$reply  = "\u{1F590} Mucho gusto $firstname!\nWant to know what I can do? Check-out /settlehelp.";
		}

		// Execute
		sendMessage($chatID, $reply);
		exit();
	}


	//
	// XX. Easter eggs
	//     Just because we can
	//
	elseif(stripos($message, '/boeboe') !== false) 
	{
		// [MSG_SUCCESS] 
		$reply  = "\u{1F48F} Boetjieboeboe!";

		// Execute
		sendMessage($chatID, $reply);
		exit();
	} 
}


/*
 * All commands that work for both chat types ($chatID>0, $chatID<0)
 */

//
// B1. Command /setiban
//     To allow a user to set his or her IBAN for other members to see
//
if (stripos($message, '/setiban') !== false) 
{
	// Set variables
	$iban 	= str_replace('/setiban', '', $message);		// Strip command
	$iban 	= preg_replace('/\s+/', '', strtoupper($iban));	// Strip all remaining whitespace

	// Validate IBAN
	if(verify_iban($iban))
	{
		// Do database query
		$stmt = $dbh->prepare("UPDATE sb_users
								SET iban=:iban 
								WHERE telegramID=:telegramID");
		$stmt->bindParam(':iban', $iban, PDO::PARAM_STR);
		$stmt->bindParam(':telegramID', $fromID, PDO::PARAM_INT);
		$stmt->execute();
	
		// Execute database statement
		if($stmt->rowCount()>0)
		{
			// [MSG_SUCCESS] Build success reply to Telegram
			$reply = "\u{2714} IBAN *$iban* set for ".ucfirst($firstname);
		} 
		else 
		{
			$reply  = "I don't think we have been properly introduced. Try `/settlehi` in any group chat first, ";
			$reply .= "before setting you IBAN."; 
		}
	} 

	// If incorrect IBAN
	else 
	{
		// [MSG_FAIL] If incorrect IBAN
		$reply  = "\u{1F913} My maths told me that this IBAN doesn't quite work. Could you check again? ";
		$reply .= "Remember, type both the command and IBAN `/setiban NL01..` .";
	}

	// Execute
	sendMessage($chatID, $reply);
	exit();
}


//
// B2. Command /payments
//     To list all individual - non-settled - payments
// 
elseif (stripos($message, '/payments') !== false) 
{
	// Set variables
	$groupID = ($chatID>0?preg_replace("/[^0-9-]/", "", $message):$chatID);

	// In individual chat, a list of transactions for what group?
	if ($groupID>0 || empty($groupID) || !is_numeric($groupID)) 
	{
		// Get all the groups this member is a part of
		$stmt = $dbh->prepare("SELECT chatID, title 
								FROM sb_users 
								WHERE telegramID=:telegramID 
								GROUP BY chatID");
		$stmt->bindValue(':telegramID', $fromID, PDO::PARAM_INT);
		$stmt->execute();

		if($stmt->rowCount()>0) 
		{
			// Loop through all groups and create individual buttons
			foreach($stmt as $row)
			{
				$options[]	=['text'=>urlencode($row['title']),'callback_data'=>'/payments '.$row['chatID']];
			}	
			
			// Send options
			$reply = "Select the desired group.";
			sendMessage($chatID, $reply, json_encode(['inline_keyboard'=>array_chunk($options,3)]));
			exit();
		}
		else
		{
			$reply = "I can't find any transactions for you in any chat group.";
		}
	}

	// Continue if we know the group
	elseif($groupID<0 && !empty($groupID) && is_numeric($groupID)) 
	{
		// Do database query [TODO]
		$transactions = $dbh->query("SELECT t.amount, t.created_at 
										FROM sb_users u, sb_transactions t 
										WHERE u.userID=t.userID AND u.excluded=0 AND u.chatID=$groupID 
										AND u.telegramID=$fromID AND t.settled=0");

		// Build success reply to Telegram
		if($transactions->rowCount()>0) 
		{
			// [MSG_SUCCESS]
			$reply = ucfirst($firstname).", these are your unsettled transactions:";
			foreach ($transactions as $row) {
				$reply .= "\n\u{25AA} ".$row['amount']." from ".date("D j F Y", strtotime($row['created_at']));
			}
		} 
		else 
		{ 
			// [MSG_FAIL]
			$reply  = ucfirst($firstname).", I couldn't find any transactions for you. "; 
			$reply .= "Are there any \u{1F914}? Are you perhaps currently excluded?";
		}
	}

	// Send options
	sendMessage($chatID, $reply);
	exit();
}


//
// 00. Everything else
//     These takes care for anything not caught by the previous if() statements (e.g. triggers)
//

//
// T1. Trigger new chat title 
// 
if (isset($update["message"]["new_chat_title"]) && $update["message"]["new_chat_title"]!="")
{
	// Do database query
	$stmt = $dbh->prepare("UPDATE sb_users 
							SET title=:title 
							WHERE chatID=:chatID");
	$stmt->bindParam(':title', $update["message"]["new_chat_title"], PDO::PARAM_STR);
	$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);
	$stmt->execute();

	exit();
} 


//
// T2. Trigger upon added new member (array)
// 
elseif (isset($update["message"]["new_chat_members"]) && !empty($update["message"]["new_chat_members"]))
{
	$i=0;
	while ($i < count($update["message"]["new_chat_members"])) 
	{
		$newID		= $update["message"]["new_chat_members"][$i]["id"]; 			// Who just joined? 
		$newFname 	= $update["message"]["new_chat_members"][$i]["first_name"];		// Her firstname
		$newLname 	= $update["message"]["new_chat_members"][$i]["last_name"];		// cont'd (opt)
		$newNname 	= $update["message"]["new_chat_members"][$i]["username"];		// cont'd (opt)

		// Get existing IBAN if any
		$iban = $dbh->prepare("SELECT iban 
								FROM sb_users
								WHERE telegramID=:telegramID");
		$iban->bindParam(':telegramID', $newID, PDO::PARAM_INT);
		$iban->execute();
		$iban = (verify_iban($iban->fetchColumn(0))?$iban->fetchColumn(0):NULL);

		// Do database query for user
		$stmt = $dbh->prepare("INSERT INTO sb_users (telegramID, chatID, title, firstname, lastname, nickname, iban) 
								VALUES (:telegramID, :chatID, :title, :firstname, :lastname, :nickname, :iban)
								ON duplicate key 
								UPDATE userID=LAST_INSERT_ID(userID), title=:title, firstname=:firstname, lastname=:lastname, nickname=:nickname, iban=:iban");
		$stmt->bindParam(':telegramID', $newID, PDO::PARAM_INT);
		$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);
		$stmt->bindParam(':title', $title, PDO::PARAM_STR);
		$stmt->bindParam(':firstname', $newFname, PDO::PARAM_STR);
		$stmt->bindParam(':lastname', $newLname, PDO::PARAM_STR);
		$stmt->bindParam(':nickname', $newNname, PDO::PARAM_STR);
		$stmt->bindParam(':iban', $iban, PDO::PARAM_STR);

		// Execute the database statement
		if($stmt->execute()) 
		{
			$i++;
		}
	} 
	exit();
}


//
// T3. Trigger upon member left delete user and notify in group and individually
//
elseif (isset($update["message"]["left_chat_member"]) && $update["message"]["left_chat_member"]!="")
{
	$leftID		= $update["message"]["left_chat_member"]["id"]; 		// Who just left? 
	$leftFname	= $update["message"]["left_chat_member"]["first_name"];	// Let's remain personal

	// Calculate open amount of left group chat member
	$sum_query = $dbh->prepare("SELECT SUM(t.amount) AS sum  
								FROM sb_users u, sb_transactions t
								WHERE u.telegramID=:telegramID AND u.userID=t.userID AND u.chatID=:chatID AND t.settled=:settled");
	$sum_query->bindParam(':telegramID', $leftID, PDO::PARAM_INT);
	$sum_query->bindParam(':chatID', $chatID, PDO::PARAM_INT);
	$sum_query->bindParam(':settled', $zero, PDO::PARAM_INT);
	$sum_query->execute();
	$open_amount = $sum_query->fetchColumn(0);

	// Prepare delete statement
	$stmt = $dbh->prepare("DELETE FROM sb_users 
							WHERE telegramID = :telegramID AND chatID = :chatID");
	$stmt->bindParam(':telegramID', $leftID, PDO::PARAM_INT);
	$stmt->bindParam(':chatID', $chatID, PDO::PARAM_INT);
	
	if($stmt->execute() && $open_amount>0)
	{
		$reply_group = "\u{1F4A1} ".ucfirst($leftFname)." left *$open_amount* worth of unsettled payments.";
		sendMessage($chatID, $reply_group);
		sleep(2);
		$reply_individual  = ucfirst($leftFname).", note that you left *$open_amount* worth of unsettled ";
		$reply_individual .= "payments in that group.";
		sendMessage($leftID, $reply_individual);
	}
	exit();
}


//
// Shows if no previous chatID>0 ran before, only for individual chats
//
if($chatID>0 && $indprocessed!=1) 
	{
		// [MSG_FAIL] Default response
		$reply  = "\u{1F633} Embarrassing... I don't know what you just meant! ";
		$reply .= "Did you maybe try to run a group command?";
		
		// Execute
		sendMessage($chatID, $reply);
		exit();
	}
?>