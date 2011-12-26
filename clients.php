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
<script src="../lib/jquery/jquery.js" type="text/javascript"></script>
<script src="../lib/jquery/jeditable.js" type="text/javascript"></script>
<script src="../lib/jquery/menu.js" type="text/javascript"></script>
<script type="text/javascript">
function tri(order,critere){
	$.ajax({
		type:"GET",
		url:"ajax/tricommande.php",
		data : 'order='+order+'&critere='+critere,
		success : function(html){
			$("#resul").html(html);  
		}
	})
}
</script>
</head>
<?php

	include_once("../classes/Commande.class.php");
	include_once("../classes/Client.class.php");
	include_once("../classes/Stock.class.php");
	include_once("../classes/Statut.class.php");
	include_once("../classes/Statutdesc.class.php");
	include_once("../fonctions/divers.php");
	include_once("../classes/Modules.class.php");

	if(!isset($action)) $action="";
	if(!isset($page)) $page=0;
	if(!isset($classement)) $classement="";

?>

<?php
// TODO : rendre la suppression efficace uniquement pour les réservations non finalisées
	/*if($action == "supprimer"){
	
		$tempcmd = new Commande();
		$tempcmd->charger($id);
		
		$modules = new Modules();
		$modules->charger_id($tempcmd->paiement);

		$nomclass=$modules->nom;
		$nomclass[0] = strtoupper($nomclass[0]);

		include_once("../client/plugins/" . $modules->nom . "/" . $nomclass . ".class.php");
		$modpaiement = new $nomclass();

		// On remet le stock si il a été défalqué
        if($modpaiement->defalqcmd || (! $modpaiement->defalqcmd && $tempcmd->statut != "1")){
   			$venteprod = new Venteprod();
   			$query = "select * from $venteprod->table where commande='" . $id . "'";
   			$resul = mysql_query($query, $venteprod->link);

			while($row = mysql_fetch_object($resul)){
				// incrémentation du stock général
    			$produit = new Produit();   
				$produit->charger($row->ref);
				$produit->stock = $produit->stock + $row->quantite;
    			$produit->maj();

				$vdec = new Ventedeclidisp();
			
				$query2 = "select * from $vdec->table where venteprod='" . $row->id . "'";
				$resul2 = mysql_query($query2, $vdec->link);
			
			
				while($row2 = mysql_fetch_object($resul2)){
					$stock = new Stock();
					if($stock->charger($row2->declidisp, $produit->id)){
						$stock->valeur = $stock->valeur + $row->quantite;
						$stock->maj();					
					}
				
				
				}
			}
		}

		$tempcmd->statut = "5";
		$tempcmd->maj();
		
		modules_fonction("statut", $tempcmd);
		
	}*/
	
?>

<?php

	// On rÃ©cupÃ¨re la liste des rÃ©servations Ã  afficher
	
	// Par dÃ©faut, rÃ©servations payÃ©es et activÃ©es
	$statut = ' and resa.active = 1 and co.statut = 2';
	if(isset($_GET['statut']) && $_GET['statut'] != "")
		$statut = $_GET['statut'];
	
	switch($statut){
		// rÃ©servations payÃ©es dont la date de fin est passÃ©e
		case 'anciennes':
			$query = '
				SELECT DISTINCT (resa.commande), MAX( resa.date_reservation )
				FROM commande AS co
				INNER JOIN reservations AS resa ON resa.commande = co.id
				WHERE resa.active =1
				AND co.statut =2
				AND resa.date_reservation < NOW()
				GROUP BY co.client
				ORDER BY resa.date_reservation
			';
			$titre_statut = 'Sold&eacute;e';
		break;
		// rÃ©servations non finalisÃ©es
		case 'non_finalisees':
			$query = '
				SELECT DISTINCT (resa.commande), MIN(resa.date_reservation)
				FROM commande AS co
				INNER JOIN reservations AS resa ON resa.commande = co.id
				WHERE resa.active = 0
				AND co.statut != 2
				GROUP BY co.client
				ORDER BY resa.date_reservation
			';
			$titre_statut = 'Impay&eacute;e';
		break;
		// rÃ©servations payÃ©es en cours et Ã  venir
		default:
			$query = '
				SELECT DISTINCT (resa.commande), MAX( resa.date_reservation )
				FROM commande AS co
				INNER JOIN reservations AS resa ON resa.commande = co.id
				WHERE resa.active = 1
				AND co.statut = 2
				AND resa.date_reservation > NOW()
				GROUP BY co.client
				ORDER BY resa.date_reservation
			';
			$titre_statut = 'Pay&eacute;e';
		break;
	}
  
  	$resul = mysql_query($query);
  	
  	// Pagination
  	if($page=="") $page=1;
  	$num = mysql_num_rows($resul);

  	$nbpage = 20;
  	$totnbpage = ceil($num/30);
  	
  	$debut = ($page-1) * 30;
  	
  	if($page>1) $pageprec=$page-1;
  	else $pageprec=$page;

  	if($page<$nbpage) $pagesuiv=$page+1;
  	else $pagesuiv=$page;
  	
  	// TODO : ajouter les classements
  	/*if($classement == "client") $ordclassement = "order by client";  	
  	else if($classement == "statut") $ordclassement = "order by statut";
  	else $ordclassement = "order by date desc";*/
