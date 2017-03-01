<?php

namespace Phlib\ConsoleConfiguration\Tests\Helper;

use Doctrine\Instantiator\Exception\UnexpectedValueException;
use Phlib\ConsoleConfiguration\Helper\ConfigurationHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use phpmock\phpunit\PHPMock;
use Symfony\Component\Yaml\Yaml;

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

    public function testSettingGettingDefaultConfiguration()
    {
        $default = ['my' => 'default', 'configuration'];
        $helper  = new ConfigurationHelper();
        $helper->setDefault($default);
        $this->assertEquals($default, $helper->getDefault());
    }

    public function testNoOptionSpecifiedReturnsFalse()
    {
        $this->setupEnvironment('/path/to/files', null);
        $this->assertFalse($this->helper->fetch());
    }

    public function testNoOptionSpecifiedReturnsDefaultConfiguration()
    {
        $default = ['my' => 'default', 'configuration'];
        $this->setupDefault($default);
        $this->assertEquals($default, $this->helper->fetch());
    }

    public function testInitHelperReturnsHelperInstance()
    {
        $application = new Application();
        $this->assertInstanceOf(
            '\Phlib\ConsoleConfiguration\Helper\ConfigurationHelper',
            ConfigurationHelper::initHelper($application)
        );
    }

    public function testInitHelperSetsDefaultConfiguration()
    {
        $default = ['my' => 'config'];
        $application = new Application();
        $helper = ConfigurationHelper::initHelper($application, $default);
        $this->assertEquals($default, $helper->getDefault());
    }

    public function testInitHelperInitializes()
    {
        $application = new Application();
        ConfigurationHelper::initHelper($application);
        $this->assertTrue($application->getDefinition()->hasOption('config'));
    }

    public function testDetectsFile()
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;
        $this->setupEnvironment(dirname($filename), null);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    public function testWithFileSpecified()
    {
        $filename = __DIR__ . '/files/my-diff-config.php';
        $expected = include $filename;
        $this->setupEnvironment('/path/to/files', $filename);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    public function testWithYmlFileSpecified()
    {
        $filename = __DIR__ . '/files/cli-config.yml';
        $expected = Yaml::parse(file_get_contents($filename));
        $this->setupEnvironment('/path/to/files', $filename);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testWithUnsupportedExtensionFileSpecified()
    {
        $filename = __DIR__ . '/files/cli-config.lala';
        $this->setupEnvironment('/path/to/files', $filename);
        $this->helper->fetch();
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
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;
        $this->setupEnvironment('/path/to/files', dirname($filename));
        $this->assertEquals($expected, $this->helper->fetch());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSpecifiedFileIsNotFound()
    {
        $this->setupEnvironment('/path/to/files', __DIR__ . '/files/my-config-not-here.php');
        $this->helper->fetch();
    }

    public function testTheResultIsCached()
    {
        $this->input->expects($this->any())
            ->method('hasOption')
            ->will($this->returnValue(true));
        $this->input->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(__DIR__ . '/files/cli-config.php'));
        $this->helper->fetch();
    }

    public function testNoConfigInputOptionGoesToAutodetect()
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;

        $this->setupEnvironment(dirname($filename), null, false);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    /**
     * @param string $expected
     * @param string $setupMethod
     * @param array $setupArgs
     * @dataProvider getConfigPathDataProvider
     */
    public function testGetConfigPath($expected, $setupMethod, array $setupArgs)
    {
        call_user_func_array([$this, $setupMethod], $setupArgs);
        $this->assertEquals($expected, $this->helper->getConfigPath());
    }

    public function getConfigPathDataProvider()
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $default  = ['some' => 'settings'];

        return [
            ['[none]',    'setupEnvironment', ['/path/to/files', null]],
            ['[default]', 'setupDefault',     [$default]],
            [$filename,   'setupEnvironment', ['/path/to/files', dirname($filename)]],
            [$filename,   'setupEnvironment', ['/path/to/files', $filename]],
            [$filename,   'setupEnvironment', [__DIR__ . '/files', null, false]],
        ];
    }

    /**
     * @param string $cwd Current working directory
     * @param string $inputGet User specified path
     * @param bool $inputHas
     */
    protected function setupEnvironment($cwd, $inputGet, $inputHas = true)
    {
        $getcwd = $this->getFunctionMock('\Phlib\ConsoleConfiguration\Helper', 'getcwd');
        $getcwd->expects($this->any())
            ->will($this->returnValue($cwd));

        $this->input->expects($this->any())
            ->method('hasOption')
            ->will($this->returnValue($inputHas));
        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValue($inputGet));
    }

    /**
     * @param array $default
     */
    protected function setupDefault(array $default)
    {
        $this->setupEnvironment('/path/to/files', null);
        $this->helper->setDefault($default);
    }
}
