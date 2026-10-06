<?php
class ilParticipationCertificateConfigs {

	public function __construct() {

	}


    /**
     * @return ilParticipationCertificateConfig[]
     * @throws arException
     */
	public function getGlobalConfigSet(int $global_config_id = 0): array
    {
		return ilParticipationCertificateConfig::where([
            'global_config_id' => $global_config_id
        ])->orderBy('order_by')->get();
	}

    /**
     * @return ilParticipationCertificateConfig[]
     * @throws arException
     */
    public function getObjectConfigSet(int $obj_ref_id = 0): array
    {
        return ilParticipationCertificateConfig::where([
            'group_ref_id' => $obj_ref_id
        ])->orderBy('order_by')->get();
    }

    /**
     * @throws arException
     */
    public function returnTextValues(
        int $group_ref_id = 0,
        int $config_type = ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE
    ): array {
		$arr_config = ilParticipationCertificateConfig::where([
			'config_type' => $config_type,
			'group_ref_id' => $group_ref_id,
			'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT
		])->orderBy('order_by')
          ->getArray('config_key', 'config_value');

        if (count($arr_config) == 0) {
           $part_cert_ob_conf = new ilParticipationCertificateObjectConfigSet();
            /**
             * @var $arr_ob_conf ilParticipationCertificateObjectConfigSet
             */
           $arr_ob_conf = $part_cert_ob_conf::where(array('config_type' => ilParticipationCertificateObjectConfigSet::CONFIG_TYPE_TEMPLATE))->first();
            $global_config_id = 0;
           if(is_object($arr_ob_conf)) {
               $global_config_id = $arr_ob_conf->getGlConfTemplateId();
           }

			$arr_config = ilParticipationCertificateConfig::where([
				'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE,
				'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT,
				'group_ref_id' => 0,
                'global_config_id' => $global_config_id,
			])->orderBy('order_by')->getArray('config_key', 'config_value');
		}

		return $arr_config;
	}

    /**
     * @param int $group_ref_id
     * @return bool|string
     */
	public function returnPercentValue(int $group_ref_id = 0): bool|string
    {
		/**
		 * @var $config ilParticipationCertificateConfig
		 */
		$config = ilParticipationCertificateConfig::where(array(
			'group_ref_id' => $group_ref_id,
			'config_key' => 'percent_value',
			'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP
		))->first();

		if(is_object($config)) {
			return $config->getConfigValue();
		}

		$config_set_templates = new ilParticipationCertificateGlobalConfigSets();
		return $config_set_templates->getDefaultConfigSetValue('percent_value');
	}


	/**
	 * @return ilParticipationCertificateConfig[]
	 * @throws arException
	 */
	public function getObjConfigSetIfNoneCreateDefaultAndCreateNewObjConfigValues(int $obj_ref_id): array
    {
		$cert_obj_config = ilParticipationCertificateObjectConfigSet::where([
            'obj_ref_id' => $obj_ref_id
        ])->first();

		if (!is_object($cert_obj_config)) {
			$global_configs = new ilParticipationCertificateGlobalConfigSets();

			$cert_obj_config = new ilParticipationCertificateObjectConfigSet();
			$cert_obj_config->setConfigType(ilParticipationCertificateObjectConfigSet::CONFIG_TYPE_TEMPLATE);
			$cert_obj_config->setObjRefId($obj_ref_id);
			$cert_obj_config->setGlConfTemplateId($global_configs->getDefaultConfig()->getId());
			$cert_obj_config->store();
		}

		switch ($cert_obj_config->getConfigType()) {
			case ilParticipationCertificateObjectConfigSet::CONFIG_TYPE_TEMPLATE:
				return $this->getGlobalConfigSet($cert_obj_config->getGlConfTemplateId());
				break;
			case ilParticipationCertificateObjectConfigSet::CONFIG_TYPE_OWN:

                $global_config_set = $this->getGlobalConfigSet($cert_obj_config->getGlConfTemplateId());

				foreach ($global_config_set as $global_config_value) {
					if (!$this->getParticipationObjConfigValueByKey($obj_ref_id, $global_config_value->getConfigKey())) {
                        $this->createParticipationObjConfigValueByGlobalConfigValue($obj_ref_id, $global_config_value);
					}
				}

				return
					ilParticipationCertificateConfig::where([
					'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP,
					'group_ref_id' => $obj_ref_id,
					'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT
				])->orderBy('order_by')->get();

				break;
		}
	}

