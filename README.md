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

## Dump configuration files for external related applications
Useful when you want to deduplicate application parameters (like db-connections, paths etc) and store related external applications configurations (backup-systems, crontab etc) inside the project.

### Example
Configuration for abstract backup-system

Configuration file template:
```yaml
# app/config/external/dist/backup.yml.twig
backup:
    mysql:
        {{ name }}
        {{ user }}
        {{ password }}
    compress: yes
    sync-to: amazon-s3
```

Twig globals configuration:
```yaml
# app/config/config.yml
twig:
    globals:
        superbackup:
            name: '%doctrine.connection.default.database_name%'
            user: '%doctrine.connection.default.database_user%'
            password: '%doctrine.connection.default.database_password%'
```

Run dumper:
```
php app/console symfony-common:external-config:dump superbackup backup.yml.twig --env=prod --no-debug
```

And config should be dumped to app/cache/prod/external_config/backup.yml (without *.twig* extension)
