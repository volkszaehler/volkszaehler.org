<?php

namespace Volkszaehler\Util;

use PHPUnit_Framework_TestCase;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    public function testLoadThrowsExceptionOnMissingFile()
    {
        $file = __DIR__ . '/fixtures/missing-configuration';
        $this->setExpectedException('\Exception', 'Configuration file not found: \''.$file.'.php\'');
        Configuration::load($file);
    }

    public function testLoadThrowsExceptionOnInvalidFile()
    {
        $file = __DIR__ . '/fixtures/invalid-configuration';
        $this->setExpectedException('\Exception', 'No variable $config found in: \''.$file.'.php\'');
        Configuration::load($file);
    }

    public function testLoad()
    {
        $file = __DIR__ . '/fixtures/valid-configuration';
        Configuration::load($file);
        $this->assertEquals('123', Configuration::read('foo'));
        $this->assertEquals(null, Configuration::read('xyz'));
        $this->assertEquals('789', Configuration::read('foobar.barfoo'));
        $this->assertEquals(null, Configuration::read('foobar.xyz'));
        $this->assertInternalType('array', Configuration::read(null));
    }

}