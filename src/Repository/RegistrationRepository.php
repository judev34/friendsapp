<?php

namespace App\Repository;

use App\Entity\Registration;
use App\Entity\RegistrationStatus;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registration>
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    /**
     * Trouve une inscription par utilisateur et événement
     */
    public function findByUserAndEvent(User $user, Event $event): ?Registration
    {
        return $this->findOneBy([
            'user' => $user,
            'event' => $event
        ]);
    }

    /**
     * Trouve les inscriptions confirmées pour un événement
     */
    public function findConfirmedByEvent(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->andWhere('r.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', RegistrationStatus::CONFIRMED)
            ->orderBy('r.confirmedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions en liste d'attente pour un événement
     */
    public function findWaitlistByEvent(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->andWhere('r.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', RegistrationStatus::WAITLIST)
            ->orderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.event', 'e')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les prochaines inscriptions confirmées d'un utilisateur
     */
    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.event', 'e')
            ->andWhere('r.user = :user')
            ->andWhere('r.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('user', $user)
            ->setParameter('status', RegistrationStatus::CONFIRMED)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les inscriptions par statut pour un événement
     */
    public function countByEventAndStatus(Event $event, RegistrationStatus $status): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->andWhere('r.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les inscriptions par code de billet
     */
    public function findByTicketCode(string $ticketCode): ?Registration
    {
        return $this->findOneBy(['ticketCode' => $ticketCode]);
    }

    /**
     * Statistiques des inscriptions par mois
     */
    public function getRegistrationStats(): array
    {
        return $this->createQueryBuilder('r')
            ->select('YEAR(r.registeredAt) as year, MONTH(r.registeredAt) as month, COUNT(r.id) as count')
            ->groupBy('year, month')
            ->orderBy('year', 'DESC')
            ->addOrderBy('month', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions en attente de confirmation depuis plus de X jours
     */
    public function findPendingOlderThan(int $days): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.registeredAt < :date')
            ->setParameter('status', RegistrationStatus::PENDING)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions payées
     */
    public function findPaidRegistrations(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.paidAt IS NOT NULL')
            ->andWhere('r.paidAmount IS NOT NULL')
            ->orderBy('r.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Revenus totaux par événement
     */
    public function getRevenueByEvent(Event $event): string
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.paidAmount) as totalRevenue')
            ->andWhere('r.event = :event')
            ->andWhere('r.paidAmount IS NOT NULL')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? '0.00';
    }

    /**
     * Prochaine inscription en liste d'attente pour un événement
     */
    public function getNextWaitlistRegistration(Event $event): ?Registration
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->andWhere('r.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', RegistrationStatus::WAITLIST)
            ->orderBy('r.registeredAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Inscriptions par statut avec pagination
     */
    public function findByStatusWithPagination(
        RegistrationStatus $status,
        int $page = 1,
        int $limit = 20
    ): array {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('r')
            ->innerJoin('r.event', 'e')
            ->innerJoin('r.user', 'u')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.registeredAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
