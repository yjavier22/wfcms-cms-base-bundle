<?php

namespace Wf\Bundle\CmsBaseBundle\Command;

use Presta\SitemapBundle\Command\DumpSitemapsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Presta\SitemapBundle\Service\Dumper;

/**
 * dumps sitemaps
 *
 * @author cv
 */
class SitemapDumpCommand extends DumpSitemapsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('wf:cms:sitemap:dump')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'The base url', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $dumper \Presta\SitemapBundle\Service\Dumper */
        $dumper = $this->getContainer()->get('presta_sitemap.dumper');

        $host = $input->getOption('host');
        $host = $host ?: getenv('SF_HOST');
        $this->getContainer()->get('router')->getContext()->setHost($host);
//        $dumpersReflection = new \ReflectionClass(get_class($dumper));
        $hostMember = new \ReflectionProperty(get_class($dumper), 'baseUrl');
        $hostMember->setAccessible(true);
        $hostMember->setValue($dumper, 'http://' . $host . '/');

        $targetDir = rtrim($input->getArgument('target'), '/');

        if (!is_dir($targetDir)) {
            throw new \InvalidArgumentException(sprintf('The target directory "%s" does not exist.', $input->getArgument('target')));
        }

        if ($input->getOption('section')) {
            $output->writeln(
                sprintf(
                    "Dumping sitemaps section <comment>%s</comment> into <comment>%s</comment> directory",
                    $input->getOption('section'),
                    $targetDir
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    "Dumping <comment>all sections</comment> of sitemaps into <comment>%s</comment> directory",
                    $targetDir
                )
            );
        }
        $filenames = $dumper->dump($targetDir, $input->getOption('section'));

        if ($filenames === false) {
            $output->writeln("<error>No URLs were added to sitemap by EventListeners</error> - this may happen when provided section is invalid");
            return;
        }

        $output->writeln("<info>Created/Updated the following sitemap files:</info>");
        foreach ($filenames as $filename) {
            $output->writeln("    <comment>$filename</comment>");
        }
    }
}
