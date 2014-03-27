<?php

namespace Volkszaehler\Util;

use PHPUnit_Framework_TestCase;
use stdClass;

class JSONTest extends PHPUnit_Framework_TestCase
{
    public function testDecodeThrowsExceptionOnNullInput()
    {
        $this->setExpectedException('Volkszaehler\Util\JSONException');
        JSON::decode(null);
    }

    public function testDecodeWorks()
    {
        $json = '{
    "glossary": {
        "title": "example glossary",
		"GlossDiv": {
            "title": "S",
			"GlossList": {
                "GlossEntry": {
                    "ID": "SGML",
					"SortAs": "SGML",
					"GlossTerm": "Standard Generalized Markup Language",
					"Acronym": "SGML",
					"Abbrev": "ISO 8879:1986",
					"GlossDef": {
                        "para": "A meta-markup language, used to create markup languages such as DocBook.",
						"GlossSeeAlso": ["GML", "XML"]
                    },
					"GlossSee": "markup"
                }
            }
        }
    }
}';
        $result = JSON::decode($json);
        $this->assertInstanceOf('Volkszaehler\Util\JSON', $result);

        $expected = new JSON();
        $expected['glossary'] = new stdClass();
        $expected['glossary']->title = 'example glossary';
        $expected['glossary']->GlossDiv = new stdClass();
        $expected['glossary']->GlossDiv->title = 'S';
        $expected['glossary']->GlossDiv->GlossList = new stdClass();
        $expected['glossary']->GlossDiv->GlossList->GlossEntry = new stdClass();
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->ID = 'SGML';
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->SortAs = 'SGML';
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->GlossTerm = 'Standard Generalized Markup Language';
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->Acronym = 'SGML';
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->Abbrev = 'ISO 8879:1986';
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->GlossDef = new stdClass();
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->GlossDef->para = 'A meta-markup language, used to create markup languages such as DocBook.';
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->GlossDef->GlossSeeAlso = array('GML', 'XML');
        $expected['glossary']->GlossDiv->GlossList->GlossEntry->GlossSee = 'markup';
        $this->assertEquals($expected, $result);
    }
    
}