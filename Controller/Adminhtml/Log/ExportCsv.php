<?php

namespace Leonex\RiskManagementPlatform\Controller\Adminhtml\Log;

use Leonex\RiskManagementPlatform\Model\LogCsv;
use Leonex\RiskManagementPlatform\Model\ResourceModel\Log\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Description of ExportCsv
 *
 * @author cstoller
 */
class ExportCsv extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Leonex_RiskManagementPlatform::log';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;


    /**
     * Constructor
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(Context $context, Filter $filter, CollectionFactory $collectionFactory)
    {
        parent::__construct($context);

        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $logCsv = new LogCsv();
        $logCsv->addLogCollection($collection);

        $content = $logCsv->getContentAndClear();

        return $this->resultFactory->create(ResultFactory::TYPE_RAW, [])
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', sprintf('attachment; filename="RMP-Logs.csv"'))
            ->setHeader('Content-Length', strlen($content))
            ->setContents($content);
    }
}
