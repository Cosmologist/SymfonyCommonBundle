<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Command;

use Cosmologist\Gears\ArrayType;
use Cosmologist\Gears\FileSystem as GearsFilesystem;
use Cosmologist\Gears\StringType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class DumpExternalConfigCommand extends ContainerAwareCommand
{
    /**
     * Section name in the twig globals for external config paramters
     */
    const TWIG_GLOBALS_CONFIG_SECTION = 'external_config';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('symfony-common:external-config:dump')
            ->setDescription('Dump external config to cache directory with parameters from config (see twig.globals)')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of twig.globals config section with parameters for the dist config file')
            ->addArgument('dist', InputArgument::REQUIRED, 'Path to dist config, absolute or relative path (relative path start directory is app/config/external/dist)')
            ->addArgument('to', InputArgument::OPTIONAL, 'Dump config to specified path (like /path/file). If missed then path will be \'%kernel.cache_dir%/external_config/dist_config_filename\'%');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $dist = $input->getArgument('dist');
        $to   = $input->getArgument('to');

        $twig       = $this->getContainer()->get('twig');
        $filesystem = new SymfonyFilesystem();

        $appDir   = $this->getContainer()->getParameter('kernel.root_dir');
        $cacheDir = $this->getContainer()->getParameter('kernel.cache_dir');

        $globals = $twig->getGlobals();
        if (!isset($globals[self::TWIG_GLOBALS_CONFIG_SECTION][$name])) {
            throw new RuntimeException("Define external config parameters at 'twig.globals.$name' in the application config.yml");
        }

        $parameters = $globals[self::TWIG_GLOBALS_CONFIG_SECTION][$name];
        if (!is_array($parameters) || !ArrayType::checkAssoc($parameters)) {
            throw new RuntimeException("Parameters for '$name' external config should be defined as assoc array");
        }

        if (!$filesystem->isAbsolutePath($dist)) {
            $dist = GearsFilesystem::joinPaths([$appDir, 'config', 'external', 'dist', $dist]);
        }

        $config = $twig->render($dist, $parameters);

        if (!$to) {
            $to = GearsFilesystem::joinPaths([$cacheDir, self::TWIG_GLOBALS_CONFIG_SECTION, basename($dist)]);
            if (StringType::endsWith($to, '.twig')) {
                $to = substr($to, 0, -5);
            }
        }

        $filesystem->dumpFile($to, $config);

        $output->writeln("'$name' config dumped to '$to'");

        return 0;
    }
}
