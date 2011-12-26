<?php
	// URL à changer lors de l'installation du plugin
	$url = 'http://www.college-hotel.com/v3/client/plugins/reservation/ajax.php';
?>
<script src="../lib/jquery/jquery-1.5.1.min.js" type="text/javascript"></script>
<script src="../lib/jquery/jquery-ui-1.8.12.custom.min.js" type="text/javascript"></script>
<link href='../lib/jquery/widget/css/smoothness/jquery-ui-1.8.12.custom.css' rel='stylesheet' type="text/css" />
<!--<script src="../../../lib/jquery/planning.js" type="text/javascript"></script>-->
<?php
// prevent caching (php)
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Expires: ' . gmdate(DATE_RFC1123, time()-1));
?>
<script type="text/javascript">
var prix_diff = 0;
var chambre = "";
var prix_reference = 0;
var qte_chambre = 0;
var date_reserv = "";

// appel ajax pour récupérer le prix de référence d'une chambre
function recupPrix() {
	chambre = $("#chambre").val();
	$.ajax({
		method: "get",
		cache: false,
		url: "<?php echo $url?>",
		data: "action=getprix&chambre="+chambre,
		dataType: "text",
		success: function(data){
			prix_reference = data;
		}
	});
}

// appel ajax pour récupérer la qté de base d'une chambre
function recupQte() {
	chambre = $("#chambre").val();
	$.ajax({
		method: "get",
		cache: false,
		url: "<?php echo $url?>",
		data: "action=getqte&chambre="+chambre,
		dataType: "text",
		success: function(data){
			qte_chambre = data;
		}
	});
}

$(document).ready( function(){
	recupPrix();
	recupQte();
});

function actionReservation(date_resa){
	// on change la valeur de la variable date_reserv globale
	date_reserv = date_resa;
	
	// tableau contenant les mois et jours sous forme de texte
	var tab_mois = new Array('Janvier', 'F&eacute;vrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Ao&ucirc;t', 'Septembre', 'Octobre', 'Novembre', 'D&eacute;cembre');
	var tab_jours = new Array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
	
	// décomposition de la date pour récupérer le jour de la semaine
	var date_split = date_reserv.split('-');
	var date_obj = new Date(date_split[0], date_split[1]-1, date_split[2]);
	
	var jour_nombre = date_obj.getDay();
	var jour = tab_jours[jour_nombre];
	
	var mois = tab_mois[date_split[1]-1];
	
	// On construit la date au format souhaité
	var date_texte = jour+" "+date_split[2]+" "+mois+" "+date_split[0];
	
	$('#dialog').css('visibility', 'visible');
	$('#dialog').dialog({
		modal: true,
		height: 'auto',
		resizable: false,
		closeOnEscape: true,
		title: date_texte,
		open: function(){
			$('#dialog').html('<ul><li>Quantit&eacute; max : <span id="qtemax"></span></li><li>Quantit&eacute; r&eacute;serv&eacute;e : <span id="qteres"></span></li><li><a title="voir r&eacute;servations" class="clickable" onclick="voirReservations();">Voir les r&eacute;servations</a></li><li><a title="changer prix" class="clickable" onClick="changerPrix();">Changer le prix &agrave; cette date</a></li><li><a title="changer quantit&eacute; max" class="clickable" onClick="changerQtemax();">Changer la quantit&eacute; max &agrave; cette date</a></li></ul>');
			var aujourdui = new Date();
			if(date_obj < aujourdui){
				$('#dialog').prepend('<p class="attention">Attention : cette date est ant&eacute;rieure &agrave; aujourd\'hui.</p>');
			}
			
			// affiche la quantité maxi à cette date
			$.ajax({
				method: "get",
				cache: false,
				url: "<?php echo $url?>",
				data: "action=get_stock_maxi&chambre="+chambre+"&date="+date_reserv,
				success: function(data){
					$('#qtemax').html(data);
				}
			});
			// affiche la quantité réservée &agrave; cette date
			$.ajax({
				method: "get",
				cache: false,
				url: "<?php echo $url?>",
				data: "action=get_nb_reservations&chambre="+chambre+"&date="+date_reserv,
				success: function(data){
					$('#qteres').html(data);
				}
			});
		},
		close: function (){ $('#dialog').empty(); getPlanning(); }
	});
}

function voirInfosReservation(commande){
	$.ajax({
		method: "get",
		cache: false,
		url: "<?php echo $url?>",
		data: "action=get_infos_reservation&commande="+commande,
		success: function(data){
			$('#dialog').empty();
			$('#dialog').html(data);
		}
	});
}

