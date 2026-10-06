<?php
class ilParticipationCertificateGlobalConfigSets {

	public function __construct() {

	}

    /**
     * @return array
     */
	public function getAllConfigsAsArray(): array
    {
		return ilParticipationCertificateGlobalConfigSet::orderBy('order_by')->getArray();
	}

    /**
     * @return ilParticipationCertificateGlobalConfigSet
     */
	public function addNewConfig(): ilParticipationCertificateGlobalConfigSet
    {
			$gl_config = new ilParticipationCertificateGlobalConfigSet();
			$gl_config->setOrderBy($this->getUnreservedOrderByValue());
			$gl_config->store();
			return $gl_config;
	}

    /**
     * @param int $id
     * @return ilParticipationCertificateGlobalConfigSet
     */
	public function getConfigSetById(int $id): ilParticipationCertificateGlobalConfigSet
    {
		/**
		 * @var ilParticipationCertificateGlobalConfigSet $gl_config
		 */
		$gl_config = ilParticipationCertificateGlobalConfigSet::where(['id' => $id])->first();
		return $gl_config;
	}

    /**
     * @return ilParticipationCertificateGlobalConfigSet
     */
	public function getDefaultConfig(): ilParticipationCertificateGlobalConfigSet
    {
		/**
		 * @var ilParticipationCertificateGlobalConfigSet $gl_config
		 */
		$gl_config = ilParticipationCertificateGlobalConfigSet::where(['order_by' => 1])->first();
		return $gl_config;
	}

    /**
     * @param string $config_key
     * @return bool|string
     */
	public function getDefaultConfigSetValue(string $config_key): bool|string
    {
		$gl_config = $this->getDefaultConfig();

		$participation_configs = new ilParticipationCertificateConfigs();

		if(is_object($participation_configs->getParticipationTemplateConfigValueByKey($gl_config->getId(),$config_key))) {
			return $participation_configs->getParticipationTemplateConfigValueByKey($gl_config->getId(),$config_key)->getConfigValue();
		}
		return false;
	}

    /**
     * @param array $arr_order_by
     * @return void
     */
	public function saveAndRearangeOrderBy(array $arr_order_by = array()): void
    {
		$arr_reserved = [];

		asort($arr_order_by);

        foreach($arr_order_by as $id => $order_by) {
			if(in_array($order_by,$arr_reserved)) {
				$order_by = $order_by + 1;
			}

			$part_cert_config = new ilParticipationCertificateGlobalConfigSet($id);
			$part_cert_config->setOrderBy($order_by);
			$part_cert_config->store();
			$arr_reserved[] = $order_by;
		}

		$i = 1;
		foreach(ilParticipationCertificateGlobalConfigSet::orderBy('order_by')->get() as $global_config) {
			/**
			 * @var ilParticipationCertificateGlobalConfigSet $global_config
			 */
			$global_config->setOrderBy($i);
			if($i === 1) {
				$global_config->setActive(1);
			}
			$global_config->store();
			$i = $i + 1;
		}

	}

    /**
     * @return int
     */
	public function getUnreservedOrderByValue(): int
    {
		/**
		 * @var ilParticipationCertificateGlobalConfigSet $config
		 */
		$config = ilParticipationCertificateGlobalConfigSet::orderBy('order_by','DESC')->first();
		return $config->getOrderBy() + 1;
	}

    /**
     * @return string[]
     * @throws arException
     */
	public function getSelectOptions(): array
    {
		$arr_select_options = [0 => '--'];

		foreach(ilParticipationCertificateGlobalConfigSet::where(['active' => 1])->orderBy('order_by')->get() as $global_config) {
			/**
			 * @var ilParticipationCertificateGlobalConfigSet $global_config
			 */
			$arr_select_options[$global_config->getId()] = $global_config->getTitle();
		}
		return $arr_select_options;
	}
}
