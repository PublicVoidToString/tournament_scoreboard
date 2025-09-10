<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\Attempt;
use App\Entity\AttemptScore;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use App\Repository\CompetitorRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    $tournament = $tournamentRepository->find($id);

    $categories = $tournamentRepository->getCategories($id);
    $results = $tournamentRepository->getCompetitorswithScores($id, $categories);

    $categoryLimits = [];
    foreach ($categories as $cat) {
        $categoryLimits[$cat['id']] = $cat['attempt_limit'];
    }

    $groupedResults = [];
    foreach ($results as $r) {
        $name = $r['competitor_name'];
        $catId = $r['category_id'];
        $score = (float)$r['score'];

        $groupedResults[$name][$catId][] = $score;
    }

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

    $groupedCategories = [];
    foreach ($categories as $cat) {
        $group = explode(' ', $cat['group_id'])[0];
        $groupedCategories[$group][] = $cat;
    }

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


#[Route('/tournament/admin/markScore/{id}', name: 'app_tournament_mark_score')]
public function markScore( Request $request, int $id, TournamentRepository $tournamentRepository, CompetitorRepository $competitorRepository): Response
{
        $tournament = $tournamentRepository->find($id);
        $attempts   = $tournamentRepository->getEmptyAttempts($id);
        $competitors = $competitorRepository->findAll();
    return $this->render('tournament/markscore.html.twig',[
        'id' => $id,
        'competitors' => $competitors,
        'attempts' => $attempts,
    ]);
}

#[Route('/admin/submit-score', name: 'submit_score', methods: ['POST'])]
public function submitScore(Request $request, EntityManagerInterface $em): JsonResponse

{
    $data = json_decode($request->getContent(), true);

    $attemptId = $data['attemptId'];
    $score1 = $data['score1'];
    $score2 = $data['score2'];
    $score3 = $data['score3'];

    $attempt = $em->getRepository(Attempt::class)->find($attemptId);
    if (!$attempt) {
        return new JsonResponse(['success' => false, 'message' => 'Attempt not found']);
    }
 $scores = [$score1, $score2, $score3];
    foreach ($scores as $scoreValue) {
        if ($scoreValue !== null && $scoreValue !== '') {
            $attemptScore = new AttemptScore();
            $attemptScore->setAttempt($attempt);
            $attemptScore->setScore((int)$scoreValue);
            $em->persist($attemptScore);
        }
    }

    $em->flush();

    return new JsonResponse(['success' => true]);
}

}
