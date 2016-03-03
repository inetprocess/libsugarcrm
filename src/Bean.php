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

use Inet\SugarCRM\Exception\UpdateBeanException;

/**
 * SugarCRM Beans Tools: search, update, create, etc...
 *
 * @todo Unit Tests
 */
class Bean
{
    /**
     * Constants for saveMode of updateBean method.
     */
    const MODE_DRY_RUN = 0;
    const MODE_CREATE = 1;
    const MODE_UPDATE = 2;
    const MODE_CREATE_WITH_ID = 4;

    /**
     * Constants for return of updateBean method
     */
    const SUGAR_FIELDS_NOT_MODIFIED = -2;
    const SUGAR_NOTCHANGED = 0;
    const SUGAR_CREATED = 1;
    const SUGAR_UPDATED = 2;

    /**
     * Constants for pretty display of beans data
     */
    const BOOL_TRUE = "\xE2\x9C\x94";  // Unicode 'HEAVY CHECK MARK' (U+2714)
    const BOOL_FALSE = "\xE2\x9C\x95"; // Unicode 'MULTIPLICATION X' (U+2715)


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
     * SugarCRM User Bean
     *
     * @var \User
     */
    protected $currentUser;

    /**
     * Beans List for that SugarCRM Instance
     *
     * @var array
     */
    protected $beanList; // Current Module Name

    /**
     * SugarCRM DB Object
     *
     * @var Mysqli
     */
    protected $db;

    /**
     * List of fields for that SugarCRM modules (multidimensional array: [$module][$lang])
     *
     * @var array
     */
    protected $moduleFields = array();
    /**
     * List of Relationships for SugarCRM modules (multidimensional array: [$module][$type])
     *
     * @var array
     */
    protected $moduleRels = array();

    /**
     * Number of Loops since I haven't cleaned the memory
     *
     * @var integer
     */
    protected $loopWithoutCleaningMemory = 0;

    /**
     * Last ID that has been updated or created via updateBean
     * @var    string
     */
    protected $lastUpdatedId = null;


    /**
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     *
     * @param EntryPoint $entryPoint Enters the SugarCRM Folder
     * @param DB         $db         Get the SugarCRM DB and make queries
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->log = $entryPoint->getLogger();

        $this->currentUser = $entryPoint->getCurrentUser();

        $this->beanList = $entryPoint->getBeansList();
        $this->db = new DB($entryPoint);
    }

    public function getLogger()
    {
        return $this->log;
    }

    /**
     * Get the bean list
     *
     * @return array
     */
    public function getBeansList()
    {
        return $this->beanList;
    }

    /**
     * Get a Bean from SugarCRM
     *
     * @param string  $module   Module's name
     * @param string  $id       UUID
     * @param array   $params   list of params
     * @param boolean $deleted  Retrieve the bean even if it's deleted
     * @param boolean $useCache
     *
     * @throws \InvalidArgumentException
     *
     * @return SugarBean SugarCRM Bean
     */
    public function getBean($module, $id = null, $params = array(), $deleted = true, $useCache = false)
    {
        if ($useCache && class_exists('BeanFactory')) {
            return \BeanFactory::getBean($module, $id, $params, $deleted);
        }

        // If I use an old version of SugarCRM, do exactly what BeanFactory does
        // Check if params is an array, if not use old arguments
        if (isset($params) && !is_array($params)) {
            $params = array('encode' => $params);
        }

        // Pull values from $params array
        $encode = isset($params['encode']) ? $params['encode'] : true;
        $deleted = isset($params['deleted']) ? $params['deleted'] : $deleted;
        // Module exists? Load it
        if (!array_key_exists($module, $this->beanList)) {
            throw new \InvalidArgumentException($module . ' does not exist in SugarCRM, I cannot retrieve anything');
        }

        $beanClass = $this->beanList[$module];
        $bean = new $beanClass();
        if (!is_null($id)) {
            $this->getLogger()->debug($this->logPrefix . "Retrieving $module with ID '$id' (deleted = $deleted)");
            // to change the parent bean, but not the related (e.g. change Account Name of Opportunity)
            if (!empty($params['disable_row_level_security'])) {
                $bean->disable_row_level_security = true;
            }
            $result = $bean->retrieve($id, $encode, $deleted);
            if (is_null($result)) {
                $this->getLogger()->info($this->logPrefix . 'Nothing to retrieve.');

                return false;
            }
        }

        return $bean;
    }

