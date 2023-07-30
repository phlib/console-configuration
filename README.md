# phlib/console-configuration

[![Code Checks](https://img.shields.io/github/actions/workflow/status/phlib/console-configuration/code-checks.yml?logo=github)](https://github.com/phlib/console-configuration/actions/workflows/code-checks.yml)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/console-configuration.svg?logo=codecov)](https://codecov.io/gh/phlib/console-configuration)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/console-configuration.svg?logo=packagist)](https://packagist.org/packages/phlib/console-configuration)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/console-configuration.svg?logo=packagist)](https://packagist.org/packages/phlib/console-configuration)
![Licence](https://img.shields.io/github/license/phlib/console-configuration.svg)

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
$helper->setDefault(['host' => 'localhost']);
```

OR 

```php
ConfigurationHelper::initHelper(
    $app,
    ['host' => 'localhost'],
    ['filename' => 'service-name.php']
);
```

## Use Case: Autodetect with no command line option
```php
$app = new Application('my-cli');
$app->setCommands(['...']);
$app->getHelperSet()->set(new ConfigurationHelper('config', 'my-config.php'));
$app->run();
```

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
