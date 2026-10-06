<?php

use ILIAS\DI\Container;;

/**
 * Class ilParticipationCertificatePlugin
 *
 * @author Silas Stulz <sst@studer-raimann.ch>
 */
class ilParticipationCertificatePlugin extends ilUserInterfaceHookPlugin
{
    public const string PLUGIN_ID = 'dhbwparticipationpdf';

    public const string PLUGIN_NAME = 'ParticipationCertificate';

    public const string PLUGIN_CLASS_NAME = self::class;

    public const string PLUGIN_DIRECTORY = 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate';

    public const string CERTIFICATIONS_PATH = 'dhbw_part_cert';

    public const string PLUGIN_VERSION_FILES_PATH_MOVED_IN_DB = '1.2.1';

    public const string PLUGIN_VERSION_FOR_INSERTING_NEW_CONFIGS = '2.1.0';

    protected static ?ilParticipationCertificatePlugin $instance = null;

    private ilPluginInfo $pluginInfo;

    protected ilDBInterface $db;

    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;
        parent::__construct($db, $component_repository, $id);

        $this->db = $DIC->database();

        $this->pluginInfo = $this->getPluginInfo();
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

    /*public function exchangeUIRendererAfterInitialization(Container $dic): Closure
    {
        $renderer = $dic->raw('ui.renderer');
        $ctrl = $dic->ctrl();
        $cmd_class = $ctrl->getCmdClass();
        $cmd = $ctrl->getCmd();


        return $renderer;
    }*/

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
     * @param string $imageName
     * @return string
     */
    public function getImagePath(string $imageName): string
    {
        return $this->getDirectory() . '/templates/images/' . $imageName;
    }

    /**
     * @return void
     */
    protected function afterUpdate(): void
    {
        $pluginVersion = $this->pluginInfo->getCurrentVersion();
        $versionBeforeUpdate = $pluginVersion->getMajor() . '.' . $pluginVersion->getMinor() . '.' . $pluginVersion->getPatch();

        if (version_compare($this->getVersion(), self::PLUGIN_VERSION_FOR_INSERTING_NEW_CONFIGS, '=')) {
            $this->insertNewParticipationCertificateConfigs();
        }


        if (version_compare($versionBeforeUpdate, self::PLUGIN_VERSION_FILES_PATH_MOVED_IN_DB, '>=')) {
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
        $participationCertificateConfigs = ilParticipationCertificateConfig::get();

        $orderByGlobalIds = [];
        foreach ($participationCertificateConfigs as $config) {
            /* @var $config ilParticipationCertificateConfig */
            $orderByGlobalIds[$config->getGlobalConfigId()][] = $config->getOrderBy();
        }

        $configWasCreatedForForGlobalIds = [];
        foreach ($participationCertificateConfigs as $config) {
            /* @var $config ilParticipationCertificateConfig */
            $globalConfigId = $config->getGlobalConfigId();
            $configType = $config->getConfigType();
            $groupRefId = $config->getGroupRefId();
            $configValueType = $config->getConfigValueType();

            if ($globalConfigId !== 0 && !in_array($globalConfigId, $configWasCreatedForForGlobalIds)) {
                /**
                 * @var ilParticipationCertificateConfig $config
                 **/
                $orderBy = max($orderByGlobalIds[$globalConfigId]) + 1;

                $newConfigIndividualAssessments = new ilParticipationCertificateConfig();
                $newConfigIndividualAssessments->setGroupRefId($groupRefId);
                $newConfigIndividualAssessments->setConfigType($configType);
                $newConfigIndividualAssessments->setGlobalConfigId($globalConfigId);
                $newConfigIndividualAssessments->setConfigValueType($configValueType);
                $newConfigIndividualAssessments->setConfigKey('individual_assessments');
                $newConfigIndividualAssessments->setConfigValue('Individuelle Bewertungen');
                $newConfigIndividualAssessments->setOrderBy($orderBy);
                $newConfigIndividualAssessments->create();

                $orderBy++;
                $newConfigSessions = new ilParticipationCertificateConfig();
                $newConfigSessions->setGroupRefId($groupRefId);
                $newConfigSessions->setConfigType($configType);
                $newConfigSessions->setGlobalConfigId($globalConfigId);
                $newConfigSessions->setConfigValueType($configValueType);
                $newConfigSessions->setConfigKey('sessions');
                $newConfigSessions->setConfigValue('Sitzungen');
                $newConfigSessions->setOrderBy($orderBy);
                $newConfigSessions->create();

                $configWasCreatedForForGlobalIds[] = $globalConfigId;
            }
        }
    }
}
