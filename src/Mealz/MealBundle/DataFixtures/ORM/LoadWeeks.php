<?php

namespace Mealz\MealBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mealz\MealBundle\Entity\Week;


class LoadWeeks extends AbstractFixture implements OrderedFixtureInterface
{
	/**
	 * @var int
	 */
	protected $counter = 0;

	/**
	 * @param ObjectManager $objectManager
	 */
	function load(ObjectManager $objectManager)
	{
		$weeks = $this->getCurrentTestWeeks();
		$weeks = array_merge($weeks, $this->getStaticTestWeeks());

		foreach ($weeks as $weekDataSet) {
			$week = new Week();
			$week->setYear($weekDataSet['year']);
			$week->setCalendarWeek($weekDataSet['calendarWeek']);
			$objectManager->persist($week);
			$this->addReference('week-' . $this->counter++, $week);
		}

		$objectManager->flush();
	}

	/**
	 * Gets the current test weeks.
	 *
	 * They are meant to be used as dummy data on integration systems.
	 *
	 * @return array
	 */
	private function getCurrentTestWeeks()
	{
		$currentWeeks = [];
		$date = new \DateTime('');
		$maxDate = new \DateTime('+1 month');

		while($date < $maxDate) {
			$year = $date->format('Y');
			$week = $date->format('W');
			$currentWeeks[$year . '-' . $week] = ['year' => $year, 'calenderWeek' => $week];
			$date->modify('+1 week');
		}

		return $currentWeeks;
	}

	/**
	 * Gets the static test weeks.
	 *
	 * They are meant to be used with unit or functional tests.
	 *
	 * @return array
	 */
	private function getStaticTestWeeks()
	{
		return [
			'2016-41' => ['year' => '2016', 'calendarWeek' => '41'],
			'2016-42' => ['year' => '2016', 'calendarWeek' => '42'],
			'2016-43' => ['year' => '2016', 'calendarWeek' => '43'],
			'2016-44' => ['year' => '2016', 'calendarWeek' => '44'],
			'2016-45' => ['year' => '2016', 'calendarWeek' => '45'],
			'2016-46' => ['year' => '2016', 'calendarWeek' => '46']
		];
	}

	public function getOrder()
	{
		return 1;
	}
}
