<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                            		 */
/*                                                                                   */
/*      Copyright (c) Octolys Development		                                     */
/*		email : thelia@octolys.fr		        	                             	 */
/*      web : http://www.octolys.fr						   							 */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 2 of the License, or            */
/*      (at your option) any later version.                                          */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*      along with this program; if not, write to the Free Software                  */
/*      Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA    */
/*                                                                                   */
/*		Seb 05/04/2011, classe rajoutée pour le système de réservation				 */
/*************************************************************************************/

	include_once(realpath(dirname(__FILE__)) . "/../../../classes/PluginsClassiques.class.php");
	include_once(realpath(dirname(__FILE__)) . "/../../../classes/Variable.class.php");

	class Reservation extends PluginsClassiques{

		var $id;
		var $type_chambre;
		var $nb_chambres;
		var $date_reservation;
		var $commande;
		var $petit_dejeuner;
		var $cuisine_a_manger;
		var $parking;
		var $active;
		var $prix_unitaire;

		const TABLE="reservations";
		var $table=self::TABLE;

		var $bddvars = array("id", "type_chambre", "nb_chambres", "date_reservation", "commande", "petit_dejeuner", "cuisine_a_manger", "parking", "active", "prix_unitaire");

		// Constructeur
		function Reservation(){
			$this->PluginsClassiques();
		}
		
		/*
		* Log un message précédé de la date et l'heure dans un fichier texte
		* 
		*/
		function log_this($message){
			$file = fopen('/var/www/vhosts/college-hotel.com/httpdocs/v3/client/plugins/reservation/resa_log.txt', 'a');
			fwrite($file, date("Y-m-d H:i:s")." : ".$message."\n");
			fclose($file);
		}
		
		function getVersion()
		{
			$VersionThelia = new Variable();
			$VersionThelia->charger("version");
			$num_version = $VersionThelia->valeur ;
		
			return $num_version;
		}
		
		// Création des tables 'reservations', 'prix', et 'stock_chambre' si elles sont inexistantes, et des index associés
		function init(){
			$creation_reservations = "
				CREATE TABLE IF NOT EXISTS `reservations` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `date_reservation` date NOT NULL,
				  `type_chambre` tinyint(2) NOT NULL,
				  `nb_chambres` tinyint(2) NOT NULL,
				  `commande` int(11) NOT NULL,
				  `petit_dejeuner` binary(1) NOT NULL DEFAULT '0',
				  `cuisine_a_manger` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `parking` binary(1) NOT NULL DEFAULT '0',
				  `active` binary(1) NOT NULL DEFAULT '0',
				  `prix_unitaire` int(11) NOT NULL DEFAULT '0',
				  PRIMARY KEY (`id`),
				  KEY `date_reservation` (`date_reservation`),
				  KEY `commande` (`commande`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
			";
			$resultat_reservations = mysql_query($creation_reservations, $this->link);

			$creation_prix = "
				CREATE TABLE IF NOT EXISTS `prix` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `chambre` int(11) NOT NULL,
				  `date` date NOT NULL,
				  `prix` float NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `prix_jour` (`date`,`chambre`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
			";
			$resultat_prix = mysql_query($creation_prix, $this->link);
			
			$creation_stock_chambre = "
				CREATE TABLE IF NOT EXISTS `stock_chambre` (
				  `id` int(10) NOT NULL AUTO_INCREMENT,
				  `date` date NOT NULL,
				  `chambre` int(11) NOT NULL,
				  `stock` tinyint(4) NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `stock_jour` (`date`,`chambre`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1		
			";
			$resultat_stock_chambre = mysql_query($creation_stock_chambre, $this->link);
		}
		
		// Destruction des tables du plugin (reservations, prix et stock_chambre) -- Commenté pour éviter les bourdes
		function destroy(){
			/*$destroy_reservations = "DROP TABLE `reservations`;";
			mysql_query($destroy_reservations, $this->link);
			
			$destroy_prix = "DROP TABLE `prix`;";
			mysql_query($destroy_prix, $this->link);
			
			$destroy_stock_chambre = "DROP TABLE `stock_chambre`;";
			mysql_query($destroy_stock_chambre, $this->link);*/
		}
		
		// création de la variable de sessions reservation
		function demarrage(){
			if(! isset($_SESSION["reservation"])){
				$_SESSION["reservation"] = new Session_resa();
			}
		}
		
		// Récupération des lignes réservation qui correspondent à une commande 
		function getReservationCommande($id_commande){
			$query = ('
				SELECT res.date_reservation as date_reservation, res.type_chambre as type_chambre, res.nb_chambres as nb_chambres, res.commande as commande, res.petit_dejeuner as petit_dejeuner, res.cuisine_a_manger as cuisine_a_manger, res.parking as parking, res.prix_unitaire
				FROM reservations AS res INNER JOIN commande AS co ON co.id = res.commande
				WHERE commande = '.$id_commande.'
			');
			
			$result = mysql_query($query, $this->link);
			
			$lignes = array();
			$i=0;
			while($row = mysql_fetch_assoc($result)){
				$lignes[$i]['date_reservation'] = $row['date_reservation'];
				$lignes[$i]['type_chambre'] = $row['type_chambre'];
				$lignes[$i]['nb_chambres'] = $row['nb_chambres'];
				$lignes[$i]['petit_dejeuner'] = $row['petit_dejeuner'];
				$lignes[$i]['cuisine_a_manger'] = $row['cuisine_a_manger'];
				$lignes[$i]['parking'] = $row['parking'];
				$lignes[$i]['prix_unitaire'] = $row['prix_unitaire'];
				$i++;
			}
			
			return($lignes);
		}
		
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Groupe de fonctions concernant la gestion des réservations												  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/

			/***************************************************************************************************************
			/* Retourne le nombre de réservations pour une chambre à une date donnée
			***************************************************************************************************************/
			function reservationsJour($date, $chambre){
				$query = '
					SELECT SUM(nb_chambres)
					FROM reservations
					WHERE type_chambre = "'.$chambre.'"
					AND date_reservation ="'.$date.'"
					AND active = 1;
				';
				$result = mysql_query($query);
				$nombre_reservations = mysql_fetch_row($result);
				// si nombre_reservations est null, c'est qu'aucune réservation n'a été faite à cette date pour cette chambre
				if(empty($nombre_reservations[0]))
					return 0;
				else
					return $nombre_reservations[0];
			}
		
			/***************************************************************************************************************
			/* Retourne un tableau contenant les id et stock de chaque chambre à réserver
			***************************************************************************************************************/
			function getChambres(){
				$lang = 1;
				if(isset($_SESSION['navig']->lang) && !empty($_SESSION['navig']->lang))
					$lang = intval($_SESSION['navig']->lang);
					
				$chambres = array();
				$query = "
					SELECT DISTINCT(ref), p.id as id, p.stock as stock, pdesc.titre as titre, pdesc.description as description, p.prix as prix, pdesc.chapo as chapo
					FROM produit as p INNER JOIN produitdesc as pdesc ON p.id = pdesc.produit
					WHERE rubrique = '1'
					AND p.ligne = '1'
					AND pdesc.lang = '".$lang."'
				";
				$result = mysql_query($query);

				$i=0;
				while($row = mysql_fetch_assoc($result)){
					$chambres[$i]['id'] .= $row['id'];
					$chambres[$i]['stock'] .= $row['stock'];
					$chambres[$i]['titre'] .= $row['titre'];
					$chambres[$i]['chapo'] .= $row['chapo'];
					$chambres[$i]['description'] .= $row['description'];
					$chambres[$i]['prix'] .= $row['prix'];
					$i++;
				}

				return $chambres;
			}
			
			/*********************************************************
			/* Transformation de la date en format FR / US
			/********************************************************/
			function dateFrToUs($date)
			{
				$delimiter = '-';
				$date = explode($delimiter, $date);
				
				// si le 1er élément fait 4 char, c'est un format US
				if(strlen($date[0]) == 2){
					$year = $date[2];
					$month = $date[1];
					$day = $date[0];
					$date_formated = $year.'-'.$month.'-'.$day;
					return $date_formated;
				}
				else{
					return "Err Date";
				}
			}
			
			/*********************************************************
			/* Transformation de la date en format US / FR
			/********************************************************/
			function dateUsToFr($date)
			{
				$delimiter = '-';
				$date = explode($delimiter, $date);
				
				// si le 1er élément fait 4 char, c'est un format US
				if(strlen($date[0]) == 4){
					$year = $date[0];
					$month = $date[1];
					$day = $date[2];
					$date_formated = $day.'-'.$month.'-'.$year;
					return $date_formated;
				}
				else{
					return "Err Date";
				}
			}
			
			/*********************************************************
			/* Retourne le format d'un date (FR ou US)
			/********************************************************/
			function checkDateFormat($date){
				$delimiter = '-';
				$date = explode($delimiter, $date);
				$longueur = strlen($date[0]);
				if($longueur == 4)
					return 'US';
				if($longueur == 2)
					return 'FR';
				if($longueur > 4)
					return null;
			}

			/***************************************************************************************************************
			/* Vérification des disponibilités :
			/* - on donne à cette fonction la date, le nombre de chambres et le nombre de nuits choisis par l'utilisateur
			/* - le paramètre id_chambre permet d'éxecuter la fonction pour chaque type de chambre afin d'en déterminer la disponibilité
			/* - retourne : un tableau contenant les types de chambres disponibles
			****************************************************************************************************************/
			function checkDispo($date, $nb_chambres, $nb_nuits){

				// On récupère dans un tableau tous les identifiants des chambres
				$chambres = array();
				$types_disponibles = array();
				$chambres = $this->getChambres();
				
				if($nb_nuits > 4)
					$nb_nuits = 4;
				
				if($nb_chambres > 4)
					$nb_chambres = 4;
					
				// On passe la date en format US pour interroger la base
				$date = $this->dateFrToUs($date);

				$year = (int)substr($date, 0, 4);
				$month = (int)substr($date, 5, 2);
				$day = (int)substr($date, 8, 2);
			
		//echo 'date '.$date.'<br />';

				// boucle sur chaque chambre
				for($i=0 ; $i < count($chambres) ; $i++){
					$dispo = true;
					// boucle sur chaque date demandée (en fonction du nombre de nuits)
					for($j=0 ; $j < $nb_nuits ; $j++){
						// date à vérifier (incrémentée)
						$date_verif = date("Ymd", mktime(0, 0, 0, $month, $day+$j, $year));
		//echo 'date verif '.$date_verif.'<br />';
						// calcul du nombre de réservations pour cette date
						$nb_reservations_jour = $this->reservationsJour($date_verif, $chambres[$i]['id']);
		//echo 'reserv jour '.$nb_reservations_jour.'<br />';
						$pre_total = $nb_reservations_jour+$nb_chambres;
		//echo 'pre total '.$pre_total.'<br />';
						// récupération de la quantité maximale de chambres pour cette date
						$stock = $this->checkStock($date_verif, $chambres[$i]['id']);
						if($stock === false)
							$stock = $chambres[$i]['stock'];
		//echo 'stock'.$stock.'<br />';
						// si stock de la chambre pas suffisant pour la demande
						if($pre_total > $stock){
							$dispo = false;
							break;
						}
					}
					// si la chambre est dispo pour toute la période, on l'ajoute au résultat
					if($dispo === true){
						//echo 'la chambre '.$chambres[$i]['id'].' est disponible.<br />';
						$types_disponibles[] .= $chambres[$i]['id'];
					}
				}
				return $types_disponibles;
			}


			// Ajout d'une réservation : une ligne par jour est ajoutée dans la table
			function addReservation($date, $type_chambre, $nb_chambres, $nb_nuits, $commande, $petit_dejeuner, $cuisine_a_manger, $parking)
			{
				
				// On inverse la date en us
				$date = $this->dateFrToUs($date);
				$year = substr($date, 0, 4);
				$month = substr($date, 5, 2);
				$day = substr($date, 8, 2);
		
				for($i=0 ; $i < $nb_nuits ; $i++){
					$date = date("Ymd", mktime(0, 0, 0, $month, $day+$i, $year));
					$prix_unitaire = $this->checkPrix($date, $type_chambre);
					if($prix_unitaire == false)
						$prix_unitaire = $this->getPrix($type_chambre);
					$query = "
						INSERT INTO reservations (date_reservation, type_chambre, nb_chambres, commande, petit_dejeuner, cuisine_a_manger, parking, prix_unitaire)
						VALUES ('".$date."', '".$type_chambre."', '".$nb_chambres."', '".$commande."', '".$petit_dejeuner."', '".$cuisine_a_manger."', '".$parking."', '".$prix_unitaire."');
					";
					$result = mysql_query($query, $this->link);
					if(!$result)
					{
						mail('sebastien@ascomedia.com', 'erreur ajout reservation', 
						"detail : \n
						 date : ".$date."\n 
						 chambre : ".$type_chambre."\n
						 nb chambres : ".$nb_chambres."\n
						 nb nuits : ".$nb_nuits."\n
						 commande : ".$commande."\n
						 petit dej : ".$petit_dejeuner."\n
						 cuisine : ".$cuisine_a_manger."\n
						 parking : ".$parking."\n
						 echec: ".mysql_error());
						$this->log_this("ajout de reservation : detail : \n
						 date : ".$date."\n 
						 chambre : ".$type_chambre."\n
						 nb chambres : ".$nb_chambres."\n
						 nb nuits : ".$nb_nuits."\n
						 commande : ".$commande."\n
						 petit dej : ".$petit_dejeuner."\n
						 cuisine : ".$cuisine_a_manger."\n
						 parking : ".$parking);
					}
					else
					{
						$this->log_this("ajout de reservation : detail : \n
						 date : ".$date."\n 
						 chambre : ".$type_chambre."\n
						 nb chambres : ".$nb_chambres."\n
						 nb nuits : ".$nb_nuits."\n
						 commande : ".$commande."\n
						 petit dej : ".$petit_dejeuner."\n
						 cuisine : ".$cuisine_a_manger."\n
						 parking : ".$parking);
					}
				}
			}
			
			/******************************
			 * Activation d'une réservation
			 * @param id_commande : id de la commande associée à la réservation à valider
			 ******************************/ 
			function activerReservation($id_commande){
				$maj = '
					UPDATE reservations
					SET active = 1
					WHERE commande = "'.$id_commande.'"
				';
				$result_query = mysql_query($maj);
				if(mysql_affected_rows() > 0){
					mail('sebastien@ascomedia.com', 'activation college', 'Activation de la commande n°'.$id_commande);
					$this->log_this('Activation de la commande n°'.$id_commande);
					return true;
				}
				else{
					mail('sebastien@ascomedia.com', 'activation college', 'Activation échouée pour la commande n°'.$id_commande);
					$this->log_this('Activation échouée pour la commande n°'.$id_commande);
					return false;
				}
			}
			
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Fin des fonctions concernant la gestion des réservations												  	  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		
		
		
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Groupe de fonctions concernant le module d'administration du plugin 										  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		
			// Génère un tableau pour l'année en cours
			function genererPlanning($annee=null, $chambre=null){

				// Nom des mois à afficher dans le tableau
				$nom_mois = array("Janvier","F&eacute;vrier","Mars","Avril","Mai","Juin","Juillet","Ao&ucirc;t","Septembre","Octobre","Novembre","D&eacute;cembre");

				// Par défaut, annee courante
				if($annee == null){
					$date = date('Ymd');
					$annee = substr($date, 0, 4);
				}
				// Par défaut, chambre 1 (Marroniers)
				if($chambre == null)
					$chambre = 1;

				$tableau = '<div align="center" style="font-size:16px">
								<a class="clickable" title="Ann&eacute;e pr&eacute;c&eacute;dente" onclick="getPlanning(\'prec\');"> << </a>
								<span id="annee">'.$annee.'</span>
								<a class="clickable" title="Ann&eacute;e suivante" onclick="getPlanning(\'suiv\');"> >> </a>
							</div>';

			
				$tableau .= '<table cellpadding="0" cellspacing="0" border="1">';
				$tableau .= '<tr><td>Mois\Jours</td>';

				// On affiche les 31 jours maxi dans la 1ère ligne du tableau
				for($i=1; $i < 32 ; $i++) {
					$tableau .= '<td class="jours">'.$i.'</td>';
				}
				$tableau .= '</tr>';

					// Affichage des jours pour chaque mois
					for($mois=1 ; $mois < 13 ; $mois++){
						$tableau .= '<tr>';
						$tableau .= '<td class="mois">'.$nom_mois[$mois-1].'</td>';

						// Génération des jours pour le mois
						$nb_jours = 32;
						if($mois==4 ||$mois==6 || $mois==9 || $mois==11) $nb_jours--;
						if($mois==2) {
							$nb_jours = $nb_jours - 3;
							if($annee%4==0) $nb_jours++;
							if($annee%100==0) $nb_jours--;
							if($annee%400==0) $nb_jours++;
						}

						// Affichage des réservations pour chaque jour
						for($jours=1; $jours < $nb_jours ; $jours++) {
							// date du jour à traiter
							$dateJour = date('Ymd', mktime(0, 0, 0, $mois, $jours, $annee));
				
							// nombre de chambre dispo sur le site à cette date
							$stock = $this->checkStock($dateJour, $chambre);

							if($stock === false)
								$stock = $this->getStock($chambre);

							// nombre de réservations pour ce jour
							$nb_reservations_jour = $this->reservationsJour($dateJour, $chambre);
							
							$quantite_affiche = $stock-$nb_reservations_jour;
						
							// si stock volontairement placé à 0, on l'indique avec un rouge
							if($stock == 0)
								$tableau .= '<td bgcolor="#F53322" id="'.$annee.'-'.$mois.'-'.$jours.'" class="jours_cases" onclick="actionReservation(\''.$annee.'-'.$mois.'-'.$jours.'\');"><span>'.$quantite_affiche.'</span></td>';
							else{
								// Si complet, on affiche en violet
								if($nb_reservations_jour > 0 && $quantite_affiche == 0)
									$tableau .= '<td bgcolor="purple" id="'.$annee.'-'.$mois.'-'.$jours.'" class="jours_cases" onclick="actionReservation(\''.$annee.'-'.$mois.'-'.$jours.'\');"><span>'.$quantite_affiche.'</span></td>';
								else{
									// sinon si des réservations ont été effectuées à ce jour, on affiche du orange
									if($nb_reservations_jour > 0 && $quantite_affiche != 0)
										$tableau .= '<td bgcolor="#FF9900" id="'.$annee.'-'.$mois.'-'.$jours.'" class="jours_cases" onclick="actionReservation(\''.$annee.'-'.$mois.'-'.$jours.'\');"><span>'.$quantite_affiche.'</span></td>';
									// sinon rien d'anormal, on affiche en blanc
									else
										$tableau .= '<td id="'.$annee.'-'.$mois.'-'.$jours.'" class="jours_cases" onclick="actionReservation(\''.$annee.'-'.$mois.'-'.$jours.'\');"><span>'.$quantite_affiche.'</span></td>';
								}
							}

						} //fin boucle des jours

						// On boucle sur les jours restants pour afficher des cases grises pour terminer le tableau
						$surplus = 32 - $nb_jours;
						for($i=0 ; $i < $surplus ; $i++){
							$tableau .= '<td bgcolor="grey" class="jours_surplus"></td>';
						}
						$tableau .= '</tr>';
					}// fin boucle des mois

				$tableau .= '</table>';

				return $tableau;
			}
			
			// renvoi les différentes réservations dans un tableau (id commande + nom + prénom)
			function getReservations($date, $chambre){
				$query = '
					SELECT res.commande as commande, cli.nom as nom, cli.prenom as prenom
					FROM reservations as res INNER JOIN commande as co ON co.id = res.commande
					INNER JOIN client as cli ON cli.id = co.client
					WHERE date_reservation = "'.$date.'"
					AND type_chambre = "'.$chambre.'"
					AND active = 1
				;';
				$result = mysql_query($query, $this->link);
				$commande = array();
				$i = 0;
				while($row = mysql_fetch_assoc($result)){
					$commande[$i]['commande'] = $row['commande'];
					$commande[$i]['nom'] = $row['nom'];
					$commande[$i]['prenom'] = $row['prenom'];
					$i++;
				}
				
				return $commande;
			}
			
			// récupère les informations d'une réservation en fonction du numéro de commande
			function getInfosReservation($id_commande){
				$query = '
					SELECT COUNT(*) as nb_nuits, cli.nom as nom, cli.prenom as prenom, cli.email as email, res.date_reservation as date_reservation, res.nb_chambres as nb_chambres, 
					res.petit_dejeuner as petit_dejeuner, res.cuisine_a_manger as cuisine_a_manger, res.parking as parking, res.type_chambre as chambre
					FROM client as cli 
					INNER JOIN commande as co ON co.client = cli.id
					INNER JOIN reservations as res ON res.commande = co.id 
					WHERE res.commande = "'.$id_commande.'"
					LIMIT 1;
				';
				$result = mysql_query($query, $this->link);
				$infos = array();
				$row = mysql_fetch_assoc($result);
				
				$infos['nom'] = $row['nom'];
				$infos['prenom'] = $row['prenom'];
				$infos['email'] = $row['email'];
				$infos['date_reservation'] = $row['date_reservation'];
				$infos['nb_chambres'] = $row['nb_chambres'];
				$infos['petit_dejeuner'] = $row['petit_dejeuner'];
				$infos['cuisine_a_manger'] = $row['cuisine_a_manger'];
				$infos['parking'] = $row['parking'];
				$infos['nb_nuits'] = $row['nb_nuits'];
				$infos['chambre'] = $row['chambre'];
				return $infos;
			}

		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Fin des fonctions concernant le module d'administration du plugin										  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		
		
		
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Groupe de fonctions concernant la gestion des stock des chambres 										  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		
		
			/*************************************************************************************************************
			/* Récupère le stock de référence d'une chambre dans la table produit
			*************************************************************************************************************/
			function getStock($chambre){
				$query = "
					SELECT stock
					FROM produit
					WHERE id = '".$chambre."'
				";
				$result = mysql_query($query);
				$stock = mysql_fetch_row($result);
				if(empty($stock))
					return false;
				else
					return $stock[0];
			}

			/***************************************************************************************************************
			/* Insertion / MàJ / Suppression des stock
			****************************************************************************************************************/
			function updateStock($date, $chambre, $stock){
				$query = "
					UPDATE stock_chambre
					SET stock = '".$stock."'
					WHERE date = '".$date."'
					AND chambre = '".$chambre."';
				";
				$result = mysql_query($query, $this->link);
				return $result;
			}

			function insertStock($date, $chambre, $stock){
				$query = "
					INSERT INTO stock_chambre (date, chambre, stock)
					VALUES ('".$date."', '".$chambre."', '".$stock."');
				";
				$result = mysql_query($query);
				return $result;
			}

			function deleteStock($date, $chambre){
				$query = "
					DELETE FROM stock_chambre
					WHERE date = '".$date."'
					AND chambre = '".$chambre."';
				";
				$result = mysql_query($query, $this->link);
				return $result;
			}

			/***************************************************************************************************************
			/* Retourne le stock d'une chambre à une date, ou false
			****************************************************************************************************************/
			function checkStock($date, $chambre){
				// On vérifie le format de la date en entrée. Si FR, on la transforme en US
				$dateformat = $this->checkDateFormat($date);
				if($dateformat == 'FR')
					$date = $this->dateFrToUs($date);
					
				$query = "
					SELECT stock
					FROM stock_chambre
					WHERE chambre = '".$chambre."'
					AND date = '".$date."'
				";
				$result = mysql_query($query, $this->link);
				$stock = mysql_fetch_row($result);

				if(empty($stock))
					return false;
				else
					return $stock[0];
			}
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Fin fonctions concernant la gestion des stocks des chambres 											  	  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		

		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Groupe de fonctions concernant la gestion des prix des chambres 											  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		
			function getPrix($chambre){
				$query = "
					SELECT prix
					FROM produit
					WHERE id = '".$chambre."'
				";
				$result = mysql_query($query);
				$prix = mysql_fetch_row($result);
				return $prix[0];
			}

			/***************************************************************************************************************
			/* MàJ du prix du bien
			/* - id de la chambre et date de la réservation donnés en entrée 
			/* - la date sert à vérifier si un tarif différent n'a pas été entré (table prix)
			/* - retourne : le prix d'origine, sauf si un prix différent doit être pratiqué
			****************************************************************************************************************/
			function updatePrix($date, $chambre, $prix){
				$query = "
					UPDATE prix
					SET prix = '".$prix."'
					WHERE date = '".$date."'
					AND chambre = '".$chambre."';
				";
				$result = mysql_query($query);
				return $result;
			}

			function insertPrix($date, $chambre, $prix){
				$query = "
					INSERT INTO prix (chambre, date, prix)
					VALUES ('".$chambre."', '".$date."', '".$prix."');
				";
				$result = mysql_query($query);
				return $result;
			}

			function deletePrix($date, $chambre){
				$query = "
					DELETE FROM prix
					WHERE date = '".$date."'
					AND chambre = '".$chambre."';
				";
				$result = mysql_query($query);
				return $result;
			}

			/***************************************************************************************************************
			/* Récupération du prix du bien, modifié ou non
			/* - retourne : le prix s'il est différent de celui d'origine, false sinon
			****************************************************************************************************************/
			function checkPrix($date, $chambre){
				// On vérifie le format de la date en entrée. Si FR, on la transforme en US
				$dateformat = $this->checkDateFormat($date);
				if($dateformat == 'FR')
					$date = $this->dateFrToUs($date);
					
				// indique si un prix différent de celui de base a été rentré
				$query = "
					SELECT prix
					FROM prix
					WHERE date = '".$date."'
					AND chambre = '".$chambre."'
				";
				$result = mysql_query($query);
				$prix_special = mysql_fetch_row($result);
				if(empty($prix_special))
					return false;
				else
					return $prix_special[0];
			}


			// Calcul du total pour une réservation, prend en compte les changements de prix
			function calculTotal($date_arrivee, $type_chambre, $nb_chambres, $nb_nuits){
				$total = 0;
				$date = array();

				// On passe la date en us pour la base
				$date_arrivee = $this->dateFrToUs($date_arrivee);

				// On décompose la date d'arrivée pour créer les autres dates 
				$year = substr($date_arrivee, 0, 4);
				$month = substr($date_arrivee, 5, 2);
				$day = substr($date_arrivee, 8, 2);
				
				$prix_nuit = 0;
				// On crée chacun des jours en fonction de la date d'arrivée
				for($i=0 ; $i < $nb_nuits ; $i++){
					$date = date("Ymd", mktime(0, 0, 0, $month, $day+$i, $year));
					// On ajoute au total le prix de la nuit à cette date
					$prix_nuit = $this->checkPrix($date, $type_chambre);
					
					if($prix_nuit == false)
						$prix_nuit = $this->getPrix($type_chambre);
						
					$total += $prix_nuit*$nb_chambres;
				}
				return $total;
			}
			
			// Calcul du total pour une réservation à partir du numéro de commande
			function calculTotalId($commande){
				$total = 0;
				$query = mysql_query('
					SELECT SUM(nb_chambres*prix_unitaire)
					FROM reservations
					WHERE commande = "'.$commande.'"
				', $this->link);
				
				$row = mysql_fetch_row($query);
				$total = $row[0];
				return $total;
			}
		/**************************************************************************************************************/
		/**************************************************************************************************************/
		/* Fin fonctions concernant la gestion des prix des chambres 											  	  */
		/**************************************************************************************************************/
		/**************************************************************************************************************/

			function boucle($texte, $args){
				// récupération des arguments
				//$id = lireTag($args, "id");
				if($_SESSION['navig']->lang == "") $lang=1; else $lang= intval($_SESSION['navig']->lang);
				$affiche = lireTag($args, "affiche");
				$search ="";
				$res="";
				
				switch($affiche){
					// si affiche dispo == true, on affiche toutes les chambres dispos, sinon on affiche la chambre choisie
					case "dispo":
						// On récupère un tableaux des id des chambres dispo selon les critères choisis
						$chambres_dispo = $this->checkDispo($_SESSION['reservation']->date, $_SESSION['reservation']->nbchambres, $_SESSION['reservation']->nbnuits);
						$nb_chambres = count($chambres_dispo);
						
						for($i=0; $i < $nb_chambres ; $i++){
							// récupération du prix pour cette date
							$prix = $this->checkprix($_SESSION['reservation']->date, $chambres_dispo[$i]);
	
							$query = mysql_query('
								SELECT pdesc.titre as titre, pdesc.chapo as chapo, pdesc.description as description, p.prix as prix
								FROM produit as p INNER JOIN produitdesc as pdesc
								ON p.id = pdesc.produit
								WHERE p.id = "'.$chambres_dispo[$i].'"
								AND pdesc.lang = "'.$lang.'"
							');
							$infos = array();
							$infos = mysql_fetch_array($query);
							$temp = str_replace("#ID", $chambres_dispo[$i], $texte);
							$temp = str_replace("#TITRE", $infos['titre'], $temp);
							$temp = str_replace("#CHAPO", $infos['chapo'], $temp);
							$temp = str_replace("#DESCRIPTION", $infos['description'], $temp);
							// On affiche le bon prix : si un prix différent est appliqué à la date d'arrivée, on l'indique
							if($prix != false)
								$temp = str_replace("#PRIX", $prix, $temp);
							else
								$temp = str_replace("#PRIX", $infos['prix'], $temp);
	
							$res .= $temp;
						}
					break;
					
					case "chambres_infos" :
						
						$infos_chambres = $this->getChambres();
						$nb_chambres_a_reserver = count($infos_chambres);
						
						for($i=0 ; $i < $nb_chambres_a_reserver ; $i++){
							$temp = str_replace("#ID", $infos_chambres[$i]['id'], $texte);
							$temp = str_replace("#TITRE", $infos_chambres[$i]['titre'], $texte);
							$temp = str_replace("#CHAPO", $infos_chambres[$i]['chapo'], $temp);
							$temp = str_replace("#DESCRIPTION", $infos_chambres[$i]['description'], $temp);
							
							// récupération du prix pour cette date
							$prix = $this->checkprix($_SESSION['reservation']->date, $infos_chambres[$i]['id']);
				
							// On affiche le bon prix : si un prix différent est appliqué à la date d'arrivée, on l'indique
							if($prix != false)
								$temp = str_replace("#PRIX", $prix, $temp);
							else
								$temp = str_replace("#PRIX", $infos_chambres[$i]['prix'], $temp);

							$res .= $temp;
						}
					break;
					
					// si affiche vaut chambre, on affiche la chambre choisie
					case "chambre":
						$query = mysql_query('
							SELECT pdesc.titre as titre, pdesc.chapo as chapo
							FROM produit as p INNER JOIN produitdesc as pdesc
							ON p.id = pdesc.produit
							WHERE p.id = "'.$_SESSION['reservation']->chambre.'"
							AND pdesc.lang = "'.$lang.'"
						');
						$resultat = mysql_fetch_array($query);
						$temp = str_replace("#TITRE", $resultat['titre'], $texte);
						$temp = str_replace("#CHAPO", $resultat['chapo'], $temp);
						$temp = str_replace("#DATE", $_SESSION['reservation']->date, $temp);
				
						$res .= $temp;
					break;
					
					// si affiche vaut recap, on affiche les informations de la réservation
					case "recap":
						// calcul du total à faire
						$total = $this->calculTotal($_SESSION['reservation']->date, $_SESSION['reservation']->chambre, $_SESSION['reservation']->nbchambres, $_SESSION['reservation']->nbnuits);
				
						$temp = str_replace("#TITRE", $_SESSION['reservation']->titre, $texte);
						$temp = str_replace("#CHAPO", $_SESSION['reservation']->chapo, $temp);
						$temp = str_replace("#NUITS", $_SESSION['reservation']->nbnuits, $temp);
						$temp = str_replace("#NBCHAMBRES", $_SESSION['reservation']->nbchambres, $temp);
						$temp = str_replace("#DATE", $_SESSION['reservation']->date, $temp);
						$temp = str_replace("#PETITDEJ", $_SESSION['reservation']->petit_dejeuner, $temp);
						$temp = str_replace("#CUISINE", $_SESSION['reservation']->cuisine_a_manger, $temp);
						$temp = str_replace("#PARKING", $_SESSION['reservation']->parking, $temp);
						$temp = str_replace("#TOTAL", $total, $temp);
				
						$res .= $temp;
					break;
				}

				return $res;
			}
			
			// Gestion des actions liées au plugin
			function action(){
				switch ($_REQUEST['action']){
					// page du choix des chambres, on stock les infos envoyées dans l'objet $_SESSION['reservation']
					case 'checkdispo':
						if(isset($_REQUEST['date-arrivee']) && !empty($_REQUEST['date-arrivee']))
						{
							// Vérification date d'arrivée
							$date_arrivee = $_REQUEST['date-arrivee'];
							
							$date = array();
							$pattern = "/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/";
							preg_match($pattern, $date_arrivee, $date);

							$date_parts = explode('-', $date[0]);
							
							$jour = (int)$date_parts[0];
							$mois = (int)$date_parts[1];
							$annee = (int)$date_parts[2];
							
							if(checkdate($mois, $jour, $annee))
								$_SESSION['reservation']->date = $date_arrivee;
							else
							{
								header("Location: http://www.college-hotel.com/v3/index.php?erreur=date");
							}
							
						}
						
						if(isset($_REQUEST['nombre-nuits']) && !empty($_REQUEST['nombre-nuits']))
							$_SESSION['reservation']->nbnuits = $_REQUEST['nombre-nuits'];
						
						if(isset($_REQUEST['nombre-chambres']) && !empty($_REQUEST['nombre-chambres']))
							$_SESSION['reservation']->nbchambres = $_REQUEST['nombre-chambres'];
						
					break;

					// page du choix des préférences, on stock les infos envoyées concernant la chambre choisie
					case 'choixchambre':
						if(isset($_REQUEST['chambre']) && !empty($_REQUEST['chambre']))
							$_SESSION['reservation']->chambre = $_REQUEST['chambre'];
						
						if(isset($_REQUEST['titre']) && !empty($_REQUEST['titre']))
							$_SESSION['reservation']->titre = $_REQUEST['titre'];
					break;
					
					// on stock les préférences choisies
					case 'preferences':
						
						if(isset($_REQUEST['petitdejeuner']) && !empty($_REQUEST['petitdejeuner']))
							$_SESSION['reservation']->petit_dejeuner = $_REQUEST['petitdejeuner'];

						if(isset($_REQUEST['cuisine']) && !empty($_REQUEST['cuisine']))
							$_SESSION['reservation']->cuisine_a_manger = $_REQUEST['cuisine'];
							
						if(isset($_REQUEST['parking']) && !empty($_REQUEST['parking']))
							$_SESSION['reservation']->parking = $_REQUEST['parking'];
					break;

					// paiement : cette fonction écrase celle du moteur de Thélia
					case 'reserver':
						if(!$_SESSION['navig']->client->id || !$_SESSION['reservation']->date){
							header("Location: index.php");
							exit;
						}
						if(empty($_SESSION['reservation'])){
							header("Location: index.php");
						}
						
						if(empty($_SESSION['navig']->client->nom) || empty($_SESSION['navig']->client->email) || empty($_SESSION['navig']->client->telfixe)){
							header("Location: coordonnees.php");
							exit;
						}
						
						
						if(isset($_REQUEST['petitdejeuner']) && !empty($_REQUEST['petitdejeuner']))
							$_SESSION['reservation']->petit_dejeuner = $_REQUEST['petitdejeuner'];

						if(isset($_REQUEST['cuisine']) && !empty($_REQUEST['cuisine']))
							$_SESSION['reservation']->cuisine_a_manger = $_REQUEST['cuisine'];
						
						if(isset($_REQUEST['parking']) && !empty($_REQUEST['parking']))
							$_SESSION['reservation']->cuisine_a_manger = $_REQUEST['parking'];
							
						if(isset($_REQUEST['type_paiement']) && !empty($_REQUEST['type_paiement']))
							$type_paiement = $_REQUEST['type_paiement'];
						
						$total = 0;
						$total = $this->calculTotal($_SESSION['reservation']->date, $_SESSION['reservation']->chambre, $_SESSION['reservation']->nbchambres, $_SESSION['reservation']->nbnuits);
						$_SESSION['reservation']->total = $total;
						
						$modules = new Modules();
						$modules->charger_id($type_paiement);
						if(! $modules->actif)
							return 0;

						$nomclass=$modules->nom;
						$nomclass[0] = strtoupper($nomclass[0]);

						include_once("client/plugins/" . $modules->nom . "/" . $nomclass . ".class.php");
						$modpaiement = new $nomclass();

						$commande = new Commande();
						$commande->transport = $_SESSION['navig']->commande->transport;
						$commande->client = $_SESSION['navig']->client->id;
						$commande->date = date("Y-m-d H:i:s");
						$commande->ref = "C" . date("ymdHis") . strtoupper(ereg_caracspec(substr($_SESSION['navig']->client->prenom,0, 3)));
						$commande->livraison = "L" . date("ymdHis") . strtoupper(ereg_caracspec(substr($_SESSION['navig']->client->prenom,0, 3)));
						$commande->remise = 0;

						$devise = new Devise();
						$devise->charger_nom($_SESSION['navig']->devise);
						$commande->devise = $devise->id;
						$commande->taux = $devise->taux;

						$client = new Client();
						$client->charger_id($_SESSION['navig']->client->id);

						$adr = new Venteadr();
						$adr->raison = $client->raison;
						$adr->entreprise = $client->entreprise;
						$adr->nom = $client->nom;
						$adr->prenom = $client->prenom;
						$adr->adresse1 = $client->adresse1;
						$adr->adresse2 = $client->adresse2;
						$adr->adresse3 = $client->adresse3;
						$adr->cpostal = $client->cpostal;
						$adr->ville = $client->ville;
						$adr->tel = $client->telfixe . "  " . $client->telport;
						$adr->pays = $client->pays;
						$adrcli = $adr->add();
						$commande->adrfact = $adrcli;

						$adr = new Venteadr();
						$livraison = new Adresse();

						if($livraison->charger($_SESSION['navig']->adresse)){

							$adr->raison = $livraison->raison;
							$adr->entreprise = $livraison->entreprise;
							$adr->nom = $livraison->nom;
							$adr->prenom = $livraison->prenom;
							$adr->adresse1 = $livraison->adresse1;
							$adr->adresse2 = $livraison->adresse2;
							$adr->adresse3 = $livraison->adresse3;
							$adr->cpostal = $livraison->cpostal;
							$adr->ville = $livraison->ville;
							$adr->tel = $livraison->tel;
							$adr->pays = $livraison->pays;

						}
						else {
							$adr->raison = $client->raison;
							$adr->entreprise = $client->entreprise;
							$adr->nom = $client->nom;
							$adr->prenom = $client->prenom;
							$adr->adresse1 = $client->adresse1;
							$adr->adresse2 = $client->adresse2;
							$adr->adresse3 = $client->adresse3;
							$adr->cpostal = $client->cpostal;
							$adr->ville = $client->ville;
							$adr->tel = $client->telfixe . "  " . $client->telport;
							$adr->pays = $client->pays;
						}

						$adrlivr = $adr->add();
						$commande->adrlivr = $adrlivr;

						$commande->facture = 0;

						$commande->statut="1";
						$commande->paiement = $type_paiement;

						$commande->lang = $_SESSION['navig']->lang;

						$commande->id = $commande->add();

						$pays = new Pays();
						$pays->charger($adr->pays);

						//$venteprod = new Venteprod();

							/* Gestion TVA */
							/*$prix = $_SESSION['navig']->panier->tabarticle[$i]->produit->prix;
							$prix2 = $_SESSION['navig']->panier->tabarticle[$i]->produit->prix2;
							$tva = $_SESSION['navig']->panier->tabarticle[$i]->produit->tva;

							if($pays->tva != "" && (! $pays->tva || ($pays->tva && $_SESSION['navig']->client->intracom != ""))) {
								$prix = round($prix/(1+($tva/100)), 2);
								$prix2 = round($prix2/(1+($tva/100)), 2);
								$tva = 0;
							}*/

							$pays = new Pays();
							$pays->charger($_SESSION['navig']->client->pays);

						 	if($_SESSION['navig']->client->pourcentage>0) $commande->remise = $total * $_SESSION['navig']->client->pourcentage / 100;

							$total -= $commande->remise;

						if($_SESSION['navig']->promo->id != ""){

							$commande->remise += calc_remise($total);

							$_SESSION['navig']->promo->utilise = 1;
							if(!empty($commande->remise))
								$commande->remise = round($commande->remise, 2);
							$commande->maj();
							$temppromo = new Promo();
							$temppromo->charger_id($_SESSION['navig']->promo->id);
							if(! $temppromo->illimite)
								$temppromo->utilise="1";
							$temppromo->maj();

							$promoutil = new Promoutil();
							$promoutil->commande = $commande->id;
							$promoutil->promo = $temppromo->id;
							$promoutil->add();
						}
		
						if($commande->remise > $total) $commande->remise = $total;

						$commande->port = port();
						if($commande->port == "" || $commande->port<0) $commande->port = 0;

						$_SESSION['navig']->promo = new Promo();
						$_SESSION['navig']->commande = $commande;

						$commande->transaction = $commande->id;
						$zero = 6 - strlen($commande->transaction);
						for($i = 0; $i < $zero; $i++)
								$commande->transaction = "0" . $commande->transaction;

						$commande->maj();

						$total = $_SESSION['reservation']->total;

						$_SESSION['navig']->commande->total = $total;
						$this->addReservation($_SESSION['reservation']->date, $_SESSION['reservation']->chambre, $_SESSION['reservation']->nbchambres, $_SESSION['reservation']->nbnuits, $commande->id, $_SESSION['reservation']->petit_dejeuner, $_SESSION['reservation']->cuisine_a_manger, $_SESSION['reservation']->parking);
// à ajouter dans la fonction paiement du module de paiement
//var_dump($this->addReservation($_SESSION['reservation']->date, $_SESSION['reservation']->chambre, $_SESSION['reservation']->nbchambres, $_SESSION['reservation']->nbnuits, $commande->id, $_SESSION['reservation']->petit_dejeuner, $_SESSION['reservation']->cuisine_a_manger));
						//redirige('merci.php');

						$modpaiement->paiement($commande);
					break;
					
					// Création du compte du client
					case 'coordonnees':
					// création d'un compte
						if(!isset($_REQUEST['raison'])) $raison=""; else $raison=lireParam("raison", "int");
						if(!isset($_REQUEST['entreprise'])) $entreprise=""; else $entreprise=lireParam("entreprise", "string+\-\'\,\s\/\(\)\&\"");	
						if(!isset($_REQUEST['siret'])) $siret=""; else $siret=lireParam("siret", "int+\-");
						if(!isset($_REQUEST['intracom'])) $intracom=""; else $intracom=lireParam("intracom", "string+\s");
						if(!isset($_REQUEST['prenom'])) $prenom=""; else $prenom=lireParam("prenom", "string+\-\'\,\s\/\(\)\&\"");
						if(!isset($_REQUEST['nom'])) $nom=""; else $nom=lireParam("nom", "string+\-\'\,\s\/\(\)\&\"");
						if(!isset($_REQUEST['adresse1'])) $adresse1=""; else $adresse1=lireParam("adresse1", "string+\-\'\,\s\/\(\)\&\";°:");
						if(!isset($_REQUEST['adresse2'])) $adresse2=""; else $adresse2=lireParam("adresse2", "string+\-\'\,\s\/\(\)\&\";°:");
						if(!isset($_REQUEST['adresse3'])) $adresse3=""; else $adresse3=lireParam("adresse3", "string+\-\'\,\s\/\(\)\&\";°:");
						if(!isset($_REQUEST['cpostal'])) $cpostal=""; else $cpostal=lireParam("cpostal", "string");
						if(!isset($_REQUEST['ville'])) $ville=""; else $ville=lireParam("ville", "string+\s\'\/\&\"");	
						if(!isset($_REQUEST['pays'])) $pays=""; else $pays=lireParam("pays", "int");
						if(!isset($_REQUEST['telfixe'])) $telfixe=""; else $telfixe=lireParam("telfixe", "string+\s\.\/");	
						//if(!isset($_REQUEST['telport'])) $telport=""; else $telport=lireParam("telport", "string+\s\.\/");
						if(!isset($_REQUEST['email1'])) $email1=""; else $email1=lireParam("email1", "string+\@\.");
						if(!isset($_REQUEST['email2'])) $email2=""; else $email2=lireParam("email2", "string+\@\.");
						if(!isset($_REQUEST['motdepasse1'])) $motdepasse1=""; else $motdepasse1=lireParam("motdepasse1", "string+\-\'\,\s\/\(\)\&\@\.\!\"");
						if(!isset($_REQUEST['motdepasse2'])) $motdepasse2=""; else $motdepasse2=lireParam("motdepasse2", "string+\-\'\,\s\/\(\)\&\@\.\!\"");
						if(!isset($_REQUEST['parrain'])) $parrain=""; else $parrain=lireParam("parrain", "string+\@\.");	

						global $obligetelfixe; //, $obligetelport;

						$client = New Client();
						$client->raison = strip_tags($raison);
						$client->nom = strip_tags($nom);
						$client->entreprise = strip_tags($entreprise);
						$client->ref = date("ymdHis") . strtoupper(ereg_caracspec(substr(strip_tags($prenom),0, 3)));
						$client->prenom = strip_tags($prenom);
						$client->telfixe = strip_tags($telfixe);
						//$client->telport =strip_tags($telport);
						if( preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]{2,}[.][a-zA-Z.]+$/","$email1") && $email1==$email2)
							$client->email = strip_tags($email1);
						$client->adresse1 = strip_tags($adresse1);
						$client->adresse2 = strip_tags($adresse2);
						$client->adresse3 = strip_tags($adresse3);
						$client->cpostal = strip_tags($cpostal);
						$client->ville = strip_tags($ville);
						$client->siret = strip_tags($siret);
						$client->intracom = strip_tags($intracom);
						$client->pays = strip_tags($pays);
						$client->type = "0";
						$client->lang = $_SESSION['navig']->lang;

						$testcli = new Client();
						if($parrain != "")
							if($testcli->charger_mail($parrain)) $parrain=$testcli->id;
							else $parrain=-1;
						else $parrain=0;

						if($testcli->id != "") $client->parrain=$testcli->id;

						if($motdepasse1 == $motdepasse2 && strlen($motdepasse1)>3 ) $client->motdepasse = strip_tags($motdepasse1);

						$_SESSION['navig']->formcli = $client;

						$obligeok = 1;

						if($obligetelfixe && $client->telfixe=="") $obligeok=0;
						//if($obligetelport && $client->telport=="") $obligeok=0;
						
						if($client->prenom!="" && $client->nom!="" && $client->email && $client->cpostal!="" && $client->ville !="" && $client->pays !="" && $obligeok){
							$_SESSION['navig']->client = $client;

							$client->crypter();

							$client->id = $client->add();

					   		//$rec = $this->charger_mail($client->email);

							//if($rec) {
								 $_SESSION['navig']->client = $client;
								 $_SESSION['navig']->connecte = 1;
							//}

							redirige("recapitulatif.php");
						}

						else {
							redirige("coordonnees_erreur.php?errform=1");
						}
					break;
				}
			}

		
		// Fonction appelée lors de la confirmation de paiement
		function confirmation($commande){
			if($commande->statut == 2){
				$this->activerReservation($commande->id);
			}
		}
	}

?>
