<?php
/**
 * InetETL
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Emmanuel Dyan
 * @copyright 2005-2015 iNet Process
 * @package iNetETL
 * @license GNU General Public License v2.0
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

/**
 * Extends bean factory class to clean chached beans.
 * Useful for older versions for PHP that don't implement that method
 */
class BeanFactoryCache extends \BeanFactory
{
    public static function clearCache()
    {
        self::$loadedBeans = array();
        self::$total = 0;
        self::$hits = 0;
    }
}
