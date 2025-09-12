<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\CategoryGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    #[Route(name: '/', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/category_edit/{id}', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, CategoryRepository $categoryRepository, CategoryGroupRepository $categoryGroupRepository, EntityManagerInterface $entityManager): Response
    {
        $categories = $categoryRepository->getCategories($id);
        $categoryGroups = $categoryGroupRepository->getCategoryGroups($id);

        return $this->render('category/edit.html.twig', [
            'category' => $categories,
            'categoryGroups' => $categoryGroups,
        ]);
    }
}
