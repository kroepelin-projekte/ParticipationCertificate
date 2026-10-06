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
            $resource = new ResourceIdentification($value);

            if ($DIC->resourceStorage()->manage()->find($resource)) {
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