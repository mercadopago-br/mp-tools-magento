<?php

class MercadoPago_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $standard = Mage::getModel('mercadopago/standard');
        $form = new Varien_Data_Form();
        $form->setAction($standard->getMercadoPagoUrl())
            ->setId('mercadopago_standard_checkout')
            ->setName('mercadopago_standard_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->getStandardCheckoutFormFields() as $field=>$value) {
        //   echo('$field "="  $value');

            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $html = '<html><body>';
        $html.= $this->__('Você será redirecionado para o MercadoPago em alguns instantes.');
        $html.= $form->toHtml();
        $html.= '<script>document.mercadopago_standard_checkout.submit()</script>';
        $html.= '</body></html>';

        return $html;
    }
}
