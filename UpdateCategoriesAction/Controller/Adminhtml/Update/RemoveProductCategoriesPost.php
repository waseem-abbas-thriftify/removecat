<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Thriftify\UpdateCategoriesAction\Controller\Adminhtml\Update;

use Magento\AsynchronousOperations\Model\MassSchedule;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\BulkException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryCatalogAdminUi\Model\BulkSessionProductsStorage;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Backend\Model\Session;

class RemoveProductCategoriesPost extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var MassSchedule
     */
    private $massSchedule;

    /**
     * @var Auth
     */
    private $authSession;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var BulkSessionProductsStorage
     */
    private $bulkSessionProductsStorage;
    /**
     * @var CategoryLinkManagementInterface
     */
    private $categoryLinkManagement;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var Session
     */
    private $session;
    /**
     * @param Action\Context $context
     * @param MassSchedule $massSchedule
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        Action\Context $context,
        MassSchedule $massSchedule,
        LoggerInterface $logger,
        BulkSessionProductsStorage $bulkSessionProductsStorage,
        CategoryLinkManagementInterface $categoryLinkManagement,
        ProductFactory $productFactory,
        Session $session
    ) {
        parent::__construct($context);
        $this->massSchedule = $massSchedule;
        $this->authSession = $context->getAuth();
        $this->logger = $logger;
        $this->bulkSessionProductsStorage = $bulkSessionProductsStorage;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->productFactory = $productFactory;
        $this->session = $session;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $categoryIds = $params["data"]['parent'];
        $productIds = $this->session->getProductIds();
        echo "<pre>"; print_r($productIds); exit;
        $this->session->unsProductIds();
        $counter = 0;
        try {
            if( is_array($productIds) && count($productIds) > 0 ){
                foreach( $productIds as $id ){
                    $productModel = $this->productFactory->create()->load( $id );
                    $previousCatIds = $productModel->getCategoryIds();
                    echo "<pre>"; print_r($previousCatIds);
                    $mergedCatIds = array_unique(array_merge($previousCatIds, $categoryIds));
                    echo "<pre>"; print_r($mergedCatIds); exit;
                    $result = $this->categoryLinkManagement->assignProductToCategories($productModel->getSku(), $mergedCatIds);
                    if( $result ){
                        $counter++;
                    }else{
                        $this->messageManager->addErrorMessage(__('Product with Sku: '. $productModel->getSku(). ' is not Updated Successfully.'));
                    }
                }
                $this->messageManager->addSuccessMessage(__('Bulk operation was successful: %count products were updated Successfully.', [
                    'count' => $counter
                ]));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong during the operation.'));
        }

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $result->setPath('catalog/product/index');
    }
}