<?php
/**
 * Magento MercadoPago Payment Modulo
 *
 * @category   Mage
 * @package    Mage_Mercadopago
 * @copyright  Copyright (c) 2010 MercadoPago [https://www.mercadopago.com/mp-brasil/]  - Fulvio Cunha [fulvio.cunha@mercadolivre.com]
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 *
 * MercadoPago Payment Action Dropdown source
 *
 */
class Mage_Mercadopago_Model_Source_StandardAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Mercadopago_Model_Standard::PAYMENT_TYPE_AUTH, 'label' => Mage::helper('Mercadopago')->__('Authorization')),
            array('value' => Mage_Mercadopago_Model_Standard::PAYMENT_TYPE_SALE, 'label' => Mage::helper('Mercadopago')->__('Sale')),
        );
    }
}