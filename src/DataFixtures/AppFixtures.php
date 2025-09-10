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

        $kurkoweWejherowo = new Association();
        $kurkoweWejherowo->setName("Bractwo Kurkowe 1");
        $kurkoweWejherowo->setLocation("Wejherowo");
        $manager->persist($kurkoweWejherowo);

        $kurkoweGdynia = new Association();
        $kurkoweGdynia->setName("Bractwo Kurkowe 2");
        $kurkoweGdynia->setLocation("Gdynia");
        $manager->persist($kurkoweGdynia);

        $pierwszy = new Competitor();
        $pierwszy->setFirstName("Strzelec");
        $pierwszy->setLastName("Pierwszy");
        $pierwszy->setAssociation($kurkoweWejherowo);
        $manager->persist($pierwszy);

        $drugi = new Competitor();
        $drugi->setFirstName("Strzelec");
        $drugi->setLastName("Drugi");
        $drugi->setAssociation($kurkoweWejherowo);
        $manager->persist($drugi);
        
        $trzeci = new Competitor();
        $trzeci->setFirstName("Strzelec");
        $trzeci->setLastName("Trzeci");
        $trzeci->setAssociation($niezrzeszony);
        $manager->persist($trzeci);

        $zawody = new Tournament();
        $zawody->setName("V Turniej Strzelecki dla upamiętnienia I Morskiego Pułku Strzelców \"Za Wolność Kaszub i Polski\"");
        $zawody->setDate(new \DateTime());
        $manager->persist($zawody);

        $pneumatyk = new CategoryGroup();
        $pneumatyk->setDescription("Karabinek Pneumatyczny - 10 metrów");
        $pneumatyk->setScoresPerAttempt(3);
        $manager->persist($pneumatyk);

        $kbks = new CategoryGroup();
        $kbks->setDescription("Karabinek sportowy KBKS - 50 metrów");
        $kbks->setScoresPerAttempt(3);
        $manager->persist($kbks);

        $luk = new CategoryGroup();
        $luk->setDescription("Łuk klasyczny - 20 metrów");
        $luk->setScoresPerAttempt(3);
        $manager->persist($luk);

        $tarczaSponsora = new Category();
        $tarczaSponsora->setName("Tarcza Charytatywna");
        $tarczaSponsora->setInitialFee(30);
        $tarczaSponsora->setAdditionalFee(20);
        $tarczaSponsora->setAttemptLimit(5);
        $tarczaSponsora->setTournament($zawody);
        $tarczaSponsora->setCategoryGroup($luk);
        $manager->persist($tarczaSponsora);

        $tarcza = new Category();
        $tarcza->setName("Puchar Wójta Gminy");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(5);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($kbks);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Mistrza Okręgu");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(5);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($kbks);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Strzelanie o Tytuł Mistrza Okręgu Pomorskiego");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(5);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($kbks);
        $manager->persist($tarcza);
        
        $tarczaBracka = new Category();
        $tarczaBracka->setName("Tarcza Skarbnika");
        $tarczaBracka->setInitialFee(30);
        $tarczaBracka->setAdditionalFee(20);
        $tarczaBracka->setAttemptLimit(5);
        $tarczaBracka->setTournament($zawody);
        $tarczaBracka->setCategoryGroup($kbks);
        $manager->persist($tarczaBracka);
        
        $tarczaGosci = new Category();
        $tarczaGosci->setName("Tarcza Posła na Sejm RP 1");
        $tarczaGosci->setInitialFee(30);
        $tarczaGosci->setAdditionalFee(20);
        $tarczaGosci->setAttemptLimit(5);
        $tarczaGosci->setTournament($zawody);
        $tarczaGosci->setCategoryGroup($pneumatyk);
        $manager->persist($tarczaGosci);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Posła na Sejm RP 2");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(5);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($pneumatyk);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Królowej KBS  ( tylko dla pań )");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(5);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($pneumatyk);
        $manager->persist($tarcza);

        $tarcza = new Category();
        $tarcza->setName("Tarcza Młodzieżowa ( do lat 16 )");
        $tarcza->setInitialFee(30);
        $tarcza->setAdditionalFee(20);
        $tarcza->setAttemptLimit(5);
        $tarcza->setTournament($zawody);
        $tarcza->setCategoryGroup($pneumatyk);
        $manager->persist($tarcza);

        $attemptPierwszy = new Attempt();
        $attemptPierwszy->setCategory($tarczaBracka);
        $attemptPierwszy->setCompetitor($pierwszy);
        $manager->persist($attemptPierwszy);
        
        $attemptPierwszy2 = new Attempt();
        $attemptPierwszy2->setCategory($tarczaBracka);
        $attemptPierwszy2->setCompetitor($pierwszy);
        $manager->persist($attemptPierwszy2);

        $attemptDrugi = new Attempt();
        $attemptDrugi->setCategory($tarczaBracka);
        $attemptDrugi->setCompetitor($drugi);
        $manager->persist($attemptDrugi);

        $attemptDrugi2 = new Attempt();
        $attemptDrugi2->setCategory($tarczaSponsora);
        $attemptDrugi2->setCompetitor($drugi);
        $manager->persist($attemptDrugi2);

        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptPierwszy);
        $strzal->setScore(9);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptPierwszy);
        $strzal->setScore(8);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptPierwszy);
        $strzal->setScore(9);
        $manager->persist($strzal);

        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptPierwszy2);
        $strzal->setScore(9);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptPierwszy2);
        $strzal->setScore(9);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptPierwszy2);
        $strzal->setScore(10);
        $manager->persist($strzal);

        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptDrugi);
        $strzal->setScore(8);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptDrugi);
        $strzal->setScore(10);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptDrugi);
        $strzal->setScore(10);
        $manager->persist($strzal);

        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptDrugi2);
        $strzal->setScore(8);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptDrugi2);
        $strzal->setScore(6);
        $manager->persist($strzal);
        
        $strzal = new AttemptScore();
        $strzal->setAttempt($attemptDrugi2);
        $strzal->setScore(5);
        $manager->persist($strzal);

        $admin = new User(); //temporary admin account to test authorization
        $admin->setUsername("admin");
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin'));

        $manager->persist($admin);
        $manager->flush();

        $manager->flush();
    }
}
