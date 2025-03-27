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

        // Obter os super atributos do produto configurável
        $configurable = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
        $superAttributes = $configurable->getConfigurableAttributesAsArray($product);

        // Preparar os dados dos super atributos
        $superAttributesData = [];
        foreach ($superAttributes as $attribute) {
            $superAttributesData[] = [
                'attribute_id' => $attribute['attribute_id'],
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label'],
                'options' => array_map(function ($option) {
                    return [
                        'id' => $option['value_index'],
                        'label' => $option['label']
                    ];
                }, $attribute['values'])
            ];
        }

        // Obter os produtos simples associados
        $associatedProducts = $configurable->getUsedProductCollection($product)
            ->addAttributeToSelect(['sku', 'name', 'price'])
            ->addFilterByRequiredOptions();

        // Carregar os atributos configuráveis para os produtos simples
        foreach ($superAttributes as $attribute) {
            $associatedProducts->addAttributeToSelect($attribute['attribute_code']);
        }

        // Preparar os dados dos produtos associados
        $associatedProductsData = [];
        foreach ($associatedProducts as $simpleProduct) {
            $productAttributes = [];
            foreach ($superAttributes as $attribute) {
                $attributeCode = $attribute['attribute_code'];
                $attributeValue = $simpleProduct->getData($attributeCode);
                $attributeLabel = $simpleProduct->getAttributeText($attributeCode);

                $productAttributes[] = [
                    'attribute_code' => $attributeCode,
                    'value_id' => $attributeValue,
                    'value_label' => $attributeLabel ?: $attributeValue
                ];
            }

            $associatedProductsData[] = [
                'product_id' => $simpleProduct->getId(),
                'sku' => $simpleProduct->getSku(),
                'name' => $simpleProduct->getName(),
                'price' => $simpleProduct->getPrice(),
                'attributes' => $productAttributes
            ];
        }

        // Retornar os dados
        return [
            'super_attributes' => $superAttributesData,
            'associated_products' => $associatedProductsData
        ];
    }
}
