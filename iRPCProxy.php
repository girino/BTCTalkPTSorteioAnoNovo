<?php
interface iRPCProxy {
	public function listTxForAddr($beginblock, $endblock, $address);
	function getAddress($txid);
	function getblockhash($height);
	function getName();
}
