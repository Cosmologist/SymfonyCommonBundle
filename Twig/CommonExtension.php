<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CommonExtension extends AbstractExtension
{
    /**
     * * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('ceil', 'ceil'), // required by pagination.html.twig
            new TwigFilter('htmlAttributes', [$this, 'mapToHtmlAttributes'], ['is_safe' => ['html']])
        ];
    }

    /**
     * Build html-attributes string from attributes map
     *
     * @see https://github.com/timkelty/htmlattributes-craft/blob/master/htmlattributes/twigextensions/HtmlAttributesTwigExtension.php#L26
     * @see https://github.com/timkelty/htmlattributes-craft
     *
     * @param array $attributes Attributes map
     *
     * @return string
     */
    public function mapToHtmlAttributes(array $attributes): string
    {
        $str = trim(implode(' ', array_map(function ($attrName) use ($attributes) {
            $attrVal = $attributes[$attrName];
            $quote   = '"';
            if (is_null($attrVal) || $attrVal === true) {
                return $attrName;
            } elseif ($attrVal === false) {
                return '';
            } elseif (is_array($attrVal)) {
                switch ($attrName) {
                    case 'class':
                        $attrVal = implode(' ', array_filter($attrVal));
                        break;
                    case 'style':
                        array_walk($attrVal, function (&$val, $key) {
                            $val = $key . ': ' . $val;
                        });
                        $attrVal = implode('; ', $attrVal) . ';';
                        break;
                    // Default to json, for data-* attributes
                    default:
                        $quote   = '\'';
                        $attrVal = json_encode($attrVal);
                        break;
                }
            } else {
                return $attrName . '="' . htmlspecialchars($attrVal, ENT_COMPAT) . '"';
            }

            return $attrName . '=' . $quote . $attrVal . $quote;
        }, array_keys($attributes))));

        if (strlen($str) > 0) {
            $str = ' ' . $str;
        }

        return $str;
    }

    /**
     * {@inheritdoc};
     */
    public function getName()
    {
        return 'symfony_common_common_extension';
    }
}
