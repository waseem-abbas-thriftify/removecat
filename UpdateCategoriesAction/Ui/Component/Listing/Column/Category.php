<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Created By : Rohan Hapani
 */
namespace Thriftify\UpdateCategoriesAction\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\App\ResourceConnection;

class Category extends \Magento\Ui\Component\Listing\Columns\Column
{

    /**
     * @var \Magento\Catalog\Model\ProductCategoryList
     */
    private $productCategory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ContextInterface                                 $context
     * @param UiComponentFactory                               $uiComponentFactory
     * @param array                                            $components
     * @param array                                            $data
     * @param \Magento\Catalog\Model\ProductCategoryList       $productCategory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param ResourceConnection                               $resourceConnection 
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = [],
        \Magento\Catalog\Model\ProductCategoryList $productCategory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->productCategory = $productCategory;
        $this->categoryRepository = $categoryRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Prepare date for category column
     * @param  array  $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $fieldName = $this->getData('name');
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $productId = $item['entity_id'];
                $categoryIds = $this->getCategoryIdsByProductId($productId);
                $categories = [];
                if (count($categoryIds)) {
                    foreach ($categoryIds as $categoryId) {
                        $categoryData = $this->categoryRepository->get($categoryId);
                        $categories[] = $categoryData->getName();
                    }
                }
                $item[$fieldName] = implode(',', $categories);
            }
        }
        return $dataSource;
    }


    /**
     * get all the category id
     *
     * @param int $productId
     * @return array
     */
    private function getCategoryIds(int $productId)
    {
        $categoryIds = $this->productCategory->getCategoryIds($productId);
        $category = [];
        if ($categoryIds) {
            $category = array_unique($categoryIds);
        }
        return $category;
    }

    /**
     * get all product category ids from product id
     *
     * @param int $productId
     * @return array
     */
    private function getCategoryIdsByProductId(int $productId)
    { 
        $connection = $this->resourceConnection->getConnection();
        
        $table = $this->resourceConnection->getTableName('catalog_category_product');
        $query = $connection->select()->from($table, ['category_id'])->where(
            'product_id = ?',
            $productId
        );
        
        return $connection->fetchCol($query);
    }
}