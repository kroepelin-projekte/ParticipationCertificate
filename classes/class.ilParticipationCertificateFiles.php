<?php

use ILIAS\ResourceStorage\Identification\ResourceIdentification;

class ilParticipationCertificateFiles extends ActiveRecord
{
    public const string TABLE_NAME = 'dhbw_part_cert_files';

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_is_primary   true
     * @db_sequence     true
     */
    protected ?int $id = 0;

    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @con_is_notnull  true
     */
    protected string $type;

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     */
    protected int $config_id;

    /**
     * @var bool
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     * @db_length       1
     */
    protected bool $resource_storage;

    /**
     * @param                  $primary_key
     * @param arConnector|null $connector
     */
    public function __construct($primary_key = 0, arConnector $connector = null)
    {
        parent::__construct($primary_key, $connector);
    }

    /**
     * @return string
     */
    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @return string
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @param string $config_id
     * @param string $file_type
     * @return ActiveRecord|null
     */
    public static function getFile(
        string $config_id,
        string $file_type
    ): ActiveRecord|null {
        /**
         * @var ilParticipationCertificateFiles|null $config
         */
        $file = self::where([
            'config_id' => $config_id,
            'type' => $file_type,
        ])->first();

        return $file ?? null;
    }

    /**
     * @param string $config_id
     * @param string $file_type
     * @param bool   $resource_storage
     * @return void
     */
    public static function setFile(
        string $config_id,
        string $file_type,
        bool $resource_storage
    ): void {
        /**
         * @var ilParticipationCertificateFiles|null $file
         */
        $file = self::where([
            'config_id' => $config_id,
            'type' => $file_type,
        ])->first();

        if (!empty($file)) {
            $file->setConfigId($config_id);
            $file->setType($file_type);
            $file->setResourceStorage($resource_storage);
            $file->update();
        } else {
            $file = new self();
            $file->setConfigId($config_id);
            $file->setResourceStorage($resource_storage);
            $file->setType($file_type);
            $file->create();
        }
    }