    /**
     * Create a new bean (Wrapper for BeanFactory and for old SugarCRM versions)
     *
     * @param string  $module   Module's name
     *
     * @return \SugarBean
     */
    public function newBean($module)
    {
        return $this->getBean($module);
    }

    /**
     * Delete a bean
     *
     * @param string  $module   Module's name
     * @param string  $id       UUID
     *
     */
    public function deleteBean($module, $id, $disableSecurity = false)
    {
        $bean = $this->getBean($module, $id, array('disable_row_level_security' => $disableSecurity));
        if ($bean === false) {
            throw new \InvalidArgumentException("Can't delete that record.");
        }
        $bean->mark_deleted($id);
    }

    /**
     * Get a list of records directly from the database
     *
     * @param string  $module  Module's name
     * @param array   $where
     * @param integer $limit
     * @param integer $offset
     * @param integer $deleted Get Deleted Records only
     *
     * @throws \InvalidArgumentException
     *
     * @return array List of records found
     */
    public function getList($module, $where = array(), $limit = 100, $offset = 0, $deleted = 0)
    {
        $this->getLogger()->debug($this->logPrefix
            . "__getList : module = $module | offset = $offset | limit = $limit");

        $records = array();
        // Get the beans and build the WHERE
        $oBeans = $this->getBean($module);

        if (count($where) > 0) {
            $where = "{$oBeans->table_name}." . implode(" AND {$oBeans->table_name}.", $where);
        } else {
            $where = '';
        }
        $this->getLogger()->debug($this->logPrefix . "I'll add the where: $where");

        // First the deleted if asked
        $aDeletedBeans = array();
        if ($deleted == 1) {
            $aDeletedBeans = $oBeans->get_list('', $where, $offset, $limit, -1, 1);
            $aDeletedBeans = $aDeletedBeans['list'];
            $limit-= count($aDeletedBeans);
        }
        $this->getLogger()->debug($this->logPrefix . '__getList : got ' . count($aDeletedBeans) . ' deleted records.');

        // Get the non-deleted rows if I have less than limit - deleted retrieved
        $aNotDeletedBeans = array();
        if ($limit > 0) {
            $aNotDeletedBeans = $oBeans->get_list('', $where, $offset, $limit, -1, 0);
            $aNotDeletedBeans = $aNotDeletedBeans['list'];
        }
        $this->getLogger()->debug($this->logPrefix
            . '__getList : got ' . count($aNotDeletedBeans) . ' NOT deleted records.');

        // Merge everything
        $aBeans = array_merge($aNotDeletedBeans, $aDeletedBeans);

        $this->getLogger()->debug($this->logPrefix
            . '__getList : got ' . count($aBeans) . " records (deleted = $deleted)");
        // change the deleted value because it's not the same for getBean
        $deleted = ($deleted == 1 ? false : true);
        foreach ($aBeans as $oBean) {
            // ReRetrieve to have the fetched row
            // It's not the best way but I need raw data
            $sugarId = $oBean->id;
            $oBean = $this->getBean($module, $sugarId, true, $deleted);
            $records[] = $oBean;

            // Clean Memory
            $this->cleanMemory();
        }

        $this->getLogger()->debug($this->logPrefix . '__getList : Sending back ' . count($records) . ' records');

        return $records;
    }