    /**
     * @param int    $obj_ref_id
     * @param string $config_key
     * @return ActiveRecord|false|null
     */
	private function getParticipationObjConfigValueByKey(int $obj_ref_id, string $config_key): ActiveRecord|false|null
    {
        $config = ilParticipationCertificateConfig::where([
            'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP,
            'config_key' => $config_key,
            'group_ref_id' => $obj_ref_id
        ]);

		if ($config->count()) {
			return $config->first();
		};

		return false;
	}

    /**
     * @param int    $global_config_id
     * @param string $config_key
     * @return ActiveRecord|false|null
     */
	public function getParticipationTemplateConfigValueByKey(int $global_config_id, string $config_key): ActiveRecord|false|null
    {
        $config = ilParticipationCertificateConfig::where([
            'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE,
            'config_key' => $config_key,
            'global_config_id' => $global_config_id
        ]);

		if ($config->count()) {
			return $config->first();
		};
		return false;
	}

    /**
     * @param string $config_key
     * @return ActiveRecord|false|null
     */
	public function getParticipationGlobalConfigValueByKey(string $config_key): ActiveRecord|false|null
    {
        $config = ilParticipationCertificateConfig::where([
            'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL,
            'config_key' => $config_key,
        ]);

		if ($config->count()) {
			return $config->first();
		};

		return false;
	}

