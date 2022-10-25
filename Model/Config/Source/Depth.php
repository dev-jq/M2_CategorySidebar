<?php

namespace Elgentos\CategorySidebar\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Depth implements ArrayInterface {


    public function toOptionArray(): array
    {
        return [
            [
                'value' => 1,
                'label' => __('1'),
            ],
            [
                'value' => 2,
                'label' => __('2'),
            ],
            [
                'value' => 3,
                'label' => __('3'),
            ],
            [
                'value' => 4,
                'label' => __('4'),
            ],
            [
                'value' => 5,
                'label' => __('5'),
            ],
        ];
    }
}
