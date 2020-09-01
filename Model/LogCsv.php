<?php

namespace Leonex\RiskManagementPlatform\Model;

use Leonex\RiskManagementPlatform\Model\Log;
use Leonex\RiskManagementPlatform\Model\ResourceModel\Log\Collection;

/**
 * Leonex\RiskManagementPlatform\Model\LogCsv
 *
 * Model to generate CSV files in memory.
 */
class LogCsv
{
    const HEADER_FIELDS = [
        'id', 'quote id', 'order increment id', 'level', 'tag', 'message', 'payload json', 'created at',
    ];

    /**
     * File pointer as data wrapper.
     *
     * @var null|resource
     */
    protected $_fp;

    public function addLog(Log $log): void
    {
        $this->_initFilePointer();

        $fields = [
            $log->getId(),
            $log->getQuoteId(),
            $log->getOrderId(),
            $log->getLevel(),
            $log->getTag(),
            $log->getMessage(),
            $log->getPayload(),
            $log->getCreatedAt(),
        ];
        fputcsv($this->_fp, $fields, ';', '"');
    }

    public function addLogCollection(Collection $collection): void
    {
        foreach ($collection as $log) {
            $this->addLog($log);
        }
    }

    /**
     * Get the content of the CSV stream in memory.
     *
     * @return string
     */
    public function getContent(): string
    {
        $this->_initFilePointer();

        return stream_get_contents($this->_fp, -1, 0);
    }

    /**
     * Get the content of the CSV stream in memory and clear it.
     *
     * Adding new log entries will create a new CSV stream.
     *
     * @return string
     */
    public function getContentAndClear(): string
    {
        $content = $this->getContent();

        fclose($this->_fp);
        $this->_fp = null;

        return $content;
    }

    protected function _initFilePointer(): void
    {
        if (!$this->_fp) {
            $this->_fp = fopen('php://memory', 'a+');

            fputcsv($this->_fp, self::HEADER_FIELDS, ';', '"');
        }
    }
}
