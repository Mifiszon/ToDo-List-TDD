<?php
/**
 * Todo controller.
 */

namespace App\Controller;

use App\Entity\Todo;
use App\Form\Type\TodoType;
use App\Service\TodoServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class TodoController.
 */
#[Route('/todo')]
class TodoController extends AbstractController
{
    /**
     * Constructor.
     *
     * @param TodoServiceInterface $todoService Todo service
     */
    public function __construct(private readonly TodoServiceInterface $todoService)
    {
    }

    /**
     * Index action.
     *
     * @return Response HTTP response
     */
    #[Route(name: 'todo_index', methods: ['GET'])]
    public function index(): Response
    {
        $todos = $this->isGranted('ROLE_ADMIN')
            ? $this->todoService->findAll()
            : $this->todoService->getListByUser($this->getUser());

        return $this->render('todo/index.html.twig', ['todos' => $todos]);
    }

    /**
     * View action.
     *
     * @param Todo $todo Todo entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}', name: 'todo_view', requirements: ['id' => '[1-9]\d*'], methods: ['GET'])]
    #[IsGranted('TODO_VIEW', subject: 'todo')]
    public function view(Todo $todo): Response
    {
        return $this->render('todo/view.html.twig', ['todo' => $todo]);
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route('/create', name: 'todo_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $todo = new Todo();
        $todo->setAuthor($this->getUser());

        $form = $this->createForm(TodoType::class, $todo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->todoService->save($todo);
            $this->addFlash('success', 'message.created_successfully');

            return $this->redirectToRoute('todo_index');
        }

        return $this->render('todo/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param Todo    $todo    Todo entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/edit', name: 'todo_edit', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'POST'])]
    #[IsGranted('TODO_EDIT', subject: 'todo')]
    public function edit(Request $request, Todo $todo): Response
    {
        $form = $this->createForm(TodoType::class, $todo, ['method' => 'POST']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->todoService->save($todo);
            $this->addFlash('success', 'message.edited_successfully');

            return $this->redirectToRoute('todo_index');
        }

        return $this->render('todo/edit.html.twig', [
            'form' => $form->createView(),
            'todo' => $todo,
        ]);
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param Todo    $todo    Todo entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'todo_delete', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'DELETE'])]
    #[IsGranted('TODO_DELETE', subject: 'todo')]
    public function delete(Request $request, Todo $todo): Response
    {
        $form = $this->createForm(TodoType::class, $todo, [
            'method' => 'DELETE',
            'action' => $this->generateUrl('todo_delete', ['id' => $todo->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->todoService->delete($todo);
            $this->addFlash('success', 'message.deleted_successfully');

            return $this->redirectToRoute('todo_index');
        }

        return $this->render('todo/delete.html.twig', [
            'form' => $form->createView(),
            'todo' => $todo,
        ]);
    }
}
