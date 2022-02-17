<?php

declare(strict_types=1);

namespace App\Mealz\MealBundle\Service;

use App\Mealz\MealBundle\Entity\Meal;
use App\Mealz\MealBundle\Entity\ParticipantRepository;
use App\Mealz\MealBundle\Entity\Slot;
use App\Mealz\MealBundle\Entity\SlotRepository;
use App\Mealz\MealBundle\Event\UpdateCountEvent;
use App\Mealz\MealBundle\Service\Exception\ParticipationException;
use App\Mealz\UserBundle\Entity\Profile;
use App\Mealz\UserBundle\Entity\ProfileRepository;
use App\Mealz\UserBundle\Entity\Role;
use App\Mealz\UserBundle\Entity\RoleRepository;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GuestParticipationService
{
    use ParticipationServiceTrait;

    private EntityManagerInterface $entityManager;
    private ParticipantRepository $participantRepo;
    private ProfileRepository $profileRepo;
    private RoleRepository $roleRepo;
    private SlotRepository $slotRepo;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParticipantRepository $participantRepo,
        ProfileRepository $profileRepo,
        RoleRepository $roleRepo,
        SlotRepository $slotRepo,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->participantRepo = $participantRepo;
        $this->profileRepo = $profileRepo;
        $this->roleRepo = $roleRepo;
        $this->slotRepo = $slotRepo;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws ParticipationException
     */
    public function join(Profile $profile, Collection $meals, ?Slot $slot = null, array $dishSlugs = []): void
    {
        $mealDate = $meals->first()->getDateTime();

        $guestProfile = $this->getCreateGuestProfile(
            $profile->getFirstName(),
            $profile->getName(),
            $profile->getCompany(),
            $mealDate
        );

        if (null === $slot || !$this->slotIsAvailable($slot, $mealDate)) {
            $slot = $this->getNextFreeSlot($mealDate);
        }

        $this->register($guestProfile, $meals, $slot, $dishSlugs);

        /* @var $meal Meal */
        foreach ($meals as $meal){
            $this->eventDispatcher->dispatch(new UpdateCountEvent($meal, $guestProfile));
        }
    }

    /**
     * Registers user with $profile to given meals and slot.
     *
     * @param Collection<int, Meal> $meals
     *
     * @throws ParticipationException
     * @throws Exception
     */
    private function register(Profile $profile, Collection $meals, ?Slot $slot = null, array $dishSlugs = []): void
    {
        $this->validateBookableMeals($meals, $dishSlugs);

        $this->entityManager->beginTransaction();

        try {
            $this->entityManager->persist($profile);
            $this->create($profile, $meals, $slot, $dishSlugs);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $exc) {
            $this->entityManager->rollBack();
            throw $exc;
        }
    }

    /**
     * @param Collection<int, Meal> $meals
     *
     * @throws ParticipationException
     */
    private function validateBookableMeals(Collection $meals, array $dishSlugs = []): void
    {
        $flippedDishSlugs = array_flip($dishSlugs);

        $participations = [];

        /** @var Meal $meal */
        foreach ($meals as $meal) {
            if (empty($participations)) {
                $participations = ParticipationCountService::getParticipationByDay($meal->getDay());
            }

            $bookable = $this->mealIsBookable($meal);
            if (!$bookable) {
                throw new ParticipationException('meal not bookable', ParticipationException::ERR_MEAL_NOT_BOOKABLE, null, ['meal' => $meal]);
            }

            $dishSlugArray = [$meal->getDish()->getSlug()];
            $participationCount = 1.0;
            if ($meal->getDish()->isCombinedDish()) {
                $dishSlugArray = $dishSlugs;
                $participationCount = 0.5;
            } else {
                // Note: There is an edge case, when a guest books a meal with limitation and a combined meal at once
                if (isset($flippedDishSlugs[$meal->getDish()->getSlug()])) {
                    $participationCount = 1.5;
                }
            }

            if (!ParticipationCountService::isParticipationPossibleForDishes($participations[ParticipationCountService::PARTICIPATION_TOTAL_COUNT_KEY], $dishSlugArray, $participationCount)) {
                throw new ParticipationException('meal not bookable', ParticipationException::ERR_MEAL_NOT_BOOKABLE, null, ['meal' => $meal, 'bookedCombinedDishes' => $dishSlugs]);
            }
        }
    }

    /**
     * Create guest participation.
     *
     * @param Collection<int, Meal> $meals
     *
     * @throws ParticipationException
     */
    private function create(Profile $profile, Collection $meals, ?Slot $slot = null, array $dishSlugs = []): void
    {
        foreach ($meals as $meal) {
            try {
                $participation = $this->createParticipation($profile, $meal, $slot, $dishSlugs);
            } catch (ParticipationException $pex) {
                $pex->addContext(['operation' => 'guest participation create']);
                throw $pex;
            }

            $participation->setCostAbsorbed(true);

            $this->entityManager->persist($participation);
        }
    }

    private function getCreateGuestProfile(
        string $firstName,
        string $lastName,
        string $company,
        DateTime $mealDate
    ): Profile {
        $guestProfileID = sprintf('%s.%s_%s', $firstName, $lastName, $mealDate->format('Y-m-d'));
        $guestProfile = $this->profileRepo->find($guestProfileID);
        if (($guestProfile instanceof Profile) && $guestProfile->isGuest()) {
            return $guestProfile;
        }

        $profile = new Profile();
        $profile->setUsername($guestProfileID);
        $profile->setFirstName($firstName);
        $profile->setName($lastName);
        $profile->setCompany($company);
        $profile->addRole($this->getGuestRole());

        return $profile;
    }

    private function getGuestRole(): Role
    {
        $guestRole = $this->roleRepo->findOneBy(['sid' => Role::ROLE_GUEST]);
        if (null === $guestRole) {
            throw new RuntimeException('role not found: ' . Role::ROLE_GUEST);
        }

        return $guestRole;
    }
}
