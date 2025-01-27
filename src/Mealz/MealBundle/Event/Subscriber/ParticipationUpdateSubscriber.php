<?php

declare(strict_types=1);

namespace App\Mealz\MealBundle\Event\Subscriber;

use App\Mealz\MealBundle\Entity\Meal;
use App\Mealz\MealBundle\Entity\MealCollection;
use App\Mealz\MealBundle\Event\ParticipationUpdateEvent;
use App\Mealz\MealBundle\Service\MealAvailabilityService;
use App\Mealz\MealBundle\Service\ParticipationService;
use App\Mealz\MealBundle\Service\Publisher\PublisherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ParticipationUpdateSubscriber implements EventSubscriberInterface
{
    private const PUBLISH_TOPIC = 'participation-updates';
    private const PUBLISH_MSG_TYPE = 'participationUpdate';

    private LoggerInterface $logger;
    private MealAvailabilityService $availabilityService;
    private ParticipationService $participationSrv;
    private PublisherInterface $publisher;

    public function __construct(
        LoggerInterface $logger,
        MealAvailabilityService $availabilityService,
        ParticipationService $participationSrv,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->availabilityService = $availabilityService;
        $this->participationSrv = $participationSrv;
        $this->publisher = $publisher;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ParticipationUpdateEvent::class => 'onUpdate',
        ];
    }

    /**
     * Triggers action when a participation is updated, e.g. join, delete.
     */
    public function onUpdate(ParticipationUpdateEvent $event): void
    {
        $mealDay = $event->getParticipant()->getMeal()->getDay();
        $mealsAvailability = $this->availabilityService->getByDay($mealDay);
        $dayMeals = $mealDay->getMeals();
        $participationCount = $this->getParticipationCount($dayMeals);
        $data = $this->getParticipationStatus($dayMeals, $mealsAvailability, $participationCount);

        $this->publish($data);
    }

    private function getParticipationCount(MealCollection $meals): array
    {
        $count = [];

        /** @var Meal $meal */
        foreach ($meals as $meal) {
            $count[$meal->getId()] = $this->participationSrv->getCountByMeal($meal);
        }

        return $count;
    }

    private function getParticipationStatus(MealCollection $meals, array $availability, array $participationCount): array
    {
        $status = [];

        /** @var Meal $meal */
        foreach ($meals as $meal) {
            $mealId = $meal->getId();
            $mealAvailability = $availability[$mealId] ?? false;

            $mealStatus = [
                'count' => $participationCount[$mealId] ?? 0,
                'locked' => $meal->isLocked(),
                'available' => false,
            ];

            if (is_array($mealAvailability) && $mealAvailability['available']) {
                $mealStatus['available'] = true;
                $mealStatus['availableWith'] = $mealAvailability['availableWith'];
            } else {
                $mealStatus['available'] = $mealAvailability;
            }

            $status[$mealId] = $mealStatus;
        }

        return $status;
    }

    private function publish(array $data): void
    {
        $published = $this->publisher->publish(self::PUBLISH_TOPIC, $data, self::PUBLISH_MSG_TYPE);

        if (!$published) {
            $this->logger->error('publish failure', ['topic' => self::PUBLISH_TOPIC]);
        }
    }
}
