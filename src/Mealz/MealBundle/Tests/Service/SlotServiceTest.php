<?php

declare(strict_types=1);

namespace App\Mealz\MealBundle\Tests\Service;

use App\Mealz\MealBundle\DataFixtures\ORM\LoadSlots;
use App\Mealz\MealBundle\Entity\Slot;
use App\Mealz\MealBundle\Entity\SlotRepository;
use App\Mealz\MealBundle\Service\SlotService;
use App\Mealz\MealBundle\Tests\AbstractDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;

class SlotServiceTest extends AbstractDatabaseTestCase
{
    private EntityManagerInterface $entityManager;
    private SlotRepository $slotRepo;
    private SlotService $sut;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([new LoadSlots()]);

        /* @var EntityManagerInterface $entityManager */
        $this->entityManager = $this->getDoctrine()->getManager();

        $this->slotRepo = static::$container->get(SlotRepository::class);
        $this->sut = new SlotService($this->entityManager);
    }

    /**
     * @test
     *
     * @testdox Disable an active slot.
     */
    public function updateStateDisableSlot(): void
    {
        $slot = $this->slotRepo->findOneBy(['disabled' => 0]);

        $this->assertInstanceOf(Slot::class, $slot);
        $this->assertFalse($slot->isDisabled());

        $slotID = $slot->getId();
        $this->sut->updateState($slot, '1');

        $this->entityManager->clear();
        $slot = $this->slotRepo->find($slotID);

        $this->assertTrue($slot->isDisabled());
    }

    /**
     * @test
     *
     * @testdox Enable an inactive slot.
     */
    public function updateStateEnableSlot(): void
    {
        $slot = $this->slotRepo->findOneBy(['disabled' => 1]);

        $this->assertInstanceOf(Slot::class, $slot);
        $this->assertTrue($slot->isDisabled());

        $slotID = $slot->getId();
        $this->sut->updateState($slot, '0');

        $this->entityManager->clear();
        $slot = $this->slotRepo->find($slotID);

        $this->assertFalse($slot->isDisabled());
    }

    /**
     * @test
     *
     * @testdox Soft delete a slot.
     */
    public function delete(): void
    {
        $slot = $this->slotRepo->findOneBy(['deleted' => 0]);

        $this->assertInstanceOf(Slot::class, $slot);
        $this->assertFalse($slot->isDeleted());

        $slotID = $slot->getId();
        $this->sut->delete($slot);
        $this->entityManager->clear();

        $slot = $this->slotRepo->find($slotID);
        $this->assertTrue($slot->isDeleted());
    }
}
