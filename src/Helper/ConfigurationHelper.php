<?php

namespace Phlib\ConsoleConfiguration\Helper;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigurationHelper
 * @package Phlib\ConsoleConfiguration\Helper
 */
class ConfigurationHelper extends AbstractHelper implements InputAwareInterface
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $detectedPath;

    /**
     * @var mixed
     */
    protected $config = null;

    /**
     * @var mixed
     */
    protected $default = null;

    /**
     * @param mixed $default
     * @return ConfigurationHelper
     */
    public static function initHelper(Application $application, $default = null, array $options = [])
    {
        $options = $options + [
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

    /**
     * @param string $name
     * @param string $filename
     */
    public function __construct($name = 'config', $filename = 'cli-config.php')
    {
        $this->name = $name;
        $this->filename = $filename;
    }

    /**
     * Sets the Console Input.
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'configuration';
    }

    /**
     * Gets the default configuration.
     *
     * @return mixed|false
     */
    public function getDefault()
    {
        if ($this->default === null) {
            $this->detectedPath = '[none]';
            return false;
        }
        $this->detectedPath = '[default]';
        return $this->default;
    }

    /**
     * Sets the default configuration used if none is specified or found.
     *
     * @param mixed $value
     * @return $this
     */
    public function setDefault($value)
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
    public function fetch()
    {
        if ($this->config === null) {
            $this->config = $this->loadConfiguration();
        }
        return $this->config;
    }

    /**
     * Gets the location of the detected configuration file. If none was detected and a default exists, '[default]' is
     * returned as the description. If none was detected and NO default exists, then '[none]' is returned.
     *
     * @return string
     */
    public function getConfigPath()
    {
        $this->fetch();
        return $this->detectedPath;
    }

    /**
     * @return mixed
     */
    protected function loadConfiguration()
    {
        $path = null;
        if ($this->input->hasOption($this->name)) {
            $path = $this->input->getOption($this->name);
        }

        if ($path === null) {
            $config = $this->loadFromDetectedFile();
        } else {
            $config = $this->loadFromSpecificFile($path);
        }

        if ($config === false) {
            $config = $this->getDefault();
        }
        return $config;
    }

    /**
     * @return mixed|false
     * @throws \UnexpectedValueException
     */
    protected function loadFromDetectedFile()
    {
        $filePath = $this->detectFile();
        if ($filePath === false || !is_file($filePath) || !is_readable($filePath)) {
            return $this->getDefault();
        }
        $this->detectedPath = $filePath;

        return $this->getConfigurationArray($filePath);
    }

    /**
     * @param string $filePath
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    protected function loadFromSpecificFile($filePath)
    {
        if (is_dir($filePath)) {
            $filePath = $filePath . DIRECTORY_SEPARATOR . $this->filename;
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("Specified configuration '${filePath}' is not accessible.");
        }

        $this->detectedPath = $filePath;

        return $this->getConfigurationArray($filePath);
    }

    /**
     * @param string $filePath
     * @return mixed
     * @throws \UnexpectedValueException
     */
    protected function getConfigurationArray($filePath)
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

    /**
     * @return string|false
     */
    protected function detectFile()
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
