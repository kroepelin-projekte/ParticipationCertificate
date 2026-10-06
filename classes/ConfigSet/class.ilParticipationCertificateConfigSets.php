<?php
class ilParticipationCertificateConfigSets {

	public function __construct() {

	}

    /**
     * @return array
     */
	public function getAllConfigSets(): array
    {
		global $DIC;

		$q = "SELECT  * from
					(
					 (SELECT 
			    id AS conf_id,
			    order_by,
			    CAST( '".ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE."' AS CHAR CHARACTER SET utf8) as configset_type,
			    CAST(title AS CHAR CHARACTER SET utf8)
			     AS title,
			     '' as parent_title,
			     0 as obj_ref_id,
			     active,
			      CONCAT('1-',order_by) conf_order,
			      0 as object_config_type,
			      0 as object_gl_conf_template_id
			     FROM
			    dhbw_part_cert_gl_conf)
			UNION ALL 
			(Select
			    0 AS conf_id,
			     0 as order_by,
			    CAST(  '".ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL."' AS CHAR CHARACTER SET utf8) as configset_type,
			   ''
			     AS title,
			     '' as parent_title,
			     0 as obj_ref_id,
			     1 as active,
			     0 as conf_order,
			     0 as object_config_type,
			     0 as object_gl_conf_template_id)
					UNION ALL 	
			(SELECT 	
			    dhbw_part_cert_ob_conf.id AS conf_id,
			    0 as order_by,
			   CAST(  '".ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP."' AS CHAR CHARACTER SET utf8) as configset_type,
			   CAST( obj_data.title AS CHAR CHARACTER SET utf8)
			    AS title,
			    parent_obj.title as parent_title,
			    obj_ref.ref_id as obj_ref_id,
			    1 as active,
			    3 as conf_order,
			    dhbw_part_cert_ob_conf.config_type as object_config_type,
			    dhbw_part_cert_ob_conf.gl_conf_template_id as object_gl_conf_template_id
			FROM
			    dhbw_part_cert_ob_conf
			        INNER JOIN
			    object_reference AS obj_ref ON obj_ref.ref_id = dhbw_part_cert_ob_conf.obj_ref_id  AND obj_ref.deleted is null
			        INNER JOIN
			    object_data AS obj_data ON obj_data.obj_id = obj_ref.obj_id
			    INNER JOIN tree on tree.child = obj_ref.ref_id
			    INNER JOIN object_reference as parent_ref on parent_ref.ref_id = tree.parent
			    INNER JOIN object_data as parent_obj on parent_obj.obj_id = parent_ref.obj_id
			    order by obj_data.title)
			) as q order by conf_order;";

		$result = $DIC->database()->query($q);

		$arr_config_sets = [];
		while($row = $DIC->database()->fetchAssoc($result)) {
			$arr_config_sets[] = $row;
		}

		return $arr_config_sets;
	}
}
