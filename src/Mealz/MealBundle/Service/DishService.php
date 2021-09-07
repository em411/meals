<?php

declare(strict_types=1);

namespace App\Mealz\MealBundle\Service;

use App\Mealz\MealBundle\Entity\Dish;
use App\Mealz\MealBundle\Entity\DishRepository;

class DishService
{
    /**
     * Number of times a dish must be taken before it is considered old.
     */
    private int $newFlagThreshold;

    private DishRepository $dishRepository;

    public function __construct(
        int $newFlagThreshold,
        DishRepository $dishRepository
    ) {
        $this->newFlagThreshold = $newFlagThreshold;
        $this->dishRepository = $dishRepository;
    }

    public function isNew(Dish $dish): bool
    {
        return (int) $this->dishRepository->countNumberDishWasTaken($dish, '0000-01-01') < $this->newFlagThreshold;
    }
}