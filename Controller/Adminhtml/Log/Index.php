<?php

namespace Leonex\RiskManagementPlatform\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Leonex\RiskManagementPlatform\Controller\Adminhtml\Log\Index
 *
 * @author cstoller
 */
class Index extends Action
{
    protected $pageFactory = false;

    public function __construct(Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Logs')));

        return $resultPage;
    }
}
