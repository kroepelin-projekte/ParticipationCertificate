<?php

class ilParticipationCertificateConfig extends ActiveRecord
{

    public const string TABLE_NAME = 'dhbw_part_cert_conf';

    public const string LOGO_FILE_NAME = 'pic.png';

    public const string  ISSUER_SIGNATURE_FILE_NAME = 'page1_issuer_signature.png';

    public const int CONFIG_SET_TYPE_TEMPLATE = 1;

    public const int CONFIG_SET_TYPE_GROUP = 2;

    public const int CONFIG_SET_TYPE_GLOBAL = 3;

    public const int CONFIG_VALUE_TYPE_CERT_TEXT = 1;

    public const int CONFIG_VALUE_TYPE_OTHER = 2;

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_primary   true
     * @db_sequence     true
     */
    protected ?int $id = 0;
    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     * @db_length       8
     */
    protected int $config_type;
    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     * @db_length       8
     */
    protected int $group_ref_id = 0;
    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     * @db_length       8
     */
    protected int $global_config_id = 0;
    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     * @db_length       8
     */
    protected int $config_value_type;
    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @con_is_notnull  true
     * @db_length       1024
     */
    protected string $config_key;
    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       1024
     */
    protected ?string $config_value = '';
    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @con_is_notnull  true
     * @db_length       8
     */
    protected int $order_by = 0;

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function __construct($primary_key = 0, arConnector $connector = null)
    {
        parent::__construct($primary_key, $connector);
    }

    public static function getConfig(
        string $config_key,
        int $group_ref_id = 0,
        int $config_type = self::CONFIG_SET_TYPE_GROUP,
        int $config_value_type = self::CONFIG_VALUE_TYPE_OTHER,
        ?string $default = null
    ): ?string {
        /**
         * @var ilParticipationCertificateConfig|null $config
         */
        $config = self::where([
            'config_key' => $config_key,
            'config_type' => $config_type,
            'config_value_type' => $config_value_type,
            'group_ref_id' => $group_ref_id
        ])->first();

        if ($config !== null) {
            return $config->getConfigValue();
        }
        return $default;
    }

    /**
     * @param string      $config_key
     * @param string|null $config_value
     * @param int         $group_ref_id
     * @param int         $config_type
     * @param int         $config_value_type
     * @return void
     */
    public static function setConfig(
        string $config_key,
        ?string $config_value,
        int $group_ref_id = 0,
        int $config_type = self::CONFIG_SET_TYPE_GROUP,
        int $config_value_type = self::CONFIG_VALUE_TYPE_OTHER
    ): void {
        /**
         * @var ilParticipationCertificateConfig|null $config
         */
        $config = self::where([
            'config_key' => $config_key,
            'config_type' => $config_type,
            'config_value_type' => $config_value_type,
            'group_ref_id' => $group_ref_id
        ])->first();

        if ($config !== null) {
            $config->setConfigValue($config_value);
            $config->update();
        } else {
            $config = new self();

            $config->setConfigKey($config_key);
            $config->setConfigValue($config_value);
            $config->setConfigType($config_type);
            $config->setConfigValueType($config_value_type);
            $config->setGroupRefId($group_ref_id);
            $config->create();
        }
    }

    /**
     * Get a path where the template layout file and static assets are stored
     */
    public static function getFileStoragePath(
        string $type = 'img',
        string $path_type = 'absolute',
        int $grp_ref_id = 0,
        bool $create = false
    ): string {
        $path = match ($path_type) {
            'relative' => ilFileUtils::getWebspaceDir() . '/dhbw_part_cert',
            default => CLIENT_WEB_DIR . '/dhbw_part_cert',
        };

        if ($grp_ref_id) {
            $path = $path . '/' . $grp_ref_id;
        }

        switch ($type) {
            case 'img':
                $path = $path . '/img/';
                if (!is_dir($path) && $create) {
                    ilFileUtils::makeDirParents($path);
                }

                return $path;
                break;
            default:
                if (!is_dir($path) && $create) {
                    ilFileUtils::makeDirParents($path);
                }
                return $path;
        }
    }

    /**
     * @param array  $file_data
     * @param int    $template_id
     * @param string $file_name
     * @return string
     * @throws ilException
     */
    public static function storePicture(array $file_data, int $template_id, string $file_name): string
    {
        self::deletePicture($template_id, $file_name);

        $file_path = self::getFileStoragePath('img', 'absolute', $template_id, true);
        ilFileUtils::moveUploadedFile($file_data['tmp_name'], '', $file_path . $file_name);

        return self::returnPicturePath('relative', $template_id, $file_name);
    }

    /**
     * @param int    $template_id
     * @param string $file_name
     * @return void
     */
    public static function deletePicture(int $template_id, string $file_name): void
    {
        $file_path = self::returnPicturePath('absolute', $template_id, $file_name);

        if (is_file($file_path)) {
            unlink($file_path);
        }
    }

    /**
     * @param string $path_type absolute|relative
     * @param int    $grp_ref_id
     * @param        $file_name
     * @return string
     */
    public static function returnPicturePath(string $path_type, int $grp_ref_id, $file_name): string
    {
        return self::getFileStoragePath('img', $path_type, $grp_ref_id, true) . $file_name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->config_key;
    }

    /**
     * @param string $config_key
     * @return void
     */
    public function setConfigKey(string $config_key): void
    {
        $this->config_key = $config_key;
    }

    /**
     * @return string|null
     */
    public function getConfigValue(): ?string
    {
        return $this->config_value;
    }

    /**
     * @param string|null $config_value
     * @return void
     */
    public function setConfigValue(?string $config_value): void
    {
        $this->config_value = $config_value;
    }

    /**
     * @return int
     */
    public function getConfigType(): int
    {
        return $this->config_type;
    }

    /**
     * @param int $config_type
     * @return void
     */
    public function setConfigType(int $config_type): void
    {
        $this->config_type = $config_type;
    }

    /**
     * @return int
     */
    public function getConfigValueType(): int
    {
        return $this->config_value_type;
    }

    /**
     * @param int $config_value_type
     * @return void
     */
    public function setConfigValueType(int $config_value_type): void
    {
        $this->config_value_type = $config_value_type;
    }

    /**
     * @return int
     */
    public function getGroupRefId(): int
    {
        return $this->group_ref_id;
    }

    /**
     * @param int $group_ref_id
     * @return void
     */
    public function setGroupRefId(int $group_ref_id): void
    {
        $this->group_ref_id = $group_ref_id;
    }

    /**
     * @return int
     */
    public function getGlobalConfigId(): int
    {
        return $this->global_config_id;
    }

    /**
     * @param int $global_config_id
     * @return void
     */
    public function setGlobalConfigId(int $global_config_id): void
    {
        $this->global_config_id = $global_config_id;
    }

    /**
     * @return int
     */
    public function getOrderBy(): int
    {
        return $this->order_by;
    }

    /**
     * @param int $order_by
     * @return void
     */
    public function setOrderBy(int $order_by): void
    {
        $this->order_by = $order_by;
    }

    /**
     * @return array
     */
    public static function returnDefaultValuesTypeOther(): array
    {
        return array(
            'udf_firstname' => 0,
            'udf_lastname' => 0,
            'color' => '73B249',
            'keyword' => 'Lerngruppe',
            'Logo' => null,
        );
    }
}