<?php
namespace Concrete\Package\CommunityStoreListmonkSubscribing\Src\Event;

use Concrete\Core\Support\Facade\Application;
use RuntimeException;

class Order
{
    public function orderPaymentComplete($event)
    {
        $order = $event->getOrder();
        if ($order) {
            $this->handleOrder($order);
        }
    }

    private function handleOrder($order): void
    {
        $app    = Application::getFacadeApplication();
        $config = $app->make('config');

        $baseUrl = rtrim((string) $config->get('listmonk_subscribing.url'), '/'); // UI-Root, OHNE /api
        $apiUser = (string) $config->get('listmonk_subscribing.apiUser');
        $apiKey  = (string) $config->get('listmonk_subscribing.apiKey');
        $apiBase = $baseUrl . '/api';

        $defaultListID = (int) $config->get('listmonk_subscribing.defaultListID');

        // Kundendaten
        $email = (string) $order->getAttribute('email');
        $name  = trim((string) $order->getAttribute('billing_first_name') . ' ' . (string) $order->getAttribute('billing_last_name'));

        // Checkbox-Attribut: nur fortfahren, wenn es NICHT existiert ODER aktiv ist
        $subscribeFlag = $order->getAttribute('listmonk_checkout_subscribe');
        if (isset($subscribeFlag) && $subscribeFlag !== true) {
            return; // bewusst NICHT abonnieren
        }

        // Alle Ziel-Listen sammeln
        $listIds = [];
        if ($defaultListID > 0) {
            $listIds[] = $defaultListID;
        }

        $items = $order->getOrderItems();
        if ($items) {
            foreach ($items as $item) {
                $attr = $item->getProductObject()->getAttribute('listmonk_list_id');
                // Attribut könnte String wie "3" oder "3,7" enthalten:
                if ($attr) {
                    foreach (preg_split('/[,\s]+/', (string)$attr) as $raw) {
                        $id = (int) trim($raw);
                        if ($id > 0) {
                            $listIds[] = $id;
                        }
                    }
                }
            }
        }

        $listIds = array_values(array_unique($listIds));
        if (!$email || empty($listIds)) {
            return;
        }

        // Idempotent: anlegen ODER Listen hinzufügen
        try {
            $this->upsertSubscriberToLists($apiBase, $apiUser, $apiKey, $email, $name, $listIds);
        } catch (\Throwable $e) {
            // Hier kannst du Concrete-Logging nutzen, z. B. \Log::addError(...)
            // \Log::addError('Listmonk subscribe failed: ' . $e->getMessage());
        }
    }

    /**
     * Legt Subscriber neu an (inkl. Listen) oder fügt ihm die Listen hinzu, falls er existiert.
     * Verwendet "search" statt SQL-Query -> WAF-freundlich.
     */
    private function upsertSubscriberToLists(string $apiBase, string $user, string $pass, string $email, string $name, array $listIds): void
    {
        // 1) Neu anlegen (inkl. aller Listen)
        $create = $this->apiCall('POST', "$apiBase/subscribers", $user, $pass, [
            'email'    => $email,
            'name'     => $name,
            'status'   => 'enabled',
            'lists'    => $listIds,
            // optional: direkte Bestätigung (nur nutzen, wenn rechtlich zulässig)
            'preconfirm_subscriptions' => true,
        ]);

        if ($create['code'] >= 200 && $create['code'] < 300) {
            return; // erfolgreich neu angelegt
        }

        // 2) Existiert bereits -> ID suchen (search) und Listen hinzufügen
        if (in_array($create['code'], [400, 409, 422], true)) {
            $find = $this->apiCall('GET', "$apiBase/subscribers", $user, $pass, null, [
                'per_page' => 1,
                'search'   => $email,
            ]);
            if (!($find['code'] >= 200 && $find['code'] < 300)) {
                throw new RuntimeException("Lookup fehlgeschlagen: HTTP {$find['code']} :: {$find['raw']}");
            }
            $results = $find['body']['data']['results'] ?? [];
            if (!$results) {
                throw new RuntimeException("Subscriber existiert, aber nicht auffindbar: {$email}");
            }
            $sid = (int) $results[0]['id'];

            // Alle Ziel-Listen in einem Rutsch hinzufügen
            $add = $this->apiCall('PUT', "$apiBase/subscribers/lists", $user, $pass, [
                'ids'             => [$sid],
                'action'          => 'add',
                'target_list_ids' => $listIds,
                'status'          => 'confirmed', // alternativ: unconfirmed / unsubscribed
            ]);
            if (!($add['code'] >= 200 && $add['code'] < 300)) {
                throw new RuntimeException("Liste hinzufügen fehlgeschlagen: HTTP {$add['code']} :: {$add['raw']}");
            }
            return;
        }

        // 3) anderer Fehler
        throw new RuntimeException("Create fehlgeschlagen: HTTP {$create['code']} :: {$create['raw']}");
    }

    private function apiCall(string $method, string $url, string $user, string $pass, ?array $json = null, array $query = []): array
    {
        $ch = curl_init();
        if ($query) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        $headers = ['Accept: application/json'];
        if ($json !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERPWD        => $user . ':' . $pass,
            CURLOPT_TIMEOUT        => 30,
        ]);

        if ($json !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('cURL error: ' . $err);
        }
        $body = json_decode($raw, true);

        return ['code' => $code, 'raw' => $raw, 'body' => $body];
    }
}
