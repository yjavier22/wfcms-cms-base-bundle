<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * renders objects tagged
 *
 * @author cv
 */
class TagController extends Controller
{

    /**
     * @Template()
     */
    public function renderTagsAction($moduleData)
    {
        $emptyResponse = new Response('');
        if (!isset($moduleData['params'])) {
            return $emptyResponse;
        }

        $params = $moduleData['params'];
        if (!$params) {
            return $emptyResponse;
        }

        $entity = null;
        if ($params['type'] == 'page' && isset($params['version'])) {
            $pageManager = $this->get('wf_cms.page_manager');
            $pageVersionRepository = $pageManager->getPageVersionRepository();
            $pageVersion = $pageVersionRepository->find($params['version']);
            if ($pageVersion) {
                $entity = $pageVersion->getPageData();
            }
        }
        if (!$entity) {
            $typeRepository = $this->get('wf_cms.repository.' . $params['type']);
            if (!$typeRepository) {
                return $emptyResponse;
            }
            $entity = $typeRepository->find($params['id']);
            if (!$entity) {
                return $emptyResponse;
            }
        }

        if (!method_exists($entity, 'getTags')) {
            return $emptyResponse;
        }

        $tags = $entity->getTags();
        if (!is_array($tags) && !$tags instanceof \Traversable) {
            return $emptyResponse;
        }

        $_tags = array();
        foreach($tags as $tag) {
            $_tags[] = array(
                'tag' => $tag->getSlug(),
                'title' => $tag->getTitle()
            );
        }


        return array(
            'tags' => $_tags,
        );
    }

}