<?php
class Increazy_Extender_Model_Observer
{
    const WEBHOOK_BASE_URL = 'https://indexer.api.increazy.com/magento1/webhook/';

    /**
     * Manipula eventos de produto (save, delete)
     */
    public function handleProductEvent($observer)
    {
        $this->log("Evento handleProductEvent disparado");
        $product = $observer->getEvent()->getProduct();
        $action = $this->getActionFromEvent($observer->getEventName());

        $data = [
            'app' => $this->getAppId(),
            'entity_type' => 'product',
            'action' => $action,
            'product_id' => $product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'timestamp' => date('c')
        ];

        $this->sendWebhook($data, 'product');
    }

    /**
     * Manipula eventos de categoria (save, delete)
     */
    public function handleCategoryEvent($observer)
    {
        $this->log("Evento handleCategoryEvent disparado");
        $category = $observer->getEvent()->getCategory();
        $action = $this->getActionFromEvent($observer->getEventName());

        $data = [
            'app' => $this->getAppId(),
            'entity_type' => 'category',
            'action' => $action,
            'category_id' => $category->getId(),
            'name' => $category->getName(),
            'timestamp' => date('c')
        ];

        $this->sendWebhook($data, 'category');
    }

    /**
     * Manipula importação em massa de produtos
     */
    public function handleProductImport($observer)
    {
        $this->log("Evento handleProductImport disparado");
        $data = [
            'app' => $this->getAppId(),
            'entity_type' => 'product',
            'action' => 'import',
            'timestamp' => date('c')
        ];

        $this->sendWebhook($data, 'product');
    }

    /**
     * Determina a ação com base no nome do evento
     */
    private function getActionFromEvent($eventName)
    {
        if (strpos($eventName, 'save_after') !== false) {
            return 'save';
        } elseif (strpos($eventName, 'delete_after') !== false) {
            return 'delete';
        }
        return 'unknown';
    }

    /**
     * Recupera o ID da aplicação a partir da configuração
     */
    private function getAppId()
    {
        $appId = Mage::getStoreConfig('extender/general/app_id');
        if (empty($appId)) {
            $this->log("ID da aplicação (app) não configurado");
        }
        return $appId;
    }

    /**
     * Verifica se o logging está habilitado
     */
    private function isLoggingEnabled()
    {
        return Mage::getStoreConfigFlag('extender/general/enable_logging');
    }

    /**
     * Função auxiliar para gravar logs, se habilitado
     */
    private function log($message)
    {
        if ($this->isLoggingEnabled()) {
            Mage::log($message, null, 'increazy_extender.log', true);
        }
    }

    /**
     * Envia o webhook para a API
     */
    private function sendWebhook($data, $entityType)
    {
        try {
            $webhookUrl = self::WEBHOOK_BASE_URL . $entityType;
            $dataJson = json_encode($data);
            $this->log("Webhook enviando: URL: $webhookUrl | Corpo: $dataJson");

            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            if ($response === false) {
                $this->log("Erro cURL: " . curl_error($ch));
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode !== 200) {
                $this->log("Webhook falhou: HTTP $httpCode - Resposta: $response");
            } else {
                $this->log("Webhook enviado com sucesso: HTTP $httpCode");
            }

            curl_close($ch);
        } catch (Exception $e) {
            $this->log("Erro ao enviar webhook: " . $e->getMessage());
        }
    }
}