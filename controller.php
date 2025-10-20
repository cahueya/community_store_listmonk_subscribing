<?php
namespace Concrete\Package\CommunityStoreListmonkSubscribing;

use Concrete\Core\Support\Facade\Application;
use Events;
use Package;
use SinglePage;
use Whoops\Exception\ErrorException;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_listmonk_subscribing';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '1.0';

    protected $pkgAutoloaderRegistries = [
        'src/Event' => '\Concrete\Package\CommunityStoreListmonkSubscribing\Src\Event',
    ];

    public function getPackageName()
    {
        return t('Listmonk Subscribing');
    }

    public function getPackageDescription()
    {
        return t('Subscribe Community Store customers to Listmonk lists based on products purchased.');
    }

    public function install()
    {
        $installed = Package::getInstalledHandles();
        if (!(is_array($installed) && in_array('community_store', $installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        }

        $pkg = parent::install();
        $sp = SinglePage::add('/dashboard/store/listmonk_subscribing', $pkg);
        if (is_object($sp)) {
            $sp->update(array('cName' => t('Listmonk Subscribing')));
        }
    }

    public function on_start()
    {
        $app = Application::getFacadeApplication();
        $config = $app->make('config');
        $enableSubscriptions = $config->get('listmonk_subscribing.enableSubscriptions');
        if ($enableSubscriptions) {
            $orderlistener = $app->make('\Concrete\Package\CommunityStoreListmonkSubscribing\Src\Event\Order');
            Events::addListener('on_community_store_payment_complete', array($orderlistener, 'orderPaymentComplete'));
        }
    }
}
