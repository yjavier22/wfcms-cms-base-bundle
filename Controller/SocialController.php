<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class SocialController extends Controller {
    public function countersAction($url)
    {
        return new JsonResponse(array(
            'gplus' => $this->getGplusCount($url)
        ));
    }

    protected function getGplusCount($url)
    {
        $gurl = "https://clients6.google.com/rpc";
        $query = array(
            'method' => 'pos.plusones.get',
            'id' => 'p',
            'params' => array(
                'nolog' => true,
                'id' => sprintf("%s", $url),
                'source' => 'widget',
                'userId' => '@viewer',
                'groupId' => '@self'
            ),
            'jsonrpc' => '2.0',
            'key' => 'p',
            'apiVersion' => 'v1'
        );

        $apiKey = $this->container->getParameter('wf_cms.social.gplus');
        $queryStr = '';
        if ($apiKey) {
            $queryStr = sprintf("%s?key=%s", $gurl, urlencode($apiKey));
        }
        $url = sprintf("%s%s", $gurl, $queryStr);

        // just one request object per query
        $reqBody = json_encode(array($query));

        $count = -1;
        $response = $this->doPostRequest($url, $reqBody);

        if ($response['err'] == 0) {
            $body = $response['body'];
            $respObj = json_decode($body, true);

            if (!empty($respObj[0]['result']['metadata']['globalCounts']['count'])) {
                $count = (int) $respObj[0]['result']['metadata']['globalCounts']['count'];
            }
        }

        return $count;
    }

    protected function doPostRequest($url, $body)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($body))
        );

        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);

        return array(
            'body' => $content,
            'err' => $err,
            'errmsg' => $errmsg
        );
    }
} 