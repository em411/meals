<?php

declare(strict_types=1);

namespace App\Mealz\MealBundle\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class DishRepository extends LocalizedRepository
{
    protected array $defaultOptions = [
        'load_category' => true,
        'orderBy_category' => true,
        'load_disabled' => false,
        'load_disabled_variations' => false,
    ];

    /**
     * Return a query builder that fetches all dish that have NO variations
     * and all variations without their dishes.
     */
    public function getDishesAndVariations(): QueryBuilder
    {
        $query = $this->createQueryBuilder('d');
        $qb2 = $this->createQueryBuilder('s');

        $qb2->select('IDENTITY(s.parent)');
        $qb2->where('IDENTITY(s.parent) is not null');
        $qb2->distinct(true);

        $query->select('d');
        $query->where(
            $query->expr()->notIn('d.id', $qb2->getDQL())
        );

        return $query;
    }

    public function getSortedDishesQueryBuilder(array $options = []): QueryBuilder
    {
        $currentLocale = $this->localizationListener->getLocale();

        $options = array_merge($this->defaultOptions, $options);

        $query = $this->createQueryBuilder('d');

        // JOIN
        if (true === $options['load_category']) {
            $query->leftJoin('d.category', 'c');
        }

        // WHERE
        if (false === $options['load_disabled']) {
            $query->where('d.enabled = 1');
        }

        // ORDER BY
        if (true === $options['load_category'] && true === $options['orderBy_category']) {
            $query->orderBy('c.title_' . $currentLocale);
            $query->addOrderBy('d.title_' . $currentLocale);
        } else {
            $query->orderBy('d.title_' . $currentLocale, 'DESC');
        }

        return $query;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function hasDishAssociatedMeals(Dish $dish): bool
    {
        $query = $this->_em->createQueryBuilder();
        $query->select('COUNT(m.dish)');
        $query->from(Meal::class, 'm');
        $query->where('m.dish = :dish');
        $query->setParameter('dish', $dish->getId(), Types::INTEGER);

        return 0 < $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Counts the number of Dish was taken in the last X Weeks.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countNumberDishWasTaken(Dish $dish, string $countPeriod): int
    {
        // prepare sql statement counting all meals taken
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('COUNT(m.dish)');
        $query->from(Meal::class, 'm');
        $query->where('m.dish = :dish');
        $query->andWhere($query->expr()->between('m.dateTime', ':date_from', ':date_to'));
        $query->setParameter('dish', $dish->getId(), Types::INTEGER);
        $query->setParameter('date_from', new DateTime($countPeriod), Types::DATETIME_MUTABLE);
        $query->setParameter('date_to', new DateTime('this week +6 days'), Types::DATETIME_MUTABLE);

        return (int) $query->getQuery()->getSingleScalarResult();
    }
}
