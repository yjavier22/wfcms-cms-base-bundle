<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Wf\Bundle\CmsBaseBundle\Job\ClearRatingJob;

class RatingController extends Controller
{
    /**
     * @Template()
     */
    public function showAction($pageId)
    {
        $manager = $this->get('wf_cms.rating_manager');
        $rating = $manager->getRating($pageId);
        $votesCount = $manager->getVotesCount($pageId);
        $votes = $manager->getVoteValues($pageId);

        $return  = array(
            'ratingAllowed' => $this->isVotable($pageId),
            'id' => $pageId,
            'rating' => $rating,
            'votesCount' => $votesCount,
            'votes' => $votes,
            'maxVote' => $this->container->getParameter('wf_cms.rating.max_vote'),
        );

        return $return;
    }

    protected function isVotable($pageId)
    {
        $votable = true;
        $page = $this->get('wf_cms.repository.page')->find($pageId);
        $settings = $page->getSettings();

        if (isset($settings['votable']) && $settings['votable'] == 'no') {
            $votable = false;
        }

        return $votable;
    }

    /**
     */
    public function rateAction($pageId, $vote)
    {
        $maxVote = $this->container->getParameter('wf_cms.rating.max_vote');
        if ($vote < 1 || $vote > $maxVote) {
            return new Response('Unaccepted vote rate', 500);
        }

        $manager = $this->get('wf_cms.rating_manager');
        $response = $manager->addVote($pageId, $vote);

        // clear ratings cache
        $job = ClearRatingJob::create($pageId);
        $this->container->get('bcc_resque.resque')->enqueue($job);

        $return = array(
            'error_code' => 0,
        );

        return new JsonResponse($return);
    }

}