<?php

namespace Mealz\MealBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mealz\UserBundle\Entity\Zombie;

/**
 * Meal
 *
 * @ORM\Table(name="meal")
 * @ORM\Entity
 */
class Meal
{
	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Dish")
	 * @ORM\JoinColumn(name="dish_id", referencedColumnName="id")
	 * @var Dish
	 */
	protected $dish;

	/**
	 * @ORM\Column(type="datetime", nullable=FALSE)
	 * @var \DateTime
	 */
	protected $dateTime;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="Participant", mappedBy="meal")
	 */
	protected $participants;

	public function __construct() {
		$this->participants = new ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param \DateTime $dateTime
	 */
	public function setDateTime($dateTime)
	{
		$this->dateTime = $dateTime;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateTime()
	{
		return $this->dateTime;
	}

	/**
	 * @param \Mealz\MealBundle\Entity\Dish $dish
	 */
	public function setDish($dish)
	{
		$this->dish = $dish;
	}

	/**
	 * @return \Mealz\MealBundle\Entity\Dish
	 */
	public function getDish()
	{
		return $this->dish;
	}

	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getParticipants()
	{
		return $this->participants;
	}

	/**
	 * get the participant object of the given user if he is registered
	 *
	 * @param Zombie $user
	 * @return \Mealz\MealBundle\Entity\Participant|null
	 */
	public function getParticipant(Zombie $user) {
		foreach($this->participants as $participant) {
			/** @var Participant $participant */
			if(!$participant->isGuest() && $participant->getUser() === $user) {
				return $participant;
			}
		}
		return NULL;
	}

	/**
	 * get all guests that the given user has invited
	 *
	 * @param Zombie $user
	 * @return Participant|null
	 */
	public function getGuestParticipants(Zombie $user) {
		$participants = array();
		foreach($this->participants as $participant) {
			/** @var Participant $participant */
			if($participant->isGuest() && $participant->getUser() === $user) {
				$participants[] = $participant;
			}
		}
		return $participants;
	}

	function __toString() {
		return $this->getDateTime()->format('Y-m-d H:i:s') . ' ' . $this->getDish();
	}


}
