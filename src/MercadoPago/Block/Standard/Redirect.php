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
            
        foreach ($standard->getStandardCheckoutFormFields() as $field=>$value)
        {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }

		$html  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		$html .= '<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="pt-BR">';
		$html .= '<head>';
		$html .= '<meta http-equiv="Content-Language" content="pt-br" />';
		$html .= '<meta name="language" content="pt-br" />';
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /></head>';
		$html .= '<body>';
        $html .= $this->__('Você será redirecionado para o MercadoPago em alguns instantes.');
        $html .= $form->toHtml();
		$html .= '<script type="text/javascript">document.getElementById("mercadopago_standard_checkout").submit();</script>';
        $html .= '</body></html>';

        return utf8_decode($html);
    }
}