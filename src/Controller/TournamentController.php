<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\Attempt;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use App\Repository\CompetitorRepository;
use App\Repository\CategoryRepository;
use App\Repository\AttemptScoreRepository;
use App\Repository\AttemptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
final class TournamentController extends AbstractController
{
    #[Route( name: 'app_tournament_index', methods: ['GET'])]
    public function index(TournamentRepository $tournamentRepository): Response
    {
        return $this->render('tournament/index.html.twig', [
            'tournaments' => $tournamentRepository->findAll(),
        ]);
    }

    
    #[Route('/scoreboard/{id}', name: 'scoreboard', methods: ['GET'])]
public function showScoreboard(TournamentRepository $tournamentRepository, int $id): Response
{
    // Pobranie turnieju
    $tournament = $tournamentRepository->find($id);

    // Pobranie kategorii i wyników
    $categories = $tournamentRepository->getCategories($id);
    $results = $tournamentRepository->getCompetitorswithScores($id, $categories);

    // Tworzenie mapy limitów prób dla każdej kategorii
    $categoryLimits = [];
    foreach ($categories as $cat) {
        $categoryLimits[$cat['id']] = $cat['attempt_limit'];
    }

    // Grupowanie wyników po zawodniku i kategorii
    $groupedResults = [];
    foreach ($results as $r) {
        $name = $r['competitor_name'];
        $catId = $r['category_id'];
        $score = (float)$r['score'];

        $groupedResults[$name][$catId][] = $score;
    }

    // Uzupełnianie brakujących prób zerami i dodanie ostatniego wiersza z max
    foreach ($groupedResults as $name => &$categoriesScores) {
        foreach ($categoryLimits as $catId => $maxAttempts) {
            if (!isset($categoriesScores[$catId])) {
                $categoriesScores[$catId] = [];
            }
            $categoriesScores[$catId] = array_pad($categoriesScores[$catId], $maxAttempts, 0);
            $categoriesScores[$catId][] = max($categoriesScores[$catId]);
        }
        ksort($categoriesScores);
    }
    unset($categoriesScores);

    // Grupowanie kategorii po typie broni (np. KBKS, Pneumatyka, Łuk)
    $groupedCategories = [];
    //dd($categories);
    foreach ($categories as $cat) {
        // Możesz tu zmienić logikę grupowania, jeśli masz pole "group" w bazie
        $group = explode(' ', $cat['group_id'])[0];
        $groupedCategories[$group][] = $cat;
    }

    //dump($groupedCategories);
    // Renderowanie widoku z podziałem na grupy
    return $this->render('tournament/scoreboard.html.twig', [
        'tournament' => $tournament,
        'results' => $groupedResults,
        'groupedCategories' => $groupedCategories
    ]);
}

    #[Route('/tournament/admin/new', name: 'app_tournament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tournament = new Tournament();
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tournament);
            $entityManager->flush();

            return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournament/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/tournament/{id}', name: 'app_tournament_show', methods: ['GET'])]
    public function show(Tournament $tournament): Response
    {
        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    #[Route('/tournament/admin/{id}/edit', name: 'app_tournament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournament/edit.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/tournament/admin/{id}', name: 'app_tournament_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tournament->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tournament);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
    }

