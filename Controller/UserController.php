<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 */
class UserController extends Controller
{
    /**
     * @Template()
     */
    public function welcomeAction(Request $request)
    {
        $securityContext = $this->get('security.context');
        $user = $securityContext->getToken()->getUser();

        if ($user instanceof UserInterface) {
            $ret = $this->getLoggedInData($user);
        } else {
            $ret = $this->getLoggedOutData();
        }

        if ($request->isXmlHttpRequest()) {
            $response = new Response();
            $response->setContent(json_encode($ret));
            $response->headers->set('Cache-Control', 'no-cache, no-store');
            return $response;
        } else {
            return $ret;
        }
    }

    /**
     */
    public function initAction()
    {
        //explicitely start the session, php.ini should have auto_start=0 and ->getUser doesn't seem to do so if the session cookie is not found
        $session = $this->get('session');
        if (!$session->isStarted()) {
            $session->start();
        }

        return new Response(json_encode(array('error_code' => 0)));
    }

    protected function getLoggedInData(UserInterface $user)
    {
        return array(
            'logged_in' => true,
            'user' => array(
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'allow_comments' => $this->get('security.context')->isGranted('ROLE_COMMENTER')
            ),
        );
    }

    protected function getLoggedOutData()
    {
        return array(
            'logged_in' => false,
        );
    }

}
