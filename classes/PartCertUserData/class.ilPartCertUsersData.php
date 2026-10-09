<?php
class ilPartCertUsersData {

	/**
	 * @return ilPartCertUserData[]
	 */
	public static function getData(
        ilParticipationCertificatePlugin $pl,
        array $arr_usr_ids = [],
        ?int $limit = null,
        ?int $offset = null,
        string $sort = null,
        ?string $sortdir = 'asc'
    ): array {
		global $DIC;

		$ilDB = $DIC->database();

        if (empty($arr_usr_ids)) {
            return [];
        }

		$result = $ilDB->query(self::getSQL($arr_usr_ids, $limit, $offset, $sort, $sortdir));
        $usr_data = [];

        while ($row = $ilDB->fetchAssoc($result)) {
			$usr = new ilPartCertUserData();
			$usr->setPartCertUsrId($row['usr_id']);
			$usr->setPartCertUserName($row['loginname']);
			$usr->setPartCertFirstname($row['firstname']);
			$usr->setPartCertLastname($row['lastname']);
            $usr->setPartCertGender($row['gender']);
            $usr->setPartCertSalutation(self::returnSalutation($row['gender'], $pl));
			$usr_data[$row['usr_id']] = $usr;
		}

		return $usr_data;
	}

    /**
     * @param array       $arr_usr_ids
     * @param int|null    $limit
     * @param int|null    $offset
     * @param string|null $sort
     * @param string|null $sort_dir
     * @return string
     */
	protected static function getSQL(
        array $arr_usr_ids = array(),
        ?int $limit = null,
        ?int $offset = null,
        ?string $sort = null,
        ?string $sort_dir = 'asc'
    ): string {
		global $DIC;

        $ilDB = $DIC->database();

        $select = "select
					usr_data.usr_id,
					usr_data.login as loginname,
					udf_firstname.value as firstname,   
					udf_lastname.value as lastname,
					usr_data.gender as gender
					from usr_data
					inner join " . ilParticipationCertificateConfig::TABLE_NAME . " as conf_udf_firstname on conf_udf_firstname.config_key = 'udf_firstname'
					left join usr_profile_data as udf_firstname on udf_firstname.field_id = conf_udf_firstname.config_value and udf_firstname.usr_id = usr_data.usr_id
					inner join " . ilParticipationCertificateConfig::TABLE_NAME . " as conf_udf_lastname on conf_udf_lastname.config_key = 'udf_lastname'
					left join usr_profile_data as udf_lastname on udf_lastname.field_id = conf_udf_lastname.config_value and udf_lastname.usr_id = usr_data.usr_id
	                where " . $ilDB->in('usr_data.usr_id', $arr_usr_ids, false, 'integer');

        if ($sort !== null and $sort_dir !== null) {
			$select .= " order by " . $sort . " " . $sort_dir;
		}

		if ($limit !== null) {
			if ($offset !== null) {
				$select .= " limit " . $limit . " offset " .$offset;
			} else {
				$select .= " limit " . $limit . " offset 0";
			}
		}
		return $select;
	}

    /**
     * @param string                           $gender
     * @param ilParticipationCertificatePlugin $pl
     * @return string
     */
    public static function returnSalutation(
        string $gender,
        ilParticipationCertificatePlugin $pl
    ): string {

        switch ($gender) {
            case 'm':
                return $pl->txt('Mr');
                break;
            case 'f':
                return $pl->txt('Ms');
                break;
            default:
                return '';
                break;
        }
    }
}
