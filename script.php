<?php
	php_sapi_name() === 'cli' or die('not allowed on web server');
	require_once 'config.php';
	require_once 'iRPCProxy.php';
	require_once 'CoreRPCProxy.php';
	require_once 'BlockChainInfoRPCProxy.php';
	
	//$proxy = new CoreRPCProxy($rpc['login'], $rpc['password'], $rpc['ip'], $rpc['port']);
	$proxy = new BlockChainInfoRPCProxy();
	
	function calculateTickets($origin) {
		global $config;
		$txsret = array();
		foreach ($origin as $trans) {
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
		foreach ($origin as $trans) {
			$ret = $ret + $trans['numTickets'];
		}
		return $ret;
	}

	function makeTicketsMap($origin) {
		$tickets = array();
		$map = array();
		foreach ($origin as $trans) {
			$ticket = $trans['ticket'];
			$numTickets = $trans['numTickets'];
			for ($ii = 0; $ii < $numTickets; $ii++) {
				array_push($tickets, $ticket);
			}
			$map[$ticket] = $trans;
		}
		return array( 'tickets' => $tickets, 'map' => $map );
	}

	function printtx($trans, $indent = "") {
		print($indent . "Ticket:  " . $trans['ticket'] . "\n");
		print($indent . "Txid:    " . $trans['txid'] . "\n");
		print($indent . "Address: " . $trans['origin'] . "\n");
	}

	function printTicketList($sorted, $map) {
		$count = 0;
		foreach($sorted as $ticket) {
			$tx = $map[$ticket];
			print ($count . ":\n");
			printtx($tx, "  ");
			$count += 1;
		}
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

	// update config parameters from command line
	$shortopts  = "a::b::e::m::";
	$longopts  = array();
	$cmdopt = getopt($shortopts, $longopts);
	if (array_key_exists('a', $cmdopt)) {
		$config['address'] = $cmdopt['a'];
	}
	if (array_key_exists('b', $cmdopt)) {
		$config['beginblock'] = intval($cmdopt['b']);
	}
	if (array_key_exists('e', $cmdopt)) {
		$config['endblock'] = intval($cmdopt['e']);
	}
	if (array_key_exists('m', $cmdopt)) {
		$config['min'] = floatval($cmdopt['m']);
	}
	
	// Parsing and adding new transactions to database
	print("Selecting the lottery winner for the following parameters:\n");
	print("  Initial Block: " . $config['beginblock'] . "\n");
	print("  Final Block:   " . $config['endblock'] . "\n");
	print("  Loterry Addr:  " . $config['address'] . "\n");
	print("  Ticket Price:  " . $config['min'] . "\n");
	print("\n");
	print("For more information on how the winner is selected,\n");
	print("see the docs at https://github.com/girino/BTCTalkPTSorteioAnoNovo \n");
	print("\n");
	// 2- list all tx between beginblock and endblock
	$transactions = $proxy->listTxForAddr($config['beginblock'], $config['endblock'], $config['address']);
	// 3- attribute wheights to tx
	// 4- atribute ticketids to tx
	$transactions = calculateTickets($transactions);
	$totaltickets = countTickets($transactions);
	print("Tickets bought:\n");
	$ticketsmap = makeTicketsMap($transactions);
	// 5- sort txs
	$sortedTickets = $ticketsmap['tickets'];
	sort($sortedTickets);
	printTicketList($sortedTickets, $ticketsmap['map']);
	print("\n");
	// 6- calculate winnerball
	// 7- make randpos := winnerball % sum(wheights)
	$beginhash = $proxy->getblockhash($config['beginblock']);
	$endhash = $proxy->getblockhash($config['endblock']);
	$winnerhash = hash_hmac('sha256', hex2bin($beginhash), hex2bin($endhash));
	print("Pseudorandom Hash:\n   " . $winnerhash . "\n");
	$winnerhash = substr($winnerhash, strlen($winnerhash) - 6, 6);
	$winnerball = hexdec ( $winnerhash ) % count($sortedTickets);
	print("Winner Ticket Number:\n   " . $winnerball . "\n");
	print("\n");
	// 8- traverse ordered list subtracting wheights from pos until pos == 0
	$winner = $sortedTickets[$winnerball];
	// 9- show winner tx and winner addr
	$winnerTx = $ticketsmap['map'][$winner];
	print("Winner:\n");
	printtx($winnerTx, "  ");
	
?>
