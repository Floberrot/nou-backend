<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\Note;
use App\Entity\User;
use App\Exception\GroupExist;
use App\Exception\GroupNotFound;
use App\Services\FileSystem\FileSystem;
use Cassandra\Date;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Group|null find($id, $lockMode = null, $lockVersion = null)
 * @method Group|null findOneBy(array $criteria, array $orderBy = null)
 * @method Group[]    findAll()
 * @method Group[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }


    private function save(Group $group)
    {
        $this->_em->persist($group);
        $this->_em->flush();
    }


    public function create(string $name, User $user): Group
    {
        $isExist = self::findOneByName($name);
        if ($isExist && $user === $isExist->getAdmin()) {
            throw new GroupExist($name);
        }
        $group = new Group();
        $this->_em->beginTransaction();
        try {
            $group
                ->setIsActive(true)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setName($name)
                ->addParticipant($user)
                ->setAdmin($user);
            self::save($group);
            $this->_em->commit();
            return $group;
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function addParticipant(string $name, User $user)
    {
        $group = self::findOneByName($name);
        $this->_em->beginTransaction();
        try {
            $group->addParticipant($user);
            self::save($group);
            $this->_em->commit();
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function removeParticipant(int $id, User $user)
    {
        $group = self::findOneById($id);
        $this->_em->beginTransaction();
        try {
            $group->removeParticipant($user);
            self::save($group);
            $this->_em->commit();
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function getNameById(int $id)
    {
        return self::findOneById($id)->getName();
    }

    public function remove(int $id, User $user)
    {
        $group = self::find($id);
        if (!$group && $user !== $group->getAdmin()) {
            throw new GroupNotFound($group->getName());
        }
        $this->_em->beginTransaction();
        try {
            $group->setIsActive(false);
            self::save($group);
            $this->_em->commit();
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function deleteANote(int $group_id, int $note_id, NoteRepository $noteRepository)
    {
        $group = self::find($group_id);
        $note = $noteRepository->find($note_id);

        if($note->getFormat() === 'file') {
            FileSystem::delete($group->getName(), $note->getContent(), $group_id);
        }

        $this->_em->beginTransaction();
        try {
            $group->removeNote($note);
            $this->_em->remove($note);
            $this->_em->persist($group);
            $this->_em->flush();
            $this->_em->commit();
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function update(int $group_id, string $group_name)
    {
        $this->_em->beginTransaction();
        try {
            $group = self::find($group_id);
            $group->setName($group_name);
            $this->_em->persist($group);
            $this->_em->flush();
            $this->_em->commit();
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function changeAdmin(int $groupId, int $userId, UserRepository $userRepository)
    {
        $group = self::findOneById($groupId);
        $participants = $group->getParticipants();
        $participants_array = [];
        $this->_em->beginTransaction();
        try {
            foreach ($participants as $participant) {
                array_push($participants_array, $participant->getId());
            }
            if (in_array($userId, $participants_array) && count($participants_array) === 1 || count($participants_array) < 1) {
                $group->setIsActive(false);
            } else {
                $keyAdmin = array_search($userId, $participants_array);
                if ($keyAdmin !== null) {
                    $group->removeParticipant($userRepository->find($userId));
                    unset($participants_array[$keyAdmin]);
                }
                $newAdminKey = array_rand($participants_array);
                $newAdmin = $userRepository->find($participants_array[$newAdminKey]);
                $group->setAdmin($newAdmin);
            }
                self::save($group);
                $this->_em->commit();
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function checkUserInGroup(int $groupId, int $userId): bool
    {
        $group = self::find($groupId);
        $adminId = $group->getAdmin()->getId();
        $participantsId = [];
        foreach ($group->getParticipants() as $participant) {
            $participantsId[] = $participant->getId();
        }
        if ($userId === $adminId || in_array($userId, $participantsId))
            return true;
        return false;
    }
}
