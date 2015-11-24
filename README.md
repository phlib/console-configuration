# phlib/console-configuration

[![Build Status](https://img.shields.io/travis/phlib/console-configuration/master.svg)](https://travis-ci.org/phlib/console-configuration)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/console-configuration.svg)](https://packagist.org/packages/phlib/console-configuration)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/console-configuration.svg)](https://packagist.org/packages/phlib/console-configuration)

Console Configuration Helper implementation.

## Install

Via Composer

``` bash
$ composer require phlib/console-configuration
```

## Configuration Helper

Adds the ```-c path/to/config.php``` parameter to the console application and makes it easily accessible to all 
commands. This is most useful for third party libraries which rely on the configuration being specified from the 
options.

### Basic Usage

```php
// your usual cli setup script

use Phlib\ConsoleConfiguration\Helper\ConfigurationHelper;

$app = new Application('my-cli');
$app->setCommands(['...']);
$helper = ConfigurationHelper::initHelper(
    $app,
    $default = ['host' => 'localhost'],
    $options = []
);
$app->run();

```

```php
class MyCommand extends Command
{
    '...'

    protected function createMyObjectInstance()
    {
        $config = $this->getHelper('configuration')->fetch();
        return new MyObjectInstance($config);
    }
}
```

### Options
You can specify some options to setup the helper through the ```initHelper``` static method.

|Name|Type|Default|Description|
|----|----|-------|-----------|
|`name`|*String*|`'config'`|The name of the option on the command line.|
|`abbreviation`|*String*|`'c'`|The abbreviation of the option on the command line.|
|`description`|*String*|`'...'`|The associated description for the option.|
|`filename`|*String*|`'cli-config.php'`|The filename that will be detected if no name is specified.|

```php
ConfigurationHelper::initHelper($app, null, [
    'name' => 'config-option',
    'filename' => 'my-cli-config.php',
]);
```

## Defaults
With no default (```null```) specified, the fetch method returns ```false```. You can specify a default configuration 
using the ```setDefault``` method or through the ```initHelper``` static method.

```php
$helper->setDefault(['my' => 'default']);
```

## Use Case: Autodetect with no command line option
```php
$app = new Application('my-cli');
$app->setCommands(['...']);
$app->getHelperSet()->set(new ConfigurationHelper('config', 'my-config.php'));
$app->run();
```
