<?php

namespace Phlib\ConsoleConfiguration\Tests\Helper;

use Phlib\ConsoleConfiguration\Helper\ConfigurationHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use phpmock\phpunit\PHPMock;

class ConfigurationHelperTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    /**
     * @var InputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $input;

    /**
     * @var ConfigurationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    public function setUp()
    {
        $this->input  = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->helper = new ConfigurationHelper();
        $this->helper->setInput($this->input);
    }

    public function testImplementsInputAwareInterface()
    {
        $this->assertInstanceOf('\Symfony\Component\Console\Input\InputAwareInterface', $this->helper);
    }

    public function testGetName()
    {
        $this->assertEquals('configuration', (new ConfigurationHelper())->getName());
    }

    public function testInitHelperInitializes()
    {
        $application = new Application();
        ConfigurationHelper::initHelper($application);
        $this->assertTrue($application->getDefinition()->hasOption('config'));
    }

    public function testDetectsFile()
    {
        $expected = require_once __DIR__ . '/files/cli-config.php';

        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(null));
        $getcwd = $this->getFunctionMock('\Phlib\ConsoleConfiguration\Helper', 'getcwd');
        $getcwd->expects($this->any())
            ->will($this->returnValue(__DIR__ . '/files'));
        $this->assertEquals($expected, $this->helper->fetch());
    }

    public function testDoesNotDetectFile()
    {
        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(null));
        $getcwd = $this->getFunctionMock('\Phlib\ConsoleConfiguration\Helper', 'getcwd');
        $getcwd->expects($this->any())
            ->will($this->returnValue('/path/to/files'));
        $this->assertFalse($this->helper->fetch());
    }

    public function testWithFileSpecified()
    {
        $expected = include __DIR__ . '/files/my-diff-config.php';
        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(__DIR__ . '/files/my-diff-config.php'));
        $this->assertEquals($expected, $this->helper->fetch());
    }

    public function testUsesSpecifiedFilenameFormat()
    {
        $filenameFormat = 'my-diff-config.php';
        $expected = include __DIR__ . '/files/' . $filenameFormat;
        $helper = new ConfigurationHelper('config', $filenameFormat);
        $helper->setInput($this->input);
        $getcwd = $this->getFunctionMock('\Phlib\ConsoleConfiguration\Helper', 'getcwd');
        $getcwd->expects($this->any())
            ->will($this->returnValue(__DIR__ . '/files'));
        $this->assertEquals($expected, $helper->fetch());
    }

    public function testSpecifiedDirectory()
    {
        $expected = include __DIR__ . '/files/cli-config.php';
        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(__DIR__ . '/files'));
        $this->assertEquals($expected, $this->helper->fetch());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSpecifiedFileIsNotFound()
    {
        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue(__DIR__ . '/files/my-config-not-here.php'));
        $this->helper->fetch();
    }

    public function testTheResultIsCached()
    {
        $this->input->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(__DIR__ . '/files/cli-config.php'));
        $this->helper->fetch();
    }
}
