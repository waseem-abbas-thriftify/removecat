<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Thriftify\UpdateCategoriesAction\Controller\Adminhtml\Update;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\Session;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Mass assign sources to products.
 */
class ProductCategories extends Action
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var Session
     */
    private $session;

    /**
     * MassActions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context,
        ResultFactory $resultFactory,
        Session $session,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
        $this->session = $session;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productIds = $collection->getAllIds();
        $this->session->setProductIds($productIds);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend("Update Products Categories");
        return $resultPage;
    }
}