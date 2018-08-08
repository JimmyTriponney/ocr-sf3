<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use OC\PlatformBundle\Event\PlatformEvents;
use OC\PlatformBundle\Event\MessagePostEvent;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Form\AdvertType;
use OC\PlatformBundle\Form\AdvertEditType;
use OC\PlatformBundle\Entity\Image;
use OC\PlatformBundle\Entity\AdvertSkill;
use OC\PlatformBundle\Entity\Application;

//Redirection : return $this->redirectToRoute('oc_platform_home');
//Redirection vers une URL : return $this->redirect($url);
//Appeler un service : $this->get('nom_du_service')

class AdvertController extends Controller
{
    /* EXEMPLE ET TEST

    public function indexAction()
    {
        // On veut avoir l'URL de l'annonce d'id 5.
        $url = $this->get('router')->generate(
            'oc_platform_view', // 1er argument : le nom de la route
            array('id' => 5)    // 2e argument : les valeurs des paramètres
        );
        // $url vaut « /platform/advert/5 »

        //Url absolue
        $absUrl = $this->generateUrl('oc_platform_view', ['id' => 5], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return new Response("L'URL absolue de l'annonce d'id 5 est : ".$absUrl);
    }
    
    public function viewAction($id, Request $request)
    {
        //Utilisé les Sessions
        // Récupération de la session
        $session = $request->getSession();
        // On récupère le contenu de la variable user_id
        $userId = $session->get('user_id');
        // On définit une nouvelle valeur pour cette variable user_id
        $session->set('user_id', 91);


        // On récupère notre paramètre tag
        $tag = $request->query->get('tag');
        
        // On utilise le raccourci : il crée un objet Response
        // Et lui donne comme contenu le contenu du template
        return $this->render(
            'OCPlatformBundle:Advert:view.html.twig',
            array('id'  => $id, 'tag' => $tag)
        );
    }

    public function addAction(Request $request)
    {
        $session = $request->getSession();
        
        // Bien sûr, cette méthode devra réellement ajouter l'annonce
        
        // Mais faisons comme si c'était le cas
        $session->getFlashBag()->add('info', 'Annonce bien enregistrée');

        // Le « flashBag » est ce qui contient les messages flash dans la session
        // Il peut bien sûr contenir plusieurs messages :
        $session->getFlashBag()->add('info', 'Oui oui, elle est bien enregistrée !');

        // Puis on redirige vers la page de visualisation de cette annonce
        return $this->redirectToRoute('oc_platform_view', array('id' => 5));
    }

    FIN DEX EXEMPLES ET TEST */


    /**
    * @ParamConverter("json")
    */
    public function ParamConverterAction($json)
    {
        return new Response(print_r($json, true));
    }



    public function indexAction($page)
    {
        // On ne sait pas combien de pages il y a
        // Mais on sait qu'une page doit être supérieure ou égale à 1
        if ($page < 1) {
            // On déclenche une exception NotFoundHttpException, cela va afficher
            // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
            throw new NotFoundHttpException('Page "'.$page.'" inexistante.');
        }


         // Notre liste d'annonce en dur
        $listAdverts = array(
            array(
            'title'   => 'Recherche développpeur Symfony',
            'id'      => 1,
            'author'  => 'Alexandre',
            'content' => 'Nous recherchons un développeur Symfony débutant sur Lyon. Blabla…',
            'date'    => new \Datetime()),
            array(
            'title'   => 'Mission de webmaster',
            'id'      => 2,
            'author'  => 'Hugo',
            'content' => 'Nous recherchons un webmaster capable de maintenir notre site internet. Blabla…',
            'date'    => new \Datetime()),
            array(
            'title'   => 'Offre de stage webdesigner',
            'id'      => 3,
            'author'  => 'Mathieu',
            'content' => 'Nous proposons un poste pour webdesigner. Blabla…',
            'date'    => new \Datetime())
        );
  

        // Mais pour l'instant, on ne fait qu'appeler le template
        return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
            'listAdverts' => $listAdverts
          ));
    }

    /**
    * @ParamConverter("advert", options={"mapping": {"id": "id"}})
    */
    public function viewAction(Advert $advert)
    {
        //Entity manager
        $em = $this->getDoctrine()->getManager();
        /*
        $advertRepository = $em->getRepository('OCPlatformBundle:Advert');
        //On récupére l'advert
        $advert = $advertRepository->find($id);
        //On vérifi que l'advert exist
        if( null === $advert ){
            throw NotFoundHttpException("L'annonce n'existe pas.");
        }
        */

        // On avait déjà récupéré la liste des candidatures
        $listApplications = $em
            ->getRepository('OCPlatformBundle:Application')
            ->findBy(array('advert' => $advert))
        ;

        // On récupère maintenant la liste des AdvertSkill
        $listAdvertSkills = $em
            ->getRepository('OCPlatformBundle:AdvertSkill')
            ->findBy(array('advert' => $advert))
        ;

        return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
            'advert'           => $advert,
            'listApplications' => $listApplications,
            'listAdvertSkills' => $listAdvertSkills
        ));
    }

    /**
     * Security("has_role('ROLE_AUTEUR')")
     */
    public function addAction(Request $request)
    {
        $advert = new Advert();
        $form   = $this->get('form.factory')->create(AdvertType::class, $advert);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            //Appel de l'événement
            // On crée l'évènement avec ses 2 arguments
            //$event = new MessagePostEvent($advert->getContent(), $advert->getUser());

            // On déclenche l'évènement
            //$this->get('event_dispatcher')->dispatch(PlatformEvents::POST_MESSAGE, $event);

            // On récupère ce qui a été modifié par le ou les listeners, ici le message
            //$advert->setContent($event->getMessage());

            $em = $this->getDoctrine()->getManager();
            $em->persist($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }

        return $this->render('OCPlatformBundle:Advert:add.html.twig', array(
        'form' => $form->createView(),
        ));
    }

    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert) {
        throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        $form = $this->get('form.factory')->create(AdvertEditType::class, $advert);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
        // Inutile de persister ici, Doctrine connait déjà notre annonce
        $em->flush();

        $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

        return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }

        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
        'advert' => $advert,
        'form'   => $form->createView(),
        ));
    }

    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert) {
        throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On crée un formulaire vide, qui ne contiendra que le champ CSRF
        // Cela permet de protéger la suppression d'annonce contre cette faille
        $form = $this->get('form.factory')->create();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
        $em->remove($advert);
        $em->flush();

        $request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");

        return $this->redirectToRoute('oc_platform_home');
        }
        
        return $this->render('OCPlatformBundle:Advert:delete.html.twig', array(
        'advert' => $advert,
        'form'   => $form->createView(),
        ));
    }

    public function menuAction($limit)
    {
        // On fixe en dur une liste ici, bien entendu par la suite
        // on la récupérera depuis la BDD !
        $listAdverts = array(
        array('id' => 2, 'title' => 'Recherche développeur Symfony'),
        array('id' => 5, 'title' => 'Mission de webmaster'),
        array('id' => 9, 'title' => 'Offre de stage webdesigner')
        );

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
        // Tout l'intérêt est ici : le contrôleur passe
        // les variables nécessaires au template !
        'listAdverts' => $listAdverts
        ));
    }


    public function translationAction($name)
    {

        // On récupère le service translator
        $translator = $this->get('translator');

        // Pour traduire dans la locale de l'utilisateur :
        $texteTraduit = $translator->trans('Mon message à inscrire dans les logs');

        return $this->render('OCPlatformBundle:Advert:translation.html.twig', array(
        'name' => $name
        ));
    }

  
}