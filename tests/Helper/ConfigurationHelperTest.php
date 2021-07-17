<?php

declare(strict_types=1);

namespace Phlib\ConsoleConfiguration\Tests\Helper;

use Phlib\ConsoleConfiguration\Helper\ConfigurationHelper;
use phpmock\phpunit\PHPMock;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
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

    public function setUp(): void
    {
        $this->input = $this->getMock(InputInterface::class);
        $this->helper = new ConfigurationHelper();
        $this->helper->setInput($this->input);
    }

    public function testImplementsInputAwareInterface(): void
    {
        $this->assertInstanceOf(InputAwareInterface::class, $this->helper);
    }

    public function testGetName(): void
    {
        $this->assertEquals('configuration', (new ConfigurationHelper())->getName());
    }

    public function testSettingGettingDefaultConfiguration(): void
    {
        $default = ['my' => 'default', 'configuration'];
        $helper = new ConfigurationHelper();
        $helper->setDefault($default);
        $this->assertEquals($default, $helper->getDefault());
    }

    public function testNoOptionSpecifiedReturnsFalse(): void
    {
        $this->setupEnvironment('/path/to/files', null);
        $this->assertFalse($this->helper->fetch());
    }

    public function testNoOptionSpecifiedReturnsDefaultConfiguration(): void
    {
        $default = ['my' => 'default', 'configuration'];
        $this->setupDefault($default);
        $this->assertEquals($default, $this->helper->fetch());
    }

    public function testInitHelperReturnsHelperInstance(): void
    {
        $application = new Application();
        $this->assertInstanceOf(
            ConfigurationHelper::class,
            ConfigurationHelper::initHelper($application)
        );
    }

    public function testInitHelperSetsDefaultConfiguration(): void
    {
        $default = ['my' => 'config'];
        $application = new Application();
        $helper = ConfigurationHelper::initHelper($application, $default);
        $this->assertEquals($default, $helper->getDefault());
    }

    public function testInitHelperInitializes(): void
    {
        $application = new Application();
        ConfigurationHelper::initHelper($application);
        $this->assertTrue($application->getDefinition()->hasOption('config'));
    }

    public function testDetectsFile(): void
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;
        $this->setupEnvironment(dirname($filename), null);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    public function testWithFileSpecified(): void
    {
        $filename = __DIR__ . '/files/my-diff-config.php';
        $expected = include $filename;
        $this->setupEnvironment('/path/to/files', $filename);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    public function testWithYmlFileSpecified(): void
    {
        $filename = __DIR__ . '/files/cli-config.yml';
        $expected = Yaml::parse(file_get_contents($filename));
        $this->setupEnvironment('/path/to/files', $filename);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testWithUnsupportedExtensionFileSpecified(): void
    {
        $filename = __DIR__ . '/files/cli-config.lala';
        $this->setupEnvironment('/path/to/files', $filename);
        $this->helper->fetch();
    }

    public function testUsesSpecifiedFilenameFormat(): void
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

    public function testSpecifiedDirectory(): void
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;
        $this->setupEnvironment('/path/to/files', dirname($filename));
        $this->assertEquals($expected, $this->helper->fetch());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSpecifiedFileIsNotFound(): void
    {
        $this->setupEnvironment('/path/to/files', __DIR__ . '/files/my-config-not-here.php');
        $this->helper->fetch();
    }

    public function testTheResultIsCached(): void
    {
        $this->input->expects($this->any())
            ->method('hasOption')
            ->will($this->returnValue(true));
        $this->input->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(__DIR__ . '/files/cli-config.php'));
        $this->helper->fetch();
    }

    public function testNoConfigInputOptionGoesToAutodetect(): void
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;

        $this->setupEnvironment(dirname($filename), null, false);
        $this->assertEquals($expected, $this->helper->fetch());
    }

    /**
     * @dataProvider getConfigPathDataProvider
     */
    public function testGetConfigPath(string $expected, string $setupMethod, array $setupArgs): void
    {
        call_user_func_array([$this, $setupMethod], $setupArgs);
        $this->assertEquals($expected, $this->helper->getConfigPath());
    }

    public function getConfigPathDataProvider(): array
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

    private function setupEnvironment(string $cwd, ?string $inputGet, bool $inputHas = true): void
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

    private function setupDefault(array $default): void
    {
        $this->setupEnvironment('/path/to/files', null);
        $this->helper->setDefault($default);
    }
}
