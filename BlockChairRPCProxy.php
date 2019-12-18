<?php
require_once 'query_functions.php';
require_once 'iRPCProxy.php';

class BlockChairRPCProxy implements iRPCProxy {

	public function listTxForAddr($beginblock, $endblock, $address) {
	
		$ret = array();
		//$addrinfo = blockchair_query('dashboards', 'address', $address, '?transaction_details=true');
		$addrinfo = blockchair_query('dashboards', 'address', $address);
		$transactions = $addrinfo['data'][$address]['transactions'];
		foreach ($transactions as $txh) {
			print("Getting tx: " . $txh . "\n");
			$tx = blockchair_query('dashboards', 'transaction', $txh)['data'][$txh];
			if ($tx['transaction']['block_id'] >= $beginblock && $tx['transaction']['block_id'] <= $endblock) {
				print("Tx " . $tx['transaction']['hash'] . " is in range...\n");
				foreach($tx['outputs'] as $vout) {
					if (array_key_exists('recipient', $vout) && $vout['recipient'] == $address) {
						//print("Tx " . $tx['hash'] . " is valid...\n");
						$tmp = array(
							'txid' => $tx['transaction']['hash'],
							'value' => $vout['value'] * 0.00000001,
							'blockhash' => $this->getblockhash($tx['transaction']['block_id']),
							'origin' => $tx['inputs'][0]['recipient'],
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
		$tx = blockchair_query('dashboards', 'transaction', $txid)['data'][$txid];
		$address = $$tx['inputs'][0]['recipient'];
		return $address;
	}
	
	function getblockhash($height) {
		$result = blockchair_query('dashboards','block', $height);
		return $result['data'][$height]['block']['hash'];
	}

	function getName() {
		return "Blockchair";
	}
}
