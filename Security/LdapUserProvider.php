<?php

namespace Garlic\User\Security;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\LdapUserProvider as BaseProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class LdapUserProvider
 */
class LdapUserProvider extends BaseProvider
{
    /**
     * @var null|string
     */
    private $passwordAttribute;

    /**
     * @var array
     */
    private $defaultRoles;

    /**
     * @param LdapInterface $ldap
     * @param string        $baseDn
     * @param string        $searchDn
     * @param string        $searchPassword
     * @param array         $defaultRoles
     * @param string        $uidKey
     * @param string        $filter
     * @param string        $passwordAttribute
     */
    public function __construct(
        LdapInterface $ldap,
        $baseDn,
        $searchDn = null,
        $searchPassword = null,
        array $defaultRoles = [],
        $uidKey = 'sAMAccountName',
        $filter = '({uid_key}={username})',
        $passwordAttribute = null
    ) {
        parent::__construct(
            $ldap,
            $baseDn,
            $searchDn,
            $searchPassword,
            $defaultRoles,
            $uidKey,
            $filter,
            $passwordAttribute
        );

        $this->passwordAttribute = $passwordAttribute;
        $this->defaultRoles = $defaultRoles;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof LdapUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return new LdapUser($user->getUsername(), null, $user->getRoles());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return 'Garlic\User\Security\LdapUser' === $class;
    }

    /**
     * Loads a user from an LDAP entry.
     *
     * @param string $username
     * @param Entry  $entry
     *
     * @return LdapUser
     */
    protected function loadUser($username, Entry $entry)
    {
        $password = null;

        if (null !== $this->passwordAttribute) {
            $password = $this->getAttributeValue($entry, $this->passwordAttribute);
        }

        return new LdapUser($username, $password, $this->defaultRoles);
    }

    /**
     * Fetches a required unique attribute value from an LDAP entry.
     *
     * @param null|Entry $entry
     * @param string     $attribute
     *
     * @return mixed
     */
    private function getAttributeValue(Entry $entry, $attribute)
    {
        if (!$entry->hasAttribute($attribute)) {
            throw new InvalidArgumentException(
                sprintf('Missing attribute "%s" for user "%s".', $attribute, $entry->getDn())
            );
        }

        $values = $entry->getAttribute($attribute);

        if (1 !== count($values)) {
            throw new InvalidArgumentException(sprintf('Attribute "%s" has multiple values.', $attribute));
        }

        return $values[0];
    }
}
