<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;
use Inet\SugarCRM\Team;

class TeamTest extends SugarTestCase
{
    public function testRightInstanciation()
    {
        $team = new Team($this->getEntryPointInstance());
        $this->assertInstanceOf('Inet\SugarCRM\Team', $team);
    }

    public function testGetTeamsFromTeamSet()
    {
        $team = new Team($this->getEntryPointInstance());
        $teams = $team->getTeamsFromTeamSet(1);
        $this->assertCount(1, $teams);
        $this->assertEquals(1, $teams[0]['id']);
        $this->assertEquals('Global', $teams[0]['name']);
    }

    public function testGetTeamsFromTeamSetWrongTeamId()
    {
        $team = new Team($this->getEntryPointInstance());
        $teams = $team->getTeamsFromTeamSet("NOTEAMID");
        $this->assertInternalType('array', $teams);
        $this->assertEmpty($teams);
    }


    public function testGetTeamMembers()
    {
        $team = new Team($this->getEntryPointInstance());
        $members = $team->getTeamMembers(1);
        $this->assertInternalType('array', $members);
        $this->assertNotEmpty($members);
    }

    public function testGetTeamMembersWrongTeamId()
    {
        $team = new Team($this->getEntryPointInstance());
        $members = $team->getTeamsFromTeamSet("NOTEAMID");
        $this->assertInternalType('array', $members);
        $this->assertEmpty($members);
    }
}
