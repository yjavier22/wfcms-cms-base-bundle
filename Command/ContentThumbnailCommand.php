<?php

namespace Wf\Bundle\CmsBaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * A console command for generating content thumbnails
 */
class ContentThumbnailCommand extends ContainerAwareCommand
{
    protected $contentThumbnailGenerator;
    
    /**
     * The imagine filter that should be applied, if any
     * @var string
     */
    protected $imagineFilter;
    
    /**
     * By default ($force == false), if a thumbnail file already exists and is newer than the 
     * date when the entity was last updated, the thumbnail for that entity will be skipped
     * @var boolean
     */
    protected $force = false;
    
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('wf:cms:content:thumbnail')
            ->setDescription('Generates thumbnails for content')
            //-e option is taken "globally" by symfony for environmnet
            ->addArgument('host', InputArgument::REQUIRED, 'The command line doesn\'t know about the host of the app - you MUST provide it', null)
            ->addArgument('filter', InputArgument::OPTIONAL, 'The imagine filter that should be aplied to thumbnails', null)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the generation of the thumbnail, even if the existing thumbnail is newer than the entity was last updated')
            ->setHelp(<<<EOF
Generates thumbnails for content (cover page/articles/etc...)
EOF
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->imagineFilter = $input->getArgument('filter');
        
        $this->force = $input->getOption('force');
        
        $this->contentThumbnailGenerator = $this->getContainer()->get('wf_cms.content.thumbnail_generator');
        $this->getContainer()->get('router')->getContext()->setHost($input->getArgument('host'));
        
        $ts = microtime(1);
        
        $tsa = microtime(1);
        $output->writeln(sprintf('<info>Generating pages thumbnails(force %b)...</info>', $this->force));
        $this->generatePages($output);
        $output->writeln(sprintf('<comment>Finished generating pages in %.2f seconds</comment>', microtime(1) - $tsa));
    }
    
    protected function generatePages(OutputInterface $output)
    {
        $pagesRepository = $this->getContainer()->get('wf_cms.repository.page');
        $pages = $pagesRepository->findAll();
        
        $output->writeln(sprintf('Found %d pages', count($pages)));
        
        foreach ($pages as $page) {
            $output->write(sprintf('Generating cover for page %s... ', $page->getTitle()));
            
            $ts = microtime(1);
            $this->contentThumbnailGenerator->page($page, $this->imagineFilter, $this->force);
            $output->writeln(sprintf('done in %.2f seconds', microtime(1) - $ts));
        }
    }
}
