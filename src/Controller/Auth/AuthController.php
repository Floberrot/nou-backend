<?php

namespace App\Controller\Auth;

use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Services\Auth\AuthManagement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class AuthController extends AbstractController
{
    private UserRepository $userRepository;
    private string $secret_key;
    private GroupRepository $groupRepository;

    public function __construct(UserRepository $userRepository, GroupRepository $groupRepository, string $secret_key)
    {
        $this->userRepository = $userRepository;
        $this->secret_key = $secret_key;
        $this->groupRepository = $groupRepository;
    }

    /**
     * Login user
     * @Route("/sign-in", name="login", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="User is connected"
     * )
     * @OA\Response(
     *     response=404,
     *     description="User not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     *  * @OA\RequestBody(
     *       required=true,
     *     @OA\JsonContent(
     *           example={
     *               "username": "username",
     *               "password": "my password"
     *            },
     *           type="object",
     *           @OA\Property(property="email", type="varchar(180)", description="User email"),
     *           @OA\Property(property="password", type="varchar(255)", description="User password"),
     *       ),
     * )
     * @OA\Tag(name="User - Auth")
     */
    public function login(Request $request): JsonResponse
    {
        $res = json_decode($request->getContent());
        $auth = new AuthManagement($this->userRepository, $this->groupRepository, $this->secret_key);
        return new JsonResponse(
            [
                'token' => $auth->login($res->username, $res->password),
                'message' => 'User is connected'
            ], 200
        );
    }

    /**
     * Register an user
     * @Route("/sign-up", name="register", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="User is connected"
     * )
     * @OA\Response(
     *     response=404,
     *     description="User not found"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Error server"
     * )
     *  * @OA\RequestBody(
     *       required=true,
     *     @OA\JsonContent(
     *           example={
     *               "email": "email@domain.com",
     *               "username": "username",
     *               "password": "my password"
     *            },
     *           type="object",
     *           @OA\Property(property="email", type="varchar(180)", description="User email"),
     *           @OA\Property(property="password", type="varchar(255)", description="User password"),
     *       ),
     * )
     * @OA\Tag(name="User - Auth")
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $res = json_decode($request->getContent());
            $auth = new AuthManagement($this->userRepository, $this->secret_key);
            return new JsonResponse(
                [
                    "token" => $auth->register($res->password, $res->email, $res->username),
                    'message' => 'User is registered'
                ], 200
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'message' => "Error: " . $e->getMessage()
                ], 500
            );
        }
    }
}
