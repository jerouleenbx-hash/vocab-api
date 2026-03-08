<?php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Infrastructure\Persistence\Doctrine\User;
use App\Infrastructure\Persistence\Doctrine\Word;
use App\Infrastructure\Persistence\Doctrine\WordProgress;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

class InitialDataFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création d'un hasher simple pour fixtures
        $factory = new PasswordHasherFactory([
            User::class => ['algorithm' => 'bcrypt'],
        ]);
        $hasher = new UserPasswordHasher($factory);

        // 1️⃣ Vérifier si l'utilisateur existe déjà
        $userRepo = $manager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => 'test@example.com']);

        if (!$user) {
            $passwordHash = $hasher->hashPassword(new User('test@example.com','temp'), 'password123');

            $user = new User('test@example.com', $passwordHash);
            $manager->persist($user);
        }

        // 2️⃣ Créer quelques mots seulement si pas déjà présents
         $wordsData = [
            [
                'value'=>'meticulous',
                'definition'=>'very attentive to detail',
                'example'=>'He was extremely meticulous when preparing the report.'
            ],
            [
                'value'=>'arduous',
                'definition'=>'very difficult and tiring',
                'example'=>'Climbing the mountain was an arduous task.'
            ],
            [
                'value'=>'eloquent',
                'definition'=>'fluent and persuasive in speaking',
                'example'=>'She gave an eloquent speech at the ceremony.'
            ],
            [
                'value'=>'resilient',
                'definition'=>'able to recover quickly from difficulties',
                'example'=>'Children are often very resilient.'
            ],
            [
                'value'=>'ambiguous',
                'definition'=>'open to more than one interpretation',
                'example'=>'The ending of the movie was ambiguous.'
            ],
        ];

        $wordRepo = $manager->getRepository(Word::class);

        foreach ($wordsData as $w) {
            $existingWord = $wordRepo->findOneBy(['value' => $w['value'], 'user' => $user]);
            if (!$existingWord) {
                $word = new Word($user, $w['value'], $w['definition'], $w['example']);
                $manager->persist($word);

                $progress = new WordProgress($user, $word);
                $manager->persist($progress);
            }
        }

        $manager->flush();
        echo "✅ Base initialisée avec utilisateur, mots et WordProgress.\n";
    }
}