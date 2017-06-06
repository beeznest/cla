<?php
/* For license terms, see /license.txt */

/**
 * Interface BuyCoursesPluginServiceSaleHookInterface
 */
interface BuyCoursesPluginHookInterface
{
    /**
     * @param array $saleInfo The service sale information
     * @return bool
     */
    public function completeSale(array $saleInfo);
}