?>

<script type="text/JavaScript">

function supprimer(id){
	if(confirm("Voulez-vous vraiment annuler cette commande ?")) location="commande.php?action=supprimer&id=" + id;

}

</script>

<body>

<div id="wrapper">
<div id="subwrapper">

<?php
	$menu="commande";
	include_once("entete.php");
?>


<div id="contenu_int"> 
    <p align="left"><a href="accueil.php" class="lien04">Accueil </a><img src="gfx/suivant.gif" width="12" height="9" border="0" /><a href="#" class="lien04">Gestion des r&eacute;servations</a>              
    </p>
<div class="entete_liste_client">
	<div class="titre">LISTE DES RESERVATIONS</div><!--<div class="fonction_ajout"><a href="commande_creer.php">CREER UNE COMMANDE</a> </div>-->
</div>
<ul id="Nav">
		<li style="height:25px; width:104px; border-left:1px solid #96A8B5;">Date op&eacute;ration</li>
		<li style="height:25px; width:104px; border-left:1px solid #96A8B5;">Date d'arriv&eacute;e</li>
		<li style="height:25px; width:200px; border-left:1px solid #96A8B5;">Soci&eacute;t&eacute;</li>
		<li style="height:25px; width:200px; border-left:1px solid #96A8B5;">Nom &amp; Pr&eacute;nom</li>	
		<li style="height:25px; width:59px; border-left:1px solid #96A8B5;">Montant</li>
		<li style="height:25px; width:70px; border-left:1px solid #96A8B5; background-image: url(gfx/picto_menu_deroulant.gif); background-position:right bottom; background-repeat: no-repeat;">Statut
			<ul class="Menu">
				<li style="width:100px;"><a href="module.php?nom=reservation&vue=clients&statut=a_venir">Pay&eacute;es</a></li>
				<li style="width:100px;"><a href="module.php?nom=reservation&vue=clients&statut=anciennes">Sold&eacute;es</a></li>
				<li style="width:100px;"><a href="module.php?nom=reservation&vue=clients&statut=non_finalisees">Impay&eacute;es</a></li>
			</ul>
		</li>
		<li style="height:25px; width:75px; border-left:1px solid #96A8B5;"></li>
		<!--<li style="height:25px; width:35px; border-left:1px solid #96A8B5;">Suppr.</li>-->
</ul>

