<?php
require_once(dirname(__FILE__).'/../bootstrap.php');

$FORCE =isset($argv[1]) && $argv[1] == 'FORCE';

$settingsDao = new GrcPool_Settings_DAO();
if (!$FORCE && $settingsDao->getValueWithName(Constants::SETTINGS_GRC_CLIENT_ONLINE) != '1') {
	echo "GRC CLIENT OFFLINE\n\n";
	exit;
}

$walletDao = new GrcPool_Wallet_Basis_DAO();
$settingsDao = new GrcPool_Settings_DAO();

$minStakeBalance = $settingsDao->getValueWithName(Constants::SETTINGS_MIN_STAKE_BALANCE)*COIN;

$hostCreditDao = new GrcPool_Member_Host_Credit_DAO();
$hostDao = new GrcPool_Member_Host_DAO();


for ($poolId = 1; $poolId <= Property::getValueFor(Constants::PROPERTY_NUMBER_OF_POOLS); $poolId++) {
	echo "\n%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% POOL # ".$poolId."\n";

	$daemon = GrcPool_Utils::getDaemonForPool($poolId);	
	
	$basisObj = $walletDao->initWithKey($poolId);
	$WALLETBASIS = $basisObj->getBasis();
	echo "Wallet Basis: ".($WALLETBASIS/COIN)."\n";
	$totalBalance = $daemon->getTotalBalance()*COIN;
	echo "Current Balance: ".($totalBalance/COIN)."\n";
	$totalInterest = $daemon->getTotalInterest()*COIN;
	echo "Wallet Interest: ".($totalInterest/COIN)."\n";
	$totalBalance = $totalBalance-$totalInterest;
	echo "Available Balance: ".($totalBalance/COIN)."\n";
	
	if ($totalBalance < $WALLETBASIS) {
		echo ($totalBalance/COIN) .' to low < '.($WALLETBASIS/COIN)."\n";
		continue;
	}
	
	$totalOwed = $hostCreditDao->getTotalOwedForPool($poolId)*COIN;
	echo "Total Owed: ".($totalOwed/COIN)."\n";
	
	$stakeBalance = $totalBalance - $WALLETBASIS - $totalOwed;
	echo 'Stake Balance: '.($stakeBalance/COIN)." = ".($totalBalance/COIN)." - ".($WALLETBASIS/COIN)." - ".($totalOwed/COIN)."\n";
	
	if ($totalBalance - $stakeBalance < $WALLETBASIS) {
		echo 'Funds Too Low: '."\n";
	}
	
	$totalMag = $hostCreditDao->getTotalMagForPool($poolId);
	echo 'Total Mag: '.$totalMag."\n";
	
	if ($stakeBalance < $minStakeBalance) {
		echo 'not enough stake balance < '.($minStakeBalance/COIN).'   '.($stakeBalance/COIN)."\n";
		//continue;
	}
	
	$sql = 'update '.Constants::DATABASE_NAME.'.member_host_credit set '.Constants::DATABASE_NAME.'.member_host_credit.owed = '.Constants::DATABASE_NAME.'.member_host_credit.owed + (('.Constants::DATABASE_NAME.'.member_host_credit.mag/'.$totalMag.') * '.($stakeBalance/COIN).'),
			'.Constants::DATABASE_NAME.'.member_host_credit.owedCalc = concat('.Constants::DATABASE_NAME.'.member_host_credit.owedCalc,\'+((\','.Constants::DATABASE_NAME.'.member_host_credit.mag,\'/\','.$totalMag.',\')*\','.($stakeBalance/COIN).',\')\') where mag > 0 and poolId = '.$poolId;
	
	
	echo "\n\n".$sql."\n\n";
	
}





















