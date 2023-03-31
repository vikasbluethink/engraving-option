<?php
namespace Valtech\TechTest\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * class EngravingData
 */
class EngravingData extends Command
{
    protected $orderCollectionFactory;

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orderCollection = $this->orderCollectionFactory->create()
                                ->addFieldToFilter('is_engraving', 1);
//        $orderCollection = $orderCollection->getShippmentCollection();
        print_r($orderCollection->getData());
        //implentring not shipping collection
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("engraving:data");
        $this->setDescription("Get Engraving data");

        parent::configure();
    }
}
