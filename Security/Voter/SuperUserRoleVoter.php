<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * This voter adds a special role "ROLE_SUPER_USER" which effectively bypasses any, and all security checks.
 */
class SuperUserRoleVoter implements VoterInterface
{
    /**
     * SuperAdmin Role
     *
     * @var string
     */
    static $ROLE_SUPER_ADMIN = 'ROLE_SUPER_USER';

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        return $this->hasSuperUserRole($token) ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_ABSTAIN;
    }

    protected function hasSuperUserRole(TokenInterface $token)
    {
        foreach ($token->getRoles() as $role) {
            if ($role->getRole() === self::$ROLE_SUPER_ADMIN) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return true;
    }
}