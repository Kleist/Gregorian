<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class TestEventForm extends PHPUnit_Extensions_SeleniumTestCase
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
        $this->openAndWait("/");
        $this->assertTrue($this->isElementPresent("link=Create entry"));
        $this->assertTrue($this->isElementPresent("link=Add tag"));
    }

    function testAddTestTag()
    {
        $this->addTestTag();
        $this->clickAndWait("link=Create entry");
        $this->assertTextPresent("TestTag");
    }

    function testRequiredFields()
    {
        $this->login();
        $this->openAndWait("/");
        $this->clickAndWait("link=Create entry");
        $this->clickAndWait("submit");
        $this->assertTextPresent("Edit event");
        $this->verifyTextPresent("Start date required");
        $this->verifyTextPresent("Summary required");
    }

    function testTagMemoryOnError()
    {
        $this->addTestTag();
        $this->clickAndWait("link=Create entry");
        $this->click("tag_TestTag");
        $this->clickAndWait("submit");
        $this->assertValue('tag_TestTag','on');
    }

    function testCreateAndDeleteEvent()
    {
        $this->login();
        $this->openAndWait('/');
        $this->clickAndWait("link=Create entry");
        $this->type('summary','Acceptance test event');
        $this->click('dtstart');
        $this->waitForElementPresent("css=td.ui-datepicker-today a");
        $this->click("css=td.ui-datepicker-today a");
        $this->clickAndWait('submit');
        $this->assertTextPresent('Acceptance test event');
        $this->deleteAllEvents();
        $this->assertTextPresent('No events found.');
    }

    function login()
    {
        $this->open("/");
        $this->clickAndWait("link=WebLogin");
        $this->type("username", "caladmin");
        $this->type("password", "caladmin");
        $this->clickAndWait("cmdweblogin");
    }

    function addTestTag()
    {
        $this->login();
        $this->openAndWait('/');
        $this->clickAndWait("link=Add tag");
        $this->type("tag","TestTag");
        $this->clickAndWait('submit');
    }

    function deleteAllEvents()
    {
        while (!$this->isTextPresent('No events found.')) {
            $this->click('link=[ Delete ]');
            $this->waitForElementPresent("//button[@type='button']");
            $this->clickAndWait("//button[@type='button']");
        }
    }
}

