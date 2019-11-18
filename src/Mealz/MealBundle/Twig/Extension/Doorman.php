<?php


namespace Mealz\MealBundle\Twig\Extension;

use Mealz\MealBundle\Entity\Meal;
use Mealz\MealBundle\Entity\Participant;
use Mealz\MealBundle\Service\Doorman as DoormanService;

/**
 * Class Doorman
 * @package Mealz\MealBundle\Twig\Extension
 */
class Doorman extends \Twig_Extension
{

    /**
     * @var DoormanService
     */
    protected $doormanService;

    /**
     * Doorman constructor.
     * @param DoormanService $doormanService
     */
    public function __construct(DoormanService $doormanService)
    {
        $this->doormanService = $doormanService;
    }

    /**
     * @return array|\Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            'is_allowed_to_join' => new \Twig_Function_Method($this, 'isUserAllowedToJoin'),
            'is_allowed_to_leave' => new \Twig_Function_Method($this, 'isUserAllowedToLeave'),
            'is_allowed_to_swap' => new \Twig_Function_Method($this, 'isUserAllowedToSwap'),
            'is_allowed_to_unswap' => new \Twig_Function_Method($this, 'isUserAllowedToUnswap'),
            'is_allowed_to_add_guest' => new \Twig_Function_Method($this, 'isUserAllowedToAddGuest'),
            'is_allowed_to_remove_guest' => new \Twig_Function_Method($this, 'isUserAllowedToRemoveGuest'),
            'is_participation_pending' => new \Twig_Function_Method($this, 'isParticipationPending'),
            'is_offer_available' => new \Twig_Function_Method($this, 'isOfferAvailable'),
        );
    }

    /**
     * @param Meal $meal
     * @return bool
     */
    public function isUserAllowedToJoin(Meal $meal)
    {
        // proxy method
        return $this->doormanService->isUserAllowedToJoin($meal);
    }

    /**
     * @param Meal $meal
     * @return bool
     */
    public function isUserAllowedToLeave(Meal $meal)
    {
        // proxy method
        return $this->doormanService->isUserAllowedToLeave($meal);
    }

    /**
     * @param Meal $meal
     * @return bool
     */
    public function isUserAllowedToSwap(Meal $meal)
    {
        //proxy method
        return $this->doormanService->isUserAllowedToSwap($meal);
    }

    /**
     * @param Meal $meal
     * @return bool
     */
    public function isUserAllowedToAddGuest(Meal $meal)
    {
        // proxy method
        return $this->doormanService->isUserAllowedToAddGuest($meal);
    }

    /**
     * @param Meal $meal
     * @return bool
     */
    public function isUserAllowedToRemoveGuest(Meal $meal)
    {
        // proxy method
        return $this->doormanService->isUserAllowedToRemoveGuest($meal);
    }

    /**
     * @param Meal $meal
     * @param Participant $participant
     * @return bool
     */
    public function isUserAllowedToUnswap(Meal $meal, Participant $participant)
    {
        //proxy method
        return $this->doormanService->isUserAllowedToUnswap($meal, $participant);
    }

    /**
     * @param Participant $participant
     * @return bool
     */
    public function isParticipationPending(Participant $participant)
    {
        //proxy method
        return $this->doormanService->isParticipationPending($participant);
    }

    /**
     * @param Meal $meal
     * @param Participant $participant
     * @return bool
     */
    public function isOfferAvailable(Meal $meal, Participant $participant = null)
    {
        //proxy method
        return $this->doormanService->isOfferAvailable($meal, $participant);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'doorman';
    }
}