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

    private string $objecttype;

    public function __construct()
    {
        global $DIC;

        $this->keywords = [];
        if ($DIC->offsetExists('tpl')) {
            $this->ctrl = $DIC->ctrl();
            $this->pl = ilParticipationCertificatePlugin::getInstance();
            if (isset($_GET['ref_id'])) {
                $this->group_ref_id = (int) $_GET['ref_id'];
            } else {
                $this->group_ref_id = 0;
            }
            $this->objecttype = ilObject::_lookupType($this->group_ref_id, true);

            if ($this->group_ref_id === 0 || ($this->objecttype !== 'crs' and $this->objecttype !== 'grp')) {
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
                $this->keywords = explode(',', $config->getConfigValue());
            }
        }
    }

    /**
     * Modify GUI objects, before they generate output
     * @throws ilCtrlException
     */
    function modifyGUI(string $a_comp, string $a_part, array $a_par = array()): void
    {
        if ($a_part == 'tabs' && $this->checkGroup()) {
            $cert_access = new ilParticipationCertificateAccess($_GET['ref_id']);

            if ($cert_access->hasCurrentUserWriteAccess()) {
                /**
                 * @var ilTabsGUI $tabs
                 */
                $tabs = $a_par['tabs'];
                // TODO
/*                $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'ref_id');
				$tabs->addTab(self::TAB_CERTIFICATES, $this->pl->txt('plugin'), $this->ctrl->getLinkTargetByClass(array(
					ilUIPluginRouterGUI::class,
					ilParticipationCertificateResultGUI::class
				), ilParticipationCertificateResultGUI::CMD_CONTENT));*/
            }
        }
    }

    /**
     * check if tab should be displayed, only displayed in groups!
     */
    public function checkGroup(): bool
    {
        foreach ($this->ctrl->getCallHistory() as $GUIClassesArray) {
            if (($this->objecttype === 'crs') && key_exists(
                    'cmdClass', $GUIClassesArray
                ) && ($GUIClassesArray['cmdClass'] == ilObjCourseGUI::class)) {
                if ($this->strposa($this->learn_group_title, $this->keywords) !== false) {
                    return true;
                }
            }
            if (($this->objecttype === 'grp') && key_exists(
                    'cmdClass', $GUIClassesArray
                ) && ($GUIClassesArray['cmdClass'] == ilObjGroupGUI::class)) {
                if ($this->strposa($this->learn_group_title, $this->keywords) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param     $haystack
     * @param     $needles
     * @param int $offset
     * @return mixed
     */
    private function strposa($haystack, $needles = array(), int $offset = 0): mixed
    {
        $chr = array();
        foreach ($needles as $needle) {
            $res = strpos($haystack, $needle, $offset);
            if ($res !== false) $chr[$needle] = $res;
        }
        if (empty($chr)) return false;
        return min($chr);
    }
}
