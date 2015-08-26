# inetprocess/sugarcrm
That library allows anybody to interact with a SugarCRM application, outside that application. Suppose you need to do a importation script, running every night, with a very specific set of transformations or check. Then you need that kind of tools to be able to "Login" to SugarCRM, create or update beans, do SQL queries, etc ...

# Warning
* Be very careful that Sugar doesn't do a very "clean" job so it's better to deactivate the strict standards erorr reporting in PHP to be able to use it. When I launch my script, I usualy do something like:
```bash
php -d 'error_reporting=E_ALL & ~E_STRICT'  test.php
```

* If you are not using my classes in another class (if you do like in examples below, directly calling the library), be more careful: **don't name your variables like sugar does, else you'll overwrite it** (example: _$db_ or _$log_).

* Last but not the least: you'll be able to instanciate the EntryPoint for only one instance of Sugar ! It uses GLOBALS variable such as $GLOBALS['db'] and I let you imagine what will happen if it's overwritten by another Instance of SugarCRM ;)

# Classes
## Inet\SugarCRM\EntryPoint
That's the class all other classes need. It says where is Sugar and does the basic steps to "login" into SugarCRM. EntryPoint needs a logger because other classes log a lot of stuff.

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Psr\Log\NullLogger;
use Inet\SugarCRM\EntryPoint;

$nullLogger = new NullLogger;
// enter sugar
$sugarInetEP = new EntryPoint($nullLogger, '/home/sugarcrm/www', '1');
```

## Inet\SugarCRM\Application
Gives general information about SugarCRM Installation. For now, it only gives the same path we set on $entryPoint.

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Psr\Log\NullLogger;
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\Application;

$nullLogger = new NullLogger;
// enter sugar
$sugarInetEP = new EntryPoint($nullLogger, '/home/sugarcrm/www', '1');
$sugarApp = new Application($sugarInetEP);
echo $sugarApp->getSugarPath();
```

## Inet\SugarCRM\Bean
Most complete class to :
* Get a list of available modules (_getBeansList()_)
* Get an Bean (_getBean()_)
* Create a Bean (_newBean()_)
* Get a list of records for a module (_getList()_)
* ...

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Psr\Log\NullLogger;
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;
use Inet\SugarCRM\Bean;

$nullLogger = new NullLogger;
// enter sugar
$sugarInetEP = new EntryPoint($logger, '/home/sugarcrm/www', '1');
// get the DB Class
$inetSugarDB = new DB($sugarInetEP);

// instanciate the Bean class to retrieve User with id 1
$inetSugarBean = new Bean($sugarInetEP, $inetSugarDB);
$adminUser = $inetSugarBean->getBean('Users', 1);
echo $adminUser->name;
```

## Inet\SugarCRM\BeanFactoryCache
Don't use it directly, it's directly used by Inet\SugarCRM\Bean to clean the cache during long loops.

## Inet\SugarCRM\DB
Useful to query SugarCRM DB directly.

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Psr\Log\NullLogger;
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;

$nullLogger = new NullLogger;
// enter sugar
$sugarInetEP = new EntryPoint($logger, '/home/sugarcrm/www', '1');
// get the DB Class
$inetSugarDB = new DB($sugarInetEP);
$users = $inetSugarDB->doQuery('SELECT * FROM users');
echo count($users);
```

## Inet\SugarCRM\Utils
Various Utils to create labels, dropdown, encode or decode multiselect, etc...

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Psr\Log\NullLogger;
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\Utils;

$nullLogger = new NullLogger;
// enter sugar
$sugarInetEP = new EntryPoint($logger, '/home/sugarcrm/www', '1');
// get the Utils Class
$inetSugarUtils = new Utils($sugarInetEP);
// Convert an array to a multiselect
$convertedArray = $inetSugarUtils->arrayToMultiselect(array('test' => 'inet'));
echo $convertedArray;
```
