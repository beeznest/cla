<?php
/* For licensing terms, see /license.txt */

/**
 * Configuration script for the Buy Courses plugin
 *
 * @package chamilo.plugin.buycourses
 */

exit;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();

$salesId = Database::select('id', BuyCoursesPlugin::TABLE_SERVICES_SALE,
    ['status = ?' => BuyCoursesPlugin::PAYOUT_STATUS_COMPLETED]);
$now = new DateTime('now', new DateTimeZone('UTC'));

echo "Sale expirations".PHP_EOL;
echo "================".PHP_EOL;

foreach ($salesId as $saleId) {
    $sale = $plugin->getServiceSale($saleId['id'], 0, BuyCoursesPlugin::PAYOUT_STATUS_COMPLETED);
    $dateEnd = new DateTime($sale['date_end'], new DateTimeZone('UTC'));

    if ($dateEnd > $now) {
//        continue;
    }

    $onComplete = unserialize($sale['service']['on_complete']);

    if (!$onComplete) {
        continue;
    }

    $hook = $onComplete['hook'];

    echo "Sale".PHP_EOL;
    echo "    id: ".$sale['id'].PHP_EOL;
    echo "    service name: ".$sale['service']['name'].PHP_EOL;
    echo "    buyer name: ".$sale['buyer']['name'].' ('.$sale['buyer']['username'].')'.PHP_EOL;
    echo "    buy date: ".$sale['buy_date'].PHP_EOL;
    echo "    date start: ".$sale['date_start'].PHP_EOL;
    echo "    date end: ".$sale['date_end'].PHP_EOL;
    echo PHP_EOL;

    /** @var BuyCoursesPluginHookInterface $onCompleteHook */
    $onCompleteHook = new $hook();
    $onCompleteHook->expireSale($sale);
}

