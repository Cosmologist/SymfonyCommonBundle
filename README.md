# SymfonyCommonBundle
Useful features for Symfony, Doctrine, Twig etc.

## Pass PHP functions and callables into Twig
```yaml
# app/config/config.yml
  
symfony_common:
  twig:
    php_extension:
      filters:
          - strip_tags # register php "strip_tags" function as twig "strip_tags" filter
          -
            foo_bar: # register static method MyApp\Foo::bar as twig filter "foo_bar"
                  - MyApp\Foo
                  - bar
      functions:
          - time # register php "time" function as twig "time" function
```