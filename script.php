<?php
	php_sapi_name() === 'cli' or die('not allowed on web server');
	require_once 'config.php';
	require_once 'jsonRPCClient.php';
	
	$client = new jsonRPCClient('http://' . $rpc['login'] . ':' . $rpc['password'] . '@' . $rpc['ip'] . ':' . $rpc['port'] . '/') or die('Error: could not connect to RPC server.');
	
	function listTxForAddr($addr) {
		global $client;
		global $config;

		$ret = array();
		
		for ($blk = $config['endblock']; $blk >= $config['beginblock']; $blk--) {
			$bhash = $client->getblockhash($blk);
			$block = $client->getblock($bhash);
			foreach ($block['tx'] as $txid) {
				$tx = $client->getrawtransaction($txid, 1);
				foreach($tx['vout'] as $vout) {
				  $sp = $vout['scriptPubKey'];
				  if (array_key_exists('addresses', $sp)) {
				    foreach($sp['addresses'] as $address) {
				      if ($address == $config['address']) {
					$tmp = array(
					  'txid' => $txid,
					  'value' => $vout['value'],
					  'blockhash' => $tx['blockhash'],
					  'origin' => getAddress($txid),
					);
				        array_push($ret, $tmp);
				      }
				    }
				  }
				}
			}

		}
		return $ret;
	}

	function getAddress($txid)
	{
		global $client;
		$address = "";

		$details = $client->getrawtransaction($txid, 1);

		$vintxid = $details['vin'][0]['txid'];
		$vinvout = $details['vin'][0]['vout'];

		try {
			$transactionin = $client->getrawtransaction($vintxid, 1);
		}
		catch (Exception $e) {
			print("here");
			print_r($details);
			die("Error with getting transaction details.\nYou should add 'txindex=1' to your .conf file and then run the daemon with the -reindex parameter.");
		}
		
		if ($vinvout == 1)
			$vinvout = 0;
		else
			$vinvout = 1;
		
		$address = $transactionin['vout'][!$vinvout]['scriptPubKey']['addresses'][0];
		return $address;
	}

	function calculateTickets($origin) {
		global $config;
		$txsret = array();
		foreach ($origin as $key => $trans) {
			$ticket=hash_hmac('sha256', hex2bin($trans['txid']), hex2bin($trans['blockhash']));
			$trans['ticket'] = $ticket;
			$price = $config['min'];
			$numTickets = intval($trans['value'] / $price);
			$trans['numTickets'] = $numTickets;
			array_push($txsret, $trans);
		}
		return $txsret;
	}

	function countTickets($origin) {
		$ret = 0;
		foreach ($origin as $key => $trans) {
			$ret = $ret + $trans['numTickets'];
		}
		return $ret;
	}

	function makeTicketsMap($origin) {
		$tickets = array();
		$map = array();
		foreach ($origin as $key => $trans) {
			$ticket = $trans['ticket'];
			$numTickets = $trans['numTickets'];
			for ($ii = 0; $ii < $numTickets; $ii++) {
				array_push($tickets, $ticket);
			}
			$map[$ticket] = $trans;
		}
		return array( 'tickets' => $tickets, 'map' => $map );
	}

	// what it should be doing:
	// 1- check if endblock is already past
	// 2- list all tx between beginblock and endblock
	// 3- attribute wheights to tx
	// 4- atribute ticketids to tx
	// 5- sort txs
	// 6- calculate winnerball
	// 7- make randpos := winnerball % sum(wheights)
	// 8- traverse ordered list subtracting wheights from pos until pos == 0
	//	(roultte select)
	// 9- show winner tx and winner addr


	// Parsing and adding new transactions to database
	print("Recovering transactions...\n");
	// 2- list all tx between beginblock and endblock
	$transactions = listTxForAddr("");
	print(count($transactions) . " transactions recovered...\n");
	// 3- attribute wheights to tx
	// 4- atribute ticketids to tx
	$transactions = calculateTickets($transactions);
	$totaltickets = countTickets($transactions);
	print($totaltickets . " tickets bought...\n");
	$ticketsmap = makeTicketsMap($transactions);
	// 5- sort txs
	$sortedTickets = $ticketsmap['tickets'];
	sort($sortedTickets);
	// 6- calculate winnerball
	// 7- make randpos := winnerball % sum(wheights)
	$beginhash = $client->getblockhash($config['beginblock']);
	$endhash = $client->getblockhash($config['endblock']);
	$winnerhash = hash_hmac('sha256', hex2bin($beginhash), hex2bin($endhash));
	print("Winnerball: " . $winnerhash . "\n");
	$winnerhash = substr($winnerhash, strlen($winnerhash) - 6, 6);
	$winnerball = hexdec ( $winnerhash ) % count($sortedTickets);
	print("Winnerball: " . $winnerball . "\n");
	//print_r($ticketsmap);
	$winner = $sortedTickets[$winnerball];
	$winnerTx = $ticketsmap['map'][$winner];
	print_r($winnerTx);
	

	echo ("Finished...\n");
?>
