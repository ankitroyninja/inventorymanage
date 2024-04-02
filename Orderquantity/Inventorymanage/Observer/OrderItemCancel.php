<?php
namespace Orderquantity\Inventorymanage\Observer;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Catalog inventory module observer
 */
class OrderItemCancel implements ObserverInterface
{
    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var Magento\CatalogInventory\Api\StockRegistryInterface 
     */
    protected $stockRegistry;

    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @param StockManagementInterface $stockManagement
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     */

    public function __construct(
        StockManagementInterface $stockManagement,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    ) {
        $this->stockManagement = $stockManagement;
        $this->stockConfiguration = $stockConfiguration;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Cancel order item
     *
     * @param   EventObserver $observer
     * @return  void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order\Item $item */
        $item = $observer->getEvent()->getItem();
        $children = $item->getChildrenItems();
        $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
        $canSubstract = $this->stockConfiguration->canSubtractQty(null);
        if ($item->getId() && $item->getProductId() && empty($children) && $qty && !$canSubstract) {
            $stockItem = $this->stockRegistry->getStockItemBySku($item->getSku());
             $stockQty = $stockItem->getQty() + $qty;
             $stockItem->setQty($stockQty);
             $stockItem->setIsInStock((bool)$stockQty);
             $this->stockRegistry->updateStockItemBySku($item->getSku(), $stockItem);
        }
    }
}
