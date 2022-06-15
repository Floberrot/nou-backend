<?php

namespace App\Controller\Admin;

use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Services\Admin\Admin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(GroupRepository $groupRepository, UserRepository $userRepository)
    {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }
    /**
     * Change admin of group
     * @Route("group/{groupId}/new-admin/{new_admin}", name="admin_manage", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="User(amdin) is modified"
     * )
     * @OA\Response(
     *     response=404,
     *     description="User or group not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     * @OA\Tag(name="User - Admin")
     */
    public function manageAdmin(Request $request) :JsonResponse
    {
        $new_admin = $request->get('new_admin');
        $groupId = $request->get('groupId');
        $admin = new Admin($this->groupRepository, $this->userRepository);
        $group = $admin->manageAdmin($groupId, $new_admin);
        return new JsonResponse(
            [
                'message' => 'Admin Modified',
                'new_admin' => $group->getAdmin()->getUsername(),
                'group_name' => $group->getName(),
                'group_id' => $group->getId(),
            ], 200
        );
    }
}
