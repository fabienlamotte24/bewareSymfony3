<?php

namespace MaterialBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
    public function listingAction(Request $request){
        //Connexion à la base de donnée, avec l'entité Material
        $entityManager = $this->getDoctrine();
        $repo = $entityManager->getRepository(Material::class);

        /**
         * Nouvelles instances de la classe Material
         */
        //Liste des outils en stock
        $material = new Material();
        //Formulaire de modification
        $modify = new Material();
        //Formulaire de suppression
        $delete = new Material();
        //Chargement d'un fichier pdf
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
     * Méthode menant à la page "rupture de stock", qui affiche la liste des outils sans quantité positive de l'utilisateur
     * similaire à listingAction, il n'y a que la méthode du modèl qui change
     */
    public function soldOutAction(){
        //Connexion à la base de donnée, avec l'entité Material
        $entityManager = $this->getDoctrine();
        $repo = $entityManager->getRepository(Material::class);

        /**
         * Nouvelles instance de la classe Material
         */
        //Formulaire d'ajout de quantité
        $supplyTool = new Material();
        //Liste des outils sans stock
        $soldOut = new Material();

        //Création du formulaire pour la quantité
        $formSupply = $this->createFormBuilder($supplyTool)
                             ->add('quantity', IntegerType::class)
                             ->getForm();
        //Recherche des outils sans stock avec la méthode get_sold_out_materials du model Material
        $soldOut = $repo->get_sold_out_materials();

        //On retourne la vue de la liste des outils sans stock 
        return $this->render("@Material/Materials/soldout.html.twig", [
            'soldOut' => $soldOut,
            'formSupply' => $formSupply->createView()
        ]);
    }

    /**
     * Méthode permettant le réapprovisionnement d'un outil en stock
     */
    public function supplyingAction(Request $request, int $id){
		//Connexion à la base de donnée
        $entityManager = $this->getDoctrine()->getManager();
		//Appel de la table Materials, avec comme parametre un id pour isoler la ligne à opérer
        $reSupplyMaterial = $entityManager->getRepository(Material::class)->find($id);
        //Création du formulaire pour la modification de la quantité
        $form = $this->createFormBuilder($reSupplyMaterial)
                        ->add('quantity', IntegerType::class)
                        ->getForm();
        //On hydrate le formulaire avec les informations contenue dans la requête
        $form->handleRequest($request);
        //Puis on vérifie que le formulaire est valide et correctement soumis
        if($form->isSubmitted() && $form->isValid()){
            //On vérifie que la quantité donnée est au dessus de 0 pour la réintégrer dans la liste des outils en stock
            if($reSupplyMaterial->getQuantity() <= 0){
                //Si ce n'est pas le cas, on affiche un message d'erreur
                $this->addFlash('error', 'Veuillez entrer un nombre au-dessus de 0 pour l\'outil "' . $reSupplyMaterial->getName() . '"');
                //Puis on redirige vers la vue des outils sans stock, avec le message d'erreur
            } else {
                //Si c'est le cas, alors on modifie le statut du stock
                $reSupplyMaterial->setSoldOut(false);
                //On met à jour le model
                $entityManager->flush();
                //On prépare un message de validation
                $this->addFlash('success', $reSupplyMaterial->getName() . ' a bien été réapprovisionné');
                //Puis on redirige la vue dans la liste des outils sans stock avecle message de validation
            }
            return $this->redirectToRoute('material_soldout');
        }
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
            if($newMaterial->getPrice() <= 0){
                //On prépare un message d'erreur: Le prix ne peut pas être inférieur à 0
                $this->addFlash("error", 'Le prix de "' . $newMaterial->getName() . '" ne peut pas être inférieur ou égal à 0');
                //Puis on revoi vers la page de création d'outil
                return $this->redirectToRoute('material_new');
            } else {
                //nous vérifions la quantité donnée par l'utilisateur, afin de lui attribuer le status de soldOut
                if($newMaterial->getQuantity() <= 0){
                    $newMaterial->setSoldOut(true);
                } else {
                    $newMaterial->setSoldOut(false);
                }
                //On auto-attribue la date à la variable dédiée (Puisque non affichée sur le formulaire)
                $newMaterial->setDateCreated(new \dateTime() );
                //On applique l'ajout à l'entité Material
                $entityManager->persist($newMaterial);
                //On met à jour le model
                $entityManager->flush();
                //Puis on revoi vers la liste
                return $this->redirectToRoute('material_listing');
            }
        }

        /**
         * On paramètre de base l'affichage vers la vue de création d'outil, avec l'affichage du formulaire
         */
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
		//Création du formulaire de modification
		$form = $this->createForm(MaterialType::class, $modifyMaterial);
		//Hydratation du formulaire avec les données récupérés dans la requête
        $form->handleRequest($request);
        //Condition pour vérifier que le formulaire est valide et soumis
		if ($form->isSubmitted() && $form->isValid()) {
            //On vérifie que le prix est au-dessus de 0
            if($modifyMaterial->getPrice() <= 0){
                //si ce n'est pas le cas, on prépare un message d'erreur
                $this->addFlash("error", 'Le prix de l\'outil "' . $modifyMaterial->getName() . '" ne peut pas être inférieur ou égal à 0');
            } else {
                //Nous vérifions si la quantité est en-dessous de 0
                if($modifyMaterial->getQuantity() <= 0){
                    //Si c'est le cas, on modifie son état en base de donnée: il devient donc en rupture de stock
                    $modifyMaterial->setSoldOut(true);
                    //Mise à jour du model
                    $entityManager->flush();
                    $this->mailingAction($modifyMaterial->getName());
                    //Nous lançons ainsi la fonction d'envoi de mail
                } else {
                    //Mise à jour du model
                    $entityManager->flush();
                    //On paramètre un message de succès
                    $this->addFlash("success", 'Changement de l\'outil ' . $modifyMaterial->getName() . ' effectué avec succès');
                }
            }
            //On redirige vers la vue qui liste les outils en stock
            return $this->redirectToRoute('material_listing');
        } else {
            $this->addFlash('error', 'Le nom que vous avez demandé est déjà attribué - changement impossible');
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
                //On paramètre un message de succès
                $this->addFlash('success', 'Suppression de l\'outil "' . $deleteMaterial->getName() . '" bien effectuée');
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
    
    /**
     * Envoi de mail à l'admin
     */
    private function mailingAction(String $name){

        /**
         *  Paramétrage du transport de swift
         *      smtp.gmail.com => envoi vers le serveur mail de gmail
         *      port 465 - encryption ssl pour l'envoi avec authentification
         */
        $transport = (new \Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl'))
        ->setUsername('testemailing133@gmail.com')
        ->setPassword('Beware1234*');

        //On hydrate le transport dans la variable mailer
        $mailer = new \Swift_Mailer($transport);

        //On configure le message à envoyer
        $message = (new \Swift_Message('Rupture de stock - ' . $name))
        ->setSubject('Rupture de stock')
        ->setFrom(array('testemailing133@gmail.com'))
        ->setTo(array('testemailing133@gmail.com'))
        ->setBody($this->renderView('@Material/Email/mailing.html.twig', ['name' => $name]), 'text/html');
        
        //Si le message s'est bien envoyé
        if($mailer->send($message)){
            //On retourne un message de confirmation
            $this->redirectToRoute('material_listing');
        } else {
            //Sinon on retourne un message d'erreur
            $this->redirectToRoute('material_listing');
        }
    }

    /**
     * Méthode Ajax pour augmenter la quantité de 1
     */
    public function plusAjaxAction(Request $request){
        if($request->isXMLHttpRequest()){
            //On stock la valeur de l'id dans la variable $id
            $id = $request->request->get('id');
            //Connexion base de donnée
            $entityManager = $this->getDoctrine()->getManager();
            //On stock les informations de la ligne correspondant à l'id précisé
            $add = $entityManager->getRepository(Material::class)->find($id);
            //On effecture l'opération => quantité + 1 de l'outil correspondant à l'id
            $quantity = $add->getQuantity() + 1;
            //On change la valeur de la base de données 
            $add->setQuantity($quantity);
            //Si la mise à jour du modèle s'effectue correctement
            $entityManager->flush();
            //On paramètre les informations à renvoyer à la requête Ajax 
            $response = new Response(json_encode(array(
                'quantity' => $quantity
            )));
            $response->headers->set('Content-Type', 'application/json');
            //On retourne la réponse
            return $response;
        } else {
            return new Response('ce n\'est pas une requête Ajax', 400);
        }
    }

    /**
     * Méthode Ajax pour diminuer la quantité de 1
     */
    public function minusAjaxAction(Request $request){
        if($request->isXMLHttpRequest()){
            //On stock la valeur de l'id dans la variable $id
            $id = $request->request->get('id');
            //Connexion base de donnée
            $entityManager = $this->getDoctrine()->getManager();
            //On stock les informations de la ligne correspondant à l'id précisé
            $add = $entityManager->getRepository(Material::class)->find($id);
            //On effecture l'opération => quantité + 1 de l'outil correspondant à l'id
            $quantity = $add->getQuantity() - 1;
            if($quantity <= 0){
                $add->setSoldOut(1);
                $this->mailingAction($add->getName());
            }
            //On change la valeur de la base de données 
            $add->setQuantity($quantity);
            //Si la mise à jour du modèle s'effectue correctement
            $entityManager->flush();
            //On paramètre les informations à renvoyer à la requête Ajax 
            $response = new Response(json_encode(array(
                'quantity' => $quantity,
            )));
            $response->headers->set('Content-Type', 'application/json');
            //On retourne la réponse
            return $response;
        } else {
            return new Response('ce n\'est pas une requête Ajax', 400);
        }
    }
}
