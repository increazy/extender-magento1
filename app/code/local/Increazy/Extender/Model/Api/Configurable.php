<?php
class Increazy_Extender_Model_Api_Configurable extends Mage_Api_Model_Resource_Abstract
{
    public function associatedProducts($productId)
    {
        // Carregar o produto pelo ID ou SKU
        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId() || $product->getTypeId() != 'configurable') {
            $this->_fault('product_not_configurable', 'The specified product is not a configurable product.');
        }

        // Obter os produtos simples associados
        $configurable = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
        $associatedProducts = $configurable->getUsedProductCollection($product)
            ->addAttributeToSelect(['sku', 'name', 'price'])
            ->addFilterByRequiredOptions();

        $result = [];
        foreach ($associatedProducts as $simpleProduct) {
            $result[] = [
                'product_id' => $simpleProduct->getId(),
                'sku' => $simpleProduct->getSku(),
                'name' => $simpleProduct->getName(),
                'price' => $simpleProduct->getPrice()
            ];
        }

        return $result;
    }
}