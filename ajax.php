<?php

	include_once("Reservation.class.php");
	
	if(isset($_GET['action']) && !empty($_GET['action'])){
		$action = $_GET['action'];
	}

	switch($action){
	
		// affiche le planning
		case 'getplanning':
			if(isset($_GET['annee']) && !empty($_GET['annee']))
				$annee = intval($_GET['annee']);
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$chambre = intval($_GET['chambre']);
	
			$reservation_obj = new Reservation();
			echo $reservation_obj->genererPlanning($annee, $chambre);
		break;
		
		// renvoi la quantité de référence d'une chambre
		case 'getqte':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			$reservation_obj = new Reservation();
			$stock = $reservation_obj->getStock($id_chambre);
			echo $stock;
		break;
		
		// suppression d'une entrée dans la table stock_chambre
		case 'delete_stock':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			$reservation_obj = new Reservation();
			$reservation_obj->deleteStock($date, $id_chambre);
			$deleted = mysql_affected_rows();
			echo $deleted;
		break;
		
		// mise à jour d'une entrée dans la table stock_chambre
		case 'update_stock':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			if(isset($_GET['stock']) && !empty($_GET['stock']))
				$stock = $_GET['stock'];

			$reservation_obj = new Reservation();
			$reservation_obj->updateStock($date, $id_chambre, $stock);
			$updated = mysql_affected_rows();
			echo $updated;
		break;

		// insertion d'une entrée dans la table stock_chambre
		case 'insert_stock':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			if(isset($_GET['stock']))
				$stock = $_GET['stock'];

			$reservation_obj = new Reservation();
			$reservation_obj->insertStock($date, $id_chambre, $stock);
			$inserted = mysql_affected_rows();
			echo $inserted;
		break;
		
		// Récupération du prix d'une chambre :
		case 'getprix':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			$reservation_obj = new Reservation();
			$prix = $reservation_obj->getPrix($id_chambre);
			echo $prix;
		break;

		case 'affiche_prix':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			// On regarde si un prix différent est pratiqué pour ce jour
			$reservation_obj = new Reservation();
			$prix_chambre = $reservation_obj->checkPrix($date, $id_chambre);

			// Si non, on prend le prix de base de la chambre
			if($prix_chambre == false)
				$prix_chambre = $reservation_obj->getPrix($id_chambre);

			echo $prix_chambre;
		break;

		// suppression d'une entrée dans la table prix
		case 'delete_prix':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			$reservation_obj = new Reservation();
			$reservation_obj->deletePrix($date, $id_chambre);
			$deleted = mysql_affected_rows();
			echo $deleted;
		break;


		// mise à jour d'une entrée dans la table prix
		case 'update_prix':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			if(isset($_GET['prix']) && !empty($_GET['prix']))
				$prix = $_GET['prix'];

			$reservation_obj = new Reservation();
			$reservation_obj->updatePrix($date, $id_chambre, $prix);
			$updated = mysql_affected_rows();
			echo $updated;
		break;

		// insertion d'une entrée dans la table prix
		case 'insert_prix':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			if(isset($_GET['prix']) && !empty($_GET['prix']))
				$prix = $_GET['prix'];

			$reservation_obj = new Reservation();
			$reservation_obj->insertPrix($date, $id_chambre, $prix);
			$inserted = mysql_affected_rows();
			echo $inserted;
		break;
		
		// affiche la quantité réservable maxi pour une chambre à une certaine date
		case 'get_stock_maxi':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			// récupération du stock maxi de la chambre à cette date
			$reservation_obj = new Reservation();
			$stock = $reservation_obj->checkStock($date, $chambre);
			if($stock === false)
				$stock = $reservation_obj->getStock($chambre);
				
			echo $stock;
		break;
		
		// affiche le nombre de réservation d'une chambre à une date
		case 'get_nb_reservations':
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$chambre = $_GET['chambre'];

			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];

			$reservation_obj = new Reservation();
			$nb_reservations = $reservation_obj->reservationsJour($date, $chambre);
			
			echo $nb_reservations;
		break;
		
		case 'get_reservations':
			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];
				
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$chambre = $_GET['chambre'];
			
			$reservation_obj = new Reservation();
			$reservations = $reservation_obj->getReservations($date, $chambre);
			if($reservations){
				$nb_reservations = count($reservations);
			
				$contenu = "";
				for($i=0 ; $i < $nb_reservations ; $i++){
					$contenu .= '<p><a class="clickable" title="Voir le détail de la réservation" onclick="voirInfosReservation('.$reservations[$i]['commande'].')">'.$reservations[$i]['nom'].' '.$reservations[$i]['prenom'].'</a></p>';
				}
				echo $contenu;
			}
			else
				echo 'Aucune réservation.';

		break;
		
		case 'get_infos_reservation':
			if(isset($_GET['commande']) && !empty($_GET['commande']))
				$commande = $_GET['commande'];
			
			$reservation_obj = new Reservation();
			$infos = $reservation_obj->getInfosReservation($commande);
			if($infos){
					
				if($infos['petit_dejeuner'] == '1')
					$infos['petit_dejeuner'] = 'oui';
				else
					$infos['petit_dejeuner'] = 'non';
					
				if($infos['parking'] == '1')
					$infos['parking'] = 'oui';
				else
					$infos['parking'] = 'non';

				$contenu = '
					<div id="voir_resa">
						<p><strong><a href="module.php?nom=reservation&vue=client_detail&commande='.$commande.'">'.$infos['nom'].' '.$infos['prenom'].'</a></strong></p>
						<p><strong>Date d\'arrivée : </strong>'.$infos['date_reservation'].'</p>
						<p><strong>Nombre de nuits : </strong>'.$infos['nb_nuits'].'</p>
						<p><strong>Nombre de chambres : </strong>'.$infos['nb_chambres'].'</p>
						<p><strong>Petit déjeuner : </strong>'.$infos['petit_dejeuner'].'</p>
						<p><strong>Nombre de personnes pour la cuisine-à-manger : </strong>'.$infos['cuisine_a_manger'].'</p>
						<p><strong>Parking : </strong>'.$infos['parking'].'</p>
					</div>
				';
			}
			else
				$contenu = '$infos is false';
			
			echo $contenu;
		break;
		
		/*case 'ajout_reservation':
			// Récupération de l'id de la chambre
			if(isset($_GET['chambre']) && !empty($_GET['chambre']))
				$id_chambre = $_GET['chambre'];

			// Récupération de la date
			if(isset($_GET['date']) && !empty($_GET['date']))
				$date = $_GET['date'];
			
			// Récupération du nb de chambres
			if(isset($_GET['nb_chambres']) && !empty($_GET['nb_chambres']))
				$nb_chambres = $_GET['nb_chambres'];
			
			// Récupération du nb de nuits
			if(isset($_GET['nb_nuits']) && !empty($_GET['nb_nuits']))
				$nb_nuits = $_GET['nb_nuits'];

			$reserv_obj = new Reservation();
			$ajout = $reserv_obj->addReservation($date, $id_chambre, $nb_chambres, $nb_nuits);

			echo "La réservation a bien été ajoutée.";
		break;*/
	}


?>
