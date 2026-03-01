<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Association;
use App\Entity\Competitor;
use App\Entity\Tournament;
use App\Entity\CategoryGroup;
use App\Entity\Category;
use App\Entity\Attempt;
use App\Entity\AttemptScore;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $niezrzeszony = new Association();
        $niezrzeszony->setName("Niezrzeszony");
        $niezrzeszony->setLocation("Null");
        $manager->persist($niezrzeszony);

        $zawody = new Tournament();
        $zawody->setName("V Turniej Strzelecki dla upamiętnienia I Morskiego Pułku Strzelców \"Za Wolność Kaszub i Polski\"");
        $zawody->setDate(new \DateTime());
        $manager->persist($zawody);

        $pneumatyk = new CategoryGroup();
        $pneumatyk->setDescription("Karabinek Pneumatyczny - 10 metrów");
        $pneumatyk->setScoresPerAttempt(3);
        $manager->persist($pneumatyk);

        $kbks = new CategoryGroup();
        $kbks->setDescription("Karabinek Sportowy KBKS - 50 metrów - 3 strzały");
        $kbks->setScoresPerAttempt(3);
        $manager->persist($kbks);

        $kbkspiatka = new CategoryGroup();
        $kbkspiatka->setDescription("Karabinek Sportowy KBKS - 50 metrów - 5 strzałów");
        $kbkspiatka->setScoresPerAttempt(5);
        $manager->persist($kbkspiatka);

        $luk = new CategoryGroup();
        $luk->setDescription("Łuk Klasyczny - 20 metrów - 3 strzały");
        $luk->setScoresPerAttempt(3);
        $manager->persist($luk);

        $kur = new CategoryGroup();
        $kur->setDescription("Strzelanie do Kura");
        $kur->setScoresPerAttempt(1);
        $manager->persist($kur);

        $tarczaSponsora = new Category();
        $tarczaSponsora->setName("Tarcza Charytatywna");
        $tarczaSponsora->setInitialFee(30);
        $tarczaSponsora->setAdditionalFee(20);
        $tarczaSponsora->setAttemptLimit(0);
        $tarczaSponsora->setTournament($zawody);
        $tarczaSponsora->setCategoryGroup($luk);
        $manager->persist($tarczaSponsora);

        $tarcza = new Category();
        $tarcza->setName("Puchar Wójta Gminy");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(0);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($kbks);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Mistrza Okręgu");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(0);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($kbks);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Strzelanie o Tytuł Mistrza Okręgu Pomorskiego");
        $tarcza->setInitialFee(50);
        $tarcza->setAdditionalFee(999);
        $tarcza->setAttemptLimit(1);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($kbks);
        $manager->persist($tarcza);
        
        $tarcza = new Category();
        $tarcza->setName("Tarcza Skarbnika");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(0);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($kbks);
        $manager->persist($tarcza);
        
        $tarcza = new Category();
        $tarcza->setName("Tarcza Posła na Sejm RP Kazimierza Plocke");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(0);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($pneumatyk);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Posła na Sejm RP Michała Kowalskiego");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(0);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($pneumatyk);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Królowej KBS Wejherowo ( tylko dla pań )");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(0);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($pneumatyk);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Młodzieżowa ( do lat 16 )");
        $tarcza->setInitialFee(10);
        $tarcza->setAdditionalFee(5);
        $tarcza->setAttemptLimit(0);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($pneumatyk);
        $manager->persist($tarcza);

        $admin = new User(); //temporary admin account to test authorization
        $admin->setUsername("admin");
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Kurk_admIn'));

        $manager->persist($admin);
        $manager->flush();

        $manager->flush();
    }
}
