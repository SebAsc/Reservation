<?php
	
class Session_resa {
		
	var $nbnuits;
	var $nbchambres;
	var $chambre;
	var $titre;
	var $date;
	var $petit_dejeuner = 0;
	var $cuisine_a_manger = 0;
	var $parking = 0;
	var $total = 0;
	
	function Session_resa(){
		$this->nbnuits = 0;
		$this->nbchambres = 0;
		$this->chambre = 0;
		$this->titre = 0;
		$this->date = "";
		$this->petit_dejeuner = 0;
		$this->cuisine_a_manger = 0;
		$this->parking = 0;
		$this->total = 0;
	}
}
	
?>

