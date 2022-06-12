<?php

namespace App\Controller\Group;

use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Services\Admin\Admin;
use App\Services\Group\GroupManagement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class GroupController extends AbstractController
{
    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(GroupRepository $groupRepository, UserRepository $userRepository)
    {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Create a group
     * @Route("/group", name="create_group", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="group is created"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     *  * @OA\RequestBody(
     *       required=true,
     *     @OA\JsonContent(
     *           example={
     *               "name": "name of your group",
     *               "username": "username of the person who want to create the group"
     *            },
     *           type="object",
     *           @OA\Property(property="name", type="varchar(180)", description="name of group"),
     *           @OA\Property(property="username", type="varchar(255)", description="username of the person who want to create the group"),
     *       ),
     * )
     * @OA\Tag(name="Group")
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $res = json_decode($request->getContent());
            $group = new GroupManagement($this->groupRepository, $this->userRepository);
            $group = $group->create($res->name, $res->username);
            return new JsonResponse(
                [
                    'groupname' => $group->getName(),
                    'group_id' => $group->getId(),
                    'author' => $group->getAdmin()->getUsername(),
                    'message' => 'Group is created'
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
     * Delete a group
     * @Route("/group/{group_id}/{username}", name="delete_group", methods={"DELETE"})
     * @OA\Response(
     *     response=200,
     *     description="group is deleted"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Group not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Group")
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $group = new GroupManagement($this->groupRepository, $this->userRepository);
            $group->delete($request->get('group_id'), $request->get('username'));
            return new JsonResponse(
                [
                    'message' => 'Group is deleted'
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
     * Get one group
     * @Route("/group/{group_id}", name="get_one__group", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="group is get"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Group not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Group")
     */
    public function getOne(Request $request): JsonResponse
    {
        try {
            $group_id = $request->get('group_id');
            $group = new GroupManagement($this->groupRepository, $this->userRepository);
            return new JsonResponse(
                [
                    'name' => $group->getOne($group_id)->getName(),
                    'group_id' => $group->getOne($group_id)->getId(),
                    'admin' => $group->getOne($group_id)->getAdmin()->getUsername(),
                    'participants' => $group->getOne($group_id)->getParticipants(),
                    'notes' => $group->getOne($group_id)->getNotes(),
                    'message' => 'Group is get'
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
     * Get all group of an user
     * @Route("/groups/{username}", name="get all_group", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="group(s) is(are) recovered"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Group not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Group")
     */
    public function getAllByUsername(Request $request): JsonResponse
    {
        try {
            $username = $request->get('username');
            $group = new GroupManagement($this->groupRepository, $this->userRepository);
            return new JsonResponse(
                [
                    'groups' => $group->getAllByUsername($username),
                    'message' => 'Groups get'
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
     * Leave a group
     * @Route("/user/{userId}/group/{groupId}/leave", name="leave_group", methods={"DELETE"})
     * @OA\Response(
     *     response=200,
     *     description="the user leave the group"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Group or user not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Group")
     */
    public function leave(Request $request): JsonResponse
    {
        try {
            $groupId = $request->get('groupId');
            $userId = $request->get('userId');
            $admin = new Admin($this->groupRepository, $this->userRepository);
            if ($admin->checkIfUserIsAdmin($groupId, $userId)) {
                $admin->changeAdmin($groupId, $userId);
            } else {
                $this->groupRepository->removeParticipant($groupId, $this->userRepository->find($userId));
            }
            return new JsonResponse(
                [
                    'message' => 'Group left'
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
     * Update the name of the group
     * @Route("/group", name="update_group", methods={"PATCH"})
     * @OA\Response(
     *     response=200,
     *     description="group(s) is(are) recovered"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Group not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     *  * @OA\RequestBody(
     *       required=true,
     *     @OA\JsonContent(
     *           example={
     *               "group_id": "id of the group",
     *               "group_name": "name of the group"
     *            },
     *           type="object",
     *           @OA\Property(property="group_id", type="varchar(180)", description="id of the group"),
     *           @OA\Property(property="group_name", type="varchar(255)", description="name of the group"),
     *       ),
     * )
     * @OA\Tag(name="Group")
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $group_id = json_decode($request->getContent())->group_id;
            $group_name = json_decode($request->getContent())->group_name;
            $group = new GroupManagement($this->groupRepository, $this->userRepository);
            $group->update($group_id, $group_name);
            return new JsonResponse(
                [
                    'message' => 'Group is updated'
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
     * Add an user in a group
     * @Route("/{group_name}/add/{username}", name="add user in group", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="A new user is in the group"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Group/user not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Group")
     */
    public function addParticipants(Request $request): JsonResponse
    {
        try {
            $group_name = $request->get('group_name');
            $username = $request->get('username');
            $group = new GroupManagement($this->groupRepository, $this->userRepository);
            $group->addParticipantInAGroup($group_name, $username);
            return new JsonResponse(
                [
                    'message' => "$username has been added to $group_name group"
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
     * Get all the users from a group
     * @Route("/group/{groupId}/users", name="getUsersFromGroup", methods={"GET"})
     *   * @OA\Response(
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
     * @OA\Tag(name="Group")
     */
    public function getAllUsersFromGroup(Request $request): JsonResponse
    {
        try {
            $groupId = $request->get('groupId');
            $group = new GroupManagement($this->groupRepository, $this->userRepository);
            return new JsonResponse(
                [
                    'groups' => $group->getAllUsersFromGroup($groupId),
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
     *  Check if an user is authorized to access the group
     * @Route("/check/group/{groupId}/user/{userId}", name="checkUserInGroup", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="user is authorized"
     * )
     * @OA\Response(
     *     response=404,
     *     description="No authorized"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="Group")
     */
    public function checkIfUserIsAuthorized(Request $request): JsonResponse
    {
        $groupId = $request->get('groupId');
        $userId = $request->get('userId');
        $group = new GroupManagement($this->groupRepository, $this->userRepository);
        if ($group->checkIfUserIsAuthorized($groupId, $userId))
            return new JsonResponse(
                [
                    'isAuthorized' => true
                ],
            );
        return new JsonResponse(
            [
                'isAuthorized' => false
            ],
        );
    }
}
