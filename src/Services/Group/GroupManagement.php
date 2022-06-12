<?php

namespace App\Services\Group;

use App\Entity\Group;
use App\Exception\UserNotFound;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use phpDocumentor\Reflection\Types\Integer;

class GroupManagement
{

    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(GroupRepository $groupRepository, UserRepository $userRepository)
    {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    public function create(string $name, string $username) : Group
    {
        $user = $this->userRepository->findOneByUsername($username);
        if (!$user) throw new UserNotFound($username);
        return $this->groupRepository->create($name, $user);
    }

    public function addParticipantInAGroup(string $name, string $username) :void
    {
        $user = $this->userRepository->findOneByUsername($username);
        !$user
            ? throw new UserNotFound($username)
            : $this->groupRepository->addParticipant($name, $user);
    }

    public function removeParticipantsInAGroup(int $id, string $username) :void
    {
        $eUser = $this->userRepository->findOneByUsername($username);
        !$eUser
            ? throw new UserNotFound($username)
            : $this->groupRepository->removeParticipant($id, $eUser);
    }

    public function delete(int $id, string $username) :void
    {
        $user = $this->userRepository->findOneByUsername($username);
        $this->groupRepository->remove($id, $user);
    }

    public function getOne(int $group_id): ?\App\Entity\Group
    {
        return $this->groupRepository->find($group_id);
    }

    public function getAllByUsername(string $username): array
    {
        $user = $this->userRepository->findOneByUsername($username);
        $groups = [];
        foreach ($user->getGroupsTheirIn() as $group) {
            if ($group->getIsActive()) {
                $temp['group_id'] = $group->getId();
                $temp['group_name'] = $group->getName();
                $temp['author_id'] = $group->getAdmin()->getId();
                $temp['author'] = $group->getAdmin()->getUsername();
                array_push($groups, $temp);
            }
        }
        return $groups;
    }

    public function getAllUsersFromGroup(int $groupId): array
    {
        $allUsers = [];
        $group = $this->groupRepository->findOneById($groupId);
        $temp['group_id'] = $group->getId();
        $temp['group_name'] = $group->getName();
        $adminTemp = [];
        $adminTemp['id'] = $group->getAdmin()->getId();
        $adminTemp['email'] = $group->getAdmin()->getEmail();
        $adminTemp['username'] = $group->getAdmin()->getUsername();
        $adminTemp['isActive'] = $group->getAdmin()->getisActive();
        $temp['admin'] = $adminTemp;
        $participants = $group->getParticipants()->toArray();
        $temp['participants'] = [];
        foreach ($participants as $participant){
            $participantTemp = [];
            $participantTemp['id'] = $participant->getId();
            $participantTemp['email'] = $participant->getEmail();
            $participantTemp['username'] = $participant->getUsername();
            $participantTemp['isActive'] = $participant->getisActive();
                array_push($temp['participants'], $participantTemp);
            }
        array_push($allUsers, $temp);
        return $allUsers;
    }

    public function getNames(int $group_id, int $user_id) : array
    {
        return [
            "group_name" => $this->groupRepository->getNameById($group_id),
            "username" => $this->userRepository->getUsernameById($user_id),
        ];
    }

    public function update(int $group_id, string $group_name)
    {
        $this->groupRepository->update($group_id, $group_name);
    }

    public function checkIfUserIsAuthorized(int $groupId, int $userId) {
        return $this->groupRepository->checkUserInGroup($groupId, $userId);
    }
}