function voirReservations(){
	$.ajax({
		method: "get",
		cache: false,
		url: "<?php echo $url?>",
		data: "action=get_reservations&chambre="+chambre+"&date="+date_reserv,
		success: function(data){
			$('#dialog').empty();
			$('#dialog').html(data);
		}
	});
	
}

function changerQtemax(){
	chambre = $('#chambre').val();
	var qtemax_actuelle = $('#qtemax').html();
	
	$('#dialog').empty();
	$('#dialog').html('<p>Qt&eacute; max actuelle : '+qtemax_actuelle+'<span id="qtemax_actuelle"></span></p><label for="qtemax">Nouvelle qt&eacute; max : </label><input type="text" id="qtemax" name="qtemax"></input><input id="sendbutton" type="button" value="Envoyer"></input>');
	$('#qtemax').focus();
	$('#sendbutton').bind('click', function (){
		var nouvelle_qtemax = $('#qtemax').val();
		// booléen qui indique si la qte actuelle est différente de l'originale
		if(qtemax_actuelle != qte_chambre)
			qte_diff = true;
		else
			qte_diff = false;
			
		// Quantité pratiquée différente de celle de référence, donc déjà changée : on la modifie
		if(qte_diff){
			// si la nouvelle qté correspond à la qté de référence, on la supprime de la table stock_chambre
			if(nouvelle_qtemax == qte_chambre){
				$.ajax({
					method: "get",
					cache: false,
					url: "<?php echo $url?>",
					data: "action=delete_stock&chambre="+chambre+"&date="+date_reserv+"&stock="+qtemax_actuelle,
					
					success: function(data){
						$('#dialog').empty();
						if(data > 0)
							$('#dialog').html('<p>La modification de la quantit&eacute; maximale a bien &eacute;t&eacute; prise en compte.</p>');
						else
							$('#dialog').html('Une erreur est survenue (1)');
					}
				});
			}
			// si la nouvelle quantité est différente de celle de référence, et si elle est différente de celle en vigueur, on met à jour la ligne existante
			else{
				if(nouvelle_qtemax != qtemax_actuelle){
					$.ajax({
						method: "get",
						cache: false,
						url: "<?php echo $url?>",
						data: "action=update_stock&chambre="+chambre+"&date="+date_reserv+"&stock="+nouvelle_qtemax,
						success: function(data){
							$('#dialog').empty();
							if(data == true)
								$('#dialog').html('<p>La modification de la quantit&eacute; maximale a bien &eacute;t&eacute; prise en compte.</p>');
							else
								$('#dialog').html('Une erreur est survenue (2)');
						}
					});
				}
				// sinon, on indique que la quantité est déjà en vigueur
				else{
					$('#dialog').empty();
					$('#dialog').html('Cette quantit&eacute; maximale est d&eacute;j&agrave; en vigueur.');
				}
			}
		}
		// Quantité pratiquée = quantité de référence
		else{
			// si la nouvelle quantité est différente, on fais un insert
			if(nouvelle_qtemax != qte_chambre){
				$.ajax({
					method: "get",
					cache: false,
					url: "<?php echo $url?>",
					data: "action=insert_stock&chambre="+chambre+"&date="+date_reserv+"&stock="+nouvelle_qtemax,
					success: function(data){
						$('#dialog').empty();
						if(data == true)
							$('#dialog').html('<p>La modification de la quantit&eacute; maximale a bien &eacute;t&eacute; prise en compte.</p>');
						else
							$('#dialog').html('Une erreur est survenue (3).');
					}
				});
			}
			// sinon, on indique que le prix souhaité est déjà pratiqué
			else{
				$('#dialog').empty();
				$('#dialog').html('Ce prix est actuellement pratiqu&eacute;.');
			}
		}
	});
}

