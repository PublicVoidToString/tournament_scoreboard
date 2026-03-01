<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\Competitor;
use App\Form\CompetitorType;
use App\Repository\CompetitorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\AssociationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/competitor')]
final class CompetitorController extends AbstractController
{
    #[Route(name: '/', methods: ['GET'])]
    public function index(CompetitorRepository $competitorRepository): Response
    {
        return $this->render('competitor/index.html.twig', [
            'competitors' => $competitorRepository->findAll(),
        ]);
    }

    #[Route('/admin/new/{id}', name: 'app_competitor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CompetitorRepository $competitorRepository, int $id): Response
    {
        $competitor = new Competitor();
        $form = $this->createForm(CompetitorType::class, $competitor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($competitor);
            $entityManager->flush();

            $this->addFlash('success', 'Pomyślnie dodano nowego zawodnika: ' . $competitor->getFirstName() . ' ' . $competitor->getLastName());
            $this->addFlash('info', 'Numer Zawodnika: ' . $competitor->getId());

            return $this->redirectToRoute('scoreboard', ['id' => $id], Response::HTTP_SEE_OTHER);
        }
        
        $competitors = $competitorRepository->findAll();
        return $this->render('competitor/new.html.twig', [
            'competitors' => $competitors,
            'competitor' => $competitor,
            'form' => $form,
            'tournamentid' => $id
        ]);
    }

    #[Route('/{id}', name: 'app_competitor_show', methods: ['GET'])]
    public function show(Competitor $competitor): Response
    {
        return $this->render('competitor/show.html.twig', [
            'competitor' => $competitor,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_competitor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Competitor $competitor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompetitorType::class, $competitor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_competitor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('competitor/edit.html.twig', [
            'competitor' => $competitor,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_competitor_delete', methods: ['POST'])]
    public function delete(Request $request, Competitor $competitor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$competitor->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($competitor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_competitor_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/update', name: 'competitor_update', methods: ['POST'])]
    public function update(Request $request, CompetitorRepository $competitorRepository, AssociationRepository $associationRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(!isset($data['id'])){
            return new JsonResponse(['success' => false, 'error' => 'Brak ID zawodnika']);
        }

        $competitor = $competitorRepository->find($data['id']);
        if(!$competitor){
            return new JsonResponse(['success' => false, 'error' => 'Zawodnik nie znaleziony']);
        }

        $competitor->setFirstName($data['first_name']);
        $competitor->setLastName($data['last_name']);
        $competitor->setAssociation($associationRepository->find($data['association_id']));

        $competitorRepository->getEntityManager()->flush();

        return new JsonResponse(['success' => true]);
    }


    #[Route('/admin/add', name: 'competitor_add', methods: ['POST'])]
    public function insert(Request $request, CompetitorRepository $competitorRepository, AssociationRepository $associationRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(empty($data['first_name']) || empty($data['last_name']) || empty($data['association_id'])){
            return new JsonResponse(['success' => false, 'error' => 'Brak wymaganych danych']);
        }

        $association = $associationRepository->find($data['association_id']);
        if(!$association){
            return new JsonResponse(['success' => false, 'error' => 'Nieprawidłowe ID klubu']);
        }

        $competitor = new Competitor();
        $competitor->setFirstName($data['first_name']);
        $competitor->setLastName($data['last_name']);
        $competitor->setAssociation($association);

        $em = $competitorRepository->getEntityManager();
        $em->persist($competitor);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'competitor' => [
                'id' => $competitor->getId(),
                'first_name' => $competitor->getFirstName(),
                'last_name' => $competitor->getLastName(),
                'association_id' => $association->getId(),
                'association_name' => $association->getName()
            ]
        ]);
    }










}
