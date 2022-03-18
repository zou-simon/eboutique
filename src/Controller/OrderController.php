<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Repository\CartLineRepository;
use App\Repository\CartRepository;
use App\Repository\OrderLineRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Cart $cart, EntityManagerInterface $entityManager, Session $session): Response
    {
        $user = $this->getUser();
        $order = new Order();
        $order->setCart($cart);
        $order->setUser($user);
        $order->setDateTime(new \DateTime());
        $order->setOrderNumber($user->getId() . $cart->getId() . date("YmdHi"));
        $order->setValid(true);
        $entityManager->persist($order);

        $cartLines = $cart->getCartLines();
        foreach ($cartLines as $cartLine) {
            $orderLine = new OrderLine();
            $orderLine->setCommand($order);
            $orderLine->setProduct($cartLine->getProduct());
            $orderLine->setQuantity($cartLine->getQuantity());
            $entityManager->persist($orderLine);
            $order->addOrderLine($orderLine);
        }

        $newCart = new Cart();
        $newCart->setUser($user);
        $user->addCart($newCart);
        $entityManager->persist($newCart);
        $entityManager->persist($user);

        $entityManager->flush();

        $session->set('cartSize', $newCart->getCartSize());
        $session->set('cartId', $newCart->getId());

        return $this->redirectToRoute('app_order_show', [
            'id' => $order->getId()
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        $user = $this->getUser();
        if ($order->getUser() === $user || in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->render('order/show.html.twig', [
                'order' => $order,
            ]);
        }
        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Order $order,
        OrderRepository $orderRepository,
        OrderLineRepository $orderLineRepository,
        CartRepository $cartRepository,
        CartLineRepository $cartLineRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $cart = $order->getCart();
            foreach ($cart->getCartLines() as $cartLine) {
                $cartLineRepository->remove($cartLine);
            }
            foreach ($order->getOrderLines() as $orderLine) {
                $orderLineRepository->remove($orderLine);
            }
            $orderRepository->remove($order);
            $cartRepository->remove($cart);
        }

        $user = $this->getUser();
        if ($order->getUser() !== $user && in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_user', [], Response::HTTP_SEE_OTHER);
    }
}
