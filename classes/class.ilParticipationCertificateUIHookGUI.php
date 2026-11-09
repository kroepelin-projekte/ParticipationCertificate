<?php

/**
 * Class ilParticipationCertificateUIHookGUI
 *
 * @ilCtrl_Calls ilParticipationCertificateUIHookGUI: ilParticipationCertificateGUI
 */
class ilParticipationCertificateUIHookGUI extends ilUIHookPluginGUI
{
    private const string TAB_CERTIFICATES = 'certificates';

    protected ilCtrl|ilCtrlInterface $ctrl;

    protected ilParticipationCertificatePlugin $pl;

    protected int $group_ref_id;

    protected ?ilObject $learn_group;

    protected string $learn_group_title;

    protected array $keywords;

    private string $object_type;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        global $DIC;

        $this->keywords = [];
        if ($DIC->offsetExists('tpl')) {
            $this->ctrl = $DIC->ctrl();
            $this->pl = ilParticipationCertificatePlugin::getInstance();

            $ref_id = Helper::fetchUrlParameter('ref_id', 'string');

            if (isset($ref_id)) {
                $this->group_ref_id = (int) $ref_id;
            } else {
                $this->group_ref_id = 0;
            }
            $this->object_type = ilObject::_lookupType($this->group_ref_id, true);

            if ($this->group_ref_id === 0 || ($this->object_type !== 'crs' and $this->object_type !== 'grp')) {
                return;
            }
            $this->learn_group = ilObjectFactory::getInstanceByRefId($this->group_ref_id);
            $this->learn_group_title = $this->learn_group->getTitle();

            $config = ilParticipationCertificateConfig::where(array(
                'config_key' => 'keyword',
                'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL,
                'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER,
                'group_ref_id' => 0
            ))->first();

            if (is_object($config)) {
                $this->keywords = self::parseKeywords((string) $config->getConfigValue());
            }
        }
    }

    /**
     * Modify GUI objects, before they generate output
     * @throws ilCtrlException
     */
    public function modifyGUI(
        string $a_comp,
        string $a_part,
        array $a_par = []
    ): void {
        if ($a_part == 'tabs' && $this->checkGroup()) {
            $cert_access = new ilParticipationCertificateAccess($_GET['ref_id']);

            if ($cert_access->hasCurrentUserWriteAccess()) {
                /**
                 * @var ilTabsGUI $tabs
                 */
                $tabs = $a_par['tabs'];
                $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'ref_id');
                $tabs->addTab(
                    self::TAB_CERTIFICATES,
                    $this->pl->txt('plugin'),
                    $this->ctrl->getLinkTargetByClass(
                        [
                            ilUIPluginRouterGUI::class,
                            ilParticipationCertificateResultGUI::class
                        ],
                        ilParticipationCertificateResultGUI::CMD_CONTENT
                    )
                );
            }
        }
    }

    /**
     * check if tab should be displayed, only displayed in groups!
     */
    public function checkGroup(): bool
    {
        foreach ($this->ctrl->getCallHistory() as $gui_classes_array) {
            if (($this->object_type === 'crs') && key_exists(
                    'cmdClass', $gui_classes_array
                ) && ($gui_classes_array['cmdClass'] == ilObjCourseGUI::class)) {
                if ($this->titleMatchesKeyword($this->learn_group_title)) {
                    return true;
                }
            }
            if (($this->object_type === 'grp') && key_exists(
                    'cmdClass', $gui_classes_array
                ) && ($gui_classes_array['cmdClass'] == ilObjGroupGUI::class)) {
                if ($this->titleMatchesKeyword($this->learn_group_title)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Comma-separated keyword config -> list of trimmed, non-empty keywords.
     * '' or ' , ' must give [] (not ['']): an empty needle matches every title.
     *
     * @return string[]
     */
    public static function parseKeywords(string $config_value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $config_value)),
            static fn(string $keyword): bool => $keyword !== ''
        ));
    }

    /**
     * Case-insensitive (multibyte-safe) match of the object title against the configured keywords.
     * No keywords configured means no match, i.e. the tab is shown nowhere.
     */
    private function titleMatchesKeyword(string $title): bool
    {
        foreach ($this->keywords as $keyword) {
            if (mb_stripos($title, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

}
