<?php

declare(strict_types=1);

namespace Phlib\ConsoleConfiguration\Helper;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurationHelperTest extends TestCase
{
    use PHPMock;

    /**
     * @var InputInterface|MockObject
     */
    protected $input;

    /**
     * @var ConfigurationHelper|MockObject
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->input = $this->createMock(InputInterface::class);
        $this->helper = new ConfigurationHelper();
        $this->helper->setInput($this->input);
    }

    public function testImplementsInputAwareInterface(): void
    {
        static::assertInstanceOf(InputAwareInterface::class, $this->helper);
    }

    public function testGetName(): void
    {
        static::assertSame('configuration', (new ConfigurationHelper())->getName());
    }

    public function testSettingGettingDefaultConfiguration(): void
    {
        $default = [
            'my' => 'default',
            'configuration',
        ];
        $helper = new ConfigurationHelper();
        $helper->setDefault($default);
        static::assertSame($default, $helper->getDefault());
    }

    public function testNoOptionSpecifiedReturnsFalse(): void
    {
        $this->setupEnvironment('/path/to/files', null);
        static::assertFalse($this->helper->fetch());
    }

    public function testNoOptionSpecifiedReturnsDefaultConfiguration(): void
    {
        $default = [
            'my' => 'default',
            'configuration',
        ];
        $this->setupDefault($default);
        static::assertSame($default, $this->helper->fetch());
    }

    public function testInitHelperReturnsHelperInstance(): void
    {
        $application = new Application();
        static::assertInstanceOf(ConfigurationHelper::class, ConfigurationHelper::initHelper($application));
    }

    public function testInitHelperSetsDefaultConfiguration(): void
    {
        $default = [
            'my' => 'config',
        ];
        $application = new Application();
        $helper = ConfigurationHelper::initHelper($application, $default);
        static::assertSame($default, $helper->getDefault());
    }

    public function testInitHelperInitializes(): void
    {
        $application = new Application();
        ConfigurationHelper::initHelper($application);
        static::assertTrue($application->getDefinition()->hasOption('config'));
    }

    public function testDetectsFile(): void
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;
        $this->setupEnvironment(dirname($filename), null);
        static::assertSame($expected, $this->helper->fetch());
    }

    public function testWithFileSpecified(): void
    {
        $filename = __DIR__ . '/files/my-diff-config.php';
        $expected = include $filename;
        $this->setupEnvironment('/path/to/files', $filename);
        static::assertSame($expected, $this->helper->fetch());
    }

    public function testWithYmlFileSpecified(): void
    {
        $filename = __DIR__ . '/files/cli-config.yml';
        $expected = Yaml::parse(file_get_contents($filename));
        $this->setupEnvironment('/path/to/files', $filename);
        static::assertSame($expected, $this->helper->fetch());
    }

    public function testWithUnsupportedExtensionFileSpecified(): void
    {
        $this->expectException(\UnexpectedValueException::class);
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
        $getcwd = $this->getFunctionMock(__NAMESPACE__, 'getcwd');
        $getcwd->expects(static::any())
            ->willReturn(__DIR__ . '/files');
        static::assertSame($expected, $helper->fetch());
    }

    public function testSpecifiedDirectory(): void
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;
        $this->setupEnvironment('/path/to/files', dirname($filename));
        static::assertSame($expected, $this->helper->fetch());
    }

    public function testSpecifiedFileIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->setupEnvironment('/path/to/files', __DIR__ . '/files/my-config-not-here.php');
        $this->helper->fetch();
    }

    public function testTheResultIsCached(): void
    {
        $this->input->expects(static::any())
            ->method('hasOption')
            ->willReturn(true);
        $this->input->expects(static::once())
            ->method('getOption')
            ->willReturn(__DIR__ . '/files/cli-config.php');
        $this->helper->fetch();
    }

    public function testNoConfigInputOptionGoesToAutodetect(): void
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $expected = include $filename;

        $this->setupEnvironment(dirname($filename), null, false);
        static::assertSame($expected, $this->helper->fetch());
    }

    /**
     * @dataProvider getConfigPathDataProvider
     */
    public function testGetConfigPath(string $expected, string $setupMethod, array $setupArgs): void
    {
        call_user_func_array([$this, $setupMethod], $setupArgs);
        static::assertSame($expected, $this->helper->getConfigPath());
    }

    public function getConfigPathDataProvider(): array
    {
        $filename = __DIR__ . '/files/cli-config.php';
        $default = [
            'some' => 'settings',
        ];

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
        $getcwd = $this->getFunctionMock(__NAMESPACE__, 'getcwd');
        $getcwd->expects(static::any())
            ->willReturn($cwd);

        $this->input->expects(static::any())
            ->method('hasOption')
            ->willReturn($inputHas);
        $this->input->expects(static::any())
            ->method('getOption')
            ->willReturn($inputGet);
    }

    private function setupDefault(array $default): void
    {
        $this->setupEnvironment('/path/to/files', null);
        $this->helper->setDefault($default);
    }
}