function changerPrix(){
	chambre = $('#chambre').val();
	$('#dialog').empty();
	$('#dialog').html('<p>Prix actuel : <span id="prix_actuel"></span></p><label for="prix">Nouveau prix : </label><input type="text" id="prix" name="prix"></input>');
	$('#prix').focus();
	// récupération du prix de la chambre pour cette date
	$.ajax({
		method: "get",
		cache: false,
		url: "<?php echo $url?>",
		data:  "action=affiche_prix&chambre="+chambre+"&date="+date_reserv,
		dataType: "text",
		// insertion du prix dans la fenetre
		success: function(data){
			// prix en vigueur de la chambre
			var prix_en_vigueur = 0;

			if(data != false)
				prix_en_vigueur = data;
			else
				prix_en_vigueur = prix_reference;

			// booléen qui indique si le prix actuel est différent de celui de référence
			if(prix_en_vigueur != prix_reference)
				prix_diff = true;
			else
				prix_diff = false;

			$('#prix_actuel').html(prix_en_vigueur);

			// ajout du bouton pour envoyer la requête de mise à jour / insertion en base
			$('#dialog').append('<input id="sendbutton" type="button" value="Envoyer"></input>');

			// ajout de l'appel ajax au bouton d'envoi
			$('#sendbutton').bind('click', function (){
				var nouveau_prix = $('#prix').val();
				// Prix déjà changé : on le modifie ou supprime
				if(prix_diff){
					// si le nouveau prix correspond au prix de référence, on le supprime de la table prix
					if(nouveau_prix == prix_reference){
						$.ajax({
							method: "get",
							cache: false,
							url: "<?php echo $url?>",
							data: "action=delete_prix&chambre="+chambre+"&date="+date_reserv+"&prix="+prix_en_vigueur,
							success: function(data){
								$('#dialog').empty();
								if(data > 0)
									$('#dialog').html('La modification du prix a bien &eacute;t&eacute; prise en compte');
								else
									$('#dialog').html('Une erreur est survenue.');
							}
						});
					}
					// si le nouveau prix est inférieur ou supérieur au prix de référence, on met à jour la ligne correspondante dans la table prix
					else{
						if(nouveau_prix > 0){
							if(nouveau_prix != prix_en_vigueur){
								$.ajax({
									method: "get",
									cache: false,
									url: "<?php echo $url?>",
									data: "action=update_prix&chambre="+chambre+"&date="+date_reserv+"&prix="+nouveau_prix,
									success: function(data){
										$('#dialog').empty();
										if(data == true)
											$('#dialog').html('La modification du prix a bien &eacute;t&eacute; prise en compte');
										else
											$('#dialog').html('Une erreur est survenue.');
									}
								});
							}
							else{
								$('#dialog').empty();
								$('#dialog').html('Ce prix est d&eacute;j&agrave; pratiqu&eacute;.');
							}
						}
						else{
							$('#dialog').empty();
							$('#dialog').html('Le prix doit être positif.');
						}
					}
				}
				// Prix non changé
				else{
					// si le nouveau prix est différent du prix de référence
					if(nouveau_prix != prix_reference){
						// le prix doit être positif
						if(nouveau_prix > 0){
							$.ajax({
								method: "get",
								cache: false,
								url: "<?php echo $url?>",
								data: "action=insert_prix&chambre="+chambre+"&date="+date_reserv+"&prix="+nouveau_prix,
								success: function(data){
									$('#dialog').empty();
									if(data == true)
										$('#dialog').html('La modification du prix a bien &eacute;t&eacute; prise en compte');
									else
										$('#dialog').html('Une erreur est survenue.');
								}
							});
						}
						// si le champs est vide ou n'est pas > 0, on ferme la fenêtre de dialog
						else{
							$('#dialog').dialog('close');
						}
					}
					else{
						$('#dialog').empty();
						$('#dialog').html('Ce prix est actuellement pratiqu&eacute;.');
					}
				}
			});

		}
	});
}
/*
function ajoutReservation(){

	$('#dialog').dialog({
		modal: true,
		height: 'auto',
		resizable: false,
		closeOnEscape: true,
		title: "Ajout d'une réservation",
		open: function(){ $('#dialog').html('<p>Nombre de chambres : <select id="nb_chambres" name="nb_chambres"><option value="1" selected="selected">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select></p><p>Nombre de nuits : <select id="nb_nuits" name="nb_nuits"><option value="1" selected="selected">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select></p><p>Date d\'arrivée<input type="text" id="date_arrivee"></input></p><p><input id="sendbutton" type="button" value="Envoyer"></input></p>'); },
		close: function (){ $('#dialog').empty(); }
	});
	$("#date_arrivee").datepicker({
		maxDate: '+1y',
		minDate: new Date(),
		dateFormat: 'yy-mm-dd'
	});
	$('#sendbutton').bind('click', function (){
		var nb_chambres = $("#nb_chambres").val();
		var date_arrivee = $("#date_arrivee").val();
		var nb_nuits = $("#nb_nuits").val();
		$.ajax({
			method: "get",
			url: "<?php echo $url?>",
			data: "action=ajout_reservation&chambre="+chambre+"&date="+date_arrivee+"&nb_chambres="+nb_chambres+"&nb_nuits="+nb_nuits,
			dataType: 'text',
			success: function(data){
				$('#dialog').empty();
				$('#dialog').html(data);
			}
		});
		
	});
	$('#dialog').css('visibility', 'visible');
	
}
*/
function getPlanning(element){
	recupPrix();
	recupQte();
	var annee = parseInt($('#annee').html(), 10);

	chambre = $('#chambre').val();

	if(element == 'suiv')
		annee += 1;
	if(element == 'prec')
		annee -= 1;

	//$('#div_planning').empty().html('<img src ="ajax/ajax-loader.gif" alt="chargement..."></img>');
	$.ajax({
		url: '<?php echo $url?>',
		cache: false,
		data: 'action=getplanning&annee='+annee+'&chambre='+chambre,
		success: function(data){
			$('#div_planning').html(data);
			console.log('renvoi du planning');
		}
	});
}
</script>