<span id="resul">

  <?php
  	$i=0;
    $query .= " limit $debut,30";
  	$resul = mysql_query($query);

  	$reservation = new Reservation();
  	
  	while($row = mysql_fetch_object($resul)){
		
		$total = $reservation->calculTotalId($row->commande);
		
		// On récupère les informations de la réservation du client
		$query_infos = '
			SELECT MIN(resa.date_reservation) AS date_arrivee, co.date as date_operation, co.id, cli.entreprise, cli.nom, cli.prenom
			FROM reservations as resa 
			INNER JOIN commande as co ON resa.commande = co.id 
			INNER JOIN client as cli ON cli.id = co.client
			WHERE resa.commande = "'.$row->commande.'"
		';
		
		$result_infos = mysql_query($query_infos);
		$row_infos = mysql_fetch_object($result_infos);
		$date_arrivee = $row_infos->date_arrivee;
		$date_operation = $row_infos->date_operation;
		
		// Formatage des dates
		$jour_arrivee = substr($date_arrivee, 8, 2);
  		$mois_arrivee = substr($date_arrivee, 5, 2);
  		$annee_arrivee = substr($date_arrivee, 0, 4);
		
  		$jour_operation = substr($date_operation, 8, 2);
  		$mois_operation = substr($date_operation, 5, 2);
  		$annee_operation = substr($date_operation, 0, 4);
  		/*
  		$heure_operation = substr($date_operation, 11, 2);
  		$minute_operation = substr($date_operation, 14, 2);
  		$seconde_operation = substr($date_operation, 17, 2);
  		*/
  		if(!($i%2)) $fond="ligne_claire_rub";
  		else $fond="ligne_fonce_rub";
  		$i++;
  ?>
<ul class="<?php echo($fond); ?>">
	<li style="width:97px;"><?php echo($jour_operation . "/" . $mois_operation . "/" . $annee_operation); ?></li>
	<li style="width:97px;"><?php echo($jour_arrivee . "/" . $mois_arrivee . "/" . $annee_arrivee); ?></li>
	<li style="width:193px;"><?php echo($row_infos->entreprise); ?></li>
	<li style="width:193px;"><?php echo($row_infos->nom . " " . $row_infos->prenom); ?></li>
	<li style="width:52px;"><?php echo $total; ?> &euro;</li>
	<li style="width:63px;"><?php echo $titre_statut; ?></li>
	<li style="width:78px;"><a href="module.php?nom=reservation&vue=client_detail&commande=<?php echo($row_infos->id); ?>">Voir</a></li>
	<!--<li style="width:35px; text-align:center;"><a href="#" onclick="supprimer('<?php echo($row->id); ?>')"><img src="gfx/supprimer.gif" width="9" height="9" border="0" /></a></li>-->
</ul>
<?php
	}
?>
</span>

<p id="pages">
<?php if($page > 1){ ?>
   <a href="<?php echo($_SERVER['PHP_SELF']); ?>?page=<?php echo($pageprec); ?>&statut=<?php echo $_GET['statut']; ?>" >Page pr&eacute;c&eacute;dente</a> |
	<?php } ?>
	<?php if($totnbpage > $nbpage){?>
		<?php if($page>1) {?><a href="<?php echo($_SERVER['PHP_SELF']); ?>?page=1&statut=<?php echo $_GET['statut']; ?>">...</a> | <?php } ?>
		<?php if($page+$nbpage-1 > $totnbpage){ $max = $totnbpage; $min = $totnbpage-$nbpage;} else{$min = $page-1; $max=$page+$nbpage-1; }?>
     <?php for($i=$min; $i<$max; $i++){ ?>
    	 <?php if($page != $i+1){ ?>
  	  		 <a href="<?php echo($_SERVER['PHP_SELF']); ?>?page=<?php echo($i+1); ?>&classement=<?php echo($classement); ?>&statut=<?php echo $_GET['statut']; ?>" ><?php echo($i+1); ?></a> |
    	 <?php } else {?>
    		 <span class="selected"><?php echo($i+1); ?></span>
    		 |
   		  <?php } ?>
     <?php } ?>
		<?php if($page < $totnbpage){?><a href="<?php echo($_SERVER['PHP_SELF']); ?>?page=<?php echo $totnbpage; ?>&statut=<?php echo $_GET['statut']; ?>">...</a> | <?php } ?>
	<?php } 
	else{
		for($i=0; $i<$totnbpage; $i++){ ?>
	    	 <?php if($page != $i+1){ ?>
	  	  		 <a href="<?php echo($_SERVER['PHP_SELF']); ?>?page=<?php echo($i+1); ?>&statut=<?php echo $_GET['statut']; ?><?php echo $lien_voir; ?>"><?php echo($i+1); ?></a> |
	    	 <?php } else {?>
	    		 <span class="selected"><?php echo($i+1); ?></span>
	    		|
	   		  <?php } ?>
	     <?php } ?>
	<?php } ?>
     <?php if($page < $totnbpage){ ?>
<a href="<?php echo($_SERVER['PHP_SELF']); ?>?page=<?php echo($pagesuiv); ?>&statut=<?php echo $_GET['statut']; ?>">Page suivante</a></p>
	<?php } ?>
</div> 
<?php
	include_once("pied.php");
?>
</div>
</div>
</body>
</html>
