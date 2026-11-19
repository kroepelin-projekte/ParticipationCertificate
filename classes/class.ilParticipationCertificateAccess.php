<?php

class ilParticipationCertificateAccess {

	protected ilParticipationCertificatePlugin $pl;

	protected int $group_ref_id;

	protected ilAccessHandler $access;

	protected ilObjUser $usr;

	protected ilDBInterface $db;

	/** @var array<int, bool> ref_id => plugin enabled, cached per request */
	private static array $enabled_cache = [];

	/** @var string[]|null configured keywords, cached per request */
	private static ?array $keywords = null;

	/**
	 * ilParticipationCertificateAccess constructor.
	 */
	public function __construct(int $group_ref_id) {
		global $DIC;
		$this->pl = ilParticipationCertificatePlugin::getInstance();
		$this->group_ref_id = $group_ref_id;
		$this->access = $DIC->access();
		$this->usr = $DIC->user();
		$this->db = $DIC->database();
	}

	/**
	 * The plugin is only
	 * active for courses/groups (not in the trash) whose title contains a configured keyword.
	 * Every entry GUI must check this, otherwise any ref_id where the user has
	 * write/read_learning_progress could be used (e.g. a group admin's group in a foreign course).
	 */
	public static function isEnabledForObject(int $ref_id): bool
	{
		if ($ref_id <= 0) {
			return false;
		}

		if (!array_key_exists($ref_id, self::$enabled_cache)) {
			$type = ilObject::_lookupType($ref_id, true);
			$enabled = ($type === 'crs' || $type === 'grp')
				&& !ilObject::_isInTrash($ref_id)
				&& self::titleMatchesKeyword((string) ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id)));

			self::$enabled_cache[$ref_id] = $enabled;
		}

		return self::$enabled_cache[$ref_id];
	}

	public function isEnabled(): bool
	{
		return self::isEnabledForObject($this->group_ref_id);
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
	 * @return string[]
	 */
	public static function getKeywords(): array
	{
		if (self::$keywords === null) {
			$config = ilParticipationCertificateConfig::where([
				'config_key' => 'keyword',
				'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL,
				'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER,
				'group_ref_id' => 0
			])->first();

			self::$keywords = is_object($config) ? self::parseKeywords((string) $config->getConfigValue()) : [];
		}

		return self::$keywords;
	}

	/**
	 * Case-insensitive (multibyte-safe) match against the configured keywords.
	 * No keywords configured means no match.
	 */
	public static function titleMatchesKeyword(string $title): bool
	{
		foreach (self::getKeywords() as $keyword) {
			if (mb_stripos($title, $keyword) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Course whose initial test belongs to this object: the course itself,
	 * or for a group the course above it. 0 if there is none.
	 */
	public function getCourseRefId(): int
	{
		global $DIC;

		$type = ilObject::_lookupType($this->group_ref_id, true);
		if ($type === 'crs') {
			return $this->group_ref_id;
		}
		if ($type === 'grp') {
			return (int) $DIC->repositoryTree()->checkForParentType($this->group_ref_id, 'crs');
		}
		return 0;
	}

    /**
     * write or read_learning_progress, only for objects where the plugin is enabled
     */
	public function hasCurrentUserWriteAccess(): bool
	{
		if (!$this->isEnabled()) {
			return false;
		}
		if ($this->access->checkAccess('write', '', $this->group_ref_id)) {
			return true;
		}
		if ($this->access->checkAccess('read_learning_progress', '', $this->group_ref_id)) {
			return true;
		}

		return false;
	}

    /**
     * @return bool
     */
	public function hasCurrentUserAdminAccess(): bool
	{
		if ($this->access->checkAccess('write', '', $this->group_ref_id)) {
			return true;
		}

		return false;
	}

	/**
	 * @throws Exception
	 */
	public function hasCurrentUserPrintAccess(?bool $print_by_course_member = false): bool
	{
        if (!$print_by_course_member && $this->hasCurrentUserWriteAccess()) {
            return true;
        }

		if (!$this->isEnabled()) {
			return false;
		}

		$usr_id = $this->usr->getId();
		if ($usr_id <= 0 || $usr_id === ANONYMOUS_USER_ID) {
			return false;
		}

		if (!$this->hasCurrentUserReadAccess()) {
			return false;
		}

		if (!$this->isCurrentUserMember()) {
			return false;
		}

		return ($this->isSelfPrintEnabled());
	}

	/**
	 * True if the current user has the member role in this course/group
	 */
	public function isCurrentUserMember(): bool
	{
		$type = ilObject::_lookupType($this->group_ref_id, true);
		if ($type !== 'crs' && $type !== 'grp') {
			return false;
		}

		return ilParticipants::getInstance($this->group_ref_id)->isMember($this->usr->getId());
	}

	/**
	 * @throws Exception
	 */
	public function isSelfPrintEnabled(): bool
	{
		// formerly hasCurrentUserPrintAccess
		$enable_self_print = boolval(ilParticipationCertificateConfig::getConfig('enable_self_print', $this->group_ref_id));

		if ($enable_self_print) {
			$start = new DateTime(ilParticipationCertificateConfig::getConfig('self_print_start', $this->group_ref_id));
			$end = new DateTime(ilParticipationCertificateConfig::getConfig('self_print_end', $this->group_ref_id));
			$now = new DateTime();

			return ($now >= $start && $now <= $end);
		}

		return false;
	}

    /**
     * @return bool
     */
	public function hasCurrentUserReadAccess(): bool
	{
		if (!$this->isEnabled()) {
			return false;
		}
		if ($this->access->checkAccess('read', '', $this->group_ref_id)) {
			return true;
		}

		return false;
	}

    /**
     * @return bool
     */
	public function hasCurrentUserSpecialAccess(): bool
	{
		if (!$this->isEnabled()) {
			return false;
		}
		if ($this->access->checkAccess('read_learning_progress', '', $this->group_ref_id)) {
			return true;
		}

		return false;
	}

    /**
     * Keeps only the given user ids that the current user may see in this
     * course/group, i.e. those returned by getUserIdsOfGroup(). Use this for
     * every user id taken from the request.
     *
     * @param array $user_ids
     * @return int[]
     */
    public function filterUserIdsOfGroup(array $user_ids): array
    {
        $member_ids = array_map('intval', $this->getUserIdsOfGroup());
        $user_ids = array_map('intval', $user_ids);

        return array_values(array_unique(array_intersect($user_ids, $member_ids)));
    }

    /**
     * @return array
     */
	public function getUserIdsOfGroup(): array
	{
        $usr_data = [];
		if ($this->hasCurrentUserWriteAccess() || $this->hasCurrentUserSpecialAccess()) {
			$objecttype = ilObject::_lookupType($this->group_ref_id, true);
			if ($objecttype != 'grp' and $objecttype != 'crs') {
				$objecttype = 'grp';
			}
			$select = "select obj_members.usr_id from obj_members
						inner join object_data as grp_obj on grp_obj.obj_id = obj_members.obj_id and grp_obj.type = '". $objecttype ."'
						inner join object_reference as grp_ref on grp_ref.obj_id = obj_members.obj_id
						where grp_ref.ref_id = " . $this->db->quote($this->group_ref_id, 'integer') . " and obj_members.member >= 1";
			$result = $this->db->query($select);

			while ($row = $this->db->fetchAssoc($result)) {
				$usr_data[] = $row['usr_id'];
			}
		} elseif ($this->hasCurrentUserReadAccess()) {
			$usr_data[] = $this->usr->getId();
		}
		return $usr_data;
	}
}
