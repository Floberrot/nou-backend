<?php

namespace App\Repository;

use App\Entity\User;
use App\Exception\UserExist;
use App\Exception\UserNotFound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getUsernameById(int $id)
    {
        return self::findOneById($id)->getUsername();
    }

    private function save(User $user)
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @throws \Exception
     */
    public function register (string $password, string $email, string $username): User
    {
        $isExistUser = $this->findOneBy(["username" => $username]);
        if ($isExistUser) {
            throw new UserExist($username);
        }
        $user = new User();
        $this->_em->beginTransaction();
        try {
            $user
                ->setPassword($password)
                ->setEmail($email)
                ->setUsername($username)
                ->setIsActive(true);
            self::save($user);
            $this->_em->commit();
            return $user;
        } catch (\Exception $exception) {
            $this->_em->rollback();
            throw new \Exception();
        }
    }

    public function login(string $username): User
    {
        $isExistUser = $this->findOneBy(["username" => $username]);
        if (!$isExistUser) {
            throw new UserNotFound($username);
        }
        return $isExistUser;
    }
}
