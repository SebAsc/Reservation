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
/*************************************************************************************/
?>
<?php
	include_once("pre.php");
	include_once("auth.php");
?>
<?php if(! est_autorise("acces_commandes")) exit; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
	include_once("title.php");
?>

</head>
<?php

	include_once("../classes/Commande.class.php");
	include_once("../classes/Produitdesc.class.php");
	include_once("../classes/Client.class.php");
	include_once("../classes/Paysdesc.class.php");
	include_once("../fonctions/divers.php");
	include_once("../classes/Modules.class.php");
	include_once(realpath(dirname(__FILE__)) . "/Reservation.class.php" );
	
	if(!isset($action)) $action="";
	if(!isset($client)) $client="";

	if(isset($_GET['commande']) && !empty($_GET['commande']))
		$id_commande = $_GET['commande'];
	
	// Récupération des informations du client et de sa réservation
	$commande = new Commande($id_commande);
	$client = new Client($commande->client);
	$nompays = new Paysdesc();
	$nompays->charger($client->pays);
	
	$reservation = new Reservation();
	// On récupère le détail de la réservation
	$infos_resa = $reservation->getReservationCommande($id_commande);
	$nb_lignes = count($infos_resa);
	
	$produitdesc = new Produitdesc();
	
?>

<body>

<div id="wrapper">
	<div id="subwrapper">
	
		<?php
			$menu="commande";
			include_once("entete.php");
		?>
		<div id="contenu_int">
			<p><a href="accueil.php" class="lien04">Accueil </a><img src="gfx/suivant.gif" width="12" height="9" border="0" /><a href="module.php?nom=reservation&vue=clients" class="lien04">Gestion des r&eacute;servations</a></p>
			
			<div id="bloc_description">
				<div class="entete_liste_client">
					<div class="titre">
						INFORMATIONS SUR LA RESERVATION
						<?php if($commande->statut == 2) echo '(Pay&eacute;e le '.$reservation->dateUsToFr($commande->datefact).')'; ?>
					</div>
				</div>
				<ul class="Nav_bloc_description" style="background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">
					<li style="width:77px; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Date</li>
					<li style="width:149px; border-left:1px solid #96A8B5; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Chambre</li>
					<li style="width:50px; border-left:1px solid #96A8B5; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Prix</li>
					<li style="width:30px; border-left:1px solid #96A8B5; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Qt&eacute;</li>
					<li style="width:242px;border-left:1px solid #96A8B5; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Total</li>
				</ul>
				
				<?php
				$total = 0;
				$petit_dejeuner = 0;
				$cuisine_a_manger = 0;
				$parking = 0;
				// On affiche une ligne par nuit réservée
				for($i=0 ; $i < $nb_lignes ; $i++){
					// Récupération du titre de la chambre
					$produitdesc->charger($infos_resa[$i]['type_chambre']);
					
					if(!($i%2)) $fond="ligne_fonce_BlocDescription";
			  		else $fond="ligne_claire_BlocDescription";
			  		
			  		// Transformation de la date au format FR
			  		$date_reservation = implode('-', array_reverse(explode('-', $infos_resa[$i]['date_reservation'])));
			  	?>
			  	<ul class="<?php echo($fond); ?>">
			  		<li style="width:69px"><?php echo $date_reservation?></li>
					<li style="width:142px;"><?php echo $produitdesc->titre; ?></li>
					<li style="width:43px;"><?php echo $infos_resa[$i]['prix_unitaire']; ?></li>
					<li style="width:23px;"><?php echo $infos_resa[$i]['nb_chambres']; ?></li>
					<li style="width:20px;"><?php echo(round($infos_resa[$i]['nb_chambres']*$infos_resa[$i]['prix_unitaire'], 2)); ?></li>
			    </ul>
			  		<?php
			  		$total += round($infos_resa[$i]['nb_chambres']*$infos_resa[$i]['prix_unitaire'], 2);
				}
				?>
				<ul class="ligne_total_BlocDescription">
				 	<li style="width:322px;">Total</li>
				 	<li><?php echo $total; ?> &euro;</li>
				</ul>
				
				<!-- Préférences du client -->
				<div class="entete_liste_client">
					<div class="titre">PREFERENCES</div>
				</div>
				<ul class="ligne_claire_BlocDescription" style="background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">
					<li class="designation" style="width:290px; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Petit-d&eacute;jeuner</li>
					<li><?php if($infos_resa[0]['petit_dejeuner'] != 0) echo "Oui"; else echo "non"; ?></li>
				</ul>
				<ul class="ligne_claire_BlocDescription">
					<li class="designation" style="width:290px;">Cuisine-&agrave;-manger</li>
					<li><?php echo $infos_resa[0]['cuisine_a_manger']; ?></li>
				</ul>
				<ul class="ligne_claire_BlocDescription" style="background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">
					<li class="designation" style="width:290px; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Parking</li>
					<li><?php if($infos_resa[0]['parking'] != 0) echo "Oui"; else echo "non"; ?></li>
				</ul>
				
				<!-- Infos du client -->
				
				<div class="bordure_bottom" style="margin:0 0 10px 0;">
					<div class="entete_liste_client">
						<div class="titre">INFORMATIONS RELATIVES AU CLIENT</div>
					</div>
					<ul class="ligne_claire_BlocDescription" style="background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">
						<li class="designation" style="width:290px; background-image: url(gfx/degrade_ligne1.png); background-repeat: repeat-x;">Soci&eacute;t&eacute;</li>
						<li><?php echo $client->entreprise; ?></li>
					</ul>
					<ul class="ligne_fonce_BlocDescription">
						<li class="designation" style="width:290px;">Pr&eacute;nom</li>
						<li><?php echo $client->prenom; ?></li>
					</ul>
					<ul class="ligne_claire_BlocDescription">
						<li class="designation" style="width:290px;">Nom</li>
						<li><?php echo $client->nom; ?></li>
					</ul>
					<ul class="ligne_fonce_BlocDescription">
						<li class="designation" style="width:290px;">Code postal</li>
						<li><?php echo $client->cpostal; ?></li>
					</ul>
					<ul class="ligne_claire_BlocDescription">
						<li class="designation" style="width:290px;">Ville</li>
						<li><?php echo $client->ville; ?></li>
					</ul>
					<ul class="ligne_fonce_BlocDescription">
						<li class="designation" style="width:290px;">Pays</li>
						<li><?php echo $nompays->titre; ?></li>
					</ul>
					<ul class="ligne_claire_BlocDescription">
						<li class="designation" style="width:290px;">T&eacute;l&eacute;phone</li>
						<li><?php echo $client->telfixe; ?></li>
					</ul>
					<ul class="ligne_fonce_BlocDescription">
						<li class="designation" style="width:290px;">Email</li>
						<li><?php echo $client->email; ?></li>
					</ul>	
				</div>
			</div>
		</div>
		<?php
			include_once("pied.php");
		?>
	</div>
</div>
</body>
</html>
