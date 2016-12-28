<?php
require_once 'jsonRPCClient.php';
require_once 'iRPCProxy.php';

class CoreRPCProxy implements iRPCProxy {

	private $client;
	public function __construct($login, $password, $ip, $port) {
		$this->client = new jsonRPCClient('http://' . $login . ':' . $password . '@' . $ip . ':' . $port . '/') or die('Error: could not connect to RPC server.');
	}

	public function listTxForAddr($beginblock, $endblock, $address) {
	
		$ret = array();
		$client = $this->client;
	
		for ($blk = $endblock; $blk >= $beginblock; $blk--) {
			$bhash = $client->getblockhash($blk);
			$block = $client->getblock($bhash);
			//print("Getting block: " . $bhash . "\n");
			foreach ($block['tx'] as $txid) {
				//print("Getting tx: " . $txid . "\n");
				$tx = $client->getrawtransaction($txid, 1);
				foreach($tx['vout'] as $vout) {
					$sp = $vout['scriptPubKey'];
					if (array_key_exists('addresses', $sp)) {
						foreach($sp['addresses'] as $curraddress) {
							if ($curraddress == $address) {
								$tmp = array(
										'txid' => $txid,
										'value' => $vout['value'],
										'blockhash' => $tx['blockhash'],
										'origin' => $this->getAddress($txid),
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
		$client = $this->client;
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
	
	
	function getblockhash($height) {
		return $this->client->getblockhash($height);
	}

	function getName() {
		return "Core";
	}
}
