<?php
require_once 'query_functions.php';
require_once 'iRPCProxy.php';

class BlocktrailRPCProxy implements iRPCProxy {
	
	private $key;
	public function __construct($key) {
		$this->key = $key;
	}

	public function listTxForAddr($beginblock, $endblock, $address) {
	
		$ret = array();
		$addrinfo = blocktrail_transactions($address, $this->key);
		$transactions = $addrinfo['data'];
		foreach ($transactions as $tx) {
			//print("Getting tx: " . $tx['hash'] . "\n");
			if ($tx['block_height'] >= $beginblock && $tx['block_height'] <= $endblock) {
				//print("Tx " . $tx['hash'] . " is in range...\n");
				foreach($tx['outputs'] as $vout) {
					if (array_key_exists('address', $vout) && $vout['address'] == $address) {
						//print("Tx " . $tx['hash'] . " is valid...\n");
						$tmp = array(
							'txid' => $tx['hash'],
							'value' => $vout['value'] * 0.00000001,
							'blockhash' => $tx['block_hash'],
							'origin' => $tx['inputs'][0]['address'],
						);
						array_push($ret, $tmp);
					}
				}
			}
		}
		return $ret;
	}
	
	function getAddress($txid)
	{
		$address = "";
		$details = blocktrail_query('transaction', $txid, $this->key);
		$address = $details['inputs'][0]['address'];

		return $address;
	}
	
	
	function getblockhash($height) {
		$result = blocktrail_query('block', $height, $this->key);
		return $result['hash'];
	}

	function getName() {
		return "Blocktrail";
	}
}
