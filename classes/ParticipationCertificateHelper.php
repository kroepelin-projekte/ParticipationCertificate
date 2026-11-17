<?php

use ILIAS\Container\InternalDomainService;

class ParticipationCertificateHelper
{
    /**
     * @param int $course_ref_id
     * @return int|null
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public static function getGroupRefId(int $course_ref_id): ?int
    {
        global $DIC;

        $domain = $DIC->container()
                      ->internal()
                      ->domain();

        $container_ref_id = $DIC->repositoryTree()->getParentId($course_ref_id);

        return self::getGroupOfContainer($container_ref_id, $domain);
    }

    /**
     * @param int $group_ref_id
     * @return array
     */
    public static function getSessions(int $group_ref_id): array
    {
        global $DIC;

        return $DIC->repositoryTree()->getChildsByType($group_ref_id, 'sess');
    }

    /**
     * ilEventParticipants reads all participants of its session, so it is created once per session.
     *
     * @param array $sessions as returned by getSessions()
     * @return array<int, int> usr_id => attended sessions
     */
    public static function countAttendedSessions(array $sessions, array $usr_ids): array
    {
        $usr_ids = array_map('intval', $usr_ids);
        $attended = array_fill_keys($usr_ids, 0);

        foreach ($sessions as $session) {
            $event_participants = new ilEventParticipants((int) $session['obj_id']);
            foreach ($usr_ids as $usr_id) {
                if ($event_participants->hasParticipated($usr_id)) {
                    $attended[$usr_id]++;
                }
            }
        }

        return $attended;
    }

    /**
     * @param int                   $container_ref_id
     * @param InternalDomainService $domain
     * @return int|null
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */

    private static function getGroupOfContainer(
        int $container_ref_id,
        InternalDomainService $domain
    ): int|null {
        $containerObjectFactory = \ilObjectFactory::getInstanceByRefId($container_ref_id);

        $itemPresentation = $domain
            ->content()
            ->itemPresentation(
                $containerObjectFactory, // TODO replace it with container ???
                null,
                false
            );

        $items = $itemPresentation->getAllRefIds();
        $group_ref_id = null;
        foreach ($items as $key => $item_ref_id) {
            $itemObject = \ilObjectFactory::getInstanceByRefId($item_ref_id);

            if ($itemObject->getType() === 'grp') {
                $group_ref_id = (int) $item_ref_id;

                break;
            }
        }
        return $group_ref_id;
    }
}
