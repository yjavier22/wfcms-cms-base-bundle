<?php

namespace Wf\Bundle\CmsBaseBundle\Entity\Repository;

use Wf\Bundle\CmsBaseBundle\Entity\Page;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * PageArticleRepository
 */
class PageArticleRepository extends PageRepository
{
    /**
     * @return QueryBuilder $qb
     */
    public function getBaseQB($onlyActive = true)
    {
        $qb = parent::getBaseQB($onlyActive);
        $qb->byType(Page::TYPE_ARTICLE);

        $qb->byList($this->getListTemplate('LATEST'));

        return $qb;
    }

    public function getMediaPagesQB($type, $limit = 20, $exclude = null, $category = null, $categoryChildren = null)
    {
        $qb = $this->getBaseQB()
            ->hasMedia($type)
            ->limit($limit)
            ->excludeIds($exclude)
            ;

        $qb->groupBy($qb->rootAlias . '.id');

        $templateKey = '';
        switch($type) {
            case 'image': $templateKey = 'LATEST_MEDIA_IMAGES'; break;
            case 'video': $templateKey = 'LATEST_MEDIA_VIDEOS'; break;
            case 'audio': $templateKey = 'LATEST_MEDIA_AUDIOS'; break;
        }

        if ($category) {
            $qb->byCategory($category, $categoryChildren, false);
            $templateKey = $templateKey ? $templateKey . '_CATEGORY' : '';
        }

        $listName = null;
        if ($templateKey) {
            $template = $this->getListTemplate($templateKey);
            if ($template) {
                if ($category) {
                    $listName = sprintf($template, $category->getId());
                } else {
                    $listName = $template;
                }
            }
        }

        $qb->byList($listName);

        return $qb;
    }

    public function getPublishedMonths($categories = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('ym', 'ym');

        $sql = "
                SELECT DISTINCT DATE_FORMAT(published_at, '%Y-%m') as ym
                FROM `" . $this->getClassMetadata()->table['name'] . "`
                WHERE published_at IS NOT NULL
                    AND status='" . Page::STATUS_PUBLISHED . "'
                    AND page_type='" . Page::TYPE_ARTICLE . "'
            ";
        if ($categories) {
            $sql .= ' AND category_id IN (' . implode(',', $categories) . ')';
        }

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        $result = $query->getResult();

        return array_column($result, 'ym');
    }

    public function getDateEdition(\DateTime $date, $onlyActive = true)
    {
        return $this->getBaseQB($onlyActive)
            ->byDateEdition($date)
            ->getResults()
            ;
    }
}