<?php

class ilParticipationCertificateObjectConfigSet extends ActiveRecord {

    public const string TABLE_NAME = 'dhbw_part_cert_ob_conf';

    public const int CONFIG_TYPE_TEMPLATE = 1;

    public const int CONFIG_TYPE_OWN = 2;

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
	protected int $obj_ref_id;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @con_is_notnull  true
	 * @db_length       8
	 */
	protected int $config_type = self::CONFIG_TYPE_TEMPLATE;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @con_is_notnull  true
	 * @db_length       8
	 */
	protected int $gl_conf_template_id;

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
     * @return int
     */
	public function getObjRefId(): int
    {
		return $this->obj_ref_id;
	}

    /**
     * @param int $obj_ref_id
     * @return void
     */
	public function setObjRefId(int $obj_ref_id): void
    {
		$this->obj_ref_id = $obj_ref_id;
	}

    /**
     * @return int
     */
	public function getGlConfTemplateId(): int
    {
		return $this->gl_conf_template_id;
	}

    /**
     * @param int $gl_conf_template_id
     * @return void
     */
	public function setGlConfTemplateId(int $gl_conf_template_id): void
    {
		$this->gl_conf_template_id = $gl_conf_template_id;
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
}
