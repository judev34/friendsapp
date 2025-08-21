<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Trouve les événements publiés et à venir
     */
    public function findUpcomingPublishedEvents(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.startDate > :now')
            ->setParameter('published', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les événements par lieu
     */
    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.location LIKE :location')
            ->andWhere('e.isPublished = :published')
            ->setParameter('location', '%' . $location . '%')
            ->setParameter('published', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements par période
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.startDate >= :startDate')
            ->andWhere('e.startDate <= :endDate')
            ->andWhere('e.isPublished = :published')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('published', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'événements par titre ou description
     */
    public function searchByTitleOrDescription(string $search): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.title LIKE :search OR e.description LIKE :search')
            ->andWhere('e.isPublished = :published')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('published', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements par tags
     */
    public function findByTags(array $tags): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->setParameter('published', true);

        foreach ($tags as $index => $tag) {
            $qb->andWhere('e.tags LIKE :tag' . $index)
               ->setParameter('tag' . $index, '%"' . $tag . '"%');
        }

        return $qb->orderBy('e.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les événements gratuits
     */
    public function findFreeEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.price IS NULL OR e.price = :zero')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.startDate > :now')
            ->setParameter('zero', '0.00')
            ->setParameter('published', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements d'un organisateur
     */
    public function findByOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements populaires (avec le plus d'inscriptions)
     */
    public function findPopularEvents(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.registrations', 'r')
            ->select('e, COUNT(r.id) as registrationCount')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.startDate > :now')
            ->setParameter('published', true)
            ->setParameter('now', new \DateTime())
            ->groupBy('e.id')
            ->orderBy('registrationCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des événements par mois
     */
    public function getEventCreationStats(): array
    {
        return $this->createQueryBuilder('e')
            ->select('YEAR(e.createdAt) as year, MONTH(e.createdAt) as month, COUNT(e.id) as count')
            ->groupBy('year, month')
            ->orderBy('year', 'DESC')
            ->addOrderBy('month', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements avec des places disponibles
     */
    public function findEventsWithAvailableSpots(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.registrations', 'r', 'WITH', 'r.status = :confirmed')
            ->select('e, COUNT(r.id) as confirmedRegistrations')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.startDate > :now')
            ->andWhere('e.maxParticipants IS NOT NULL')
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('published', true)
            ->setParameter('now', new \DateTime())
            ->groupBy('e.id')
            ->having('confirmedRegistrations < e.maxParticipants')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pagination des événements avec filtres
     */
    public function findWithFilters(
        array $filters = [],
        string $sortBy = 'startDate',
        string $sortOrder = 'ASC',
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->setParameter('published', true);

        // Filtres
        if (!empty($filters['location'])) {
            $qb->andWhere('e.location LIKE :location')
               ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['startDate'])) {
            $qb->andWhere('e.startDate >= :startDate')
               ->setParameter('startDate', $filters['startDate']);
        }

        if (!empty($filters['endDate'])) {
            $qb->andWhere('e.startDate <= :endDate')
               ->setParameter('endDate', $filters['endDate']);
        }

        if (isset($filters['isFree']) && $filters['isFree'] === true) {
            $qb->andWhere('e.price = 0 OR e.price IS NULL');
        }

        if (!empty($filters['tags'])) {
            foreach ($filters['tags'] as $index => $tag) {
                $qb->andWhere('e.tags LIKE :tag' . $index)
                   ->setParameter('tag' . $index, '%"' . $tag . '"%');
            }
        }

        // Compter le total
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Pagination et tri
        $events = $qb->orderBy('e.' . $sortBy, $sortOrder)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'events' => $events,
            'total' => (int) $total
        ];
    }

    /**
     * Trouve les événements par tag
     */
    public function findByTag(string $tag, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.tags LIKE :tag')
            ->setParameter('published', true)
            ->setParameter('tag', '%"' . $tag . '"%')
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements recommandés par tags
     */
    public function findRecommendedByTags(array $tags, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.startDate > :now')
            ->setParameter('published', true)
            ->setParameter('now', new \DateTime());

        foreach ($tags as $index => $tag) {
            $qb->orWhere('e.tags LIKE :tag' . $index)
               ->setParameter('tag' . $index, '%"' . $tag . '"%');
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les événements de ce mois
     */
    public function countEventsThisMonth(): int
    {
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');

        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.startDate >= :start')
            ->andWhere('e.startDate <= :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtient la moyenne de participants par événement
     */
    public function getAverageParticipantsPerEvent(): float
    {
        // Total events
        $totalEvents = (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalEvents === 0) {
            return 0.0;
        }

        // Total confirmed registrations across all events
        $totalConfirmed = (int) $this->createQueryBuilder('e')
            ->leftJoin('e.registrations', 'r', 'WITH', 'r.status = :confirmed')
            ->select('COUNT(r.id)')
            ->setParameter('confirmed', 'confirmed')
            ->getQuery()
            ->getSingleScalarResult();

        return $totalConfirmed / $totalEvents;
    }

}
