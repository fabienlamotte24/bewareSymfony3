
//Gestion de l"animation des vue changement d"information
$(function(){
	//Affichage du tableau avec plugin jquery datatable
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
	//Paramètre de la vue des fenêtres modale
	$(".second_modal_body").hide();
	$(".second_modal_buttons").hide();
	$(".modifyTitle").hide();

	//Au click "modification" de l"outil
	$(".modify_button").click(function(){
		$(".first_modal_body").hide("slide", {direction: "right", distance: '600px'}, 700);
		$(".first_modal_buttons").hide("slide", {direction: "left", distance: '600px'}, 700);
		$(".modaleTitle").hide("slide", {direction: "left", distance: '200px'}, 700);
		$(".second_modal_body").delay(500).fadeIn("slow");
		$(".second_modal_buttons").delay(500).fadeIn("slow");
		$(".modifyTitle").delay(500).fadeIn("slow");
	});

	//Au click de "retour" ou "annuler" de la fenêtre modale
	$('.dismissModal').click(function(){
		$(".second_modal_body").fadeOut();
		$(".second_modal_buttons").fadeOut();
		$(".modifyTitle").fadeOut();
		$(".first_modal_body").delay(300).fadeIn();
		$(".first_modal_buttons").delay(300).fadeIn();
		$(".modaleTitle").delay(300).fadeIn();
	});
});

//Fonction pour afficher l'infos produit au click du bouton "voir"
function toolToModify(that){
	//On stock l'id du produit
	let id = that.id;

	//Puis on viens chercher les informations détaillées dans la liste
    let getName = document.getElementById('getName' + id).innerHTML;
	let getPrice = document.getElementById('getPrice' + id).innerHTML;
	let getQuantity = document.getElementById('getQuantity' + id).innerHTML;
	let getDateCreated = document.getElementById('getDateCreated' + id).innerHTML;
	
	//On donne aux champs du formulaire les variables du produit en value
	$('#materialbundle_material_id').val(id);
	$('#materialbundle_material_name').val(getName);
	$('#materialbundle_material_price').val(getPrice);
	$('#materialbundle_material_quantity').val(getQuantity);
	$('#materialbundle_material_DateCreated').val(getDateCreated);

	//On donne à l'action du formulaire l'id du produit pour le passer en paramètre dans le controller: updateAction
	let $form = $('#update_form');
	$form.data().value = id;
	let action = $form.data().path.replace(':slug:', '') + $form.data().value;
	$form[0].action = action;

	//On vide le contenu html des fenêtre modale pour éviter l'incrémentation des informations
	$('.modal-title').empty();
	$('.first_modal_body').empty();
	$('#modifyTitle').empty();

	//Puis enrichit la fenêtre modale avec les informations demandées
	$('.modaleTitle').prepend(getName);
	$('.modifyTitle').prepend("Modifier " + getName);
	$('.first_modal_body').prepend("<p>L'article '" + getName + "' coûte " + getPrice + " €.<br />Vous en avez " + getQuantity + " en stock.<br />Cet article a été ajouté le " + getDateCreated + "</p>");
}

function toolToDelete(that){
	//On stock l'id passé en parametre de la fonction
	var id = that.id;

	//Puis on viens chercher les informations détaillées dans la liste
	let getName = document.getElementById('getName' + id).innerHTML;

	//On donne à l'action du formulaire l'id du produit pour le passer en paramètre dans le controller: updateAction
	let $form = $('#delete_form');
	$form.data().value = id;
	let action = $form.data().path.replace(':slug:', '') + $form.data().value;
	$form[0].action = action;

	//On vide le contenu html des fenêtre modale pour éviter l'incrémentation des informations
	$('.deleteModalTitle').empty();
	$('#delete_modal_body').empty();

	//Puis on personnalise en JS les informations html des fenêtre modale
	$('.deleteModalTitle').prepend("Supprimer '" + getName + "'");
	$('.delete_modal_body').prepend("Vous vous apprêtez à supprimer définitivement l'outil '" + getName + "'<br />Confirmer ?");
}

function showPdf(that){
	//On stock l'id passé en parametre de la fonction
	var id = that.id;
	//Puis on viens chercher les informations détaillées dans la liste
	let getName = document.getElementById('getName' + id).innerHTML;

	//On donne à l'action du formulaire l'id du produit pour le passer en paramètre dans le controller: updateAction
	let $form = $('#pdf_form');
	$form.data().value = id;
	let action = $form.data().path.replace(':slug:', '') + $form.data().value;
	$form[0].action = action;

	$('.pdf_modal_body').empty();
	$('.pdf_modal_body').prepend('Afficher le pdf de l\'article "' + getName + '" ?');
}