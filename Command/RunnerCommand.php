<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Command;

use Cosmologist\Bundle\SymfonyCommonBundle\Type\CallableType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunnerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('symfony-common:runner')
            ->addArgument('file', InputArgument::REQUIRED, 'The class to run')
            ->addArgument('line', InputArgument::REQUIRED, 'The method to run')
            ->setDescription('Executes the code located in the passed file on the passed line')
            ->setHelp('This is useful for debugging code.' . PHP_EOL .
                'If you are using PhpStorm, then an easy way to use this feature is to create an external tool (File-> Settings-> Tools-> External Tool) with parameters:' . PHP_EOL .
                ' - Name: Runner' . PHP_EOL .
                ' - Program: /usr/bin/php' . PHP_EOL .
                ' - Arguments: -d xdebug.remote_autostart=1 -d xdebug.remote_enable=1 bin/console symfony-common:runner $FilePath$ $LineNumber$' . PHP_EOL .
                ' - Working Directory: $ProjectFileDir$' . PHP_EOL .
                PHP_EOL .
                'After that, select Tools -> External Tools -> Runner - the command will try to execute a function or method from where the cursor is currently located.' . PHP_EOL .
                'At this point, execution will not automatically stop at the specified location - you must manually set a breakpoint.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        [$class, $method] = $this->parseClassAndMethod(token_get_all(file_get_contents($input->getArgument('file'))), $input->getArgument('line'));

        $result = call_user_func_array(CallableType::toCallable("$class::$method"), []);

        var_dump($result);
    }

    /**
     * @param array $tokens
     * @param int   $lineNumber
     *
     * @return array
     */
    protected function parseClassAndMethod(array $tokens, int $lineNumber): array
    {
        $lastToken = null;

        $forward = function (&$tokens, $stopOnTokens = []) use (&$lastToken) {
            while ($token = array_shift($tokens)) {
                if (in_array(is_string($token) ? $token : $token[0], $stopOnTokens)) {
                    return $token;
                }

                $lastToken = $token;
            }

            return false;
        };

        $class = $method = null;

        while ($token = $forward($tokens, [T_NAMESPACE, T_CLASS, T_FUNCTION])) {
            if ($token[2] > $lineNumber) {
                break;
            }

            if ($token[0] === T_NAMESPACE) {
                while ($subtoken = $forward($tokens, [T_STRING, T_NS_SEPARATOR, ';'])) {
                    if ($subtoken === ';') {
                        break;
                    }

                    $class .= $subtoken[1];
                }
            } elseif ($token[0] === T_CLASS && $lastToken[0] !== T_PAAMAYIM_NEKUDOTAYIM) {
                $class .= '\\' . $forward($tokens, [T_STRING])[1];
            } elseif ($token[0] === T_FUNCTION) {
                $method = $forward($tokens, [T_STRING])[1];
            }
        }

        return [$class, $method];
    }
}
