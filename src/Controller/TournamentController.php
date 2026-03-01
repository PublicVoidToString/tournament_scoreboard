<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\Attempt;
use App\Entity\AttemptScore;
use App\Entity\Competitor;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use App\Repository\CompetitorRepository;
use App\Repository\CategoryRepository;
use App\Repository\AttemptScoreRepository;
use App\Repository\AttemptRepository;
use App\Repository\AssociationRepository;
use App\Form\AttemptScoreType;
use COM;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Length;

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

        $categories = $tournamentRepository->getCategoriesShortened($id);
        $results = $tournamentRepository->getCompetitorsWithBestScores($id, $categories);

        $categoriesById = [];
        foreach ($categories as &$cat) {
            $cat['results'] = [];
            $categoriesById[$cat['id']] = $cat;
        }
        unset($cat);

        foreach ($results as $res){
            $categoryId = $res['category_id'];
            if (isset($categoriesById[$categoryId])) {
                if(!isset($categoriesById[$categoryId]['results'][$res['competitor_id']])){
                    $categoriesById[$categoryId]['results'][$res['competitor_id']] = array("competitor_id" => $res['competitor_id'], "competitor_name" => $res['competitor_name'], "scores" => []);
                }
                $categoriesById[$categoryId]['results'][$res['competitor_id']]['scores'][] = $res['score'];
            }
        }


        foreach ($categoriesById as $cat) {
            $group = (int) explode(' ', $cat['group_id'])[0];
            $groupedCategories[$group][] = $cat;
        }
        // At this point groupedCategories stores all necessary info, next step adds empty scores

        foreach($groupedCategories as &$catgroup){
            $competitors = array();
            foreach($catgroup as $cat){

            $limit = $cat['attempt_limit'];
            if ($limit == 0 || $limit > 5) $limit = 5;
            $scores = array_fill(0, $limit, null);
            $scores[] = 0;

                foreach($cat['results'] as $comp){
                    if (!isset($competitors[$comp['competitor_id']])){
                        $competitors[$comp['competitor_id']]= array("competitor_id" => $comp['competitor_id'], "competitor_name" => $comp['competitor_name'], "scores" => $scores);
                    }
                }
            }
            foreach($catgroup as &$cat){
                foreach($competitors as $competitor){
                    if (!isset($cat['results'][$competitor['competitor_id']])){
                        $cat['results'][$competitor['competitor_id']]=$competitor;
                    }
                }
                asort($cat['results']);
            }
            unset($cat);
        }
        unset($catgroup);
        // At this point groupedCategories stores all necessary info with empty scores, next steps organizes values for display
        foreach($groupedCategories as &$catgroup){
            foreach($catgroup as &$cat){

                $limit = $cat['attempt_limit'];
                if ($limit == 0 || $limit > 5) $limit = 5;
                
                foreach($cat['results'] as &$comp){
                    $scores = [];
                    $i = 0;
                    $biggest = 0;
                        foreach($comp['scores'] as $score){
                            if($score>$biggest) $biggest=$score;
                            if($i<$limit){
                                $scores[] = $score;
                                $i++;
                            }else{
                                if($score>min($scores)){
                                    $scores[array_search(min($scores), $scores)] = $score;
                                }
                            }
                        }
                        while($i<$limit){
                            $i+=1;
                            $scores[] = " ";
                        }
                        $scores[] = $biggest;
                        $comp['scores'] = $scores;
                }
                unset($comp);
            }
            unset($cat);
        }
        unset($catgroup);

        return $this->render('tournament/scoreboard.html.twig', [
            'tournament' => $tournament,
            'scoreboard' => $groupedCategories
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

    #[Route('/tournament/admin/adminpanel/{id}', name: 'app_tournament_admin_panel', methods: ['GET'])]
    public function adminPanel(
        CompetitorRepository $competitorRepository,
        AssociationRepository $associationRepository,
        CategoryRepository $categoryRepository,
        int $id,
    ): Response {
        $competitors = $competitorRepository->getAll();
        $associations = $associationRepository->getAssociations();
        $competitorsAttempts = $competitorRepository->getAttempsFromTournament($id);
        $categories = $categoryRepository->getCategories($id);
        $attemptsOrganized = [];
        foreach ($competitorsAttempts as $row) {
            $competitorId = $row['competitor_id'];

            if (!isset($attemptsOrganized[$competitorId])) {
                $attemptsOrganized[$competitorId] = [
                    'competitor_id' => $competitorId,
                    'categories' => [],
                ];
            }
            $attemptsOrganized[$competitorId]['categories'][ $row['category_id']] = [
                'category_id' => $row['category_id'],
                'attempts' => $row['attempts']
            ];
        }
        
        return $this->render('tournament/adminpanel.html.twig', [
            'tournament_id' => $id,
            'competitor_list' => $competitors,
            'association_list' => $associations,
            'competitors_attempts' => $attemptsOrganized,
            'categories' => $categories,
        ]);
    }


#[Route('/tournament/admin/markScore/{id}', name: 'app_tournament_mark_score')]
public function markScore( Request $request, int $id, TournamentRepository $tournamentRepository, CompetitorRepository $competitorRepository): Response
{
        $attempts   = $tournamentRepository->getEmptyAttempts($id);
        $competitors = $competitorRepository->findAll();
        
        //dump($attempts);
        //dd($competitors);
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
    $scores = $data['scores'] ?? [];

    $attempt = $em->getRepository(Attempt::class)->find($attemptId);
    if (!$attempt) {
        return new JsonResponse(['success' => false, 'message' => 'Attempt not found']);
    }

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



#[Route('/tournament/admin/exchange-attempt/{id}', name: 'app_tournament_exchange_attempt')]
public function exchangeAttempt( Request $request, int $id, TournamentRepository $tournamentRepository, CompetitorRepository $competitorRepository): Response
{
        $exchangeableAttempts = $tournamentRepository->getCompetitorAttemptsWithoutScore($id);
        $categoriesAttemps = $tournamentRepository->getCompetitorsAttemptCount($id);
        $competitorsGroupedAttempts = [];

        foreach ($categoriesAttemps as $row) {
            $competitorId = $row['id'];

            if (!isset($competitorsGroupedAttempts[$competitorId])) {
                $competitorsGroupedAttempts[$competitorId] = [
                    'id' => $row['id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'categories' => [],
                ];
            }

            $competitorsGroupedAttempts[$competitorId]['categories'][] = [
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'category_all_attempts' => $row['category_all_attempts'],
            ];
        }

            //dump($exchangeableAttempts);
            //dd($competitorsGroupedAttempts);
    return $this->render('tournament/exchange.html.twig',[
        'id' => $id,
        'competitorsGroupedAttempts' => $competitorsGroupedAttempts,
        'exchangeableAttempts' => $exchangeableAttempts,
    ]);
}

}
