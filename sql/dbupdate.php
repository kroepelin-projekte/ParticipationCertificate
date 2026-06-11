<#1>
<?php
//
?>
<#2>
<?php
//
?>
<#3>
<?php
//
?>
<#4>
<?php
//
?>
<#5>
<?php
//
?>
<#6>
<?php
//
?>
<#7>
<?php
//
?>
<#8>
<?php
//
?>
<#9>
<?php
global $DIC;
$ilDB = $DIC->database();
if ($ilDB->tableExists("participationcert")) {
    $ilDB->dropTable("participationcert");
}
if ($ilDB->tableExists("participationcert" . '_seq')) {
    $ilDB->dropTable("participationcert" . '_seq');
}
?>
<#10>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
ilParticipationCertificateConfig::updateDB();
?>
<#11>
<?php
//
?>
<#12>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
ilParticipationCertificateConfig::updateDB();

$part_cert_configs = new ilParticipationCertificateConfigs();

foreach ($part_cert_configs->returnCertTextDefaultValues() as $key => $value) {
    $part_conf = $value;
    $part_conf->store();
}
?>
<#13>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
$config = ilParticipationCertificateConfig::where(array('config_key' => 'page2_box1_row2', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
    $config->setGroupRefId(0);
    $config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
    $config->setConfigKey('page2_box1_row2');
    $config->setConfigValue('Bearbeitung von empfohlenen Mathematik- Lernmodulen'); // TODO lang
    $config->store();
}
?>
<#14>
<?php
//
?>
<#15>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
$config = ilParticipationCertificateConfig::where(array('config_key' => 'footer_config', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
    $part_conf->setConfigKey('footer_config');
    $part_conf->setConfigValue('Die Resultate dieser Bescheinigung wurden manuell berechnet.'); // TODO lang
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}
?>
<#16>
<?php
//
?>
<#17>
<?php
/*
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
$config = ilParticipationCertificateConfig::where(['config_key' =>  'percent_value',
	'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE,
	'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER,
	"group_ref_id" => 0])->first();
if(!is_object($config)) {
$part_conf = new ilParticipationCertificateConfig();
$part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
$part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$part_conf->setConfigKey('percent_value');
$part_conf->setConfigValue(50);
$part_conf->setGroupRefId(0);
$part_conf->store();
}*/
//
?>
<#18>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
ilParticipationCertificateConfig::updateDB();

$config = ilParticipationCertificateConfig::where([
    'config_key' => 'keyword',
    'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE,
    'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER,
    "group_ref_id" => 0])->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
    $part_conf->setConfigKey('keyword');
    $part_conf->setConfigValue("Lerngruppe");
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}
?>
<#19>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
$config = ilParticipationCertificateConfig::where(array('config_key' => 'page1_issuer_signature', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
    $part_conf->setConfigKey('page1_issuer_signature');
    $part_conf->setConfigValue("");
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}
?>
<#20>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
$config = ilParticipationCertificateConfig::where(array('config_key' => 'page1_disclaimer', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
    $part_conf->setConfigKey('page1_disclaimer');
    $part_conf->setConfigValue("");
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}
?>
<#21>
<?php
//
?>
<#22>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
$configs = ilParticipationCertificateConfig::where(array('config_key' => 'page1_disclaimer', 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER))->get();

foreach ($configs as $config) {
    $config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
    $config->store();
}
?>
<#23>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

$config = ilParticipationCertificateConfig::where(array('config_key' => 'udf_firstname', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
    $part_conf->setConfigKey('udf_firstname');
    $part_conf->setConfigValue("");
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}

$config = ilParticipationCertificateConfig::where(array('config_key' => 'udf_lastname', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
    $part_conf->setConfigKey('udf_lastname');
    $part_conf->setConfigValue("");
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}

$config = ilParticipationCertificateConfig::where(array('config_key' => 'udf_gender', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
    $part_conf->setConfigKey('udf_gender');
    $part_conf->setConfigValue("");
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}

$config = ilParticipationCertificateConfig::where(array('config_key' => 'color', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
    $part_conf->setConfigKey('color');
    $part_conf->setConfigValue("");
    $part_conf->setGroupRefId(0);
    $part_conf->store();
}
?>
<#24>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

global $DIC;
$ilDB = $DIC->database();
$result = $ilDB->query('SELECT DISTINCT group_ref_id FROM ' . ilParticipationCertificateConfig::TABLE_NAME . ' WHERE group_ref_id != 0');
while ($row = $this->db->fetchAssoc($result)) {
    $group_ref_id = $row["group_ref_id"];
    $part_cert_configs = new ilParticipationCertificateConfigs();
    foreach ($part_cert_configs->returnDefaultValues() as $key => $value) {
        $config = ilParticipationCertificateConfig::where(array('config_key' => $key, 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT, "group_ref_id" => $group_ref_id))->first();
        if (!is_object($config)) {
            $config = new ilParticipationCertificateConfig();
            $config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP);
            $config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
            $config->setConfigKey($key);
            $config->setConfigValue($value);
            $config->setGroupRefId($group_ref_id);
            $config->store();
        }
    }
}
?>
<#25>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
ilParticipationCertificateConfig::updateDB();

//Set Config ID of Default Config to 1.
foreach (ilParticipationCertificateConfig::where(array(
    "config_type" => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE,
    "group_ref_id" => 0
))->orderBy('order_by')->get() as $config) {
    $config->setGlobalConfigId(1);
    $config->update();
}
?>
<#26>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
ilParticipationCertificateGlobalConfigSet::updateDB();
?>
<#27>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
$global_config = new ilParticipationCertificateGlobalConfigSet();
$global_config->setId(1);
$global_config->setOrderBy(1);
$global_config->setTitle("Default");
$global_config->store();
?>
<#28>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
ilParticipationCertificateGlobalConfigSet::updateDB();
$part_cert_global = ilParticipationCertificateGlobalConfigSet::where(["order_by" => 1])->first();
$part_cert_global->setActive(1);
$part_cert_global->store();
?>
<#29>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
ilParticipationCertificateObjectConfigSet::updateDB();
?>
<#30>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";


//set global plugin configurations
$config = ilParticipationCertificateConfig::where(["config_key" => 'udf_firstname'])->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
}
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigKey('udf_firstname');
$config->setGlobalConfigId(0);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->store();

$config = ilParticipationCertificateConfig::where(["config_key" => 'udf_lastname'])->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('udf_lastname');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


$config = ilParticipationCertificateConfig::where(["config_key" => 'udf_gender'])->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('udf_gender');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


$config = ilParticipationCertificateConfig::where(["config_key" => 'keyword'])->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('keyword');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


$config = ilParticipationCertificateConfig::where(["config_key" => 'color'])->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('color');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


$config = ilParticipationCertificateConfig::where(["config_key" => 'logo'])->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('logo');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();
?>
<#31>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

$part_cert_global_config_sets = new ilParticipationCertificateGlobalConfigSets();
$part_cert_default_config_set = $part_cert_global_config_sets->getDefaultConfig();


$part_cert_configs = new ilParticipationCertificateConfigs();
foreach ($part_cert_configs->returnCertTextDefaultValues() as $key => $value) {
    /**
     * @var $value ilParticipationCertificateConfig
     */
    $config = ilParticipationCertificateConfig::where(['config_key' => $value->getConfigKey(),
        "global_config_id" => $part_cert_default_config_set->getId()])->first();

    if (!is_object($config)) {
        $value->setGlobalConfigId($part_cert_default_config_set->getId());
        $config = $value;
    } else {
        $config->setOrderBy($value->getOrderBy());
    }
    $config->store();
}
?>
<#32>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

$config = ilParticipationCertificateConfig::where(["config_key" => 'logo'])->first();
$config->delete();

$part_cert_global_config_sets = new ilParticipationCertificateGlobalConfigSets();
$part_cert_default_config_set = $part_cert_global_config_sets->getDefaultConfig();

$part_cert_configs = new ilParticipationCertificateConfigs();
foreach ($part_cert_configs->returnCertTextDefaultValues() as $key => $value) {
    /**
     * @var $value ilParticipationCertificateConfig
     */
    $config = ilParticipationCertificateConfig::where(['config_key' => $value->getConfigKey(),
        "global_config_id" => $part_cert_default_config_set->getId()])->first();

    if (!is_object($config)) {
        $value->setGlobalConfigId($part_cert_default_config_set->getId());
        $config = $value;
    } else {
        $config->setOrderBy($value->getOrderBy());
    }
    $config->store();
}
?>
<#33>
<?php
/*
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
global $DIC;

//FIX cert_config
$select = "UPDATE dhbw_part_cert_conf SET config_type = 2, config_value_type = 2  where config_key in('period_start','period_end','enable_self_print','self_print_start','self_print_end') and group_ref_id is not null";
$DIC->database()->query($select);

//Fix global config if necessary
$config = ilParticipationCertificateConfig::where(["config_key" => 'udf_firstname'])->first();
if(!is_object($config)) {
	$config = new ilParticipationCertificateConfig();
}
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigKey('udf_firstname');
$config->setGlobalConfigId(0);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->store();

$config = ilParticipationCertificateConfig::where(["config_key" => 'udf_lastname'])->first();
if(!is_object($config)) {
	$config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('udf_lastname');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


$config = ilParticipationCertificateConfig::where(["config_key" => 'udf_gender'])->first();
if(!is_object($config)) {
	$config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('udf_gender');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


$config = ilParticipationCertificateConfig::where(["config_key" => 'keyword'])->first();
if(!is_object($config)) {
	$config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('keyword');
$config->setConfigValue("Lerngruppe");
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


$config = ilParticipationCertificateConfig::where(["config_key" => 'color'])->first();
if(!is_object($config)) {
	$config = new ilParticipationCertificateConfig();
}
$config->setConfigKey('color');
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
$config->setGlobalConfigId(0);
$config->store();


//TEMPLATE
//Config Template
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

$part_cert_global_config_sets = new ilParticipationCertificateGlobalConfigSets();
$part_cert_default_config_set = $part_cert_global_config_sets->getDefaultConfig();

$part_cert_configs = new ilParticipationCertificateConfigs();
foreach($part_cert_configs->returnCertTextDefaultValues() as $key => $value) {
*/
/**
 * @var $value ilParticipationCertificateConfig
 */
/*
$config = new ilParticipationCertificateConfig();
$config->setConfigKey($value->getConfigKey());
$config->setGlobalConfigId($part_cert_default_config_set->getId());
$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);


$config->setConfigKey($value->getConfigKey());
$config->setOrderBy($value->getOrderBy());
$config->setConfigType($value->getConfigType());
$config->setConfigValueType($value->getConfigValueType());


$config->store();

if(!is_object($config)) {
    $value->setGlobalConfigId($part_cert_default_config_set->getId());
    $config = $value;
} else {
    $config->setOrderBy($value->getOrderBy());
}
$config->store();
}
*/
?>
<#34>
<?php
//
?>
<#35>
<?php
//
?>
<#36>
<?php

$config_textes_to_reconfigure[] = 'page2_box1_row2';
$config_textes_to_reconfigure[] = 'page2_box2_title';
$config_textes_to_reconfigure[] = 'page2_box2_row1';
$config_textes_to_reconfigure[] = 'page2_box2_row2';

$configs_with_box_total_questions = ilParticipationCertificateConfig::where(
    [
        "config_key" => "page2_box1_row1_total_questions",
        "config_value_type" => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT])->get();

$global_config_ids_already_configured = [];
$group_ref_ids_already_configured = [];

if (count($configs_with_box_total_questions) > 0) {
    foreach ($configs_with_box_total_questions as $configs_with_box_total_question) {
        /** @var ilParticipationCertificateConfig $configs_with_box_total_question */
        if ($configs_with_box_total_question->getGlobalConfigId()
            > 0) {
            $global_config_ids_already_configured[] = $configs_with_box_total_question->getGlobalConfigId();
        }

        if ($configs_with_box_total_question->getGlobalConfigId()
            > 0) {
            $group_ref_ids_already_configured[] = $configs_with_box_total_question->getGroupRefId();
        }
    }
}


$configs_type_cert_text = ilParticipationCertificateConfig::where(
    [
        "config_value_type" => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT])->get();

$global_config_ids_to_configure = [];
$group_ref_ids_to_configure = [];
if (count($configs_type_cert_text) > 0) {
    foreach ($configs_type_cert_text as $config_type_cert_text) {
        /** @var ilParticipationCertificateConfig $config_type_cert_text */
        if ($config_type_cert_text->getGlobalConfigId()
            > 0) {
            if (in_array(
                $config_type_cert_text->getGlobalConfigId(),
                $global_config_ids_already_configured
            )) {
                continue;
            }
            $global_config_ids_to_configure[$config_type_cert_text->getGlobalConfigId()] = $config_type_cert_text->getGlobalConfigId();

            if (in_array(
                $config_type_cert_text->getConfigKey(),
                $config_textes_to_reconfigure
            )
            ) {
                $config_type_cert_text->setOrderBy(((int)$config_type_cert_text->getOrderBy() + 1));
                $config_type_cert_text->update();
            }

        }

        if ($config_type_cert_text->getGroupRefId()
            > 0) {
            if (in_array(
                $config_type_cert_text->getGroupRefId(),
                $group_ref_ids_already_configured
            )) {
                continue;
            }
            $group_ref_ids_to_configure[$config_type_cert_text->getGroupRefId()] = $config_type_cert_text->getGroupRefId();

            if (in_array(
                $config_type_cert_text->getConfigKey(),
                $config_textes_to_reconfigure
            )
            ) {
                $config_type_cert_text->setOrderBy(((int)$config_type_cert_text->getOrderBy() + 1));
                $config_type_cert_text->update();
            }
        }
    }

    foreach ($global_config_ids_to_configure as $global_config_id_to_configure) {
        $cert_config = new ilParticipationCertificateConfig();
        $cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
        $cert_config->setGroupRefId(0);
        $cert_config->setGlobalConfigId($global_config_id_to_configure);
        $cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
        $cert_config->setConfigKey('page2_box1_row1_total_questions');
        $cert_config->setConfigValue('');
        $cert_config->setOrderBy(23);
        $cert_config->store();
    }

    foreach ($group_ref_ids_to_configure as $group_ref_id_to_configure) {
        $cert_config = new ilParticipationCertificateConfig();
        $cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP);
        $cert_config->setGroupRefId($group_ref_id_to_configure);
        $cert_config->setGlobalConfigId(0);
        $cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
        $cert_config->setConfigKey('page2_box1_row1_total_questions');
        $cert_config->setConfigValue('');
        $cert_config->setOrderBy(23);
        $cert_config->store();
    }
}
?>
<#37>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

$config = ilParticipationCertificateConfig::where(array('config_key' => 'unsugg_color', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
    $part_conf->setConfigKey('unsugg_color');
    $part_conf->setConfigValue("14528e");
    $part_conf->setGroupRefId(0);
    $part_conf->setOrderBy(5);
    $part_conf->store();
}
?>
<#38>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

$config = ilParticipationCertificateConfig::where(array('config_key' => 'true_name_helper', 'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL, 'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER, "group_ref_id" => 0))->first();
if (!is_object($config)) {
    $part_conf = new ilParticipationCertificateConfig();
    $part_conf->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
    $part_conf->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
    $part_conf->setConfigKey('true_name_helper');
    $part_conf->setConfigValue("");
    $part_conf->setGroupRefId(0);
    $part_conf->setOrderBy(6);
    $part_conf->store();
}
?>

<#39>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";

$config = ilParticipationCertificateConfig::where(["config_key" => 'udf_gender'])->first();
if (!is_object($config)) {
    $config = new ilParticipationCertificateConfig();
}
$config->delete();
?>

<#40>
<?php
$fields = array(
    'id' => array(
        'type' => 'integer',
        'notnull' => true
    ),
    'config_id' => array(
        'type' => 'integer',
        'notnull' => true
    ), // It is whether the global config id or the group ref id
    'type' => array(
        'type' => 'text',
        'notnull' => true
    ),
    'resource_storage' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true
    )
);

$ilDB->createTable('dhbw_part_cert_files', $fields);
$ilDB->addPrimaryKey('dhbw_part_cert_files', ['id']);
$ilDB->createSequence('dhbw_part_cert_files');
?>

<#41>
<?php
$config = ilParticipationCertificateConfig::where(array('config_key' => 'page1_box1_row1'))->get();

if (!is_object($config)) {
    $data = [];
    foreach($config as $con) {
        $configLastOrderBy = ilParticipationCertificateConfig::where([
                'global_config_id' => $con->getGlobalConfigId()
        ])->orderBy('order_by', 'DESC')->first();

        $orderBy = $configLastOrderBy->getOrderBy() + 1;

        $data[] = [
            'config-type' => $con->getConfigType(),
            'config-value-type' => $con->getConfigValueType(),
            'config-key' => 'page1_box1_row1_alternative',
            'config-value' => 'Abschluss Abschlusstest',
            'global-config-id' => $con->getGlobalConfigId(),
            'group-ref-id' => $con->getGroupRefId(),
            'order-by' => $orderBy,
        ];
    }

    foreach($data as $config) {
        $part_conf = new ilParticipationCertificateConfig();
        $part_conf->setConfigType($config['config-type']);
        $part_conf->setConfigValueType($config['config-value-type']);
        $part_conf->setConfigKey($config['config-key']);
        $part_conf->setConfigValue($config['config-value']);
        $part_conf->setGroupRefId($config['group-ref-id']);
        $part_conf->setGlobalConfigId($config['global-config-id']);
        $part_conf->setOrderBy($config['order-by']);
        $part_conf->store();
    }
}
?>

<#42>
<?php
$config = ilParticipationCertificateConfig::where(array('config_key' => 'page2_box1_row1'))->get();

if (!is_object($config)) {
    $data = [];
    foreach($config as $con) {
        $configLastOrderBy = ilParticipationCertificateConfig::where([
                'global_config_id' => $con->getGlobalConfigId()
        ])->orderBy('order_by', 'DESC')->first();

        $orderBy = $configLastOrderBy->getOrderBy() + 1;

        $data[] = [
                'config-type' => $con->getConfigType(),
                'config-value-type' => $con->getConfigValueType(),
                'config-key' => 'page2_box1_row1_alternative',
                'config-value' => 'Abschlusstest',
                'global-config-id' => $con->getGlobalConfigId(),
                'group-ref-id' => $con->getGroupRefId(),
                'order-by' => $orderBy,
        ];
    }

    foreach($data as $config) {
        $part_conf = new ilParticipationCertificateConfig();
        $part_conf->setConfigType($config['config-type']);
        $part_conf->setConfigValueType($config['config-value-type']);
        $part_conf->setConfigKey($config['config-key']);
        $part_conf->setConfigValue($config['config-value']);
        $part_conf->setGroupRefId($config['group-ref-id']);
        $part_conf->setGlobalConfigId($config['global-config-id']);
        $part_conf->setOrderBy($config['order-by']);
        $part_conf->store();
    }
}
?>