    /**
     * Search a bean from a specific module and with WHERE criteras
     *
     * @param string  $module       Sugar's Module name
     * @param array   $searchFields List of fields where to search with their value
     * @param boolean $deleted      Search for deleted record
     *
     * @throws \Exception|\RuntimeException
     *
     * @return array List of Records
     */
    public function searchBeans($module, array $searchFields, $deleted = 0)
    {
        // Search the related record ID
        $sugarBean = $this->getBean($module);
        foreach ($searchFields as $searchField => $externalValue) {
            // Check the the fields are defined correctly
            if (!isset($sugarBean->field_name_map[$searchField])) {
                $msg = "{$searchField} ($externalValue) not in Sugar for module $module, can't search on it";
                throw new \RuntimeException($msg);
            }
        }

        // Try to get the records with the same DB ID
        // what is the parent sql table ? by default the normal one
        // but maybe the _cstm ?
        $whereCriteras = array();
        $moduleFields = $this->getModuleFields($module);
        foreach ($searchFields as $searchField => $externalValue) {
            // Search my field in the module fields
            $searchField = $moduleFields[$searchField]['Table'] . '.' . $searchField;
            $whereCriteras[] = "$searchField = '$externalValue'";
        }

        $where = implode(' AND ', $whereCriteras);
        $msg = "Searching a record from '{$module}' with $where (deleted = {$deleted})";
        $this->getLogger()->info($this->logPrefix . $msg);
        $aList = $sugarBean->get_list('', $where, 0, -1, -1, $deleted);

        // Clean Memory
        $this->cleanMemory();

        return $aList['list'];
    }

    /**
     * Update a SugarCRM Bean
     *
     * @param \SugarBean $bean
     * @param array      $data     Array of field => value
     * @param integer    $saveMode DRY_RUN / CREATE / UPDATE / CREATE_WITH_ID
     *
     * @throws Inet\SugarCRM\Exception\UpdateBeanException if trying to create or update a bean
     * when not in the correct mode. If trying to create an empty bean.
     *
     * @return int Return code
     *
     */
    public function updateBean(\SugarBean $sugarBean, array $data, $saveMode)
    {
        $code = self::SUGAR_NOTCHANGED;

        // Save my ID
        if (!empty($sugarBean->id)) {
            $this->lastUpdatedId = $sugarBean->id;
        }

        $changedValues = $this->updateBeanFields($sugarBean, $data);
        if ($changedValues === 0) {
            if (!($saveMode & self::MODE_UPDATE)) {
                $msg = 'Error: Won\'t create an empty bean.';
                $this->getLogger()->info($this->logPrefix . $msg);
                throw new UpdateBeanException($msg, self::SUGAR_FIELDS_NOT_MODIFIED);
            }
            // Update mode, this is not an error we just notify that nothing changed.
            $msg = 'Not Saving the bean because the records are identical.';
            $this->getLogger()->info($this->logPrefix . $msg);

            return $code;
        }

        $this->updateBeanFieldsFromCurrentUser($sugarBean);


        if ($saveMode === self::MODE_DRY_RUN) {
            $this->getLogger()->info($this->logPrefix . 'Dry run. Won\'t save anything.');
            return $code;
        }

        if (empty($sugarBean->id)) {
            // We have a new Bean
            if ($saveMode & self::MODE_CREATE) {
                $code = self::SUGAR_CREATED;
                $this->getLogger()->info($this->logPrefix . 'Creating a new record.');
            } else {
                $msg = 'Won\'t create a new record with an empty id when not in CREATE mode.';
                $this->getLogger()->info($this->logPrefix . $msg);
                throw new UpdateBeanException($msg, self::SUGAR_NOTCHANGED);
            }
        } else {
            if ($saveMode & self::MODE_UPDATE) {
                $code = self::SUGAR_UPDATED;
                $this->getLogger()->info($this->logPrefix . "Updating record with id '{$sugarBean->id}'.");
            } elseif ($saveMode & self::MODE_CREATE_WITH_ID) {
                $sugarBean->new_with_id = 1;
                $code = self::SUGAR_CREATED;
                $this->getLogger()->info($this->logPrefix . "Creating new record with id '{$sugarBean->id}'.");
            } else {
                $msg = "Will not save record with id '{$sugarBean->id}'. Because not in UPDATE or CREATE_WITH_ID mode.";
                $this->getLogger()->info($this->logPrefix . $msg);
                throw new UpdateBeanException($msg, self::SUGAR_NOTCHANGED);
            }
        }

        $sugarBean->save();

        // save my new ID
        $this->lastUpdatedId = $sugarBean->id;

        $msg = "Bean with ID {$sugarBean->id} saved because {$changedValues} value(s) ha(s)ve been changed";
        $this->getLogger()->info($this->logPrefix . $msg);

        // clean memory
        $this->cleanMemory();

        return $code;
    }

