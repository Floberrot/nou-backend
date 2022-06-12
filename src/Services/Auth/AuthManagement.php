<?php

namespace App\Services\Auth;

use App\Exception\UserNotFound;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;

class AuthManagement
{

    private UserRepository $userRepository;
    private string $secret_key;

    public function __construct(UserRepository $userRepository, string $secret_key)
    {
        $this->userRepository = $userRepository;
        $this->secret_key = $secret_key;
    }

    public function register(string $password, string $email, string $username): string
    {
        $this->userRepository->register(self::encodePassword($password), $email, $username);
        return self::encodeToken($this->secret_key,
            [
                "user_id" => $this->userRepository->login($username)->getId(),
                "username" => $username,
                "iat" => time(),
                "exp" => time() + 60 * 60
            ]);
    }

    public function login(string $username, string $password): string
    {
        if (self::ensurePasswordIsValid($password, $this->userRepository->findOneByUsername($username)->getPassword())) {
            $user = $this->userRepository->login($username);
            $groups = [];
            foreach ($user->getGroups() as $group) {
                array_push($groups,
                    [
                        'group_id' => $group->getId(),
                        'group_name' => $group->getName(),
                    ]);
            }
            json_encode($groups);
            return self::encodeToken($this->secret_key,
                [
                    "user_id" => $user->getId(),
                    'email' => $user->getEmail(),
                    'groups' => $groups,
                    "username" => $username,
                    "iat" => time(),
                    "exp" => time() + 60 * 60
                ]);
        }
        throw new UserNotFound($username);
    }


    private static function encodePassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private static function ensurePasswordIsValid(string $entryPassword, string $password): bool
    {
        return password_verify($entryPassword, $password);
    }

    private static function encodeToken(string $key, array $payload): string
    {
        return JWT::encode($payload, $key, 'HS256');
    }
}
