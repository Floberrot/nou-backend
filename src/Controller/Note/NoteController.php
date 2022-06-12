<?php

namespace App\Controller\Note;

use App\Repository\GroupRepository;
use App\Repository\NoteRepository;
use App\Repository\UserRepository;
use App\Services\FileSystem\FileSystem;
use App\Services\Note\NoteManagement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class NoteController extends AbstractController
{
    private NoteRepository $noteRepository;
    private UserRepository $userRepository;
    private GroupRepository $groupRepository;

    public function __construct(NoteRepository $noteRepository, UserRepository $userRepository, GroupRepository $groupRepository)
    {
        $this->noteRepository = $noteRepository;
        $this->userRepository = $userRepository;
        $this->groupRepository = $groupRepository;
    }

    /**
     * Create a note
     * @Route("/note", name="create note", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="users are recovered"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Group not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="form-data",
     *           @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="format",
     *                     description="Format of the note (file or text)",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="group",
     *                     description="name of the group",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="author",
     *                     description="author of the note",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="content",
     *                     description="Text of the note (if file, it can be null because we set the name of the file)",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     description="If the format is file, you have to send it",
     *                     type="file"
     *                 )
     *              )
     *   ),
     * )
     * @OA\Tag(name="Note")
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $note_management = new NoteManagement($this->noteRepository, $this->userRepository, $this->groupRepository);
            $note = null;
            if ($request->get('format') === 'file') {
                $note = $note_management->create($request->get('group'), $request->get('author'), $request->get('format'), $request->files->get('file')->getClientOriginalName());
                FileSystem::upload(file_get_contents($request->files->get('file')), $request->get('group'), $request->get('group_id'), $request->files->get('file')->getClientOriginalName());
            } else if ($request->get('format') === 'text') {
                $note = $note_management->create($request->get('group'), $request->get('author'), $request->get('format'), $request->get('content'));
            }
            return new JsonResponse(
                [
                    'note_id' => $note->getId(),
                    'content' => $note->getContent(),
                    'author' => $note->getAuthor()->getUsername(),
                    'format' => $note->getFormat(),
                    'is_done' => $note->getIsDone(),
                    'message' => 'Note created'
                ], 200
            );
        } catch (\Exception $exception) {
            $exception->getCode() === 0
                ? $code = 500
                : $code = $exception->getCode();
            return new JsonResponse(
                [
                    'message' => $exception->getMessage()
                ], $code
            );
        }
    }

    /**
     * Get all notes by type of note (text or file)
     * @Route("/notes/{group_id}/{type_note}", name="get all notes by group", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Notes are recovered"
     * )
     * @OA\Response(
     *     response=404,
     *     description="notes/user not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Note")
     */
    public function getAllNotesByGroup(Request $request): JsonResponse
    {
        try {
            $note_management = new NoteManagement($this->noteRepository, $this->userRepository, $this->groupRepository);
            return new JsonResponse(
                [
                    'notes' => $note_management->getAllNotesByGroup($request->get('group_id'), $request->get('type_note')),
                    'message' => 'Notes of the group get'
                ], 200
            );
        } catch (\Exception $exception) {
            $exception->getCode() === 0
                ? $code = 500
                : $code = $exception->getCode();
            return new JsonResponse(
                [
                    'message' => $exception->getMessage()
                ], $code
            );
        }
    }

    /**
     * Delete a note of a group
     * @Route("/note/{group_id}/{note_id}", name="delete note of a group", methods={"DELETE"})
     * @OA\Response(
     *     response=200,
     *     description="Note is deleted"
     * )
     * @OA\Response(
     *     response=404,
     *     description="note not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Note")
     */
    public function delete(Request $request): JsonResponse
    {
        $group_id = $request->get('group_id');
        $note_id = $request->get('note_id');
        try {
            $note_management = new NoteManagement($this->noteRepository, $this->userRepository, $this->groupRepository);
            $note_management->delete($group_id, $note_id);
            return new JsonResponse(
                [
                    'message' => 'Note is deleted'
                ], 200
            );
        } catch (\Exception $exception) {
            $exception->getCode() === 0
                ? $code = 500
                : $code = $exception->getCode();
            return new JsonResponse(
                [
                    'message' => $exception->getMessage()
                ], $code
            );
        }
    }

    /**
     * Update a note
     * @Route("/note", name="udpate note", methods={"PATCH"})
     * @OA\Response(
     *     response=200,
     *     description="Note is updated"
     * )
     * @OA\Response(
     *     response=404,
     *     description="note not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\RequestBody(
     *       required=true,
     *     @OA\JsonContent(
     *           example={
     *               "note_id": "id of your note",
     *               "content_note": "If the note format is text, you can change the content"
     *            },
     *           type="object",
     *           @OA\Property(property="note_id", type="varchar(180)", description="id of your note"),
     *           @OA\Property(property="content_note", type="varchar(255)", description="If the note format is text, you can change the content"),
     *       ),
     * )
     * @OA\Tag(name="Note")
     */
    public function update(Request $request): JsonResponse
    {
        $note_id = json_decode($request->getContent())->note_id;
        $content_note = json_decode($request->getContent())->content_note;
        try {
            $note_management = new NoteManagement($this->noteRepository, $this->userRepository, $this->groupRepository);
            $note_management->update($note_id, $content_note);
            return new JsonResponse(
                [
                    'message' => 'Note is updated'
                ], 200
            );
        } catch (\Exception $exception) {
            $exception->getCode() === 0
                ? $code = 500
                : $code = $exception->getCode();
            return new JsonResponse(
                [
                    'message' => $exception->getMessage()
                ], $code
            );
        }
    }

    /**
     * Change status of the note (DONe or TO-DO)
     * @Route("/note/status", name="change_status", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="Note is updated"
     * )
     * @OA\Response(
     *     response=404,
     *     description="note not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Note")
     */
    public function changeStatus(Request $request): JsonResponse
    {
        try {
            $note_id = json_decode($request->getContent())->note_id;
            $note_management = new NoteManagement($this->noteRepository, $this->userRepository, $this->groupRepository);
            $note_management->changeStatusForNote($note_id);
            return new JsonResponse(
                [
                    'message' => 'status updated'
                ], 200
            );
        } catch (\Exception $exception) {
            $exception->getCode() === 0
                ? $code = 500
                : $code = $exception->getCode();
            return new JsonResponse(
                [
                    'message' => $exception->getMessage()
                ], $code
            );
        }
    }
}
