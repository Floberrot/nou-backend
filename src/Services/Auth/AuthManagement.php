<?php

namespace App\Services\Auth;

use App\Entity\User;
use App\Exception\UserNotFound;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;

class AuthManagement
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

    public function register(string $password, string $email, string $username): string
    {
        $user = $this->userRepository->register(self::encodePassword($password), $email, $username);
        $groups = [];
        return self::constructToken($user, $groups, $username);
    }

    public function login(string $username, string $password): string
    {
        if (self::ensurePasswordIsValid($password, $this->userRepository->findOneByUsername($username)->getPassword())) {
            $user = $this->userRepository->login($username);
            $groups = [];
            return self::constructToken($user, $groups, $username);
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

    private function constructToken(User $user,?array $groups, string $username): string
    {
        foreach ($user->getGroups() as $group) {
            if ($group->getIsActive()) {
                array_push($groups,
                    [
                        'group_id' => $group->getId(),
                        'group_name' => $group->getName(),
                    ]);
            }
        }
        $group_user_in = $this->groupRepository->getGroupsOfAnUser($user->getId());
        foreach ($group_user_in as $group_of_user) {
            if ($group_of_user->getIsActive()) {
                array_push($groups,
                    [
                        'group_id' => $group_of_user->getId(),
                        'group_name' => $group_of_user->getName(),
                    ]);
            }
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
}
