<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartLine;
use App\Entity\Product;
use App\Repository\CartLineRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart')]
    public function index(CartRepository $cartRepository, EntityManagerInterface $entityManager, Session $session): Response
    {
        if ($user = $this->getUser()) {
            if (count($user->getCarts())) {
                $cart = $user->getLastCart();
            } else {
                $cart = new Cart();
                $cart->setUser($user);
                $entityManager->persist($cart);
                $entityManager->flush();
            }
        } else {
            if ($session->get('cartId') !== null) {
                $cart = $cartRepository->findOneBy(['id' => $session->get('cartId')]);
            } else {
                $cart = new Cart();
                $entityManager->persist($cart);
                $entityManager->flush();
            }
        }

        $session->set('cartSize', $cart->getCartSize());
        $session->set('cartId', $cart->getId());
        return $this->render('cart/index.html.twig', [
            'cart' => $cart
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request, Product $product, CartRepository $cartRepository, EntityManagerInterface $entityManager): Response
    {
        $cartLines = [];
        if ($this->isCsrfTokenValid('add'.$product->getId(), $request->request->get('_token'))) {
            $session = $request->getSession();
            if ($user = $this->getUser()) {
                if (count($user->getCarts())) {
                    $cart = $user->getLastCart();
                    $cartLines = $cart->getCartLines();
                } else {
                    $cart = new Cart();
                    $cart->setUser($user);
                    $entityManager->persist($cart);
                }
            } else {
                if ($session->get('cartId') !== null) {
                    $cart = $cartRepository->findOneBy(['id' => $session->get('cartId')]);
                    $cartLines = $cart->getCartLines();
                } else {
                    $cart = new Cart();
                    $entityManager->persist($cart);
                }
            }
            $result = $this->checkProductInCart($cartLines, $product, $cart);

            if ($cart->getId() == null) {
                $cart->addCartLine($result['cartLine']);
            }
            $entityManager->persist($result['cartLine']);
            $entityManager->flush();

            if (!$result['inCart']) { // Product not in cart, need to add in $cartLines
                if (count($cartLines) == 0) $cartLines[] = $result['cartLine'];
                else $cartLines->add($result['cartLine']);
            }

            $session->set('cartSize', $cart->getCartSize());
            $session->set('cartId', $cart->getId());
        }

        return $this->redirectToRoute('app_cart', ['cartLines' => $cartLines], Response::HTTP_SEE_OTHER);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/delete/{id}', name: 'app_cart_delete', methods: ['POST'])]
    public function delete(CartLine $cartLine, CartLineRepository $cartLineRepository): Response
    {
        $cartLineRepository->remove($cartLine);

        return $this->redirectToRoute('app_cart', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Check if a product are in the cart and return cartLine and boolean
     *
     * @param $cartLines
     * @param Product $product
     * @param Cart $cart
     * @return array cartLine and boolean
     */
    public function checkProductInCart($cartLines, Product $product, Cart $cart): array
    {
        // Product in cart
        $i = 0;
        while ($i < count($cartLines)) {
            $cartLine = $cartLines[$i];
            if ($cartLine->getProduct() === $product) {
                $cartLine->setQuantity($cartLine->getQuantity() + 1);
                return [
                    'cartLine' => $cartLine,
                    'inCart' => true
                ];
            }
            $i++;
        }
        // Product not in cart
        $cartLine = new CartLine();
        $cartLine->setProduct($product);
        $cartLine->setCart($cart);
        $cartLine->setQuantity(1);
        return [
            'cartLine' => $cartLine,
            'inCart' => false
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/update', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, CartRepository $cartRepository, CartLineRepository $cartLineRepository, EntityManagerInterface $entityManager): Response
    {
        $cartLines = [];
        if ($request->getMethod() == 'POST') {
            $session = $request->getSession();
            if ($user = $this->getUser()) {
                $cart = $user->getLastCart();
            } else {
                $cart = $cartRepository->findOneBy(['id' => $session->get('cartId')]);
            }
            $cartLines = $cart->getCartLines();

            $i = 0;
            while ($i < count($cartLines)) {
                $cartLine = $cartLines[$i];
                $quantity = $request->get('quantity_' . $cartLine->getId());
                if ($quantity != null) {
                    if ($quantity <= 0) {
                        $cart->removeCartLine($cartLine);
                        $cartLineRepository->remove($cartLine);
                        $entityManager->persist($cart);
                    } else {
                        $cartLine->setQuantity($quantity);
                        $entityManager->persist($cartLine);
                    }
                    break;
                }
                $i++;
            }
            $entityManager->flush();

            $session->set('cartSize', $cart->getCartSize());
        }
        return $this->redirectToRoute('app_cart', ['cartLines' => $cartLines], Response::HTTP_SEE_OTHER);
    }
}
