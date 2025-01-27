<?php

declare(strict_types=1);

namespace App\Mealz\UserBundle\Tests\Service;

use App\Mealz\MealBundle\Tests\Controller\AbstractControllerTestCase;
use App\Mealz\UserBundle\DataFixtures\ORM\LoadRoles;
use App\Mealz\UserBundle\Entity\Profile;
use App\Mealz\UserBundle\Provider\OAuthUserProvider;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class OAuthProviderTest extends AbstractControllerTestCase
{
    use ProphecyTrait;

    private OAuthUserProvider $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearAllTables();
        $this->loadFixtures([new LoadRoles()]);

        $this->sut = new OAuthUserProvider($this->getDoctrine()->getManager());
    }

    /**
     * Test oAuth Provider - create new User and check if admin.
     *
     * @dataProvider idpUserDataProvider
     */
    public function testCreateNewUser(array $idpUserData, array $mealsRoles): void
    {
        $firstName = $idpUserData['given_name'];
        $lastName = $idpUserData['family_name'];
        $username = $idpUserData['username'];
        $idpRoles = $idpUserData['roles'];

        $userResponseMock = $this->getMockedUserResponse($username, $firstName, $lastName, $idpRoles);

        // check if valid oAuth User comes in return
        $user = $this->sut->loadUserByOAuthUserResponse($userResponseMock);
        $this->assertInstanceOf(Profile::class, $user);

        // check if new valid Profile is written in Database
        $newCreatedProfile = $this->getDoctrine()->getManager()->find(Profile::class, $username);
        $this->assertEquals($username, $newCreatedProfile->getUsername());

        // check role mapping
        $this->assertSame($mealsRoles, $user->getRoles());

        // check that user is not hidden after loading
        $this->assertFalse($user->isHidden());
    }

    public function idpUserDataProvider(): array
    {
        return [
            'admin' => [
                'idpUserData' => [
                    'username' => 'kochomi.meals',
                    'given_name' => 'kochomi',
                    'family_name' => 'imohcok',
                    'roles' => ['meals.admin'],
                ],
                'mealsRoles' => ['ROLE_ADMIN'],
            ],
            'kitchen staff' => [
                'idpUserData' => [
                    'username' => 'kochomi.meals',
                    'given_name' => 'kochomi',
                    'family_name' => 'imohcok',
                    'roles' => ['meals.kitchen'],
                ],
                'mealsRoles' => ['ROLE_KITCHEN_STAFF'],
            ],
            'standard user' => [
                'idpUserData' => [
                    'username' => 'alice.meals',
                    'given_name' => 'alice',
                    'family_name' => 'ecila',
                    'roles' => ['meals.user'],
                ],
                'mealsRoles' => ['ROLE_USER'],
            ],
            'finance' => [
                'idpUserData' => [
                    'username' => 'finance.meals',
                    'given_name' => 'finance',
                    'family_name' => 'ecnanif',
                    'roles' => ['meals.user', 'meals.finance'],
                ],
                'mealsRoles' => ['ROLE_FINANCE'],
            ],
            'user with invalid role' => [
                'idpUserData' => [
                    'username' => 'invalid.role',
                    'given_name' => 'invalid',
                    'family_name' => 'role',
                    'roles' => ['invalid.role'],
                ],
                'mealsRoles' => [],
            ],
        ];
    }

    /**
     * Returns the mocked response from identity provider.
     */
    private function getMockedUserResponse(string $username, string $firstName, string $lastName, array $roles): object
    {
        $userData = [
            'preferred_username' => $username,
            'family_name' => $lastName,
            'given_name' => $firstName,
            'roles' => $roles,
        ];
        $responseProphet = $this->prophesize(UserResponseInterface::class);
        $responseProphet->getData()->shouldBeCalledOnce()->willReturn($userData);
        $responseProphet->getFirstName()->shouldBeCalledOnce()->willReturn($firstName);
        $responseProphet->getLastName()->shouldBeCalledOnce()->willReturn($lastName);
        $responseProphet->getNickname()->shouldBeCalledOnce()->willReturn($username);

        return $responseProphet->reveal();
    }
}
