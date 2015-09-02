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
 * @license GNU General Public License v2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

/**
 * SugarCRM Team Management
 *
 * @todo Unit Tests
 */
class Team
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
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     *
     * @param EntryPoint $entryPoint Enters the SugarCRM Folder
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->log = $entryPoint->getLogger();

        $this->sugarBean = new Bean($entryPoint);
    }

    /**
     * Returns an array of teams from a team set id
     *
     * @param string $teamSetId   UUID from SugarCRM.
     *
     * @return array              List of teams with metadata
     */
    public function getTeamsFromTeamSet($teamSetId)
    {
        require_once('modules/Teams/TeamSetManager.php');
        $teams = \TeamSetManager::getTeamsFromSet($teamSetId);

        // Fetch more details from the team
        foreach ($teams as $key => $team) {
            $teamFields = $this->sugarBean->getBean('Teams', $team['id'])->fetched_row;
            $teams[$key] = array_merge($team, $teamFields);
        }

        return $teams;
    }

    /**
     * Returns an array of members from a team
     *
     * @param string $teamId      UUID from SugarCRM.
     *
     * @return array              List of users with metadata
     */
    public function getTeamMembers($teamId)
    {
        $team = $this->sugarBean->getBean('Teams', $teamId);
        $users = $team->get_team_members();

        foreach ($users as $key => $user) {
            $users[$key] = $user->fetched_row;
        }

        return $users;
    }
}
