<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Wf\Bundle\CmsBaseBundle\Entity\PollOption;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;


class PollController extends Controller
{
    /**
     */
    public function pollsAction(Request $request)
    {
        $response = array();
        $response['poll_user_login'] = $this->container->getParameter('wf_cms.poll.user_login');

        $ids = $request->query->get('ids');

        if (!$ids) {
            return new Response();
        }

        $repository = $this->container->get('wf_cms.repository.poll');
        $results = $repository->getPollsByIds($ids);

        foreach ($results as $poll) {
            $response['polls'][$poll->getId()] = $this->renderView('WfCmsBaseBundle:Poll:pollTemplate.html.twig',
                 array('poll' => $poll));
        }

        return new Response(json_encode($response));
    }

    /**
     * @Template()
     */
    public function pollAction()
    {
        return array();
    }

    /**
     */
    public function postPollVoteAction($id)
    {
        $poll = $this->get('wf_cms.repository.poll')->find($id);
        if (empty($poll)) {
            return $this->createNotFoundException();
        }

        $vote = $this->get('request')->request->get('vote');
        $optionVoteCount = $this->get('wf_cms.repository.poll_option')->increaseOptionVoteCount($poll, $vote);

        if (!$optionVoteCount) {
            throw $this->createNotFoundException('Vote option not found');
        }

        return new Response($this->renderView('WfCmsBaseBundle:Poll:pollTemplate.html.twig',
                                                array('poll' => $poll)
                                             )
                   );
    }
}