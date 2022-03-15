<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if ($user = $this->getUser()) {
            if (count($user->getCarts())) {
                $session = new Session();
                $session->set('cartSize', $user->getLastCart()->getCartSize());
            }
        }

        return $this->render('home/index.html.twig');
    }
}
