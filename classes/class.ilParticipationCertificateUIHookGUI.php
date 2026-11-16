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

    private string $object_type;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        global $DIC;

        if ($DIC->offsetExists('tpl')) {
            $this->ctrl = $DIC->ctrl();
            $this->pl = ilParticipationCertificatePlugin::getInstance();

            $ref_id = ilParticipationCertificateHelper::fetchUrlParameter('ref_id', 'string');

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
            $ref_id = ilParticipationCertificateHelper::fetchUrlParameter('ref_id', 'string');
            $cert_access = new ilParticipationCertificateAccess($ref_id);

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
                if (ilParticipationCertificateAccess::isEnabledForObject($this->group_ref_id)) {
                    return true;
                }
            }
            if (($this->object_type === 'grp') && key_exists(
                    'cmdClass', $gui_classes_array
                ) && ($gui_classes_array['cmdClass'] == ilObjGroupGUI::class)) {
                if (ilParticipationCertificateAccess::isEnabledForObject($this->group_ref_id)) {
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
        return ilParticipationCertificateAccess::parseKeywords($config_value);
    }

}