    /**
     * Update fields of a SugarCRM Bean
     *
     * @param \SugarBean $bean
     * @param array      $fields     Array of field name => value
     *
     * @return integer Number of changed fields.
     *
     */
    public function updateBeanFields(\SugarBean $sugarBean, array $fields)
    {
        $changedValues = 0;
        $nonEmptyFields = array('date_entered');
        $moduleFields = $this->getModuleFields($sugarBean->module_name);
        $moduleRels = $this->getModuleRelationships($sugarBean->module_name, 'one');
        foreach ($fields as $field => $value) {
            // It does not exist in Sugar
            if (!array_key_exists($field, $moduleFields) && !array_key_exists($field, $moduleRels)
              && !array_key_exists($field, $sugarBean->field_name_map)
            ) {
                $this->getLogger()->error($this->logPrefix . "$field doesn't seem to exist in Sugar");
            }

            // Field value and new value are the same
            // or I ignore empty values
            if ((isset($sugarBean->$field) && $sugarBean->$field == htmlspecialchars($value, ENT_QUOTES))
              || (in_array($field, $nonEmptyFields) && empty($value))) {
                $this->getLogger()->debug($this->logPrefix . "Skipping $field, values are same or value is empty");
                continue;
            }

            $sugarBean->$field = $value;
            $this->getLogger()->debug($this->logPrefix . "Saving $field, value has changed");
            $changedValues++;
        }

        return $changedValues;
    }


   /**
     * Count Records for a Sugar Module
     *
     * @param string  $module        SugarCRM Module's Name
     * @param array   $whereCriteras Search Criteras + values
     * @param boolean $deleted       Take deleted records into account
     *
     * @throws \InvalidArgumentException
     *
     * @return integer Total number of records
     */
    public function countRecords($module, array $whereCriteras = array(), $deleted = false)
    {
        $bean = $this->getBean($module);
        // build the where
        $where = ($deleted ? 'WHERE 1 = 1' : 'WHERE deleted = 0');
        if (!empty($whereCriteras)) {
            $where.= ' AND ' . implode(' AND ', $whereCriteras);
        }
        $this->getLogger()->info($this->logPrefix . "Going to count the record with '{$where}'.");

        // shot the query
        $sql = "SELECT * FROM {$bean->table_name} $where";
        $countSql = $bean->create_list_count_query($sql);
        $countRes = (int)$bean->db->getOne($countSql);

        if ($bean->db->database->error) {
            throw new \RuntimeException('The query to Count records failed');
        }

        if ($countRes === 0) {
            $this->getLogger()->info($this->logPrefix . "Query '$sql' gave 0 result.");
        }

        return $countRes;
    }


    public function updateBeanFieldsFromCurrentUser(\SugarBean $sugarBean)
    {
        $propertiesMapping = array(
            'assigned_user_id' => 'id',
            'created_by' => 'id',
            'team_id' => 'team_id',
            'team_set_id' => 'team_set_id',
        );

        foreach ($propertiesMapping as $beanProperty => $userProperty) {
            if (property_exists($sugarBean, $beanProperty) && empty($sugarBean->$beanProperty)) {
                $sugarBean->$beanProperty = $this->currentUser->$userProperty;
            }
        }
        $msg = "Record assigned to id {$sugarBean->assigned_user_id} with team_id {$sugarBean->team_id} ";
        $msg.= "and team_set_id {$sugarBean->team_set_id}";
        $this->getLogger()->info($this->logPrefix . $msg);
    }


    /**
     * Return the last written ID by updateBean
     * @return    string    UUID (or empty)
     */
    public function getLastUpdatedId()
    {
        return $this->lastUpdatedId;
    }

