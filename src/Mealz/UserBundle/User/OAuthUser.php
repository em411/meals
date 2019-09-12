<?php
/**
 * OAuthUser to provide Keycloak Login
 */

namespace Mealz\UserBundle\User;

use Mealz\UserBundle\Entity\Profile;

/**
 * OAuthUser.
 *
 * @author     Geoffrey Bachelet <geoffrey.bachelet@gmail.com>
 */
class OAuthUser implements OAuthUserInterface
{
    /**
     * @var Profile
     */
    protected $profile;

    /**
     * @var        string
     */
    protected $username;

    /**
     * @var        Array
     */
    protected $roles = [];

    /**
     * @param      string  $username
     */
    public function __construct($username)
    {
        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function addRole($role)
    {
        if (array_search($role, $this->roles) === false) {
            $this->roles[] = $role;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeRole($role)
    {
        if (($key = array_search($role, $this->roles)) !== false) {
            unset($this->roles[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param Profile|null $profile
     */
    public function setProfile(Profile $profile = null)
    {
        $this->profile = $profile;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(UserInterface $user)
    {
        return $user->getUsername() === $this->username;
    }

    public function serialize()
    {
        return serialize(array(
        $this->username,
        ));
    }

    public function unserialize($serialized)
    {
        list(
        $this->username,
        ) = unserialize($serialized);
    }

    public function isEqualTo(\Symfony\Component\Security\Core\User\UserInterface $user)
    {
        if (!$user instanceof LdapUserInterface
        || $user->getUsername() !== $this->username
        ) {
            return false;
        }

        return true;
    }

    /**
     * Return username when converting class to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUserName();
    }
}
