<?php
namespace Orderquantity\Inventorymanage\Observer;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Catalog inventory module observer
 */
class RefundOrderInventoryObserver implements ObserverInterface
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
     * @var \Magento\SalesInventory\Model\Order\ReturnProcessor
     */
    private $returnProcessor;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @param StockManagementInterface $stockManagement
     * @param ReturnProcessor $returnProcessor
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        StockManagementInterface $stockManagement,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\SalesInventory\Model\Order\ReturnProcessor $returnProcessor,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->stockManagement = $stockManagement;
        $this->stockRegistry = $stockRegistry;
        $this->returnProcessor = $returnProcessor;
        $this->stockConfiguration = $stockConfiguration;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Cancel order item
     *
     * @param   EventObserver $observer
     * @return  void
     */
    public function execute(EventObserver $observer)
    {
        /* @var $creditmemo \Magento\Sales\Model\Order\Creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $canSubstract = $this->stockConfiguration->canSubtractQty(null);
        if(!$canSubstract){
            foreach ($creditmemo->getItems() as $item) {
                if ($item->getQty() && $item->getSku()) {
                    $orderItem = $item->getOrderItem();
                    $stockItem = $this->stockRegistry->getStockItemBySku($item->getSku());
                    $processedQty = $orderItem->getQtyInvoiced() + $orderItem->getQtyRefunded() + $stockItem->getQty();
                    $stockItem->setQty($processedQty);
                    $stockItem->setIsInStock((bool)$processedQty);
                    $this->stockRegistry->updateStockItemBySku($item->getSku(), $stockItem);
                }
            }
        }
    }
}