    /**
     * @param int                              $obj_ref_id
     * @param ilParticipationCertificateConfig $global_config_value
     * @return void
     */
	private function createParticipationObjConfigValueByGlobalConfigValue(
        int $obj_ref_id,
        ilParticipationCertificateConfig $global_config_value
    ): void {
		$part_cert_obj_config_value = $global_config_value;
		$part_cert_obj_config_value->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP);
		$part_cert_obj_config_value->setGroupRefId($obj_ref_id);
		$part_cert_obj_config_value->setGlobalConfigId(0);
		$part_cert_obj_config_value->create();
	}

    /**
     * @param int $obj_ref_id
     * @param int $global_template_id
     * @return void
     */
	public function setObjToUseCertTemplate(int $obj_ref_id, int $global_template_id): void
    {
		$this->deleteObjConfigSet($obj_ref_id);
		/**
		 * @var ilParticipationCertificateObjectConfigSet $part_cert_config
		 */
		$part_cert_config = ilParticipationCertificateObjectConfigSet::where([ 'obj_ref_id' => $obj_ref_id ])->first();
		if (!is_object($part_cert_config)) {
			$part_cert_config = new ilParticipationCertificateObjectConfigSet();
		}
		$part_cert_config->setObjRefId($obj_ref_id);
		$part_cert_config->setConfigType(ilParticipationCertificateObjectConfigSet::CONFIG_TYPE_TEMPLATE);
		$part_cert_config->setGlConfTemplateId($global_template_id);
		$part_cert_config->store();

        $file_logo = ilParticipationCertificateFiles::getFile(
            $global_template_id,
            'logo'
        );

        if (!empty($file_logo)) {
            if(!$file_logo->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $global_template_id, ilParticipationCertificateConfig::LOGO_FILE_NAME))) {
                copy(ilParticipationCertificateConfig::returnPicturePath('absolute', $global_template_id, ilParticipationCertificateConfig::LOGO_FILE_NAME), ilParticipationCertificateConfig::returnPicturePath('absolute', $obj_ref_id, ilParticipationCertificateConfig::LOGO_FILE_NAME));
                ilParticipationCertificateFiles::setFile($obj_ref_id, 'logo', false);
            } elseif ($file_logo->getResourceStorage()) {
                ilParticipationCertificateFiles::setFile($obj_ref_id, 'logo', true);
            }
        }

        $file_signature = ilParticipationCertificateFiles::getFile(
            $global_template_id,
            'page1_issuer_signature'
        );

        if (!empty($file_signature)) {
            if(!$file_signature->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $global_template_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                copy(ilParticipationCertificateConfig::returnPicturePath('absolute', $global_template_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME), ilParticipationCertificateConfig::returnPicturePath('absolute', $obj_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME));
                ilParticipationCertificateFiles::setFile($obj_ref_id, 'page1_issuer_signature', false);

            }elseif ($file_signature->getResourceStorage()) {
                ilParticipationCertificateFiles::setFile($obj_ref_id, 'page1_issuer_signature', true);
            }
        }
	}

    /**
     * @param int $obj_ref_id
     * @param int $global_template_id
     * @return void
     */
	public function setOwnCertConfigFromTemplate(int $obj_ref_id, int $global_template_id): void
    {
		$part_cert_config = ilParticipationCertificateObjectConfigSet::where([ 'obj_ref_id' => $obj_ref_id ])->first();
		if (!is_object($part_cert_config)) {
			$part_cert_config = new ilParticipationCertificateObjectConfigSet();
		}
		$part_cert_config->setObjRefId($obj_ref_id);
		$part_cert_config->setConfigType(ilParticipationCertificateObjectConfigSet::CONFIG_TYPE_OWN);
		$part_cert_config->setGlConfTemplateId($global_template_id);
		$part_cert_config->store();

		$this->createOrUpdateObjConfigSetFromTemplate($obj_ref_id, $global_template_id);
	}

    /**
     * @param int $obj_ref_id
     * @param int $global_template_id
     * @return void
     */
	private function createOrUpdateObjConfigSetFromTemplate(int $obj_ref_id, int $global_template_id): void
    {
		$this->deleteObjConfigSet($obj_ref_id);

        $config = ilParticipationCertificateConfig::where([
            'config_type' => ilParticipationCertificateObjectConfigSet::CONFIG_TYPE_TEMPLATE,
            'global_config_id' => $global_template_id
        ])->get();

		foreach ($config as  $part_cert_template_config_value) {
			/**
			 * @var ilParticipationCertificateConfig $part_cert_template_config_value
			 */
			$part_cert_config_value = $part_cert_template_config_value;
			$part_cert_config_value->setGroupRefId($obj_ref_id);
			$part_cert_config_value->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP);
			$part_cert_config_value->setGlobalConfigId(0);
			$part_cert_config_value->create();

			if($part_cert_template_config_value->getConfigKey() == 'logo') {
                $file = ilParticipationCertificateFiles::getFile(
                    $global_template_id,
                    'logo'
                );

				if (!empty($file)) {
                    if (!$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $global_template_id, ilParticipationCertificateConfig::LOGO_FILE_NAME))) {

                        if (is_file(
                            ilParticipationCertificateConfig::returnPicturePath(
                                'absolute',
                                $obj_ref_id,
                                ilParticipationCertificateConfig::LOGO_FILE_NAME
                             )
                        )) {
                            unlink(
                                ilParticipationCertificateConfig::returnPicturePath(
                                    'absolute',
                                    $obj_ref_id,
                                    ilParticipationCertificateConfig::LOGO_FILE_NAME
                                )
                            );
                        }

                        copy(
                            ilParticipationCertificateConfig::returnPicturePath(
                                'absolute',
                                $global_template_id,
                                ilParticipationCertificateConfig::LOGO_FILE_NAME
                            ),
                            ilParticipationCertificateConfig::returnPicturePath(
                                'absolute',
                                $obj_ref_id,
                                ilParticipationCertificateConfig::LOGO_FILE_NAME
                            )
                        );

                        ilParticipationCertificateFiles::setFile(
                            $obj_ref_id,
                            'logo',
                            false
                        );
                    }elseif ($file->getResourceStorage()) {
                        ilParticipationCertificateFiles::setFile(
                            $obj_ref_id,
                            'logo',
                            true
                        );
                    }
                }
            }

            if($part_cert_template_config_value->getConfigKey() == 'page1_issuer_signature') {
                $file = ilParticipationCertificateFiles::getFile(
                    $global_template_id,
                    'page1_issuer_signature'
                );

                if (!empty($file)) {
                    if (!$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $global_template_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                        if(is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $obj_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                            unlink(ilParticipationCertificateConfig::returnPicturePath('absolute', $obj_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME));
                        }
                        copy(ilParticipationCertificateConfig::returnPicturePath('absolute', $global_template_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME), ilParticipationCertificateConfig::returnPicturePath('absolute', $obj_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME));

                        ilParticipationCertificateFiles::setFile($obj_ref_id, 'page1_issuer_signature', false);
                    }elseif ($file->getResourceStorage()) {
                        ilParticipationCertificateFiles::setFile($obj_ref_id, 'page1_issuer_signature', true);
                    }
                }
            }
		}
	}

    /**
     * @param int $obj_ref_id
     * @return void
     */
	private function deleteObjConfigSet(int $obj_ref_id): void
    {
		$arr_config = ilParticipationCertificateConfig::where([
            'group_ref_id' => $obj_ref_id
        ])->get();

        if (count($arr_config)) {
			foreach ($arr_config as $config) {

				/**
				 * @var ilParticipationCertificateConfig $config
				 */
                if
                ($config->getConfigValueType() == ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER) {
                    continue;

                }

				switch ($config->getConfigKey()) {
					case 'page1_issuer_signature':
						ilParticipationCertificateConfig::deletePicture(
                            $config->getGroupRefId(),
                            $config->getConfigKey() . '.png');
						break;
					default:
						break;
				}

				$config->delete();
			}
		}
	}


	/**
	 * @return ilParticipationCertificateConfig[]
	 */
	public function returnCertTextDefaultValues(): array
    {
		$arr_configs = [];

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('logo');
		$cert_config->setOrderBy(1);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('percent_value');
		$cert_config->setConfigValue('50');
		$cert_config->setOrderBy(2);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_title');
		$cert_config->setConfigValue('Teilnahmebescheinigung');
		$cert_config->setOrderBy(3);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_introduction1');
		$cert_config->setConfigValue('{{username}}, hat am Studienvorbereitungsprogramm mit Schwerpunkt „Mathematik“ auf der Lernplattform studienvorbereitung.dhbw.de teilgenommen.');
		$cert_config->setOrderBy(4);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_introduction2');
		$cert_config->setConfigValue('Die Teilnahme vor Studienbeginn an der DHBW Karlsruhe umfasste:');
		$cert_config->setOrderBy(5);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_box1_title');
		$cert_config->setConfigValue('Studienvorbereitung - Mathematik:');
		$cert_config->setOrderBy(6);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_box1_row1');
		$cert_config->setConfigValue('Abschluss Diagnostischer Einstiegstest Mathematik');
		$cert_config->setOrderBy(7);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_box1_row2');
		$cert_config->setConfigValue('Bearbeitung von empfohlenen Mathematik - Lernmodulen');
		$cert_config->setOrderBy(8);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_box2_title');
		$cert_config->setConfigValue('Studienvorbereitung - eMentoring:');
		$cert_config->setOrderBy(9);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_box2_row1');
		$cert_config->setConfigValue('Aktive Teilnahme an Videokonferenzen');
		$cert_config->setOrderBy(10);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_box2_row2');
		$cert_config->setConfigValue('Bearbeitung der Aufgaben zu überfachlichen Themen:');
		$cert_config->setOrderBy(11);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_location_date');
		$cert_config->setConfigValue('Karlsruhe, den {{date}}');
		$cert_config->setOrderBy(12);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_issuer_name');
		$cert_config->setConfigValue('Max Mustermann');
		$cert_config->setOrderBy(13);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_issuer_title');
		$cert_config->setConfigValue('(Education Support Center)');
		$cert_config->setOrderBy(14);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_issuer_signature');
		$cert_config->setConfigValue('');
		$cert_config->setOrderBy(15);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page1_disclaimer');
		$cert_config->setConfigValue('');
		$cert_config->setOrderBy(16);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_title');
		$cert_config->setConfigValue('Erläuterungen zur Bescheinigung');
		$cert_config->setOrderBy(17);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_introduction1');
		$cert_config->setConfigValue('Das  Studienvorbereitungsprogramm  mit  Schwerpunkt  Mathematik  auf  der  Lernplattform studienstart.dhbw.de,  richtet  sich  an  Studienanfänger/-innen der  Wirtschaftsinformatik  der DHBW Karlsruhe. Die Teilnehmer/-innen des Programms erhalten die Möglichkeit sich bereits vor  Studienbeginn,  Studientechniken anzueignen  sowie  das  fehlende  Vorwissen  im  Fach  „Mathematik“  aufzuarbeiten.  Dadurch  haben Studierende  mehr  Zeit  ihre  Wissenslücken  in  Mathematik zu schließen und sich mit dem neuen Lernen auseinanderzusetzen.');
		$cert_config->setOrderBy(18);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_introduction2');
		$cert_config->setConfigValue('Ziel des Programms ist es,  Studienanfänger/-innen vor Studienbeginn auf das Fach Mathematik im Studium vorzubereiten. Neben der Vermittlung von mathematischen Inhalten, fördert der Online-Vorkurs  überfachliche  Kompetenzen  wie  Zeitmanagement  und  Lerntechniken  sowie  die Fähigkeit zum Selbststudium.');
		$cert_config->setOrderBy(19);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_introduction3');
		$cert_config->setConfigValue('{{username}} hat im Rahmen des Studienvorbereitungsprogramms mit Schwerpunkt Mathematik mit folgenden Aufgabenstellungen teilgenommen:');
		$cert_config->setOrderBy(20);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_box1_title');
		$cert_config->setConfigValue('Studienvorbereitung – Mathematik');
		$cert_config->setOrderBy(21);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_box1_row1');
		$cert_config->setConfigValue('Abschluss Diagnostischer Einstiegstest Mathematik');
		$cert_config->setOrderBy(22);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

        $cert_config = new ilParticipationCertificateConfig();
        $cert_config->setConfigKey('page2_box1_row1_total_questions');
        $cert_config->setConfigValue('42');
        $cert_config->setOrderBy(23);
        $cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
        $cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
        $arr_configs[] = $cert_config;


		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_box1_row2');
		$cert_config->setConfigValue('Bearbeitung von empfohlenen Mathematik - Lernmodulen');
		$cert_config->setOrderBy(24);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_box2_title');
		$cert_config->setConfigValue('Studienvorbereitung - eMentoring');
		$cert_config->setOrderBy(25);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_box2_row1');
		$cert_config->setConfigValue('Aktive Teilnahme an Videokonferenzen');
		$cert_config->setOrderBy(26);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('page2_box2_row2');
		$cert_config->setConfigValue('Bearbeitung der Aufgaben zu überfachlichen Themen:');
		$cert_config->setOrderBy(27);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		$cert_config = new ilParticipationCertificateConfig();
		$cert_config->setConfigKey('footer_config');
		$cert_config->setConfigValue('Die Resultate dieser Bescheinigung wurden manuell berechnet.');
		$cert_config->setOrderBy(28);
		$cert_config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
		$cert_config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
		$arr_configs[] = $cert_config;

		return $arr_configs;
	}
}