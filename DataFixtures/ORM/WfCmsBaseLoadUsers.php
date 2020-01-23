<?php

namespace Wf\Bundle\CmsBaseBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Wf\Bundle\CmsBaseBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WfCmsBaseLoadUsers
    extends AbstractFixture
    implements FixtureInterface, OrderedFixtureInterface, ContainerAwareInterface
{
    protected $categoryClass = 'Wf\Bundle\CmsDemoBundle\Entity\Category';
    protected $container;

    function load(ObjectManager $em)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        /*
        $roles = $this->container->getParameter('security.role_hierarchy.roles');
        $roles['ROLE_USER'] = array();//simple user
        */

        //create only super admin user, the rest are getting in PMs way :D
        $roles = array('ROLE_ADMIN' => array());
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');

        foreach($roles as $role => $roleHierarchy) {
            $username = str_replace('role_', '', strtolower($role));
            $user = $userManager->createUser();
            $user->setUsername($username);
            $password = substr($tokenGenerator->generateToken(), 0, 12);
            error_log(sprintf('Generated password %s for user %s', $password, $username));

            $user->setPlainPassword($password);
            $user->setEnabled(true);
            $user->setEmail($username . '@wfcms.com');
            $user->addRole($role);
            if (method_exists($user, 'setFirstName')) {
                $parts = explode('_', $username);
                $lastName = ucfirst(array_pop($parts));
                $firstName = ucwords(implode(' ', $parts));
                if (empty($firstName)) {
                    $firstName = 'User';
                }
                if (empty($lastName)) {
                    $lastName = 'User';
                }
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
            }
            $em->persist($user);
            $this->addReference('user-' . $username, $user);
        }

        $em->flush();
    }

    function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    function getOrder()
    {
        return 1;
    }



}
