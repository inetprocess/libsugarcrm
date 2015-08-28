# inetprocess/sugarcrm
That library allows anybody to interact with a SugarCRM application, outside that application.
Suppose you need to do a importation script, running every night, with a very specific set of
transformations or check. Then you need that kind of tools to be able to "Login" to SugarCRM,
create or update beans, do SQL queries, etc ...

# Warning
* Be very careful that Sugar doesn't do a very "clean" job so it's better to deactivate
the strict standards erorr reporting in PHP to be able to use it. When I launch my script, I usualy do something like:
```bash
php -d 'error_reporting=E_ALL & ~E_STRICT'  test.php
```

* If you are not using my classes in another class (if you do like in examples below, directly calling the library),
be more careful: **don't name your variables like sugar does, else you'll overwrite it** (example: _$db_ or _$log_).

* Last but not the least: you'll be able to instanciate the EntryPoint for only one instance of Sugar !
It uses GLOBALS variable such as $GLOBALS['db'] and I let you imagine what will happen
if it's overwritten by another Instance of SugarCRM ;)

# Classes
## Inet\SugarCRM\Application
Gives general information about SugarCRM Installation.
Others classes depends on this one.

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Inet\SugarCRM\Application;

$sugarApp = new Application('/home/sugarcrm/www');
echo $sugarApp->getSugarPath();
if ($sugarApp->isValid()) {
    echo $sugarApp->getVersion();
}
```

## Inet\SugarCRM\EntryPoint
It says where is Sugar and does the basic steps to "login" into SugarCRM.
EntryPoint needs a logger because other classes log a lot of stuff.
The EntryPoint can only be loaded once for the entire program.

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Psr\Log\NullLogger;
use Inet\SugarCRM\Application;
use Inet\SugarCRM\EntryPoint;

if (!EntryPoint::isCreated()) {
    $nullLogger = new NullLogger;
    $sugarApp = new Application('/home/sugarcrm/www');
    // enter sugar
    EntryPoint::createInstance($nullLogger, $sugarApp, '1');
}
$sugarEP = EntryPoint::getInstance();
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

if (!EntryPoint::isCreated()) {
    $nullLogger = new NullLogger;
    $sugarApp = new Application('/home/sugarcrm/www');
    // enter sugar
    EntryPoint::createInstance($nullLogger, $sugarApp, '1');
}
$sugarEP = EntryPoint::getInstance();
// get the DB Class
$inetSugarDB = new DB($sugarEP);

// instanciate the Bean class to retrieve User with id 1
$inetSugarBean = new Bean($sugarEP, $inetSugarDB);
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
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;

// get the DB Class
$inetSugarDB = new DB(EntryPoint::getInstance());
$users = $inetSugarDB->doQuery('SELECT * FROM users');
echo count($users);
```

## Inet\SugarCRM\Utils
Various Utils to create labels, dropdown, encode or decode multiselect, etc...

Usage Example:
```php
<?php
require_once 'vendor/autoload.php';
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\Utils;

// get the Utils Class
$inetSugarUtils = new Utils(EntryPoint::getInstance());
// Convert an array to a multiselect
$convertedArray = $inetSugarUtils->arrayToMultiselect(array('test' => 'inet'));
echo $convertedArray;
```
