<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mail\Header;

use Zend\Mail\Header\ContentType;

/**
 * @group      Zend_Mail
 */
class ContentTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testContentTypeFromStringCreatesValidContentTypeHeader()
    {
        $contentTypeHeader = ContentType::fromString('Content-Type: xxx/yyy');
        $this->assertInstanceOf('Zend\Mail\Header\HeaderInterface', $contentTypeHeader);
        $this->assertInstanceOf('Zend\Mail\Header\ContentType', $contentTypeHeader);
    }

    public function testContentTypeGetFieldNameReturnsHeaderName()
    {
        $contentTypeHeader = new ContentType();
        $this->assertEquals('Content-Type', $contentTypeHeader->getFieldName());
    }

    public function testContentTypeGetFieldValueReturnsProperValue()
    {
        $contentTypeHeader = new ContentType();
        $contentTypeHeader->setType('foo/bar');
        $this->assertEquals('foo/bar', $contentTypeHeader->getFieldValue());
    }

    public function testContentTypeToStringReturnsHeaderFormattedString()
    {
        $contentTypeHeader = new ContentType();
        $contentTypeHeader->setType('foo/bar');
        $this->assertEquals("Content-Type: foo/bar", $contentTypeHeader->toString());
    }

    /**
     * @group 6491
     */
    public function testTrailingSemiColonFromString()
    {
        $contentTypeHeader = ContentType::fromString(
            'Content-Type: multipart/alternative; boundary="Apple-Mail=_1B852F10-F9C6-463D-AADD-CD503A5428DD";'
        );
        $params = $contentTypeHeader->getParameters();
        $this->assertEquals(array('boundary' => 'Apple-Mail=_1B852F10-F9C6-463D-AADD-CD503A5428DD'), $params);
    }

    public function testProvidingParametersIntroducesHeaderFolding()
    {
        $header = new ContentType();
        $header->setType('application/x-unit-test');
        $header->addParameter('charset', 'us-ascii');
        $string = $header->toString();

        $this->assertContains("Content-Type: application/x-unit-test;", $string);
        $this->assertContains(";\r\n charset=\"us-ascii\"", $string);
    }

    public function testExtractsExtraInformationFromContentType()
    {
        $contentTypeHeader = ContentType::fromString(
            'Content-Type: multipart/alternative; boundary="Apple-Mail=_1B852F10-F9C6-463D-AADD-CD503A5428DD"'
        );
        $params = $contentTypeHeader->getParameters();
        $this->assertEquals($params, array('boundary' => 'Apple-Mail=_1B852F10-F9C6-463D-AADD-CD503A5428DD'));
    }

    public function testExtractsExtraInformationWithoutBeingConfusedByTrailingSemicolon()
    {
        $header = ContentType::fromString('Content-Type: application/pdf;name="foo.pdf";');
        $this->assertEquals($header->getParameters(), array('name' => 'foo.pdf'));
    }

    /**
     * @group #2728
     *
     * Tests setting different MIME types
     */
    public function testSetContentType()
    {
        $header = new ContentType();

        $header->setType('application/vnd.ms-excel');
        $this->assertEquals('Content-Type: application/vnd.ms-excel', $header->toString());

        $header->setType('application/rss+xml');
        $this->assertEquals('Content-Type: application/rss+xml', $header->toString());

        $header->setType('video/mp4');
        $this->assertEquals('Content-Type: video/mp4', $header->toString());

        $header->setType('message/rfc822');
        $this->assertEquals('Content-Type: message/rfc822', $header->toString());
    }

    /**
     * @group ZF2015-04
     */
    public function testFromStringRaisesExceptionForInvalidName()
    {
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException', 'header name');
        $header = ContentType::fromString('Content-Type' . chr(32) . ': text/html');
    }

    public function headerLines()
    {
        return array(
            'newline'      => array("Content-Type: text/html;\nlevel=1"),
            'cr-lf'        => array("Content-Type: text/html\r\n;level=1",),
            'multiline'    => array("Content-Type: text/html;\r\nlevel=1\r\nq=0.1"),
        );
    }

    /**
     * @dataProvider headerLines
     * @group ZF2015-04
     */
    public function testFromStringRaisesExceptionForNonFoldingMultilineValues($headerLine)
    {
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException', 'header value');
        $header = ContentType::fromString($headerLine);
    }

    /**
     * @group ZF2015-04
     */
    public function testFromStringHandlesContinuations()
    {
        $header = ContentType::fromString("Content-Type: text/html;\r\n level=1");
        $this->assertEquals('text/html', $header->getType());
        $this->assertEquals(array('level' => '1'), $header->getParameters());
    }

    /**
     * @group ZF2015-04
     */
    public function testAddParameterRaisesInvalidArgumentExceptionForInvalidParameterName()
    {
        $header = new ContentType();
        $header->setType('text/html');
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException', 'parameter name');
        $header->addParameter("b\r\na\rr\n", "baz");
    }

    /**
     * @group ZF2015-04
     */
    public function testAddParameterRaisesInvalidArgumentExceptionForInvalidParameterValue()
    {
        $header = new ContentType();
        $header->setType('text/html');
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException', 'parameter value');
        $header->addParameter('foo', "\nbar\r\nbaz\r");
    }
}
