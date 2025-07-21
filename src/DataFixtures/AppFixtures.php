<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création de l'admin
        $admin = new User();
        $admin->setEmail('admin@esportify.com');
        $admin->setPseudo('Admin');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_ORGANISATEUR', 'ROLE_USER']); 
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // Création de l'organisateur
        $organisateur = new User();
        $organisateur->setEmail('orga@esportify.com');
        $organisateur->setPseudo('Orga');
        $organisateur->setRoles(['ROLE_ORGANISATEUR', 'ROLE_USER']); 
        $organisateur->setPassword($this->passwordHasher->hashPassword($organisateur, 'orgapass'));
        $organisateur->setIsVerified(true);
        $manager->persist($organisateur);

        // Création d'un user simple
        $user = new User();
        $user->setEmail('user@esportify.com');
        $user->setPseudo('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'userpass'));
        $user->setIsVerified(true);
        $manager->persist($user);

        // Création de quelques événements déjà approuvés
        for ($i = 1; $i <= 3; $i++) {
            $event = new Event();
            $event->setTitle('Événement de test ' . $i);
            $event->setDescription('Description de l\'événement de test numéro ' . $i);
            $event->setStartDateTime(new \DateTime('+'.$i.' days'));
            $event->setEndDateTime(new \DateTime('+'.$i.' days +2 hours'));
            $event->setMaxPlayers(20 + $i);
            $event->setIsApproved(true);
            $event->setIsAdminApproved(true);
            $event->setIsStarted(false);
            $event->setIsFinished(false);
            $event->setPoints(null);
            $event->setPointsAwarded(false);
            $event->setOrganizer($organisateur);
            $event->setGame('Jeu ' . $i);
            $event->setImage(null);
            $event->setIsAdminApproved(true); 
            $manager->persist($event);
        }

        $manager->flush();
    }
}
