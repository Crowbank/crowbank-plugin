<?php
define( 'ABSPATH', '' );

# require_once 'crowbank.php';
require_once 'classes/petadmin_class.php';

global $petadmin;

$petadmin->load();
$now = new DateTime();
$last_transfer = $petadmin->get_lasttransfer();

$age = $now->getTimestamp() - $last_transfer->getTimestamp();
	

$status['lasttransfer'] = $last_transfer;
$status['status'] = $petadmin->get_status();
$status['age'] = $age;

echo json_encode($status);
