<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class Example extends PHPUnit_Extensions_SeleniumTestCase
{
    function setUp()
    {
        $this->setBrowser("*chrome");
        $this->setBrowserUrl("http://localhost/");
    }


    function testLogin()
    {
        $this->login();
        $this->assertTrue($this->isElementPresent("link=Logout"));
    }

    function testAdminButtonsAvailable()
    {
        $this->login();
        $this->open("/");
        $this->assertTrue($this->isElementPresent("link=Create entry"));
        $this->assertTrue($this->isElementPresent("link=Add tag"));
    }

    function testAddTestTag()
    {
        $this->addTestTag();
        $this->open("/?action=show&view=EventForm");
        $this->assertTextPresent("TestTag");
    }

    function testRequiredFields()
    {
        $this->login();
        $this->open("/");
        $this->waitForPageToLoad("30000");
        $this->click("link=Create entry");
        $this->waitForPageToLoad("30000");
        $this->click("submit");
        $this->waitForPageToLoad("30000");
        $this->assertTextPresent("Edit event");
        $this->verifyTextPresent("Start date required");
        $this->verifyTextPresent("Summary required");
    }

    function testTagMemoryOnError()
    {
        $this->addTestTag();
        $this->open("/?action=show&view=EventForm");
        $this->click("tag_TestTag");
        $this->clickAndWait("submit");
        $this->assertValue('tag_TestTag','on');
    }

    function login()
    {
        $this->open("/");
        $this->click("link=WebLogin");
        $this->waitForPageToLoad("30000");
        $this->type("username", "caladmin");
        $this->type("password", "caladmin");
        $this->click("cmdweblogin");
        $this->waitForPageToLoad("30000");
    }

    function addTestTag()
    {
        $this->login();
        $this->open("/?action=show&view=TagForm");
        $this->type("tag","TestTag");
        $this->click('submit');
    }
}
?>