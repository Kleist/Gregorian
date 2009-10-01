<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../GregorianController.class.php';
require_once 'mockups/modx_mockup.class.php';
require_once 'mockups/xpdo_mockup.class.php';
require_once 'mockups/gregorian_mockup.class.php';

class GregorianControllerTest extends PHPUnit_Framework_TestCase
{
    protected $gc; // GregorianController
	
	public function setUp() {
		global $modx;
		global $xpdo;
		$xpdo = new xpdo_mockup();
		$modx = new modx_mockup();
        $this->gc = new GregorianController(&$modx,&$xpdo);
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
    
    public function testSafeSet_RuleArray() {
        $this->assertTrue($this->gc->safeSet('view','list',array('list')));
        $this->assertEquals($this->gc->get('view'),'list');
    }
    
    public function testSafeSet_NotRequestable() {
    	$this->gc->set('showWarnings',false); // Make sure gc doesn't try to use $calendar        
        $this->assertFalse($this->gc->safeSet('does_not_exist_in_config',true,$rule));
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
        $this->gc->set('adminGroup','isMember');
        $this->assertTrue($this->gc->isEditor());
        $this->gc->set('adminGroup','isNotMember');
        $this->assertFalse($this->gc->isEditor());
    }
    
    
}
?>