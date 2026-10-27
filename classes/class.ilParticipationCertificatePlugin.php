<?php

/**
 * Class ilParticipationCertificatePlugin
 *
 * @author Silas Stulz <sst@studer-raimann.ch>
 */
class ilParticipationCertificatePlugin extends ilUserInterfaceHookPlugin
{
    public const string PLUGIN_ID = 'dhbwparticipationpdf';

    public const string PLUGIN_NAME = 'ParticipationCertificate';

    public const string PLUGIN_DIRECTORY = 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate';

    public const string CERTIFICATIONS_PATH = 'dhbw_part_cert';

    public const string PLUGIN_VERSION_FILES_PATH_MOVED_IN_DB = '1.2.1';

    public const string PLUGIN_VERSION_FOR_INSERTING_NEW_CONFIGS = '2.1.0';

    protected static ?ilParticipationCertificatePlugin $instance = null;

    private ilPluginInfo $plugin_info;

    protected ilDBInterface $db;

    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;
        parent::__construct($db, $component_repository, $id);

        $this->db = $DIC->database();

        $this->plugin_info = $this->getPluginInfo();
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @return ilParticipationCertificatePlugin
     */
    public static function getInstance(): ilParticipationCertificatePlugin
    {
        global $DIC;

        /** @var ilParticipationCertificatePlugin $plugin */
        $plugin = $DIC['component.factory']->getPlugin(self::PLUGIN_ID);

        return $plugin;
    }

    protected function afterUninstall(): void
    {
        $tables = [
            'participationcert',
            'dhbw_part_cert_ob_conf',
            'dhbw_part_cert_conf',
            'dhbw_part_cert_gl_conf',
            'dhbw_part_cert_files'
        ];

        foreach ($tables as $table) {
            try {
                $this->db->dropTable($table, false);
                $this->db->dropSequence($table);
            } catch (Exception $e) {
                //ignore
            }
        }
    }

    /**
     * @param string $image_name
     * @return string
     */
    public function getImagePath(string $image_name): string
    {
        return $this->getDirectory() . '/templates/images/' . $image_name;
    }

    /**
     * @return void
     */
    protected function afterUpdate(): void
    {
        $plugin_version = $this->plugin_info->getCurrentVersion();
        $version_before_update = $plugin_version->getMajor() . '.' . $plugin_version->getMinor() . '.' . $plugin_version->getPatch();

        if (version_compare($this->getVersion(), self::PLUGIN_VERSION_FOR_INSERTING_NEW_CONFIGS, '=')) {
            $this->insertNewParticipationCertificateConfigs();
        }


        if (version_compare($version_before_update, self::PLUGIN_VERSION_FILES_PATH_MOVED_IN_DB, '>=')) {
            return; // file paths already where transferred in database in an older version of the plugin
        }

        $this->moveFilesPathInDB();

    }

    /**
     * @return void
     */
    private function moveFilesPathInDB()
    {

        // Get all files and directories in the certifications' path
        $path = CLIENT_WEB_DIR . '/' . self::CERTIFICATIONS_PATH;
        $items = scandir($path);

        // Filter out the current (.) and parent (..) directories, and keep only directories
        $directories = array_filter($items, function ($item) use ($path) {
            return is_dir($path . '/' . $item) && $item !== '.' && $item !== '..';
        });

        $directories = array_values($directories);

        foreach ($directories as $directory) {
            $file = new ilParticipationCertificateFiles();
            if (is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', (int) $directory, ilParticipationCertificateConfig::LOGO_FILE_NAME))) {
                $file->setFile($directory, 'logo', false);
            }

            if (is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', (int) $directory, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                $file->setFile($directory, 'page1_issuer_signature', false);
            }
        }
    }

    /**
     * @return void
     */
    private function insertNewParticipationCertificateConfigs(): void
    {
        $participation_certificate_configs = ilParticipationCertificateConfig::get();

        $order_by_global_ids = [];
        foreach ($participation_certificate_configs as $config) {
            /* @var $config ilParticipationCertificateConfig */
            $order_by_global_ids[$config->getGlobalConfigId()][] = $config->getOrderBy();
        }

        $config_was_created_for_global_ids = [];
        foreach ($participation_certificate_configs as $config) {
            /* @var $config ilParticipationCertificateConfig */
            $global_config_id = $config->getGlobalConfigId();
            $config_type = $config->getConfigType();
            $group_ref_id = $config->getGroupRefId();
            $config_value_type = $config->getConfigValueType();

            if ($global_config_id !== 0 && !in_array($global_config_id, $config_was_created_for_global_ids)) {
                /**
                 * @var ilParticipationCertificateConfig $config
                 **/
                $order_by = max($order_by_global_ids[$global_config_id]) + 1;

                $new_config_individual_assessments = new ilParticipationCertificateConfig();
                $new_config_individual_assessments->setGroupRefId($group_ref_id);
                $new_config_individual_assessments->setConfigType($config_type);
                $new_config_individual_assessments->setGlobalConfigId($global_config_id);
                $new_config_individual_assessments->setConfigValueType($config_value_type);
                $new_config_individual_assessments->setConfigKey('individual_assessments');
                $new_config_individual_assessments->setConfigValue('Individuelle Bewertungen');
                $new_config_individual_assessments->setOrderBy($order_by);
                $new_config_individual_assessments->create();

                $order_by++;
                $new_config_sessions = new ilParticipationCertificateConfig();
                $new_config_sessions->setGroupRefId($group_ref_id);
                $new_config_sessions->setConfigType($config_type);
                $new_config_sessions->setGlobalConfigId($global_config_id);
                $new_config_sessions->setConfigValueType($config_value_type);
                $new_config_sessions->setConfigKey('sessions');
                $new_config_sessions->setConfigValue('Sitzungen');
                $new_config_sessions->setOrderBy($order_by);
                $new_config_sessions->create();

                $config_was_created_for_global_ids[] = $global_config_id;
            }
        }
    }
}
