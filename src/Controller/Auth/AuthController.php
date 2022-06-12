<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use App\Services\Auth\AuthManagement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{

    private UserRepository $userRepository;
    private string $secret_key;

    public function __construct(UserRepository $userRepository, string $secret_key)
    {
        $this->userRepository = $userRepository;
        $this->secret_key = $secret_key;
    }

    /**
     * @Route("/sign-in", name="login", methods={"POST"})
     */
    public function login(Request $request): JsonResponse
    {
        $res = json_decode($request->getContent());
        $auth = new AuthManagement($this->userRepository, $this->secret_key);
        return new JsonResponse(
            [
                'token' => $auth->login($res->username, $res->password),
                'message' => 'User is connected'
            ], 200
        );
    }

    /**
     * @Route("/sign-up", name="register", methods={"POST"})
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
