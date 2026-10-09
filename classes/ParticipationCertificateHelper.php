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

    /**
     * @param int                   $container_ref_id
     * @param InternalDomainService $domain
     * @return int|null
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */

    private static function getSessionsOfContainer(
        int $container_ref_id,
        InternalDomainService $domain
    ): int|null {
        $container_object_factory = \ilObjectFactory::getInstanceByRefId($container_ref_id);

        $item_presentation = $domain
            ->content()
            ->itemPresentation(
                $container_object_factory, // TODO replace it with container ???
                null,
                false
            );

        $items = $item_presentation->getAllRefIds();

        $session_ref_ids = null;
        foreach ($items as $key => $item_ref_id) {
            $item_object = \ilObjectFactory::getInstanceByRefId($item_ref_id);

            if ($item_object->getType() === 'grp') {
                $session_ref_ids = (int) $item_ref_id;
            }
        }
        return $session_ref_ids;
    }
}