    /**
     * @param string $group_ref_id
     * @return void
     */
    public function setConfigId(string $group_ref_id): void
    {
        $this->config_id = $group_ref_id;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param bool $resource_storage
     * @return void
     */
    public function setResourceStorage(bool $resource_storage): void
    {
        $this->resource_storage = $resource_storage;
    }

    /**
     * @param int $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getConfigId(): string
    {
        return $this->config_id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function getResourceStorage(): bool
    {
        return $this->resource_storage;
    }

    /**
     * Files are stored under ./data/default/dhbw_part_cert in ILIAS8, while the resource storage is used in ILIAS9.
     *
     * @param        $value
     * @param string $config_id
     * @param string $file_type
     * @return string
     */
    /**
     * Returns the resource only if it exists in the IRSS and is owned by this plugin's stakeholder.
     * Resource ids posted with forms or stored in the config must pass this check before they are
     * stored or served; otherwise any IRSS file whose id is known could be read (IDOR).
     */
    public static function findOwnResource(?string $identifier): ?ResourceIdentification
    {
        global $DIC;

        if ($identifier === null || trim($identifier) === '') {
            return null;
        }

        $manager = $DIC->resourceStorage()->manage();
        $id = $manager->find($identifier);
        if ($id === null) {
            return null;
        }

        $own_stakeholder_id = (new ilParticipationCertificateStakeholder())->getId();
        foreach ($manager->getResource($id)->getStakeholders() as $stakeholder) {
            if ($stakeholder->getId() === $own_stakeholder_id) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Returns a plugin-owned PNG/JPEG resource as data URI for the PDF, or '' if it is not usable.
     * The PDF must not get a delivery URL: mPDF would fetch it over HTTP from the server itself,
     * and that URL is built from the request's Host header (SSRF).
     */
    public static function getImageDataUri(?string $identifier): string
    {
        global $DIC;

        $id = self::findOwnResource($identifier);
        if ($id === null) {
            return '';
        }

        $content = (string) $DIC->resourceStorage()->consume()->stream($id)->getStream()->getContents();
        $image_info = $content !== '' ? @getimagesizefromstring($content) : false;
        if ($image_info === false || !in_array($image_info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return '';
        }

        return 'data:' . $image_info['mime'] . ';base64,' . base64_encode($content);
    }

    /**
     * @return void
     */
    public static function migrateLegacyFiles(): void
    {
        global $DIC;

        if (!isset($DIC) || !$DIC->offsetExists('resourceStorage') || !defined('CLIENT_WEB_DIR')) {
            return;
        }

        $legacy_root = CLIENT_WEB_DIR . '/dhbw_part_cert';
        $logger = $DIC->logger()->root();
        $all_migrated = true;

        /** @var ilParticipationCertificateFiles[] $legacy_files */
        $legacy_files = self::where(['resource_storage' => 0])->get();
        foreach ($legacy_files as $legacy_file) {
            $config_id = (int) $legacy_file->getConfigId();
            $type = $legacy_file->getType();
            $file_name = $type === 'page1_issuer_signature'
                ? ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME
                : ilParticipationCertificateConfig::LOGO_FILE_NAME;
            $path = $legacy_root . '/' . $config_id . '/img/' . $file_name;

            if (!is_file($path)) {
                continue; // nothing on disk; the row was not usable before either
            }

            try {
                $handle = fopen($path, 'rb');
                if ($handle === false) {
                    throw new RuntimeException('Cannot open ' . $path);
                }
                $rid = $DIC->resourceStorage()->manage()->stream(
                    \ILIAS\Filesystem\Stream\Streams::ofResource($handle),
                    new ilParticipationCertificateStakeholder(),
                    $file_name
                )->serialize();

                $configs = array_merge(
                    ilParticipationCertificateConfig::where([
                        'config_key' => $type,
                        'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE,
                        'global_config_id' => $config_id,
                    ])->get(),
                    ilParticipationCertificateConfig::where([
                        'config_key' => $type,
                        'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP,
                        'group_ref_id' => $config_id,
                    ])->get()
                );
                foreach ($configs as $config) {
                    $config->setConfigValue($rid);
                    $config->update();
                }

                $legacy_file->setResourceStorage(true);
                $legacy_file->update();

                $logger->info('Participation certificate: migrated legacy ' . $type . ' of config ' . $config_id . ' to IRSS ' . $rid);
            } catch (\Throwable $e) {
                $all_migrated = false;
                $logger->warning('Participation certificate: could not migrate ' . $path . ': ' . $e->getMessage());
            }
        }

        // returnPicturePath() may recreate empty legacy folders; only move when real files are left
        if ($all_migrated && self::containsFiles($legacy_root) && defined('CLIENT_DATA_DIR')) {
            $backup = CLIENT_DATA_DIR . '/dhbw_part_cert_legacy_' . date('Ymd_His');
            if (@rename($legacy_root, $backup)) {
                $logger->info('Participation certificate: moved legacy folder out of the web root to ' . $backup);
            } else {
                $logger->warning('Participation certificate: could not move ' . $legacy_root . ' to ' . $backup);
            }
        }
    }

    private static function containsFiles(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                return true;
            }
        }
        return false;
    }

    public function getFileSrcByStorageType(
        $value,
        string $config_id,
        string $file_type
    ): string {
        global $DIC;

        $file = ilParticipationCertificateFiles::getFile($config_id, $file_type);

        if (empty($file)) {
            return '';
        }
        $file_array = $file->asArray();

        $src = '';
        if ($file_array['resource_storage']) {
            // only serve resources owned by this plugin, even if a foreign id got stored somehow
            $resource = self::findOwnResource(is_string($value) ? $value : null);

            if ($resource !== null) {
                $src = $DIC->resourceStorage()->consume()
                           ->src($resource)
                           ->getSrc();
            }
        } else {
            $file_name = ilParticipationCertificateConfig::LOGO_FILE_NAME;
            if ($file_type === 'page1_issuer_signature') {
                $file_name = ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME;
            }

            $file_path = ilParticipationCertificateConfig::returnPicturePath(
                'relative',
                $config_id,
                $file_name
            );

            if (is_file($file_path)) {
                $stream = \ILIAS\Filesystem\Stream\Streams::ofResource(
                    fopen($file_path, 'r')
                );

                $src = $DIC->fileDelivery()->buildTokenURL(
                    $stream,
                    $file_name,
                    \ILIAS\FileDelivery\Delivery\Disposition::INLINE,
                    $DIC->user()->getId(),
                    6
                );
            }
        }
        return $src;
    }
}