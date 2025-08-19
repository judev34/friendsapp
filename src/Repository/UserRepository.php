<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve un utilisateur par email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Trouve les utilisateurs vérifiés
     */
    public function findVerifiedUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isVerified = :verified')
            ->setParameter('verified', true)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les organisateurs d'événements (utilisateurs ayant créé au moins un événement)
     */
    public function findOrganizers(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.organizedEvents', 'e')
            ->groupBy('u.id')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'utilisateurs par nom ou email
     */
    public function searchByNameOrEmail(string $search): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des utilisateurs par mois
     */
    public function getUserRegistrationStats(): array
    {
        return $this->createQueryBuilder('u')
            ->select('YEAR(u.createdAt) as year, MONTH(u.createdAt) as month, COUNT(u.id) as count')
            ->groupBy('year, month')
            ->orderBy('year', 'DESC')
            ->addOrderBy('month', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs les plus actifs (avec le plus d'inscriptions à des événements)
     */
    public function findMostActiveUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.registrations', 'r')
            ->select('u, COUNT(r.id) as registrationCount')
            ->groupBy('u.id')
            ->orderBy('registrationCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
