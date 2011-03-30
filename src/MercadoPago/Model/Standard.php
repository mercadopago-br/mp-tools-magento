<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    MercadoPago
 * @copyright  Copyright (c) 2010 MercadoPago [https://www.mercadopago.com/mp-brasil/]  - Fulvio Cunha [fulvio.cunha@mercadolivre.com]
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 *
 * MercadoPago Checkout Module
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MercadoPago_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    //changing the payment to different from cc payment type and mercadopago payment type
    const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
    const PAYMENT_TYPE_SALE = 'SALE';
    protected $_code  = 'mercadopago_standard';
    protected $_formBlockType = 'mercadopago/standard_form';
    
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;

    /**
     * Get mercadopago session namespace
     *
     * @return MercadoPago_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('mercadopago/session');
    }
    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('mercadopago/standard_form', $name)
            ->setMethod('mercadopago_standard')
            ->setPayment($this->getPayment())
            ->setTemplate('MercadoPago/standard/form.phtml');
        return $block;
    }
    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
        return $this;
    }
    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {
    }
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('mercadopago/standard/redirect', array('_secure' => true));
    }

    function _trataTelefone($tel)
    {
        $numeros = preg_replace('/\D/','',$tel);
        $tel     = substr($numeros,sizeof($numeros)-9);
        $ddd     = substr($numeros,sizeof($numeros)-11,2);
        return array($ddd, $tel);
    }
    private function _endereco($endereco)
    {
        require_once(dirname(__FILE__).'/trata_dados.php');
        return TrataDados::trataEndereco($endereco);
    }
    public function getStandardCheckoutFormFields()
    {
       $a = $this->getQuote()->getShippingAddress();
        // Fazendo o telefone
        list($ddd, $telefone) = $this->_trataTelefone($a->getTelephone());
        // Dados de endereço (Endereço)
        list($endereco, $numero, $complemento) = $this->_endereco($a->getStreet(1).' '.$a->getStreet(2));
        // Dados de endereço (CEP)
        $cep = preg_replace('@[^\d]@', '', $a->getPostcode());


        // Montando os dados para o formulário
        $item_atrib = $this->getQuote()->getAllVisibleItems();
   
        $sArr = array(
                       'acc_id' => $this->getConfigData('acc_id'),//'5895960',
                       'cart_cep' => $cep,
                       'cart_city' => $a->getCity(),
                       'cart_complement' => '',
                       'cart_district' => $a->getCountry(),
                       'cart_doc_nbr' => '',
                       'cart_email' => $a->getEmail(),
                       'cart_name' => $a->getFirstname(),
                       'cart_number' => '',
                       'cart_phone' => $telefone,
                       'cart_state' => $a->getState(),
                       'cart_street' => $endereco,
                       'cart_surname' => $a->getLastname(),
                       'currency' => 'REA',
                       'enc' => $this->getConfigData('enc'),//'W1gd8zSlVA96%2B0qPwBEc%2FRLQWFo%3D',//,MODULE_PAYMENT_MERCADOPAGO_CODE
                       'extra_part' =>'',
                       //'item_id' => $itemid,
                       //'name' => $itemdesc,//$item_atrib->getName(),//'Diamante Azul',
                       'op_retira' => '',
                       'ship_cost_mode' => '',
					   
					   'price'  => $a->getGrandTotal(),
						'seller_op_id'=>$this->getCheckout()->getLastRealOrderId(),
						
                       'shipping_cost' => '',
                       'url_cancel' => 'FILENAME_CHECKOUT_PAYMENT', '','SSL',
                       'url_process' => $this->getConfigData('url_process'), //', '', 'SSL',
                       'url_succesfull' =>$this->getConfigData('url_succesfull'), //'http://www.globo.com','FILENAME_CHECKOUT_SUCCESS', '', 'SSL',// $this->getConfigData('FILENAME_CHECKOUT_PROCESS','','SSL'),
                       );



        $items = $this->getQuote()->getAllVisibleItems();
		
        if ($items) {
            $i = 1;
            foreach($items as $item){
			
                $sArr = array_merge($sArr, array(
							//Coloca os itens do pedido em um array e concatena
							$zb[]=$item->getName(), 
                            'name' => implode ("+",$zb),
							'item_id'.$i      => $item->getSku(),
                            'item_quant_'.$i   => $item->getQty()+1,
                            'item_val'.$i   => (($item->getQty())*($item->getBaseCalculationPrice() - $item->getBaseDiscountAmount())),
                            //'price'  => (($this->getQuote()->getShippingAddress()->getBaseShippingAmount())+($item->getQty())*($item->getBaseCalculationPrice() - $item->getBaseDiscountAmount())),
                            						 
                            ))									
							;
							
							/*$count=$item->getName();
							echo $count;*/
							
														
							/*$junta = implode("+", $sArr);
							echo $junta;*/
							
							//print_r($sArr);
							
                if($item->getBaseTaxAmount()>0){
                    $sArr = array_merge($sArr, array(
                                'tax_'.$i      => sprintf('%.2f',$item->getBaseTaxAmount()),
								
                                ));
                }
				
                $i++;
						
            }				
        }

		$transaciton_type = $this->getConfigData('transaction_type');
        $totalArr = $a->getTotals();
        $shipping = sprintf('%.2f', $this->getQuote()->getShippingAddress()->getBaseShippingAmount());
        // passa o valor do frete total em uma única variavel para o mercadopago, utilizado junto com o modulo de correio
        $sArr = array_merge($sArr, array('item_frete_1' => str_replace(".", ",", $shipping) ));
        // die('<pre>'.print_r($sArr, true));
        $sReq = '';
        $rArr = array();
        foreach ($sArr as $k=>$v) {
            /*
               replacing & char with and. otherwise it will break the post
             */
            $value =  str_replace("&","and",$v);
            $rArr[$k] =  $value;
            $sReq .= '&'.$k.'='.$value;
        }
        if ($this->getDebug() && $sReq) {
            $sReq = substr($sReq, 1);
            $debug = Mage::getModel('Mercadopago/api_debug')
                ->setApiEndpoint($this->getMercadopagoUrl())
                ->setRequestBody($sReq)
                ->save();
        }
        return $rArr;
    }
    //  define a url do mercadopago
    public function getMercadoPagoUrl()
    {
         $url='https://www.mercadopago.com/mlb/buybutton/';
         return $url;
    }

    public function getDebug()
    {
        return Mage::getStoreConfig('mercadopago/wps/debug_flag');
    }

    public function ipnPostSubmit()
    {
        $sReq = '';
        foreach($this->getIpnFormData() as $k=>$v) {
            $sReq .= '&'.$k.'='.urlencode(stripslashes($v));
        }
        //append ipn commdn
        $sReq .= "&cmd=_notify-validate";
        $sReq = substr($sReq, 1);
        if ($this->getDebug()) {
            $debug = Mage::getModel('mercadopago/api_debug')
                ->setApiEndpoint($this->getMercadoPagoUrl())
                ->setRequestBody($sReq)
                ->save();
        }
        $http = new Varien_Http_Adapter_Curl();
        $http->write(Zend_Http_Client::POST,$this->getMercadoPagoUrl(), '1.1', array(), $sReq);
        $response = $http->read();
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        if ($this->getDebug()) {
            $debug->setResponseBody($response)->save();
        }
        //when verified need to convert order into invoice
        $id = $this->getIpnFormData('invoice');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($id);
        if ($response=='VERIFIED') {
            if (!$order->getId()) {
                /*
                 * need to have logic when there is no order with the order id from mercadopago
                 */
            } else {
                if ($this->getIpnFormData('mc_gross')!=$order->getGrandTotal()) {
                    //when grand total does not equal, need to have some logic to take care
                    $order->addStatusToHistory(
                            $order->getStatus(),//continue setting current order status
                            Mage::helper('mercadopago')->__('Order total amount does not match mercadopago gross total amount')
                            );
                } else {
                    /*
                       if payer_status=verified ==> transaction in sale mode
                       if transactin in sale mode, we need to create an invoice
                       otherwise transaction in authorization mode
                     */
                    if ($this->getIpnFormData('payment_status')=='Completed') {
                        if (!$order->canInvoice()) {
                            //when order cannot create invoice, need to have some logic to take care
                            $order->addStatusToHistory(
                                    $order->getStatus(),//continue setting current order status
                                    Mage::helper('mercadopago')->__('Error in creating an invoice')
                                    );
                        } else {
                            //need to save transaction id
                            $order->getPayment()->setTransactionId($this->getIpnFormData('txn_id'));
                            //need to convert from order into invoice
                            $invoice = $order->prepareInvoice();
                            $invoice->register()->capture();
                            Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder())
                                ->save();
                            $order->addStatusToHistory(
                                    'processing',//update order status to processing after creating an invoice
                                    Mage::helper('mercadopago')->__('Invoice '.$invoice->getIncrementId().' was created')
                                    );
                        }
                    } else {
                        $order->addStatusToHistory(
                                $order->getStatus(),
                                Mage::helper('mercadopago')->__('Received IPN verification'));
                    }
                }//else amount the same and there is order obj
                //there are status added to order
                $order->save();
            }
        }else{

            $payment_status= $this->getIpnFormData('payment_status');
            $comment = $payment_status;
            if ($payment_status == 'Pending') {
                $comment .= ' - ' . $this->getIpnFormData('pending_reason');
            } elseif ( ($payment_status == 'Reversed') || ($payment_status == 'Refunded') ) {
                $comment .= ' - ' . $this->getIpnFormData('reason_code');
            }
            //response error
            if (!$order->getId()) {
                /*
                 * need to have logic when there is no order with the order id from mercadopago
                 */
            } else {
                $order->addStatusToHistory(
                        $order->getStatus(),//continue setting current order status
                        Mage::helper('mercadopago')->__('MercadoPago IPN Invalid.'.$comment)
                        );
                $order->save();
            }
        }
    }
}
