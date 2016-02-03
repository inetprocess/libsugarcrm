<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Emmanuel Dyan
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

use Inet\SugarCRM\Exception\SugarException;

/**
 * SugarCRM MetadataParser for language and dropdown management
 *
 * @todo Unit Tests
 */
class MetadataParser
{
    /**
     * Prefix that should be set by each class to identify it in logs
     *
     * @var string
     */
    protected $logPrefix;

    /**
     * Logger, inherits PSR\Log and uses Monolog
     *
     * @var Inet\Util\Logger
     */
    protected $log;

    /**
     * SugarCRM entryPoint
     * @var    EntryPoint
     */
    protected $entryPoint;

    /**
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     *
     * @param EntryPoint $entryPoint Enters the SugarCRM Folder
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->entryPoint = $entryPoint;
        $this->log = $this->entryPoint->getLogger();
    }

    /**
     * Add a button to the record view
     * @param    string    $module
     * @param    string    $name
     * @param    string    $acl
     * @param    bool      $divider
     */
    public function addButtonInRecordView($module, $name, $acl = 'view', $divider = false)
    {
        $parser = $this->getParser($module);
        $buttons = $this->getRecordViewButtons($parser, 'actiondropdown');

        // Search if the button exists
        foreach ($buttons as $button) {
            if ($button['name'] === strtolower($name)) {
                throw new SugarException("The button $name already exists in $module");
            }
        }

        if ($divider === true) {
            $buttons[] = array(
                'type' => 'divider',
            );
        }

        // Add the button
        $buttons[] = array(
            'type' => 'rowaction',
            'event' => 'button:' . 'btn' . ucfirst(strtolower($name)) . ':click',
            'name' => strtolower($name),
            'label' => 'LBL_' . strtoupper($name),
            'acl_action' => $acl
        );

        // Add label
        $utils = new Utils($this->entryPoint);
        $utils->addLabel($module, $GLOBALS['current_language'], 'LBL_' . strtoupper($name), $name);

        $this->setRecordViewButtons($parser, 'actiondropdown', $buttons);
    }

    /**
     * Add a button to the record view
     * @param    string    $module
     * @param    string    $name
     */
    public function deleteButtonInRecordView($module, $name)
    {
        $parser = $this->getParser($module);
        $buttons = $this->getRecordViewButtons($parser, 'actiondropdown');

        // Search if the button exists
        $buttonPos = false;
        foreach ($buttons as $pos => $button) {
            if ($button['name'] === strtolower($name)) {
                $buttonPos = $pos;
                break;
            }
        }

        if ($buttonPos === false) {
            throw new SugarException("The button $name does not exist in $module");
        }

        // Remove the button
        unset($buttons[$buttonPos]);

        // Remove label
        $utils = new Utils($this->entryPoint);
        $utils->removeLabel($module, $GLOBALS['current_language'], 'LBL_' . strtoupper($name), $name);

        $this->setRecordViewButtons($parser, 'actiondropdown', $buttons);
    }

    /**
     * Return the parser
     * @param     string    $module
     * @return    AbstractMetaDataParser
     */
    protected function getParser($module)
    {
        $parser = \ParserFactory::getParser('recordview', $module);
        if (empty($parser->_viewdefs['panels'])) {
            throw new SugarException("Can't work with the view, try to redeploy it with the Studio");
        }


        return $parser;
    }

    /**
     * Get the record view buttons defined for a view
     * @param     \AbstractMetaDataParser    $parser
     * @param     string                     $module
     * @param     string                     $type      Buttons Types (such as actiondropdown)
     * @return    array
     */
    protected function getRecordViewButtons(\AbstractMetaDataParser $parser, $type)
    {
        $keyDp = null;
        foreach ($parser->_viewdefs['buttons'] as $key => $buttonsType) {
            if ($buttonsType['type'] === $type) {
                $keyDp = $key;
                break;
            }
        }

        if (is_null($keyDp)) {
            throw new SugarException("Can't find the $type array in record.php");
        }

        return $parser->_viewdefs['buttons'][$keyDp]['buttons'];
    }

    /**
     * Save a new configuration of buttons
     * @param    \AbstractMetaDataParser    $parser
     * @param    string                     $type       Buttons Types (such as actiondropdown)
     */
    protected function setRecordViewButtons(\AbstractMetaDataParser $parser, $type, array $buttons)
    {
        foreach ($parser->_viewdefs['buttons'] as $key => $buttonsType) {
            if ($buttonsType['type'] === $type) {
                $keyDp = $key;
                break;
            }
        }

        $parser->_viewdefs['buttons'][$keyDp]['buttons'] = $buttons;
        $parser->handleSave(false);
    }
}
