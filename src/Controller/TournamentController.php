<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
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

    dump($groupedCategories);
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
}
