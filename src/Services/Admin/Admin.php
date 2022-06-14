<?php

namespace App\Services\Admin;


use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;

class Admin
{

    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(GroupRepository $groupRepository, UserRepository $userRepository)
    {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    public function changeAdmin(int $groupId, int $userId)
    {
        $this->groupRepository->changeAdmin($groupId, $userId, $this->userRepository);
    }

    public function manageAdmin(int $groupId, int $userId)
    {
        return $this->groupRepository->manageAdmin($groupId, $userId, $this->userRepository);
    }

    public function checkIfUserIsAdmin(int $groupId, int $userId): bool
    {
        if ($this->groupRepository->find($groupId)->getAdmin()->getId() !== $userId ) return false;
        return true;
    }
}
