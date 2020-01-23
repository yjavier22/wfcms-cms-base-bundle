<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Inflector\Inflector;

/**
 * @author gk
 */
class ModuleController extends Controller
{
    /**
     * @Template()
     */
    public function sidebarAction($slug = "")
    {
        $ret = array();
        $ret['slug'] = $slug;
        return $ret;
    }
    
    public function pagePartAction($slug, $partType)
    {
        $response = new Response('');

        $article = $this->get('wf_cms.repository.page')->findOneBySlug($slug);
        if (!$article) {
            throw $this->createNotFoundException();
        }
        $modulesCollection = $article->getModulesCollection();

        $methodName = 'get'. ucfirst(Inflector::classify($partType));
        if (method_exists($modulesCollection, $methodName)) {
            $media = call_user_func(array($modulesCollection, $methodName));
        }

        if (isset($media[0]['html'])) {
            $response->setContent($media[0]['html']);
        }

        return $response;
    }
}
