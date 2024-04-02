<?php
 
namespace Orderquantity\Inventorymanage\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;
 
class Orderplaceafter implements ObserverInterface
{
    protected $logger;

    /**
     * @var Magento\CatalogInventory\Api\StockRegistryInterface 
     */
    protected $stockRegistry;

    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    protected $stockConfiguration;
 
    public function __construct(
        LoggerInterface $logger,
        StockManagementInterface $stockManagement,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    )
    {
        $this->logger = $logger;
        $this->stockManagement = $stockManagement;
        $this->stockConfiguration = $stockConfiguration;
        $this->stockRegistry = $stockRegistry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if($order->getState() == "new")
        {
            $canSubstract = $this->stockConfiguration->canSubtractQty(null);
            if(!$canSubstract){
                foreach($order->getAllVisibleItems() as $item ) {
                    $productId = $item->getProductId();
                    $productSku = $item->getSku();
                    $QtyOrdered = $item->getQtyOrdered();
                    $stockItem = $this->stockRegistry->getStockItemBySku($productSku);
                    $stockQty = $stockItem->getQty() - $QtyOrdered;
                    $stockItem->setQty($stockQty);
                    $stockItem->setIsInStock((bool)$stockQty);
                    $this->stockRegistry->updateStockItemBySku($productSku, $stockItem);
                }
            }
        }
    }
}
