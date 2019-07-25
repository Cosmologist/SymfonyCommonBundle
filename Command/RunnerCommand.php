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
            ->addArgument('line', InputArgument::REQUIRED, 'The method to run');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        [$class, $method] = $this->parseClassAndMethod(token_get_all(file_get_contents($input->getArgument('file'))), $input->getArgument('line'));

        var_dump(call_user_func_array(CallableType::toCallable("$class::$method"), []));
    }

    /**
     * @param array $tokens
     * @param int   $lineNumber
     *
     * @return array
     */
    protected function parseClassAndMethod(array $tokens, int $lineNumber): array
    {
        $forward = function (&$tokens, $stopOnTokens = []) {
            while ($token = array_shift($tokens)) {
                if (in_array(is_string($token) ? $token : $token[0], $stopOnTokens)) {
                    return $token;
                }
            }

            return false;
        };

        $class = $method = null;

        while ($token = $forward($tokens, [T_NAMESPACE, T_CLASS, T_FUNCTION])) {

            if ($token[0] === T_NAMESPACE) {
                while ($subtoken = $forward($tokens, [T_STRING, T_NS_SEPARATOR, ';'])) {
                    if ($subtoken === ';') {
                        break;
                    }

                    $class .= $subtoken[1];
                }
            } elseif ($token[0] === T_CLASS) {
                $class .= '\\' . $forward($tokens, [T_STRING])[1];
            } elseif ($token[0] === T_FUNCTION) {
                $method = $forward($tokens, [T_STRING])[1];
            }

            if ($token[2] >= $lineNumber) {
                break;
            }
        }

        return [$class, $method];
    }
}
