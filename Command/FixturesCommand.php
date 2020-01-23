<?php

namespace Wf\Bundle\CmsBaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Wf\Bundle\CommonBundle\Util\Lipsum;

/**
 * Description of FixturesCommand
 *
 * @author cv
 */
class FixturesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('wf:cms:add:fixt')
             ->setDescription('Add fixtures')
             ->addOption('pages', 'p', InputOption::VALUE_OPTIONAL, 'How many pages', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        /* @var $pm \Wf\Bundle\CmsBaseBundle\Manager\PageManager */;
        $pm = $this->getContainer()->get('wf_cms.page_manager');
        $pages = $input->getOption('pages');
        $pageClass = $this->getContainer()->get('wf_cms.repository.page_article')->getClassName();
        $categories = $this->getCategories();
        $authors = $this->getAuthors();
        for($i = 0; $i < $pages; $i++) {
            /* @var $page \Wf\Bundle\CmsBaseBundle\Entity\Page */
            $page = new $pageClass();
            $pm->setupPageEntity($page);
            $page->setAuthor(Lipsum::getRandArray($authors));
            $page->setCategory(Lipsum::getRandArray($categories));
            $cats = array();
            for($j = 0; $j < mt_rand(2, 5); $j++) {
                $cats[] = Lipsum::getRandArray($categories);
            }
            $cats = array_unique($cats);
            $page->setCategories(array_unique($cats));
            $page->setTemplate('default');
            $page->setTitle('[FIXT] ' . Lipsum::getText(mt_rand(2, 10)));
            $page->setCreatedAt($this->getRandomDate(365));
            $page->setUpdatedAt($this->getRandomDate(365));
            $page->setPublishedAt($this->getRandomDate(365));
            $page->setPublished();
            $em->persist($page);
            if ($i && $i % 100 == 0) {
                $output->writeln('Written 100 pages');
                $em->flush();
            }
        }
        $em->flush();
    }

    protected function getCategories()
    {
        $categoryRepository = $this->getContainer()->get('wf_cms.repository.category');
        return $categoryRepository->findAll();
    }

    protected function getAuthors()
    {
        $userManager = $this->getContainer()->get('wf_cms.repository.user');
        return $userManager->findAll();
    }

    protected function getRandomDate($pastDays, $futureDays = 0)
    {
        $now = time();
        $min = $now - $pastDays * 24 * 3600;
        $max = $now + $futureDays * 24 * 3600;
        $random = mt_rand($min, $max);
        return new \DateTime('@' . $random);
    }
}
