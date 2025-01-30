<?php

declare(strict_types=1);

namespace Phlib\ConsoleConfiguration\Helper;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * @package phlib/console-configuration
 */
class ConfigurationHelper extends AbstractHelper implements InputAwareInterface
{
    protected InputInterface $input;

    protected string $detectedPath;

    protected mixed $config;

    protected mixed $default;

    /**
     * @param mixed $default
     */
    public static function initHelper(Application $application, $default = null, array $options = []): self
    {
        $options += [
            'name' => 'config',
            'abbreviation' => 'c',
            'description' => 'Path to the configuration file.',
            'filename' => 'cli-config.php',
        ];

        $helper = new static($options['name'], $options['filename']);
        $helper->setDefault($default);

        $application
            ->getDefinition()
            ->addOption(new InputOption(
                $options['name'],
                $options['abbreviation'],
                InputOption::VALUE_REQUIRED,
                $options['description']
            ));
        $application
            ->getHelperSet()
            ->set($helper);

        return $helper;
    }

    public function __construct(
        protected string $name = 'config',
        protected string $filename = 'cli-config.php'
    ) {
    }

    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @api
     */
    public function getName(): string
    {
        return 'configuration';
    }

    /**
     * Gets the default configuration.
     *
     * @return mixed|false
     */
    public function getDefault(): mixed
    {
        if (!isset($this->default)) {
            $this->detectedPath = '[none]';
            return false;
        }
        $this->detectedPath = '[default]';
        return $this->default;
    }

    /**
     * Sets the default configuration used if none is specified or found.
     */
    public function setDefault(mixed $value): self
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Fetches the configuration. If no option is specified it'll search the local path for a file with the expected
     * filename. If an option is provided, it'll try to load the configuration from that file. If no configuration is
     * found then it returns false. There is no enforced return type.
     *
     * @return mixed|false
     */
    public function fetch(): mixed
    {
        if (!isset($this->config)) {
            $this->config = $this->loadConfiguration();
        }
        return $this->config;
    }

    /**
     * Gets the location of the detected configuration file. If none was detected and a default exists, '[default]' is
     * returned as the description. If none was detected and NO default exists, then '[none]' is returned.
     */
    public function getConfigPath(): string
    {
        $this->fetch();
        return $this->detectedPath;
    }

    protected function loadConfiguration(): mixed
    {
        $path = null;
        if ($this->input->hasOption($this->name)) {
            $path = $this->input->getOption($this->name);
        }

        $config = $path === null ? $this->loadFromDetectedFile() : $this->loadFromSpecificFile($path);

        if ($config === false) {
            $config = $this->getDefault();
        }
        return $config;
    }

    /**
     * @return mixed|false
     */
    protected function loadFromDetectedFile(): mixed
    {
        $filePath = $this->detectFile();
        if ($filePath === false || !is_file($filePath) || !is_readable($filePath)) {
            return $this->getDefault();
        }
        $this->detectedPath = $filePath;

        return $this->getConfigurationArray($filePath);
    }

    protected function loadFromSpecificFile(string $filePath): mixed
    {
        if (is_dir($filePath)) {
            $filePath = $filePath . DIRECTORY_SEPARATOR . $this->filename;
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("Specified configuration '{$filePath}' is not accessible.");
        }

        $this->detectedPath = $filePath;

        return $this->getConfigurationArray($filePath);
    }

    protected function getConfigurationArray(string $filePath): mixed
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === 'php') {
            $configuration = include $filePath;
        } elseif ($extension === 'yml' || $extension === 'yaml') {
            $configuration = Yaml::parse(file_get_contents($filePath));
        } else {
            throw new \UnexpectedValueException("ConfigurationHelper: Extension \"{$extension}\" isn't supported");
        }

        return $configuration;
    }

    protected function detectFile(): string|false
    {
        $directories = [getcwd(), getcwd() . DIRECTORY_SEPARATOR . 'config'];
        $configFile = null;
        foreach ($directories as $directory) {
            $configFile = $directory . DIRECTORY_SEPARATOR . $this->filename;
            if (file_exists($configFile)) {
                return $configFile;
            }
        }
        return false;
    }
}