#[Route('/tournament/admin/addScore/{id}', name: 'app_tournament_add_score', methods: ['GET','POST'])]
public function addScore(
    Request $request,
    int $id,
    TournamentRepository $tournamentRepository,
    CompetitorRepository $competitorRepository,
    CategoryRepository $categoryRepository,
    EntityManagerInterface $em
): Response {
    $tournament = $tournamentRepository->find($id);

    if (!$tournament) {
        throw $this->createNotFoundException('Nie znaleziono turnieju');
    }

    // POST – zapis nowych prób
    if ($request->isMethod('POST')) {
        $competitorId = $request->request->get('competitor');
        $quantities   = $request->request->all('quantities');

        if ($competitorId && $quantities) {
            $competitor = $competitorRepository->find($competitorId);

            foreach ($quantities as $categoryId => $qty) {
                $qty = (int)$qty;
                if ($qty > 0) {
                    $category = $categoryRepository->find($categoryId);
                    if (!$category) {
                        continue;
                    }

                    for ($i = 0; $i < $qty; $i++) {
                        $attempt = new Attempt();
                        $attempt->setCompetitor($competitor);
                        $attempt->setCategory($category);

                        $em->persist($attempt);
                    }
                }
            }
            $em->flush();
        }

        return $this->redirectToRoute('scoreboard', ['id' => $id]);
    }

    // GET – render formularza
    $categories = $tournamentRepository->getCategories($id);
    $attempts   = $tournamentRepository->getAttempts($id);
    $competitors = $competitorRepository->findAll();

    $competitorAttempts = [];
    $attemptMap = [];

    foreach ($attempts as $attempt) {
        $competitorId = $attempt['competitor_id'];
        $categoryId   = $attempt['category_id'];

        if (!isset($attemptMap[$competitorId])) {
            $attemptMap[$competitorId] = [];
        }
        if (!isset($attemptMap[$competitorId][$categoryId])) {
            $attemptMap[$competitorId][$categoryId] = 0;
        }
        $attemptMap[$competitorId][$categoryId]++;
    }

    foreach ($competitors as $competitor) {
        $competitorId = $competitor['id'];

        $competitorAttempts[$competitorId] = [
            'id' => $competitorId,
            'first_name' => $competitor['first_name'],
            'last_name' => $competitor['last_name'],
            'association_name' => $competitor['association_name'],
            'categories' => []
        ];

        foreach ($categories as $category) {
            $categoryId = $category['id'];

            $competitorAttempts[$competitorId]['categories'][] = [
                'id' => $categoryId,
                'name' => $category['name'],
                'initial_fee' => $category['initial_fee'],
                'additional_fee' => $category['additional_fee'],
                'count' => $attemptMap[$competitorId][$categoryId] ?? 0
            ];
        }
    }

    return $this->render('tournament/addscore.html.twig', [
        'id' => $id,
        'categories' => $categories,
        'competitors' => $competitors,
        'competitorAttempts' => $competitorAttempts,
    ]);
}


#[Route('/tournament/admin/markScore/{id}', name: 'app_tournament_mark_score', methods: ['GET','POST'])]
public function markScore(
    Request $request,
    int $id,
    TournamentRepository $tournamentRepository,
    AttemptRepository $attemptRepository,
    EntityManagerInterface $em
): Response
{
    $attempts   = $tournamentRepository->getEmptyAttempts($id); // zwraca listę Attempt z polem 'id'
    $categories = $tournamentRepository->getCategories($id);
    $tournament = $tournamentRepository->find($id);

    if ($request->isMethod('POST')) {
        $attemptId = $request->request->get('attempt_id');
        $scores = $request->request->all('scores');

        if (!$attemptId || empty($scores)) {
            $this->addFlash('error', 'Wszystkie pola muszą być wypełnione.');
            return $this->redirectToRoute('app_tournament_mark_score', ['id' => $id]);
        }

        // walidacja wyników: liczby całkowite 0–100
        foreach ($scores as $scoreValue) {
            if (!is_numeric($scoreValue) || (int)$scoreValue < 0 || (int)$scoreValue > 100) {
                $this->addFlash('error', 'Wyniki muszą być liczbami całkowitymi od 0 do 100.');
                return $this->redirectToRoute('app_tournament_mark_score', ['id' => $id]);
            }
        }

        $attempt = $attemptRepository->find($attemptId);
        if (!$attempt) {
            $this->addFlash('error', 'Nie znaleziono wybranej próby.');
            return $this->redirectToRoute('app_tournament_mark_score', ['id' => $id]);
        }

        foreach ($scores as $scoreValue) {
            $attemptScore = new \App\Entity\AttemptScore();
            $attemptScore->setScore((int)$scoreValue);
            $attemptScore->setAttempt($attempt);
            $em->persist($attemptScore);
        }

        $em->flush();
        $this->addFlash('success', 'Wyniki zapisane pomyślnie.');
        return $this->redirectToRoute('app_tournament_mark_score', ['id' => $id]);
    }

    return $this->render('tournament/markscore.html.twig', [
        'tournament' => $tournament,
        'attempts' => $attempts,
        'categories' => $categories
    ]);
}


}
