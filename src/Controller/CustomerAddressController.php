<?php

namespace App\Controller;

use App\Entity\CustomerAddress;
use App\Form\CustomerAddressType;
use App\Repository\CustomerAddressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/address')]
class CustomerAddressController extends AbstractController
{
    #[Route('/new', name: 'app_address_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CustomerAddressRepository $customerAddressRepository): Response
    {
        $user = $this->getUser();
        $customerAddress = new CustomerAddress();
        $form = $this->createForm(CustomerAddressType::class, $customerAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerAddress->setUser($user);
            $customerAddressRepository->add($customerAddress);
            return $this->render('user/index.html.twig', [
                'user' => $user,
                'message' => 'Successfully added an address!',
            ]);
        }

        return $this->renderForm('customer_address/new.html.twig', [
            'customer_address' => $customerAddress,
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_address_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CustomerAddress $customerAddress, CustomerAddressRepository $customerAddressRepository): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(CustomerAddressType::class, $customerAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerAddressRepository->add($customerAddress);
            return $this->render('user/index.html.twig', [
                'user' => $user,
                'message' => 'Successful address update!',
            ]);
        }

        return $this->renderForm('customer_address/edit.html.twig', [
            'customer_address' => $customerAddress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_address_delete', methods: ['POST'])]
    public function delete(Request $request, CustomerAddress $customerAddress, CustomerAddressRepository $customerAddressRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customerAddress->getId(), $request->request->get('_token'))) {
            $customerAddressRepository->remove($customerAddress);
        }

        return $this->redirectToRoute('app_user', [], Response::HTTP_SEE_OTHER);
    }
}