    /**
     * Get the list of fields for a specific module
     *
     * @param string  $module            SugarCRM Module's name
     * @param string  $lang              Language (else FR by default)
     * @param boolean $getUnusedDbFields Also find the fields in DB and not in Sugar
     *
     * @throws \InvalidArgumentException|\RuntimeException
     *
     * @return array List Of fields
     */
    public function getModuleFields($module, $lang = 'fr_FR', $getUnusedDbFields = false)
    {
        // Check in cache
        if (isset($this->moduleFields[$module][$lang])) {
            $this->getLogger()->debug($this->logPrefix . 'Got Fields for this module in cache');

            return $this->moduleFields[$module][$lang];
        }

        $sugarBean = $this->getBean($module);
        // Load the fields from each tables (normal and _cstm) to get a diff and show when we have
        // fields in DB that are not needed anymore
        // that'll also be useful to get the DBTYPE
        // Main Table
        $tableName = $sugarBean->table_name;
        $mainTable = $tableFields = $this->db->query("SHOW FULL COLUMNS FROM $tableName");

        // Custom Table
        $tableNameCustom = $sugarBean->table_name . '_cstm';
        // check if table custom exists
        $tableCustom = $this->db->query("SHOW TABLES LIKE '{$tableNameCustom}';");
        $customTable = array();
        if (!empty($tableCustom)) {
            $customTable = $customColumns = $this->db->query("SHOW FULL COLUMNS FROM $tableNameCustom");
            $tableFields = array_merge($tableFields, $customColumns);
        }

        // Set key 'Field' as main Key of the array
        foreach ($tableFields as $k => $v) {
            $tableFields[$v['Field']] = $v;
            // Search if the field is in main table or custom table
            foreach ($mainTable as $tableField) {
                if ($tableField['Field'] == $v['Field']) {
                    $tableFields[$v['Field']]['Table'] = $tableName;
                    continue;
                }
            }
            // I didn't find in MainTable check in custom
            if (!array_key_exists('Table', $tableFields[$v['Field']])) {
                foreach ($customTable as $tableField) {
                    if ($tableField['Field'] == $v['Field']) {
                        $tableFields[$v['Field']]['Table'] = $tableNameCustom;
                        continue;
                    }
                }
            }

            unset($tableFields[$k]);
        }
        unset($tableFields['id_c']);

        // Set language to translate labels and dropdown lists
        global $current_language;
        $_REQUEST['login_language'] = $current_language = $lang;
        $listStrings = return_app_list_strings_language($lang);
        // Loop and get my attributes
        $moduleInfoFields = array();
        $sugarAttributes = array('type', 'vname', 'required', 'massupdate', 'default', 'help',
            'comment', 'importable', 'duplicate_merge', 'duplicate_merge_dom_value', 'audited',
            'reportable', 'unified_search', 'calculated', 'len', 'size', 'options'
        );
        foreach ($sugarBean->column_fields as $fieldName) {
            // No information about that field: ERROR
            if (!array_key_exists($fieldName, $sugarBean->field_name_map)) {
                throw new \RuntimeException("I am not able to find the field $field in field_name_map");
            }
            if ($sugarBean->field_name_map[$fieldName]['type'] == 'relate'
              || $sugarBean->field_name_map[$fieldName]['type'] == 'link'
              || (array_key_exists('source', $sugarBean->field_name_map[$fieldName])
                  && $sugarBean->field_name_map[$fieldName]['source'] == 'non-db')
            ) {
                unset($tableFields[$fieldName]);
                continue;
            }

            if (!array_key_exists($fieldName, $tableFields)) {
                throw new \RuntimeException("Field $fieldName found in metadata but not in DB !");
            }

            $moduleInfoFields[$fieldName] = array();
            $moduleInfoFields[$fieldName]['name'] = $fieldName;
            $moduleInfoFields[$fieldName]['Table'] = $tableFields[$fieldName]['Table'];
            $moduleInfoFields[$fieldName]['dbType'] = $tableFields[$fieldName]['Type'];

            // Loop over fields
            foreach ($sugarAttributes as $sugarAttribute) {
                $moduleInfoFields[$fieldName][$sugarAttribute] = '';

                // No attribute of that type ?
                if (!array_key_exists($sugarAttribute, $sugarBean->field_name_map[$fieldName])) {
                    continue;
                }

                // specific case of label
                if ($sugarAttribute === 'vname') {
                    $label = translate($sugarBean->field_name_map[$fieldName][$sugarAttribute], $module);
                    $moduleInfoFields[$fieldName][$sugarAttribute] = $label;
                // Specific case of Lists (dropdown)
                } elseif ($sugarAttribute === 'options') {
                    $optionsName = $sugarBean->field_name_map[$fieldName][$sugarAttribute];
                    $moduleInfoFields[$fieldName][$sugarAttribute] = $optionsName;
                    if (array_key_exists($optionsName, $listStrings)) {
                        $moduleInfoFields[$fieldName]['options_list'] = array();
                        foreach ($listStrings[$optionsName] as $k => $label) {
                            $moduleInfoFields[$fieldName]['options_list'][$k] = $label;
                        }
                    } else {
                        $moduleInfoFields[$fieldName]['options_list'][] = 'LIST NOT FOUND';
                    }
                } else {
                    $moduleInfoFields[$fieldName][$sugarAttribute] = $sugarBean
                        ->field_name_map[$fieldName][$sugarAttribute];
                }
            }
        }

        // Now check if I have some strange fields (in DB but not in metadata)
        if ($getUnusedDbFields) {
            foreach ($tableFields as $field => $data) {
                if (!array_key_exists($field, $moduleInfoFields)) {
                    $moduleInfoFields[$field] = array(
                        'name' => $field,
                        'dbType' => $data['Type'],
                    );
                    foreach ($sugarAttributes as $sugarAttribute) {
                        $moduleInfoFields[$field][$sugarAttribute] = '';
                    }

                    $moduleInfoFields[$field]['comment'] = 'ERROR: FIELD IS IN DB BUT NO IN FIELDS META DATA !';
                }
            }
        }

        $this->moduleFields[$module][$lang] = $moduleInfoFields;

        return $moduleInfoFields;
    }

