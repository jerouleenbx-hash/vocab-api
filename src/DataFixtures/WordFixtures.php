<?php
namespace App\DataFixtures;

use App\Infrastructure\Persistence\Doctrine\User;
use App\Infrastructure\Persistence\Doctrine\Word;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

class WordFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer l'utilisateur depuis les références
        //$user = $this->getReference('user_admin');


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

        $words = [
            ['value' => 'apple', 'definition' => 'A fruit that is red, green, or yellow', 'example' => 'I eat an apple every morning.', 'difficulty' => 1],
            ['value' => 'book', 'definition' => 'A set of written pages bound together', 'example' => 'She borrowed a book from the library.', 'difficulty' => 1],
            ['value' => 'computer', 'definition' => 'An electronic device for storing and processing data', 'example' => 'I bought a new computer last week.', 'difficulty' => 2],
            ['value' => 'dream', 'definition' => 'A series of thoughts and images occurring in sleep', 'example' => 'He had a strange dream last night.', 'difficulty' => 2],
            ['value' => 'elephant', 'definition' => 'A very large gray mammal with a trunk', 'example' => 'The elephant walked slowly through the forest.', 'difficulty' => 2],
            ['value' => 'flower', 'definition' => 'The seed-bearing part of a plant', 'example' => 'The garden is full of colorful flowers.', 'difficulty' => 1],
        ];

        foreach ($words as $w) {
            $existingWord = $manager->getRepository(Word::class)
                ->findOneBy(['value' => $w['value'], 'user' => $user]);

            if (!$existingWord) {
                $word = new Word(
                    $user,
                    $w['value'],
                    $w['definition'],
                    $w['example'],
                    $w['difficulty']
                );
                $manager->persist($word);
                echo "✅ Mot '{$w['value']}' ajouté\n";
            } else {
                echo "ℹ️ Mot '{$w['value']}' déjà existant\n";
            }
        }

        $manager->flush();
        echo "🎉 Mots WordFixtures chargés.\n";
    }
}