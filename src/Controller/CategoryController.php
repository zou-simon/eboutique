<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category, CategoryRepository $categoryRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $categoryRepository->find($category->getId())->getProducts(),
            'categories' => $categoryRepository->findAll(),
            'selectedCategory' => $category->getId(),
        ]);
    }
}