    /**
     * Get relationships for a specific module
     *
     * @param string $module SugarCRM Module's name
     * @param string $type   Could be either 'all' or 'one'. One will give only the rels as "fields"
     *
     * @throws \InvalidArgumentException
     *
     * @return array List of relationships
     */
    public function getModuleRelationships($module, $type = 'all')
    {
        // Check in cache
        if (isset($this->moduleRels[$module][$type])) {
            $this->getLogger()->debug($this->logPrefix . 'Got rels for this module in cache');

            return $this->moduleRels[$module][$type];
        }

        $sugarBean = $this->getBean($module);

        $data = array();
        $rels = $sugarBean->get_linked_fields();
        // removing the "right side" of a relationship
        foreach ($rels as $props) {
            $relName = $props['relationship'];

            $relationship = new \Relationship();
            $relationship->retrieve_by_name($relName);
            // Id is empty: fake relationship, especially for users
            if (empty($relationship->id)) {
                continue;
            }
            // Just rename the relationship if I ask only the beans directly related
            if ($type == 'one' &&
               (array_key_exists('side', $props) && $props['side'] == 'right'
               || array_key_exists('link_type', $props) && $props['link_type'] == 'one')
            ) {
                $relName = $props['name'];
            }

            // I am in a many to One, ignore other
            if ($type == 'one' && $relName == $relationship->relationship_name) {
                continue;
            }

            // Write data
            $data[$relName] = array(
                'relationship_name' => $relName,
                'lhs_key' => $relationship->lhs_key,
                'lhs_module' => $relationship->lhs_module,
                'relationship_type' => $relationship->relationship_type,
                'rhs_module' => $relationship->rhs_module,
                'rhs_key' => $relationship->rhs_key,
                'join_table' => $relationship->join_table,
                'join_key_lhs' => $relationship->join_key_lhs,
                'join_key_rhs' => $relationship->join_key_rhs,
            );
        }
        // sort it
        ksort($data);
        // cache it
        $this->moduleRels[$module][$type] = $data;

        return $data;
    }

    /**
     * Returns the table's name for a module
     *
     * @param string $module Module's name
     *
     * @throws \InvalidArgumentException
     *
     * @return string Returns the table name
     */
    public function getModuleTable($module)
    {
        $sugarBean = $this->getBean($module);

        return $sugarBean->table_name;
    }

