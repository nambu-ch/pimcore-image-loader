<?php
namespace ImageLoaderBundle\Install;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Extension\Bundle\Installer\OutputWriterInterface;
use Symfony\Component\Filesystem\Filesystem;

class Installer extends AbstractInstaller {

    /**
     * @var string
     */
    private $installSourcesPath;
    /**
     * @var Filesystem
     */
    private $fileSystem;
    const LOCAL_CONFIG_PATH = '/bundles/ImageLoaderBundle/config.yml';
    const LOCAL_CONFIG_BACKUP_PATH = '/bundles/ImageLoaderBundle/config_backup.yml';

    public function __construct(OutputWriterInterface $outputWriter = null) {
        parent::__construct($outputWriter);
        $this->installSourcesPath = __DIR__ . '/../Resources/install';
        $this->fileSystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function install(): void {
        $this->copyConfigFile();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void {
        $target = PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_PATH;
        if ($this->fileSystem->exists(PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_BACKUP_PATH)) {
            $this->fileSystem->remove(PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_BACKUP_PATH);
        }
        if ($this->fileSystem->exists($target)) {
            $this->fileSystem->rename(
                $target,
                PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_BACKUP_PATH
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool {
        $target = PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_PATH;
        return $this->fileSystem->exists($target);
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled(): bool {
        $target = PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_PATH;
        return !$this->fileSystem->exists($target);
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled(): bool {
        $target = PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_PATH;
        return $this->fileSystem->exists($target);
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall(): bool {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUpdated(): bool {
        return false;
    }

    /**
     * copy sample config file - if not exists.
     */
    private function copyConfigFile() {
        $target = PIMCORE_PRIVATE_VAR . self::LOCAL_CONFIG_PATH;
        if (!$this->fileSystem->exists($target)) {
            $this->fileSystem->copy(
                $this->installSourcesPath . '/config.yml',
                $target
            );
        }
    }

}
