<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\User;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        // On accepte que $user soit null si non connecté
        $pseudo = $user instanceof User ? $user->getPseudo() : null;

        $formBuilder = $this->createFormBuilder()
            ->add('subject', TextType::class, [
                'label' => 'Objet',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ]);

        // Si utilisateur connecté, on peut pré-remplir un champ par exemple (optionnel)
        if ($pseudo) {
            $formBuilder->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'data' => $pseudo,
                'disabled' => true,
                'mapped' => false,
            ]);
        }

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement du formulaire

            $this->addFlash('success', 'Votre message a été envoyé avec succès !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
            'pseudo' => $pseudo,
        ]);
    }
}
