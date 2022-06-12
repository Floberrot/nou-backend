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
     * @Route("group/{groupId}/user/{userId}", name="admin_manage", methods={"POST"})
     *  /**
     * Login user
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
        $userId = $request->get('userId');
        $groupId = $request->get('groupId');
        $admin = new Admin($this->groupRepository, $this->userRepository);
        $admin->changeAdmin($userId, $groupId);
        return new JsonResponse(
            [
                'message' => 'Admin Modified'
            ], 200
        );
    }
}
