<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Wf\Bundle\CmsBaseBundle\Manager\DomainManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CheckController extends Controller
{
    public function checkAction()
    {
        $ret = array(
            'error_code' => 0
        );

        try {
            $this->checkDoctrine();
        } catch (\Exception $e) {
            $ret['error_code'] = 1;
            $ret['source'] = 'doctrine';
            $ret['message'] = $e->getMessage();
        }

        try {
            $this->checkElasticSearch();
        } catch (\Exception $e) {
            $ret['error_code'] = 1;
            $ret['source'] = 'elastic';
            $ret['message'] = $e->getMessage();
        }

        return new Response(json_encode($ret));
    }

    protected function checkDoctrine()
    {
        $connection = $this->get('database_connection');
        $res = $connection->fetchAll('SHOW GRANTS');
    }

    protected function checkElasticSearch()
    {
        $client = $this->get('fos_elastica.client.default');
        $status = $client->getStatus();
        $statusData = $status->getData();

        if (!$statusData['ok']) {
            throw new \Exception(sprintf('ElasticSearch doesn\'t feel fine (%s)', json_encode($statusData)));
        }
    }
}
