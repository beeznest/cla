<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course,
    Chamilo\CoreBundle\Entity\Session;

/**
 * Class BuyCoursesPluginChangeExtraFieldValueHook
 */
class BuyCoursesPluginChangeExtraFieldValueHook implements BuyCoursesPluginHookInterface
{
    /**
     * @param array $saleInfo The service sale information
     * @return bool
     */
    public function completeSale(array $saleInfo)
    {
        $em = Database::getManager();

        $onComplete = unserialize($saleInfo['service']['on_complete']);

        if ($onComplete === false) {
            return;
        }

        $itemId = 0;
        $efv = null;

        switch ($saleInfo['node_type']) {
            case BuyCoursesPlugin::SERVICE_TYPE_COURSE:
                /** @var Course $course */
                $course = $em->find('ChamiloCoreBundle:Course', $saleInfo['node_id']);

                if (!$course) {
                    return;
                }

                $itemId = $course->getId();

                $efv = new ExtraFieldValue('course');
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_SESSION:
                /** @var Session $session */
                $session = $em->find('ChamiloCoreBundle:Session', $saleInfo['node_id']);

                if (!$session) {
                    return;
                }

                $itemId = $session->getId();

                $efv = new ExtraFieldValue('session');
                break;
        }

        if (!$efv) {
            return;
        }

        $efv->save([
            'item_id' => $itemId,
            'value' => $onComplete['new_value'],
            'variable' => $onComplete['variable']
        ]);
    }
}