    /**
     * Returns the custom table's name for a module
     *
     * @param string $module Module's name
     *
     * @throws \InvalidArgumentException
     *
     * @return string Returns the table name
     */
    public function getModuleCustomTable($module)
    {
        $sugarBean = $this->getBean($module);

        return $sugarBean->get_custom_table_name();
    }

    /**
     * Returns the module directory
     *
     * @param string $module Module's name
     *
     * @throws \InvalidArgumentException
     *
     * @return string Returns the module directory
     */
    public function getModuleDirectory($module)
    {
        $sugarBean = $this->getBean($module);

        return $sugarBean->module_dir;
    }

    /**
     * Check if I have to clean the memory or not. That should be call by the
     * functions that do a Save or any kind of retrieve (getBean, get_list ...)
     *
     * @return boolean Operation was succesful
     */
    private function cleanMemory()
    {
        $usedMemory = round(memory_get_usage() / 1024 / 1024, 0);
        if ($usedMemory > 250 && $this->loopWithoutCleaningMemory > 300) {
            BeanFactoryCache::clearCache();
            $this->loopWithoutCleaningMemory = 0;
            $msg = "Memory Cleaned because usage is around {$usedMemory} Mib. ";
            $msg.= 'Collected ' . gc_collect_cycles()  . ' cycles.';
            $this->getLogger()->warning($this->logPrefix . $msg);
        } else {
            $this->loopWithoutCleaningMemory++;
        }

        return true;
    }

    /**
     * Return an array with the field_name and the value after replacing by a label when available.
     *
     * @param $module_definition Module definition fetch with language as in getModuleFields.
     * @param $field_name Technical name of the field
     * @param $value Value fetched from db. Enums will be replaced by label from list.
     *
     * @return array Array with a field_name and value.
     */
    public function applyLabelsToField($module_definition, $field_name, $value)
    {
        $key = $field_name;
        if (array_key_exists($field_name, $module_definition)) {
            $field_definition = $module_definition[$field_name];
            if (isset($field_definition['vname'])) {
                $field_name = $field_definition['vname'];
            }
            if (isset($field_definition['type'])) {
                switch ($field_definition['type']) {
                    case 'enum':
                        if (isset($field_definition['options_list'][$value])) {
                            $value = $field_definition['options_list'][$value];
                        }
                        break;
                    case 'bool':
                        $value = $value ? self::BOOL_TRUE : self::BOOL_FALSE;
                        break;
                }
            }
        }
        return array($field_name, $value);
    }

    /**
     * Fetch values for fields name from bean
     *
     * @param $pretty if true, will return the display name from the language.
     * @param $lang language to use in pretty mode. Default to en_us.
     *
     * @return An array of key => value pairs.
     */
    public function beanToArray(array $fields_name, \SugarBean $bean, $pretty = false, $lang = 'en_us')
    {
        $module_definition = null;
        if ($pretty) {
            $module_definition = $this->getModuleFields($bean->module_name, $lang);
        }
        $fields = array();
        foreach ($fields_name as $field_name) {
            $key = $field_name;
            $value = $bean->$field_name;
            if (!is_null($module_definition)) {
                list($key, $value) = $this->applyLabelsToField($module_definition, $key, $value);
            }
            $fields[$key] = $value;
        }
        return $fields;
    }

    /**
     * Convert an array of \SugarBean objects to an array of arrays matching the Beans.
     *
     * @param $fields_name Only the fields named in this array will be present.
     * @param $bean_list An array of SugarBean objects.
     * @param $pretty If true, return the fields using the labels.
     * @param $lang Language to use if pretty is true. Default to english.
     *
     * @return array An array of arrays with bean fields as keys.
     */
    public function beanListToArray(array $fields_name, array $bean_list, $pretty = false, $lang = 'en_us')
    {
        $ret = array();
        foreach ($bean_list as $bean) {
            $ret[] = $this->beanToArray($fields_name, $bean, $pretty, $lang);
        }
        return $ret;
    }
}
