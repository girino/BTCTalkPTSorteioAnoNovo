<?php
require_once 'query_functions.php';
require_once 'iRPCProxy.php';

class BlockChainInfoRPCProxy implements iRPCProxy {

	public function listTxForAddr($beginblock, $endblock, $address) {
	
		$ret = array();
		$addrinfo = blockchain_info_query('address', $address);
		$transactions = $addrinfo['txs'];
		foreach ($transactions as $tx) {
			//print("Getting tx: " . $tx['hash'] . "\n");
			if ($tx['block_height'] >= $beginblock && $tx['block_height'] <= $endblock) {
				//print("Tx " . $tx['hash'] . " is in range...\n");
				foreach($tx['out'] as $vout) {
					if (array_key_exists('addr', $vout) && $vout['addr'] == $address) {
						//print("Tx " . $tx['hash'] . " is valid...\n");
						$tmp = array(
							'txid' => $tx['hash'],
							'value' => $vout['value'] * 0.00000001,
							'blockhash' => $this->getblockhash($tx['block_height']),
							'origin' => $tx['inputs'][0]['prev_out']['addr'],
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
		$details = blockchain_info_query('rawtx', $txid);
		$address = $details['inputs'][0]['prev_out']['addr'];

		return $address;
	}
	
	
	function getblockhash($height) {
		$result = blockchain_info_query('block-height', $height);
		$blocks = $result['blocks'];
		foreach ($blocks as $block) {
			if ($block['main_chain'] == true) {
				return $block['hash'];
			}
		}
	}
}