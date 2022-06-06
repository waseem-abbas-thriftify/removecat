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
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\CategoryLinkRepository;

/**
 * Mass assign sources to products.
 */
class RemoveProductCategories extends Action
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
     * @var CategoryLinkRepository
     */
    private $categoryLinkManagement;
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context,
        ResultFactory $resultFactory,
        Session $session,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CategoryLinkRepository $categoryLinkManagement,
        ProductFactory $productFactory
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
        $this->session = $session;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->productFactory = $productFactory;
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productIds = $collection->getAllIds();

        if( count($productIds) < 1 ){
            $this->messageManager->addErrorMessage(__('Not able to get Product Ids, please perform the operation again...'));
            return $result->setPath('catalog/product/index');
        }

        $categoryIds = array();
        $params = $this->getRequest()->getParams();
        if( isset($params["filters"]["category_id"]) ){
            $categoryIds = $params["filters"]["category_id"];
        }

        if( count($categoryIds) < 1 ){
            $this->messageManager->addErrorMessage(__('Not able to get Category Ids, please perform the operation again...'));
            return $result->setPath('catalog/product/index');
        }

        $counter = 0;
        foreach( $productIds as $id ){
            $productModel = $this->productFactory->create()->load( $id );
            $previousCatIds = $productModel->getCategoryIds();
            //$mergedCatIds = array_diff($previousCatIds, $categoryIds);
            foreach( $categoryIds as $categoryId ){
                if(in_array($categoryId, $previousCatIds)){
                    $response = $this->categoryLinkManagement->deleteByIds($categoryId, $productModel->getSku());
                    if (!$response){
                        $this->messageManager->addErrorMessage(__('Product with Sku: '. $productModel->getSku(). ' is not Updated Successfully.'));
                    }
                }
            }
            $counter++;
        }
        $this->messageManager->addSuccessMessage(__('Bulk operation was successful: %count products were updated Successfully.', [
            'count' => $counter
        ]));
        return $result->setPath('catalog/product/index');
    }
}