<?php
	
	// Instanciations
	$reservation_obj = new Reservation();
	$chambres = $reservation_obj->getChambres();

	// On récupère les paramètre dans l'url : annee et chambre
	if(isset($_GET['annee']) && !empty($_GET['annee']))
		$annee = $_GET['annee'];
	else
		$annee = date('Y');


	if(isset($_GET['chambre']) && !empty($_GET['chambre']))
		$chambre = $_GET['chambre'];
	else
		$chambre = 1;

?>
	<style>
		.jours_cases, .mois, .jours{
			width: 25px;
			text-align: center;
		}
		.jours_surplus{
			padding: 5px;
		}
		.mois{
			padding: 5px;
		}
		#annee{
			font-size: 16px;
		}
		.clickable{
			cursor:pointer;
		}
		#div_planning{
			text-align: center;
		}
		#ajout_reservation{
			padding-top: 10px;
		}
		#recharger_planning{
			text-align: center;
		}
		#dialog{
			visibility: hidden;
			text-align: center;
		}
		#voir_resa{
			text-align: left;
		}
		.attention{
			color: red;
		}
		.carre_rouge{
			background-color: red;
			padding: 5px;
			width: 30px;
			height: 20px;
			display: block;
		}
		.carre_orange{
			background-color: orange;
			padding: 5px;
			width: 30px;
			height: 20px;
			display: block;
		}
		.carre_violet{
			background-color: purple;
			padding: 5px;
			width: 30px;
			height: 20px;
			display: block;	
		}
	</style>

	<div id="contenu_int"> 
	   <p class="titre_rubrique">Planning </p>
	     <p align="right" class="geneva11Reg_3B4B5B"><a href="accueil.php" class="lien04">Accueil </a> <img src="gfx/suivant.gif" width="12" height="9" border="0" /><a href="#" class="lien04">Gestion des r&eacute;servations</a>              
	    </p>
	    
		<p>Type de chambre : </p>
		<select id="chambre" name="chambre" onChange="getPlanning();">
			<?php
				$nb_chambres = count($chambres);
				for($i=0 ; $i<$nb_chambres ; $i++){
					if($chambres[$i]['id'] == $chambre)
						echo '<option value='.$chambres[$i]['id'].' selected="selected">'.$chambres[$i]['titre'].'</option>';
					else
						echo '<option value='.$chambres[$i]['id'].'>'.$chambres[$i]['titre'].'</option>';
				}
			?>
		</select>

		<div id="div_planning" width="100%" height="100%">
				<?php
					$reservations_annee = $reservation_obj->genererPlanning($annee, $chambre);
					echo $reservations_annee;
				?>
		</div>
		
		<div id="recharger_planning">
			<p><a onclick="getPlanning();" class="clickable" title="Rafraichir les donn&eacute;es du planning">Rafraichir le planning</a></p>
		</div>

		<table>
			<tr>
				<td><span class="carre_rouge"></span></td>
				<td><p> R&eacute;servation d&eacute;sactiv&eacute;e ("stock" volontairement plac&eacute; &agrave; 0)</p></td>
			</tr>
			<tr>
				<td><span class="carre_orange"></span></td>
				<td><p> Un ou plusieurs clients ont r&eacute;serv&eacute; &agrave; cette date</p></td>
			</tr>
			<tr>
				<td><span class="carre_violet"></span></td>
				<td><p> Un ou plusieurs clients ont r&eacute;serv&eacute; &agrave; cette date et les chambres sont complètes</p></td>
			</tr>
		</table>

		<?php
		/******************************************************************************/
		/* Actions sur le planning :
		/* Apparaît après un clic sur le tableau, appel ajax sur reservation.php    
		/******************************************************************************/
		?>
		<div id="dialog">

		</div>

	</div>

</body>

</html>
