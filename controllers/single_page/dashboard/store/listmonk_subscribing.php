<?php
namespace Concrete\Package\CommunityStoreListmonkSubscribing\Controller\SinglePage\Dashboard\Store;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Support\Facade\Application;

class ListmonkSubscribing extends DashboardPageController
{
    public function view()
    {
        $app = Application::getFacadeApplication();
        $config = $app->make('config');
        $this->set('enableSubscriptions', $config->get('listmonk_subscribing.enableSubscriptions'));
        $this->set('apiKey', $config->get('listmonk_subscribing.apiKey'));
        $this->set('apiUser', $config->get('listmonk_subscribing.apiUser'));
        $this->set('defaultListID', $config->get('listmonk_subscribing.defaultListID'));
        $this->set('url', $config->get('listmonk_subscribing.url'));
    }

    public function settings_saved()
    {
        $this->set('message', t('Settings Saved'));
        $this->view();
    }

    public function save_settings()
    {
        $app = Application::getFacadeApplication();
        $config = $app->make('config');

        if ($this->post()) {
            if ($this->token->validate('save_settings')) {
                $enableSubscriptions = $this->request->post('enableSubscriptions');
                $apiKey = $this->request->post('apiKey');
                $apiUser = $this->request->post('apiUser');
                $defaultListID = $this->request->post('defaultListID');
                $url = $this->request->post('url');

                if ($enableSubscriptions) {
                    if (!$apiKey) {
                        $this->error->add(t('An API Key is required'));
                    }
                    if (!$apiUser) {
                        $this->error->add(t('An API User is required'));
                    }
                    if (!$url) {
                        $this->error->add(t('An URL is required'));
                    }
                }

                $config->save('listmonk_subscribing.enableSubscriptions', $enableSubscriptions);
                $config->save('listmonk_subscribing.apiKey', $apiKey);
                $config->save('listmonk_subscribing.apiUser', $apiUser);
                $config->save('listmonk_subscribing.defaultListID', $defaultListID);
                $config->save('listmonk_subscribing.url', $url);

                if (!$this->error->has()) {
                    $this->redirect('/dashboard/store/listmonk_subscribing', 'settings_saved');
                }
            } else {
                $this->error->add(t('Invalid CSRF token. Please refresh and try again.'));
                $this->view();
            }
        }
    }
}
