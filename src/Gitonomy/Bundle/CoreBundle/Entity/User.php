<?php

namespace Gitonomy\Bundle\CoreBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User in Gitonomy.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com
 * @author Julien DIDIER <julien@jdidier.net>
 */
class User extends Base\BaseUser implements UserInterface
{
    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->fullname;
    }

    /**
     * @inheritdoc
     */
    public function equals(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        return $user->getUsername() === $this->username;
    }

    /**
     * @inheritdoc
     */
    public function eraseCredentials()
    {
    }

    /**
     * @inheritdoc
     */
    public function regenerateSalt()
    {
        return $this->salt = md5(uniqid().microtime());
    }

    /**
     * @inheritdoc
     */
    public function getRoles()
    {
        $roles = array();

        foreach ($this->getProjectRoles() as $projectRole) {
            $roles = array_merge($roles, $projectRole->getSecurityRoles());
        }

        foreach ($this->getGlobalRoles() as $globalRole) {
            $roles = array_merge($roles, $globalRole->getSecurityRoles());
        }

        return $roles;
    }

    /**
     * Returns the default email.
     *
     * @return Email The default e-mail or null if not found.
     */
    public function getDefaultEmail()
    {
        foreach ($this->getEmails() as $email) {
            if ($email->isDefault()) {
                return $email;
            }
        }
    }

    /**
     * Tests if a default email exists for the user.
     *
     * @return boolean Result of test
     */
    public function hasDefaultEmail()
    {
        return null !== $this->getDefaultEmail();
    }

    /**
     * Defines an mail as default.
     *
     * @param Email $email An email to mark as default
     *
     * @throws \LogicException An exceptions is thrown if a default mail already
     * exists for this user.
     */
    public function setDefaultEmail(Email $email)
    {
        if ($this->hasDefaultEmail()) {
            throw new \LogicException(sprintf('User "%s" has already a default email address : "%s"', $this, $this->getDefaultEmail()));
        }

        $email->setUser($this);
        $email->setIsDefault(true);
        $this->addEmail($email);
    }

    /**
     * Fills fields to create a new forgot password token.
     */
    public function createForgotPasswordToken()
    {
        if (!$this->getForgotPassword() instanceof UserForgotPassword) {
            $forgotPasswordToken = new UserForgotPassword($this);
            $this->setForgotPassword($forgotPasswordToken);
        }
        $this->getForgotPassword()->generateToken();
    }

    /**
     * Marks all SSH keys of the user as uninstalled.
     */
    public function markAllKeysAsUninstalled()
    {
        foreach ($this->sshKeys as $sshKey) {
            $sshKey->setIsInstalled(false);
        }
    }

    /**
     * Generates a new activation token.
     */
    public function generateActivationToken()
    {
        $this->activationToken = md5(uniqid().microtime());
    }

    /**
     * Removes the activation token from the user.
     */
    public function removeActivationToken()
    {
        $this->activationToken = null;
    }

    /**
     * Tests of the account is activated.
     *
     * @return boolean Result of the test
     */
    public function isActivated()
    {
        return (null !== $this->password && null === $this->activationToken);
    }

    /**
     * Adds a new global role to the user.
     *
     * @param Role $globalRole The role to add
     */
    public function addGlobalRole(Role $globalRole)
    {
        $this->globalRoles->add($globalRole);
    }

    /**
     * Adds a new email to the user.
     *
     * @param Email $email The email to add.
     */
    public function addEmail(Email $email)
    {
        if (!$this->hasDefaultEmail()) {
            $email->setIsDefault(true);
        }

        $email->setUser($this);
        $this->emails->add($email);
    }
}
