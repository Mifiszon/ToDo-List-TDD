<?php

/**
 * User Controller.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserType;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class UserController.
 */
#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    /**
     * Constructor.
     *
     * @param UserServiceInterface $userService User service
     * @param TranslatorInterface  $translator  Translator
     */
    public function __construct(private readonly UserServiceInterface $userService, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Index action.
     *
     * @return Response HTTP response
     */
    #[Route(name: 'user_index', methods: ['GET'])]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $this->userService->getPaginatedList($page);

        return $this->render('user/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * View action.
     *
     * @return Response HTTP response
     */
    #[Route('/{id}', name: 'user_view', requirements: ['id' => '[1-9]\d*'], methods: ['GET'])]
    public function view(User $user): Response
    {
        return $this->render('user/view.html.twig', ['user' => $user]);
    }

    /**
     * Create action.
     *
     * @return Response HTTP response
     */
    #[Route('/create', name: 'user_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();

            if ($newPassword) {
                $this->userService->changePassword($user, $newPassword);
            }

            $this->userService->save($user);

            $this->addFlash('success', $this->translator->trans('message.created_successfully'));

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param User    $user    User entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/edit', name: 'user_edit', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'PUT'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'method' => 'PUT',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if ($newPassword) {
                $this->userService->changePassword($user, $newPassword);
            }

            $this->userService->save($user);

            $this->addFlash('success', $this->translator->trans('message.edited_successfully'));

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param User    $user    User entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'user_delete', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'DELETE'])]
    public function delete(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('warning', $this->translator->trans('message.user_cannot_delete_self'));

            return $this->redirectToRoute('user_index');
        }

        $form = $this->createForm(FormType::class, $user, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('user_delete', ['id' => $user->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userService->delete($user);
                $this->addFlash('success', $this->translator->trans('message.deleted_successfully'));

                return $this->redirectToRoute('user_index');
            } catch (\LogicException $e) {
                $this->addFlash('danger', $this->translator->trans($e->getMessage()));
            }
        }

        return $this->render('user/delete.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Grant admin role.
     *
     * @param User $user User entity
     *
     * @return Response HTTP eesponse
     */
    #[Route('/{id}/grant-admin', name: 'user_grant_admin', methods: ['POST'])]
    public function grantAdmin(User $user): Response
    {
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles)) {
            $roles[] = 'ROLE_ADMIN';
            $this->userService->setUserRoles($user, array_unique($roles));
            $this->addFlash('success', $this->translator->trans('message.admin_granted'));
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Revoke admin role.
     *
     * @param User $user User entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/revoke-admin', name: 'user_revoke_admin', methods: ['POST'])]
    public function revokeAdmin(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('warning', $this->translator->trans('message.cannot_revoke_self'));

            return $this->redirectToRoute('user_index');
        }

        try {
            $roles = array_diff($user->getRoles(), ['ROLE_ADMIN']);
            $this->userService->setUserRoles($user, $roles);
            $this->addFlash('success', $this->translator->trans('message.admin_revoked'));
        } catch (\LogicException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('user_index');
    }
}
