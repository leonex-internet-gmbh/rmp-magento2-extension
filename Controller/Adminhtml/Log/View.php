<?php

namespace Leonex\RiskManagementPlatform\Controller\Adminhtml\Log;

use Leonex\RiskManagementPlatform\Model\LogFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\OrderRepository;

/**
 * Leonex\RiskManagementPlatform\Controller\Adminhtml\Log\View
 *
 * @author cstoller
 */
class View extends Action
{
    protected $pageFactory = false;
    protected $logFactory;

    public function __construct(Context $context, PageFactory $pageFactory, LogFactory $logFactory, OrderRepository $of)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->logFactory = $logFactory;
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Log'));

        $logId = $this->getRequest()->getParam('id');
        $log = $this->logFactory->create();
        $log->load($logId);

        $contentBlock = $page->getLayout()->getBlock('rmp_log_view_content');
        $contentBlock->setData('log', $log);

        return $page;
    }
}
