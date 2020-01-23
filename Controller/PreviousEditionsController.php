<?php

namespace Wf\Bundle\CmsBaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class PreviousEditionsController extends Controller
{
    protected function getListManager()
    {
        if (isset($this->listManager)) {
            return $this->listManager;
        }

        $this->listManager = $this->container->get('wf_cms.publish.manager');
        return $this->listManager;
    }

    /**
     * @Template()
     */
    public function indexAction($year = '', $month = '')
    {
        $today = new \DateTime();

        if (!$year) {
            $year = $today->format('Y');
        }
        if (!$month) {
            $month = $today->format('n');
        }

        $beginningOfTime = $this->getListManager()->getFirstDate();
        $options = array(
            'firstYear' => $beginningOfTime->format('Y'),
            'firstMonth' => $beginningOfTime->format('m'),
        );

        $generated = $this->generateCalendar($year, $month, $options);
        if ($generated instanceOf RedirectResponse) {
            return $generated;
        }

        return $generated;
    }

    /**
     * @Template()
     */
    public function historyAction($year = '', $month = '', $day = '', $page)
    {
        $today = new \DateTime();

        $monthStr = substr('0' . $month, -2);
        $dayStr = substr('0' . $day, -2);
        $dateStr = $year . $monthStr . $dayStr;

        $beginningOfTime = $this->getListManager()->getFirstDate();
        if ($dateStr < $beginningOfTime->format('Ymd') || $dateStr > $today->format('Ymd')) {
            return $this->redirectIncorrectDate($today->format('Y'), $today->format('m'));
        }

        $date = \DateTime::createFromFormat('Ymd', $dateStr);
        $listName = $this->getListName($date);

        $pagerFactory = $this->get('wf_cms.pager.factory');
        $articleRepository = $this->get('wf_cms.repository.page_article');

        $qb = $articleRepository->getBaseQB();
        $qb->byList($listName);

        $pages = $pagerFactory->createPager($qb, $page, null, 20);
        return array(
            'pages' => $pages,
            'pagerOptions' => array(
                'routeName' => 'wf_previous_editions_view',
                'routeParams' => array(
                    'year' => $year, 
                    'month' => $month,
                    'day' => $day
                ),
            ),
        );
    }

    public function getListName($date)
    {
        $listManagerClass = get_class($this->getListManager());
        $listTemplate = constant($listManagerClass . '::LATEST_DATE');
        $listName = sprintf($listTemplate, $date->format('Y-m-d'));

        return $listName;
    }

    public function redirectIncorrectDate($year, $month)
    {
        return $this->redirect($this->generateUrl('wf_previous_editions_date',
            array('year' => $year, 'month' => $month)
        ));
    }

    public function generateCalendar($year, $month, $options = array())
    {
        $today = new \DateTime();

        $monthStr = substr('0' . $month, -2);

        $daySelected = isset($options['daySelected']) ? $options['daySelected'] : $today;

        $firstYear = isset($options['firstYear']) ? $options['firstYear'] : 2000;
        $firstMonth = isset($options['firstMonth']) ? $options['firstMonth'] : 1;
        $firstDate = new \DateTime($firstYear . $firstMonth . '01');

        $lastYear = isset($options['lastYear']) ? $options['lastYear'] : $today->format('Y');

        $months = array();
        $week = array();

        for ($x = 1; $x <= 12; $x++) {
            $date = new \DateTime($year . '-' . $x . '-01');
            if (($today > $date) && ($firstDate <= $date)){
                $months[$x] = $date;
            }
        }

        if (!isset($months[$month])) {
            if ($year > $today->format('Y')) {
                $year = $today->format('Y');
                $month = $today->format('n');
            } else if ($year < $firstYear) {
                $year = $firstYear;
                $month = $firstMonth;
            } else {
                $keys = array_keys($months);
                $firstMonth = array_shift($keys);
                $lastMonth = (count($keys))?array_pop($keys):$firstMonth;

                $month = ($month < $firstMonth)?$firstMonth:$lastMonth;
            }

            return $this->redirectIncorrectDate($year, $month);
        }

        $nextDay = new \DateTime('next Sunday');
        for ($x = 1; $x <= 7; $x++) {
            $nextDay->modify('+1 day');
            $week[$x] = clone $nextDay;
        }

        $firstDayMonth = gmstrftime('%u', gmmktime(0, 0, 0, $month, 1, $year));
        $sizeMonth = gmstrftime('%d', gmmktime(0, 0, 0, $month + 1, 0, $year));
        $lastDayMonth = gmstrftime('%u', gmmktime(0, 0, 0, $month + 1, 0, $year));

        $firstDayCalendar = 2 - $firstDayMonth;
        $lastDayCalendar = $sizeMonth + 7 - $lastDayMonth;

        $calendar = array();
        for ($x=$firstDayCalendar; $x<=$lastDayCalendar; $x++) {
            $dayStr = substr('0' . $x, -2);
            $date = $year . $monthStr . $dayStr;

            $isToday = ($date == $today->format('Ymd'));
            $isSelected = ($daySelected && $date == $daySelected->format('Ymd'));

            // if day has articles
            $dateObject = \DateTime::createFromFormat('Ymd', $date);
            if ($dateObject) {
                $listName = $this->getListName($dateObject);
                $hasContent = $this->getListManager()->getListSize($listName);
            } else {
                $hasContent = false;
            }

            $calendar[] = array(
                'time' => gmmktime(0, 0, 0, $month, $x, $year),
                'actualMonth' => (($x>=1 && $x<=$sizeMonth) && ($date <= $today->format('Ymd')))?true:false,
                'today' => $isToday,
                'selected' => $isSelected,
                'hasContent' => $hasContent,
                'dateStr' => $date,
                'year' => $year,
                'year2' => substr($year, -2),
                'month' => $month,
                'monthStr' => $monthStr,
                'day' => $x,
                'dayStr' => $dayStr,
            );
        }

        // Filter navigation
        if (($lastYear > $year) || ($month < $today->format('m'))) {
            if ($month == 12) {
                $nextMonth = 1;
                $nextYear = $year + 1;
            } else {
                $nextMonth = $month + 1;
                $nextYear = $year;
            }
        } else {
            $nextMonth = false;
            $nextYear = false;
        }

        if (($firstYear < $year) || ($month > $firstMonth)) {
            if ($month == 1) {
                $prevMonth = 12;
                $prevYear = $year - 1;
            } else {
                $prevMonth = $month - 1;
                $prevYear = $year;
            }
        } else {
            $prevMonth = false;
            $prevYear = false;
        }

        $navigation = array(
            'months' => $months,
            'week' => $week,
            'firstYear' => $firstYear,
            'firstMonth' => $firstMonth,

            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,

            'actualDay' => $daySelected->format('d'),
            'actualMonth' => $month,
            'actualYear' => $year,

            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,

            'lastYear' => $lastYear,
        );

        return array(
            'calendar' => $calendar,
            'navigation' => $navigation,
        );
    }
}
