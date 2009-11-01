<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class TestEventForm extends PHPUnit_Extensions_SeleniumTestCase
{
    function setUp()
    {
        $this->setBrowser("*chrome");
        $this->setBrowserUrl("http://localhost/");
    }

    function testAddTestData()
    {
        $this->login();
        $this->addTestData();
        $this->assertXpathCount("//div[@class='event']",5);
    }

    function login()
    {
        $this->openAndWait("/");
        $this->clickAndWait("link=WebLogin");
        $this->type("username", "caladmin");
        $this->type("password", "caladmin");
        $this->clickAndWait("cmdweblogin");
    }

    function addTestData()
    {
        $this->openAndWait('/');
        $this->clickAndWait("link=Create entry");
        $this->type('summary','TestData');
        $this->click('dtstart');
        $this->waitForElementPresent("css=td.ui-datepicker-today a");
        $this->click("css=td.ui-datepicker-today a");
        $this->clickAndWait('submit');
    }
}