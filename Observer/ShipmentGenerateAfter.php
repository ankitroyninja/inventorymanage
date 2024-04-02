<?php

namespace Orderquantity\Inventorymanage\Observer;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\Framework\Event\ObserverInterface;

class ShipmentGenerateAfter implements ObserverInterface
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

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();

        /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
        $canSubstract = $this->stockConfiguration->canSubtractQty(null);
        if(!$canSubstract){
            foreach ($shipment->getAllItems() as $shipmentItem) {
                $orderItem = $shipmentItem->getOrderItem();
                $productSku = $orderItem->getSku();
                $qtyShipped = $orderItem->getQtyShipped();
                if($productSku && $qtyShipped) {
                    $stockItem = $this->stockRegistry->getStockItemBySku($productSku);
                    $stockQty = $stockItem->getQty();
                    $stockItem->setQty($stockQty);
                    $stockItem->setIsInStock((bool)$stockQty);
                    $this->stockRegistry->updateStockItemBySku($productSku, $stockItem);
                }
            }
        }
    }
}