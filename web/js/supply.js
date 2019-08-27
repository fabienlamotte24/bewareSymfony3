//Au chargement de la page
$(function(){
	//On utilise le plug-in jquery datatable
    $("#myTable").DataTable({
		//Intégration de la traduction en Français de la table jquery
		language: {
			processing:     "Traitement en cours...",
			search:         "Rechercher&nbsp;:",
			lengthMenu:    "Afficher _MENU_ &eacute;l&eacute;ments",
			info:           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
			infoEmpty:      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
			infoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
			infoPostFix:    "",
			loadingRecords: "Chargement en cours...",
			zeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
			emptyTable:     "Aucune donnée disponible dans le tableau",
			paginate: {
				first:      "Premier",
				previous:   "Pr&eacute;c&eacute;dent",
				next:       "Suivant",
				last:       "Dernier"
			},
			aria: {
				sortAscending:  ": activer pour trier la colonne par ordre croissant",
				sortDescending: ": activer pour trier la colonne par ordre décroissant"
			}
		}
	});
});

//Function custom supplyTool pour réapprovisionner le stock d'un outil
function supplyTool(that){
	//On récupère l'id du produit
	let id = that.id;

	//On injecte le contenu html dans les blocs de la modale
	$('.sentenceModalBody').empty();
	$('.sentenceModalBody').append('Attention !<br />Réapprovisionner cet outil fera réapparaître ce produit dans votre liste principale<br />Confirmer ?');

	//On donne à l'action du formulaire l'id du produit pour le passer en paramètre dans le controller: supplyingAction
	let $form = $('#supply_form');
	$form.data().value = id;
	let action = $form.data().path.replace(':slug:', '') + $form.data().value;
	$form[0].action = action;
}

$('.flash-notice').delay(1500).slideUp(500);