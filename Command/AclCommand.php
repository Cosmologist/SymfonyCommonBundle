<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Command;

use Cosmologist\Gears\StringType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class AclCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('symfony-common:acl:set')->setDescription('Interactive setup ACLs and ACEs.')->addOption('user', null, InputOption::VALUE_REQUIRED, 'User name')->addOption(
            'class',
            null,
            InputOption::VALUE_REQUIRED,
            'Object Class'
        )->addOption('id', null, InputOption::VALUE_REQUIRED, 'Object identifier')->addOption('field', null, InputOption::VALUE_REQUIRED, 'Object field name')->addOption(
            'mask',
            null,
            InputOption::VALUE_REQUIRED,
            'Mask code (VIEW, CREATE, EDIT, DELETE etc. See all codes in the MaskBuilder class)'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ss = new SymfonyStyle($input, $output);

        $user        = null;
        $objectClass = null;
        $maskCode    = null;
        $field       = null;

        if (null === $user = $input->getOption('user')) {
            $users = $this->loadSids();
            if (null === $user = $ss->choice('Choose a user', $users)) {
                while (null === $user = $ss->ask('Enter a username')) {
                }
            }
        }

        if (null === $objectClass = $input->getOption('class')) {
            $classes = $this->loadClasses();
            if (null === $objectClass = $ss->choice('Choose the object class', $classes)) {
                while (null === $objectClass = $ss->ask('Enter the object class')) {
                }
            }
        }

        $objectIdentifier = $ss->ask('Enter the object identifier', 'class');

        if (null === $maskCode = $input->getOption('mask')) {
            $maskCodes = $this->loadMaskCodes();
            while (null === $maskCode = $ss->choice('Choose the mask code', $maskCodes)) {
            }
        }

        $field = $ss->ask('Enter the field name, or Enter to skip', false) ?: null;

        $aclProvider = $this->getContainer()->get('security.acl.provider');

        $oid  = new ObjectIdentity($objectIdentifier, $objectClass);
        $sid  = new UserSecurityIdentity(StringType::strAfter($user, '-'), StringType::strBefore($user, '-'));
        $mask = (new MaskBuilder())->resolveMask($maskCode);

        try {
            $acl = $aclProvider->findAcl($oid);
        } catch (AclNotFoundException $e) {
            $acl = $aclProvider->createAcl($oid);
        }

        if ($field !== null) {
            $acl->insertClassFieldAce($field, $sid, $mask);
        } else {
            $acl->insertClassAce($sid, $mask);
        }

        $aclProvider->updateAcl($acl);

        return 0;
    }

    /**
     * @return array
     */
    private function loadSids(): array
    {
        return $this->getSecurityConnection()->executeQuery('SELECT identifier FROM acl_security_identities')->fetchAll(FetchMode::COLUMN);
    }

    /**
     * @return array
     */
    private function loadClasses(): array
    {
        return $this->getSecurityConnection()->executeQuery('SELECT class_type FROM acl_classes')->fetchAll(FetchMode::COLUMN);
    }

    /**
     * @return Connection
     */
    private function getSecurityConnection(): Connection
    {
        return $this->getContainer()->get('doctrine.dbal.security_connection');
    }

    /**
     * @return array
     */
    private function loadMaskCodes(): array
    {
        return array_map(
            function ($name) {
                return substr($name, 5);
            },
            array_filter(
                array_keys(
                    (new \ReflectionClass(MaskBuilder::class))->getConstants()
                ),
                function ($name) {
                    return StringType::startsWith($name, 'MASK_');
                }
            )
        );
    }
}
