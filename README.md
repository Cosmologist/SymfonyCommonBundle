# SymfonyCommonBundle
Useful features for Symfony, Doctrine, Twig etc.

## Debug
### Easy way to start debug with RunnerCommand
If you are using *PhpStorm*, then an easy way to use this feature is to create an external tool (*File-> Settings-> Tools-> External Tools*) with parameters:
 - Name: Runner
 - Program: */usr/bin/php*
 - Arguments: *-d xdebug.remote_autostart=1 -d xdebug.remote_enable=1 bin/console symfony-common:runner $FilePath$ $LineNumber$*
 - Working Directory: *$ProjectFileDir$*
 
After that, select *Tools -> External Tools -> Runner* - the command will try to execute a function or method from where the cursor is currently located.  
At this point, execution will not automatically stop at the specified location - you must manually set a breakpoint. 


## Doctrine

### Extra DBAL events
*Cosmologist\Bundle\SymfonyCommonBundle\Doctrine\ExtraConnection* is Doctrine DBAL-connection wrapper that add useful features and methods to DBAL.

#### Activation
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

#### Additional DBAL-events
postBeginTransaction, postCommit, postRollback

#### Helper methods
*Connection::fetchAllIndexed* prepares and executes an SQL query and returns the result as an associative array, each row in the result set array is indexed by the value of the first column.
```php
$connection->fetchAllIndexed('SELECT id, name FROM users');
// 1  => [id: 1, name: Ivan]
// 7  => [id: 7, name: Vasiliy]
// ...
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

Determine if the object or FQCN is a Doctrine entity (under Doctrine control) or not
```php
$utils->isEntity($entity);
```

Compute a query results count
```php
$utils->getQueryResultCount($queryBuilder);
```

Get the readable alias for the doctrine entity
```php
$this->getEntityAlias(FooBundle\Entity\Bar\Baz::class); // 'foo.bar.baz'
$this->decodeEntityAlias('foo.bar.baz'); // 'FooBundle\Entity\Bar\Baz::class'
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

## Dependency Injection

### Convenient way to get a Reference to a Doctrine DBAL connection
```php
DependencyInjectionUtils::getDoctrineDbalConnectionReference('default'); // doctrine.dbal.default_connection
```

### Convenient way to get a Reference to a Doctrine EntityManager
```php
DependencyInjectionUtils::getDoctrineOrmEntityManagerReference('default'); // doctrine.orm.default_entity_manager
```

### Store key as attribute in configuration
Useful for:
- to simplify your configuration
- to avoid problem of losing the key when you merge config across files ([Symfony Issue #29817](https://github.com/symfony/symfony/issues/29817))

Usage:
```php
# AppBundle\DependencyInjection\Configuration.php
...
use Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection\DependencyInjectionUtils;
...
->arrayNode('events')
    ->beforeNormalization()
        ->always(ConfigurationUtils::useKeyAsAttribute('server'))
    ->end()
    ->prototype('array')
...
```
Config like this:
```
something:
  servers:
     serverA:
       username: userA
       password: passwordA
     serverB:
       username: userB
       password: passwordB
```
comes out like:
```
servers [
   serverA => [username: userA, password: passwordA, server: serverA],
   serverB => [username: userB, password: passwordB, server: serverB],
 ]
```

#### ServiceBridge
A convenient way to dynamically access symfony services.

#### Call Symfony services over HTTP 

##### Include routing.yml
```
# app/config/routing.yml
admin.service:
  resource: "@SymfonyCommonBundle/Resources/config/routing.yml"
  prefix:   /admin
```

##### Send POST-request
URL example:  
```yourdomain.com/bridge/mybundle.foo/bar```  
or  
```yourdomain.com/bridge/MyBundle\Foo/bar```

- **/bridge** is a ServiceBridge route suffix
- **mybundle.foo** (or **MyBundle\Foo**) is a service name
- **process** is service method name

Method arguments must be passed as POST parameters.
ServiceBridge automatically fetches a Doctrine entity if the method expects an argument of the entity (the hint type of the argument).
  
The method arguments should be passed as POST-parameters.  
ServiceBridge fetch entity from Doctrine automatically, by the identifier from request, if method expects entity argument (argument type-hint).

**Caution**: Use security [access_control](https://symfony.com/doc/current/security/access_control.html) option to restrict access to the service controller.

##### Return types to response types map
- array|object -> json
- binary string -> response with content-disposition=attachment and binary content-type
- another scalar -> simple response

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

## Monolog
### Monolog activation strategy for Symfony 3.x to skip the 404 HttpException records.
Monolog NotFoundActivationStrategy (activation_strategy, excluded_404s and excluded_http_codes options) does not work in Symfony 3.0 as currently monolog-bundle injects a reference to request from the service container into the NotFoundActivationStrategy.

- [Issue #166](https://github.com/symfony/monolog-bundle/issues/166#issuecomment-221725696)

#### TODO
 - Other HTTP-codes support
 - Configure default actionLevel value via Configuration 

#### Usage
```yaml
    main:
      type:                fingers_crossed
      handler:             grouped
       activation_strategy: symfony_common.monolog.fingers_crossed.ignore_http_not_found_activation_strategy
```

## BrowserKit
Add the specified HTTP-header to the prepared BrowserKit request
```php
use Cosmologist\Bundle\SymfonyCommonBundle\BrowserKit\BrowserKitUtils;

/** @var \Symfony\Component\BrowserKit\Client $cient */

BrowserKitUtils::addHeader($client, 'header-name', 'header-value');
````

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
