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
 * @category  Mage
 * @package   MercadoPago
 * @author    Marcio dos Santos <marcio.santos@mercadolivre.com>
 * @copyright Copyright (c) 2010 MercadoLivre.com [ http:/www.mercadolivre.com.br] - Marcio dos Santos[ marcio.santos@mercadolivre.com ]
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
    //changing the payment to different from cc payment type and MercadoPago payment type
    const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
    const PAYMENT_TYPE_SALE = 'SALE';
    protected $_code  = 'MercadoPago_standard';
    protected $_formBlockType = 'MercadoPago/standard_form';
    
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;

    /**
     * Get MercadoPago session namespace
     *
     * @return MercadoPago_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('MercadoPago/session');
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
        $block = $this->getLayout()->createBlock('MercadoPago/standard_form', $name)
            ->setMethod('MercadoPago_standard')
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
        return Mage::getUrl('MercadoPago/standard/redirect', array('_secure' => true));
    }

    public function getStandardCheckoutFormFields()
    {
        // Montando os dados para o formulário
        $TipoMoneda='REA';
        $sArr = array(
            //'name'              => $productos,
            'currency'          => $TipoMoneda,
            'price'             => $precio;
            'url_cancel'        => tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
            'item_id'           => MODULE_PAYMENT_MERCADOPAGO_ID;
            //'acc_id'          => MODULE_PAYMENT_MERCADOPAGO_ID;
            'acc_id'            => '4961541';
            'shipping_cost'     => '';
            'ship_cost_mode'    => '';
            'op_retira'         => '';
            //'url_process'     => '';
            'url_process'       => tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
            'url_succesfull'    => tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
            //'enc'             => MODULE_PAYMENT_MERCADOPAGO_CODE;
            'enc'               => 'kKXXr%2F8DQAQ%2FO%2FUiGJYhh7kHIsI%3D';
            'cart_cep'          => $order->delivery['postcode'];
            'cart_street'       => $order->delivery['street_address'];
            'cart_number'       => '';
            'cart_complement'   => '';
            'cart_phone'        => $order->customer['telephone'];
            'cart_district'     => $order->delivery['suburb'];
            'cart_city'         => $order->delivery['city'];
            'cart_state'        =>$order->delivery['state'];
            'cart_name'         => $order->delivery['firstname'];
            'cart_surname'      => $order->delivery['lastname'];
            'cart_email'        => $order->customer['email_address'];
            'cart_doc_nbr'      => '';

        if ($this->getDebug() && $sReq)
        {
            $sReq = substr($sReq, 1);
            $debug = Mage::getModel('MercadoPago/api_debug')
                ->setApiEndpoint($this->getMercadoPagoUrl())
                ->setRequestBody($sReq)
                ->save();
        }
        return $sArr;
    
    }
    //  define a url do MercadoPago
    public function getMercadoPagoUrl()
    {
        $url='https://www.mercadopago.com/mlb/buybutton';
        return $url;
    }

    public function getDebug()
    {
        return Mage::getStoreConfig('MercadoPago/wps/debug_flag');
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
            $debug = Mage::getModel('MercadoPago/api_debug')
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
                 * need to have logic when there is no order with the order id from MercadoPago
                 */
            } else {
                if ($this->getIpnFormData('mc_gross')!=$order->getGrandTotal()) {
                    //when grand total does not equal, need to have some logic to take care
                    $order->addStatusToHistory(
                            $order->getStatus(),//continue setting current order status
                            Mage::helper('MercadoPago')->__('Order total amount does not match MercadoPago gross total amount')
                            );
                } else {
                    /*
                    //quote id
                    $quote_id = $order->getQuoteId();
                    //the customer close the browser or going back after submitting payment
                    //so the quote is still in session and need to clear the session
                    //and send email
                    if ($this->getQuote() && $this->getQuote()->getId()==$quote_id) {
                    $this->getCheckout()->clear();
                    $order->sendNewOrderEmail();
                    }
                     */
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
                                    Mage::helper('MercadoPago')->__('Error in creating an invoice')
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
                                    Mage::helper('MercadoPago')->__('Invoice '.$invoice->getIncrementId().' was created')
                                    );
                        }
                    } else {
                        $order->addStatusToHistory(
                                $order->getStatus(),
                                Mage::helper('MercadoPago')->__('Received IPN verification'));
                    }
                }//else amount the same and there is order obj
                //there are status added to order
                $order->save();
            }
        }else{
            /*
               Canceled_Reversal
               Completed
               Denied
               Expired
               Failed
               Pending
               Processed
               Refunded
               Reversed
               Voided
             */
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
                 * need to have logic when there is no order with the order id from MercadoPago
                 */
            } else {
                $order->addStatusToHistory(
                        $order->getStatus(),//continue setting current order status
                        Mage::helper('MercadoPago')->__('MercadoPago IPN Invalid.'.$comment)
                        );
                $order->save();
            }
        }
    }
}