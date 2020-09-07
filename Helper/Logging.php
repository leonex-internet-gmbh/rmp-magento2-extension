<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Leonex\RiskManagementPlatform\Model\Log;
use Leonex\RiskManagementPlatform\Model\LogFactory;
use Leonex\RiskManagementPlatform\Model\ResourceModel\Log as LogResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Leonex\RiskManagementPlatform\Helper\Logging
 */
class Logging extends AbstractHelper
{
    const XML_PATH = 'leonex_rmp/logging/';

    protected $logFactory;

    protected $logResource;

    protected $logger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        LogFactory $logFactory,
        LogResource $logResource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logFactory = $logFactory;
        $this->logResource = $logResource;
        $this->logger = $logger;
    }

        /**
     * Get config value from given key
     *
     * @param      $code
     * @param null $storeId
     *
     * @return mixed
     */
    protected function getConfigValue($code, $storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH . $code, ScopeInterface::SCOPE_STORE, $storeId);
    }

    protected function getConfigFlag($code, $storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH . $code, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Check if debug logging is enabled.
     *
     * @return bool
     */
    public function isDebugLoggingEnabled(): bool
    {
        return (bool) $this->getConfigFlag('debug_logging_enabled');
    }

    /**
     * Get the config value for how long logs should be stored.
     *
     * @return int
     */
    public function getStorageDurationInDays(): int
    {
        $value = $this->getConfigValue('storage_duration_in_days');

        return $value > 0 ? (int) $value : 0;
    }

    public function log(string $level, string $message, string $tag = null, array $payload = [], int $quoteId = null, int $orderId = null): void
    {
        if (!$this->isDebugLoggingEnabled()) {
            return;
        }

        $this->forceLog($level, $message, $tag, $payload, $quoteId, $orderId);
    }

    public function forceLog(string $level, string $message, string $tag = null, array $payload = [], int $quoteId = null, int $orderId = null): void
    {
        $log = $this->logFactory->create();
        $log
            ->setLevel($level)
            ->setMessage($message)
            ->setTag($tag)
            ->setPayload($payload)
            ->setQuoteId($quoteId)
            ->setOrderId($orderId)
            ->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $this->logResource->save($log);
    }

    public function logToFile(string $level, string $message, string $tag = null, array $payload = [], int $quoteId = null, int $orderId = null): void
    {
        if (!$this->isDebugLoggingEnabled()) {
            return;
        }

        $this->forceLog($level, $message, $tag, $payload, $quoteId, $orderId);
        $this->logger->log($level, $message, $payload);
    }

    public function forceLogToFile(string $level, string $message, string $tag = null, array $payload = [], int $quoteId = null, int $orderId = null): void
    {
        $this->forceLog($level, $message, $tag, $payload, $quoteId, $orderId);
        $this->logger->log($level, $message, $payload);
    }
}
