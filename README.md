# SymfonyCommonBundle
Useful features for Symfony, Doctrine, Twig etc.

## Doctrine

### Extra DBAL events
*Cosmologist\Bundle\SymfonyCommonBundle\Doctrine\ExtraConnection* is Doctrine DBAL-connection wrapper that adds a new "postCommit" event to the Doctrine event system.

Add the wrapper_class parameter to the Doctrine DBAL connection configuration in config.yml to use:
```yaml
default:
    driver: pdo_mysql
    dbname: ~
    user: ~
    password: ~
    host: ~
    wrapper_class: \Cosmologist\Bundle\SymfonyCommonBundle\Doctrine\ExtraConnection
```  

### Doctrine Utils
Get Doctrine utils
```php
$utils = $container->get('symfony_common.doctrine.utils');
```

Simple way to get doctrine entity metadata
```php
$utils->getClassMetadata($entity);
$utils->getClassMetadata(Entity::class);
```

Get entity identifier field name (does not support multiple identifiers - throws DoctrineUtilsException)
```php
$utils->getEntitySingleIdentifierField($entity);
$utils->getEntitySingleIdentifierField(Entity::class);
```
Get entity identifier value (does not support multiple identifiers - throws DoctrineUtilsException)
```php
$utils->getEntitySingleIdentifierValue($entity);
```

## Routing
Forwards to another URI.  
Like *Symfony\Bundle\FrameworkBundle\Controller\Controller::forward*, but using URI.
```php
$utils = $container->get('symfony_common.routing.utils');

$utils->forwardToUri('/products/programmers-t-shirts');
// or
$utils->forwardToUri('https://myshop.com/products/programmers-t-shirts');
```

## Security
### ROLE_SUPER_USER
*Cosmologist\Bundle\SymfonyCommonBundle\Security\Voter\SuperUserRoleVoter* adds a special role "ROLE_SUPER_USER" which effectively bypasses any, and all security checks.

## Symfony DI

### Call any Symfony Service over HTTP 
  
Send POST-request to specified URL like this:
```example.com/admin/service/appbundle.service.processor/process```  
where *appbundle.service.processor* is service name and *process* is name of method.  
The method arguments must be passed as POST-parameters.  
If the service expects the entity as argument (type-hint exists) - pass the entity identifier instead, the suitable entity will be loaded automatically (Doctrine is used, but you can use the custom loader in the future).

Don't forget to include the routing-file to enable the controller:
```
# app/config/routing.yml
admin.service:
    resource: "@SymfonyCommonBundle/Resources/config/routing.yml"
    prefix:   /admin
```

**Caution**: Use security [access_control](https://symfony.com/doc/current/security/access_control.html) option to restrict access to the service controller.

### Static access to the service container from anywhere
```php
use Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection\ContainerStatic;

ContainerStatic::getContainer();
ContainerStatic::get('serivice_id');
ContainerStatic::getParameter('parameter_id');
```

## Twig
### Inject any callable to the Twig
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

See also: [umpirsky/twig-php-function](https://github.com/umpirsky/twig-php-function)

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

Define parameters for dist:
```yaml
# app/config/config.yml
symfony_common:
    external_config:
        superbackup:
            name: '%doctrine.connection.default.database_name%'
            user: '%doctrine.connection.default.database_user%'
            password: '%doctrine.connection.default.database_password%'
```

Run dumper:
```
php app/console symfony-common:external-config:dump superbackup backup.yml.twig --env=prod --no-debug
```

And config should be dumped to *app/cache/prod/external_config/backup.yml* (without *.twig* extension)
