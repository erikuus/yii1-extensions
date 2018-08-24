<?php
/**
 * Central hub for configuration and session management.
 *
 * Directory where temporary files are stored can be configured by passing
 * configuration array to constructor. Default directory for temporary files
 * is `sys_get_temp_dir() . DIRECTORY_SEPARATOR . "php-dds-hashcode"`
 *
 * ```php
 * // Example 1. Overriding default configuration.
 * use SK\Digidoc\Digidoc;
 * // You can override default configuration parameters by
 * // by passing array of configuration variables to Digidoc-s constructor.
 * // currently only setting temporary dir is supported like so:
 * $digidoc = new Digidoc(
 *     array(
 *         Digidoc::TEMPORARY_DIR => '/path/to/dir'
 *     ));
 * $digidoc->createSession(); // and so on...
 *
 * ```
 *
 * Every {@link DigidocSession} gets its own private directory for temporary files
 * which will be deleted by calling {@link DigidocSession::end()} on {@link DigidocSession}
 * instance. To delete all temporary files in temporary directory you can call
 * {@link Digidoc::deleteLocalTempFiles()}
 *
 */

class Digidoc
{
    /**
     * Configuration key for temporary dir.
     *
     * @var string
     */
    const TEMPORARY_DIR = 'temporary_dir';
    const DIGIDOC_VERSION = '1.1.4';
    const HASHCODE_DEFAULT_TEMP_HASHCODE_DIRECTORY = 'php-dds-hashcode';
    const DDOC_DATA_FILE_CHUNK_SPLIT = true;

    private $configuration;

    /**
     * Creates new Digidoc Service instance with given configuration or using default configuration
     *
     * Digidoc constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = array())
    {
        $this->configuration = array_merge($this->configurationDefaults(), $configuration);
    }

    /**
     * Get dds-hashcode library version
     *
     * @return string
     */
    public static function version()
    {
        return static::DIGIDOC_VERSION;
    }

    /**
     * Factory method to create hashcode session.
     *
     * @return DigidocSession
     */
    public function createSession()
    {
        return new DigidocSession($this->configuration);
    }

    /**
     * Delete files from temporary file directory
     */
    public function deleteLocalTempFiles()
    {
        $dir = static::temporaryDirectory($this->configuration);
        if (file_exists($dir)) {
            static::deleteAllFilesInDirectory($dir);
        }
    }

    /**
     * Get or set temporary upload directory for files
     *
     * @param array $configuration
     *
     * @return string
     */
    public static function temporaryDirectory($configuration)
    {
        return empty($configuration[static::TEMPORARY_DIR])
            ?
            Digidoc::defaultTemporaryDirectory()
            :
            $configuration[static::TEMPORARY_DIR];
    }

    /**
     * Deletes all files in directory.
     *
     * @internal for internal use only
     *
     * @param String $dir
     */
    public static function deleteAllFilesInDirectory($dir)
    {
        $dirIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }
    }

    /**
     * Default values for Digidoc Service configuration
     *
     * @return array
     */
    private function configurationDefaults()
    {
        return array(static::TEMPORARY_DIR => static::defaultTemporaryDirectory());
    }

    private static function defaultTemporaryDirectory()
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.static::HASHCODE_DEFAULT_TEMP_HASHCODE_DIRECTORY;
    }
}
