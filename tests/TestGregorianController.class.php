<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../GregorianController.class.php';
require_once 'modx_mockup.class.php';

class GregorianControllerTest extends PHPUnit_Framework_TestCase
{
    protected $gc; // GregorianController
	
	public function setUp() {
		global $modx;
		$modx = new modx_mockup();
        $this->gc = new GregorianController(&$modx);
	}
	
	public function tearDown() {
		unset($this->gc);
		unset($modx);
	}
	
    public function testSetAndGetConfig() {
        $configValue = "Just a string";
        $this->gc->set('testConfig', $configValue);
        $this->assertEquals($this->gc->get('testConfig'), $configValue);
    }
    
    public function testGetUnknownConfig() {
        $this->assertNULL($this->gc->get('not_a_real_config'));    	
    }
    
    public function testMrgIsAdmin() {
    	global $modx;
    	// Two booleans equals four options, test them all.
    	$this->gc->set('mgrIsAdmin',true);
        $this->assertFalse($this->gc->isEditor());
        $this->gc->set('mgrIsAdmin',false);
        $this->assertFalse($this->gc->isEditor());
        $modx->setMgrValidated();
        $this->assertFalse($this->gc->isEditor());
        $this->gc->set('mgrIsAdmin',true);
        $modx->setMgrValidated();
        $this->assertTrue($this->gc->isEditor());
    }

    public function testWebIsAdmin() {
    	// "isMember" and "isNotMember" are webgroups in the modx_mockup that the mockup user are and are not a member of.
        $this->gc->set('adminGroups','isMember');
        $this->assertTrue($this->gc->isEditor());
        $this->gc->set('adminGroups','isNotMember');
        $this->assertFalse($this->gc->isEditor());
    }
}
?>