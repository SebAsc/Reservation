<?php

	include_once(realpath(dirname(__FILE__)) . "/Reservation.class.php" );

	$version = new Reservation();
	$num_version = $version->getVersion();

	if( $num_version >= 142 && !is_null($num_version) )
	{
		/* PAS AUTHENTIFICATION SI VERSION THELIA < 1.4.2 	*/
		include_once(realpath(dirname(__FILE__)) . "/../../../fonctions/authplugins.php");
		autorisation("reservation");
	}

	if(isset($_GET['vue']) && !empty($_GET['vue']))
		$vue = $_GET['vue'];
		
	switch($vue){
		case 'clients':
			include('clients.php');
		break;
		
		case 'client_detail':
			include('client_detail.php');
		break;
		
		default:
			include('planning.php');
		break;
	}


?>


