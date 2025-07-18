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
use App\Document\Message; // Document MongoDB
use Doctrine\ODM\MongoDB\DocumentManager;

class ContactController extends AbstractController
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->render('contact/index.html.twig', [
                'contactForm' => null,
                'pseudo' => null,
            ]);
        }

        $pseudo = $user instanceof User ? $user->getPseudo() : null;

        $form = $this->createFormBuilder()
            ->add('subject', TextType::class, [
                'label' => 'Objet',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();


            $message = new Message();
            $message->setSubject($data['subject']);
            $message->setContent($data['message']);
            $message->setAuthorId((string) $user->getId());
            $message->setCreatedAt(new \DateTimeImmutable());

            $this->dm->persist($message);
            $this->dm->flush();

            $this->addFlash('success', 'Votre message a été envoyé avec succès !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
            'pseudo' => $pseudo,
        ]);
    }
}
