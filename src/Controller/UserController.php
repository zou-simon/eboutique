<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\CartLineRepository;
use App\Repository\CartRepository;
use App\Repository\CustomerAddressRepository;
use App\Repository\OrderLineRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/account')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->render('user/index.html.twig', [
            'user' => $user,
            'customer_addresses' => $user->getCustomerAddresses(),
        ]);
    }

    #[Route('/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }
            $userRepository->add($user);
            return $this->render('user/index.html.twig', [
                'user' => $user,
                'customer_addresses' => $user->getCustomerAddresses(),
                'message' => 'Successful update!',
            ]);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        UserRepository $userRepository,
        CartLineRepository $cartLineRepository,
        CartRepository $cartRepository,
        CustomerAddressRepository $customerAddressRepository,
        OrderRepository $orderRepository,
        OrderLineRepository $orderLineRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Delete carts
            $carts = $user->getCarts();
            foreach ($carts as $cart) {
                $cartLines = $cart->getCartLines();
                foreach ($cartLines as $cartLine) {
                    $cartLineRepository->remove($cartLine);
                }
                $cartRepository->remove($cart);
            }
            // Delete addresses
            $customerAddresses = $user->getCustomerAddresses();
            foreach ($customerAddresses as $customerAddress) {
                $customerAddressRepository->remove($customerAddress);
            }
            // Delete orders
            $orders = $user->getOrders();
            foreach ($orders as $order) {
                $orderLines = $order->getOrderLines();
                foreach ($orderLines as $orderLine) {
                    $orderLineRepository->remove($orderLine);
                }
                $orderRepository->remove($order);
            }
            // Delete user
            $userRepository->remove($user);

            $currentUserId = $this->getUser()->getId();
            if ($currentUserId == $user->getId()) {
                $session = new Session();
                $session->invalidate();
            }
            return $this->redirectToRoute('app_logout', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
}
