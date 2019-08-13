<?php

namespace MaterialBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use MaterialBundle\Entity\Material;
use MaterialBundle\Form\MaterialType;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * Méthode menant à la page d'accueil
     */
    public function indexAction()
    {
        return $this->render('@Material/Default/index.html.twig');
    }

    /**
     * Méthode menant à la page "listing", qui affiche la liste des outils de l'utilisateur
     */
    public function listingAction(){
        //Connexion à la base de donnée, avec l'entité Material
        $entityManager = $this->getDoctrine();
        $repo = $entityManager->getRepository(Material::class);
        //Nouvelles instances de la classe Material
        $material = new Material();
        $modify = new Material();
        $delete = new Material();
        $pdf = new Material();

        /**
         * Création des formulaires pour chacune des actions
         */
        //Utilisation du formBuilder dans MaterialType pour la création d'un outil
        $formModify = $this->createForm(MaterialType::class, $modify);
        //Autres formulaire ne nécessitant pas des champs de la table Material
        $formDelete = $this->createFormBuilder($delete)->getForm();
        $formPdf = $this->createFormBuilder($pdf)->getForm();

        //Recherche de tous les outils avec la méthode dédiée 
        $material = $repo->get_all_materials();

        /**
         * On afficha le vue listing, avec en paramètre les résultats de la recherche contenu dans la variable $material
         */
        return $this->render("@Material/Materials/listing.html.twig", [
            'material' => $material,
            'modify' => $formModify->createView(),
            'delete' => $formDelete->createView(),
            'pdf' => $formPdf->createView()
        ]);
    }

    /**
     * Méthode permettant la création d'un outil
     */
    public function newMaterialAction(Request $request){
        //Connexion à la base de donnée -> table Material
		$entityManager = $this->get("doctrine.orm.entity_manager");
		$repo = $entityManager->getRepository(Material::class);
        //Nouvelle instance de la classe Material
        $newMaterial = new Material();
        //Création d'un formulaire, avec buildForm de MaterialType.php
        $form = $this->createForm(MaterialType::class, $newMaterial);
        //On hydrate le formulaire avec les données du formulaire remplis
        $form->handleRequest($request);
        //On vérifie si une requête a été envoyée avec les bonnes données
        if($form->isSubmitted() && $form->isValid()){
            //On auto-attribue la date à la variable dédiée (Puisque non affichée sur le formulaire)
            $newMaterial->setDateCreated(new \dateTime() );
            //On applique l'ajout à l'entité Material
            $entityManager->persist($newMaterial);
            //On met à jour le model
            $entityManager->flush();
            //Puis on revoi vers la liste
            return $this->redirectToRoute('material_listing');
        }

        return $this->render('@Material/Forms/newMaterial.html.twig', [
            'formNew' => $form->createView()
        ]);
    }

    /**
     * Méthode permettant la modification des informations de l'outil 
     */
    public function updateAction(Request $request, int $id) {
		//Connexion à la base de donnée
        $entityManager = $this->getDoctrine()->getManager();
		//Appel de la table Materials, avec comme parametre un id pour isoler la ligne à opérer
		$modifyMaterial = $entityManager->getRepository(Material::class)->find($id);
        //Condition si l'id ne retourne aucun résultat
		if (!$modifyMaterial) {
			//Un message d'erreur apparaît
			throw $this->createNotFoundException(
				'No material found for id '.$id
			);
        }
		//Création d'un formulaire
		$form = $this->createForm(MaterialType::class, $modifyMaterial);
		//Hydratation du formulaire avec les données récupérés par la requête
        $form->handleRequest($request);
        //Condition pour vérifier si une requête a été passée, et si les données rentrées sont valides
		if ($form->isSubmitted() && $form->isValid()) {
			//Mise à jour du model
			$entityManager->flush();
			//Affichage de la vue listing
			return $this->redirectToRoute('material_listing');
        }
	}

    /**
     * Méthode permettant la suppression d'un outil
     */
    public function deleteAction(Request $request, int $id){
		//Connexion à la base de donnée
        $entityManager = $this->getDoctrine()->getManager();
		//Appel de la table Material, avec comme parametre un id pour isoler la ligne à opérer
		$deleteMaterial = $entityManager->getRepository(Material::class)->find($id);
            //Condition si l'id ne retourne aucun résultat
            if (!$deleteMaterial) {
                //Un message d'erreur apparaît
                throw $this->createNotFoundException(
                    'No material found for id '.$id
                );
            } else {
                //Si l'id est conforme, On supprime l'outil
                $entityManager->remove($deleteMaterial);
                //On met à jour le model
                $entityManager->flush();
                //On retourne la vue vers la liste des outils
                return $this->redirectToRoute('material_listing');
            }
    }

    /**
	 * Affichage des informations du produit au format PDF
	 */
	public function pdfAction(Request $request, int $id){
		
		//Accès à la base de donnée
		$entityManager = $this->getDoctrine()->getManager();

		//On accède à la ligne concordant à l'id donné en paramètre
		$pdf = $entityManager->getRepository(Material::class)->find($id);

		/**
		 * On vérifie que l'id donné en paramètre n'est pas null
		 */
		if ($pdf === null) {
			//Un message d'erreur apparaît
			throw $this->createNotFoundException(
				'No material found for id '.$id
			);
		} 

		//On hydrate les valeurs de l'objet $pdf dans des variables plus concises, prêtes à être utilisées
		$name = $pdf->getName();
		$price = $pdf->getPrice();
		$quantity = $pdf->getQuantity();
		setlocale(LC_TIME, 'fr_FR.UTF8', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8');
		$dateFormatee = $pdf->getDateCreated()->getTimestamp();
		$dateCreated = strftime('%A %d %B %Y', $dateFormatee);
		$resultPrice = $price * $quantity;

		/**
		 * Rappel paramètres 
		 * 		SetFont (fontStyle, font-weight, font-size)
		 * 		Cell (width, height, text, border, end line, [align])
		 * 		new FPDF ( type['P' => 'portrait' / 'L' => 'landscape' ~ 'paysage'], unité[pt, mm, cm, in], taille[A3, A4, A5, letter, legal] => valeur par défaut => A4 )
		 * 		width par défaut A4 = 219mm
		 * 		marge par défaut = 10mm de chaque coté
		 * 		marge d'écriture effective = 189mm
		 */

		$pdf = new \FPDF('P', 'mm', 'A4');
		$pdf->AddPage();

		//Titre du PDF
        $pdf->SetFont('Arial','B',16);
		$pdf->Cell(0,10, 'Bienvenue dans le gestionnaire de votre inventaire !', 0, 1, 'C');
		$pdf->Cell(0,10, utf8_decode('Voici les informations du produit sélectionné: ' . $name), 0, 1, 'C');
		
		//Affichage des informations
		$pdf->SetFont('Arial','',8);
		$textBody = 'Le produit "' . $name . '" a été ajouté le ' . $dateCreated . ' à votre inventaire. Vous en avez ' . $quantity . ' en stock, pour une valeur unitaire de ' . $price . ' euros.';
		$pdf->Cell(0,10, utf8_decode($textBody), 0, 1, 'C');

		//Création d'un saut de ligne
		$pdf->SetFont('Arial','',8);
		$pdf->Cell(0,30,'',0,1);
		
		/**
		 * Formation du tableau :header
		*/
		$pdf->SetFont('Arial','B',16);
		$pdf->Cell(0,20, $name, 1, 1, 'C');

		/**
		 * Formation du tableau :body: Date de création
		 */
		$pdf->SetFont('Arial','',8);
		$pdf->Cell(50,15, utf8_decode('Date de création de l\'outil:'), 1, 0, 'C');
		$pdf->Cell(140,15, utf8_decode($dateCreated), 1, 1, 'C');
		/**
		 * Formation du tableau :body: quantité
		 */
		$pdf->SetFont('Arial','',8);
		$pdf->Cell(50,15, utf8_decode('Quantité:'), 1, 0, 'C');
		$pdf->Cell(140,15, $quantity, 1, 1, 'C');

		/**
		 * Formation du tableau :body: prix
		 */
		$pdf->SetFont('Arial','',8);
		$pdf->Cell(50,15, utf8_decode('Prix:'), 1, 0, 'C');
		$pdf->Cell(140,15, $price . chr(128), 1, 1, 'C');

		/**
		 * Formation du tableau :body: Total
		 */
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(140,15, utf8_decode('Total:'), 1, 0, 'C');
		$pdf->Cell(50,15, $resultPrice . chr(128), 1, 1, 'C');

		//Affichage du fichier pdf dans une page dédiée
        return new Response($pdf->Output(), 200, array(
            'Content-Type' => 'application/pdf'));
	}
}
