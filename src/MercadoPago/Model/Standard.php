<?php

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
	protected $_allowCurrencyCode = array('BRL');
	
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
	
	public function canUseInternal()
    {
        return false;
    }
	
	 public function canUseForMultishipping()
    {
        return false;
    }
	
	
	
	
	
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('mercadopago/standard_form', $name)
            ->setMethod('mercadopago_standard')
            ->setPayment($this->getPayment())
            ->setTemplate('mercadopago/standard/form.phtml');
        return $block;
    }
	
	
	public function validate()
    {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if (!in_array($currency_code,$this->_allowCurrencyCode)) {
           // Mage::throwException(Mage::helper('MercadoPago')->__('A moeda selecionada ('.$currency_code.') não é compatível com o mercadopago'));
        }
        return $this;
    }
	
	
	
	
    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
        return $this;
    }
    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {
    }
	
	
	public function canCapture()
    {
        return true;
    }
	
	
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('mercadopago/standard/redirect', array('_secure' => true));
    }
	
	
	function getNumEndereco($endereco) {
    	$numEndereco = '';

    	$posSeparador = $this->getPosSeparador($endereco, false);
    	if ($posSeparador !== false)
		    $numEndereco = trim(substr($endereco, $posSeparador + 1));

      	$posComplemento = $this->getPosSeparador($numEndereco, true);
		if ($posComplemento !== false)
		    $numEndereco = trim(substr($numEndereco, 0, $posComplemento));

		if ($numEndereco == '')
		    $numEndereco = '?';

		return($numEndereco);
	}
	
	
	function getPosSeparador($endereco, $procuraEspaco = false) {
		$posSeparador = strpos($endereco, ',');
		if ($posSeparador === false)
			$posSeparador = strpos($endereco, '-');

		if ($procuraEspaco)
			if ($posSeparador === false)
				$posSeparador = strrpos($endereco, ' ');

		return($posSeparador);
	}
	

   /* function _trataTelefone($tel)
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
    }*/
	
	
	
    public function getStandardCheckoutFormFields()
    {
      $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

        $isOrderVirtual = $order->getIsVirtual();
        $a = $isOrderVirtual ? $order->getBillingAddress() : $order->getShippingAddress();

        $currency_code = $order->getBaseCurrencyCode();

     // list($items, $totals, $discountAmount, $shippingAmount) = Mage::helper('mercadopago')->prepareLineItems($order, false, false);

		$postal_code = substr(eregi_replace ("[^0-9]", "", $a->getPostcode()).'00000000',0,8);
   
        $sArr = array(
                       'acc_id' => $this->getConfigData('acc_id'),//'5895960',
                       'cart_cep' => $postal_code,
                       'cart_city' => $a->getCity(),
                       'cart_complement' => '',
                       'cart_district' => $a->getCountry(),
                       'cart_doc_nbr' => '',
                       'cart_email' => $a->getEmail(),
                       'cart_name' => $a->getFirstname(),
                       'cart_number' => '',
                       'cart_phone' => substr(str_replace(" ","",str_replace("(","",str_replace(")","",str_replace("-","",$a->getTelephone())))),0,2) . substr(str_replace(" ","",str_replace("-","",$a->getTelephone())),-8),
                       'cart_state' => $a->getState(),
                       'cart_street' => $a->getStreet(1),
                       'cart_surname' => $a->getLastname(),
                       'currency' => 'REA',
                       'enc' => $this->getConfigData('enc'),//'W1gd8zSlVA96%2B0qPwBEc%2FRLQWFo%3D',//,MODULE_PAYMENT_MERCADOPAGO_CODE
                       'extra_part' =>$this->getCheckout()->getLastRealOrderId(),
                       //'item_id' => $itemid,
                       'name' => 'Pedido Nro'.$this->getCheckout()->getLastRealOrderId(),
                       'op_retira' => '',
                       'ship_cost_mode' => '',
					   
					   'price'  => $order->getBaseGrandTotal(),
						'seller_op_id'=>$this->getCheckout()->getLastRealOrderId(),
						
                       'shipping_cost' => '',
                       'url_cancel' => 'FILENAME_CHECKOUT_PAYMENT', '','SSL',
                       'url_process' => $this->getConfigData('url_process'), //', '', 'SSL',
                       'url_succesfull' =>$this->getConfigData('url_succesfull'), //'http://www.globo.com','FILENAME_CHECKOUT_SUCCESS', '', 'SSL',// $this->getConfigData('FILENAME_CHECKOUT_PROCESS','','SSL'),
                       );



        $items = $this->getQuote()->getAllVisibleItems();
		
    if ($items) {
            $i = 1;
            foreach($items as $item) {
            	//if ($item->getParentItem()) continue;
            	
					$sArr = array_merge($sArr, array(
						$zb[]=$item->getName(),
						'name' => implode ("+",$zb),
	                    'produto_descricao_'.$i => $item->getName(),
	                    'produto_codigo_'.$i    => $item->getId(),
	                    'produto_qtde_'.$i   	=> $item->getQty(),
	                    'produto_valor_'.$i  	=> $valorProduto,
				    ));
            	


            	$i++;
				
				
            }
        }
		
		$totalArr = $order->getBaseGrandTotal();

		$shipping = sprintf('%.2f',$shippingAmount);
		
    	$sArr = array_merge($sArr, array('frete' => $shipping));

		if ($this->getConfigData('retorno') != '') {
	    	$sArr = array_merge($sArr, array('url_retorno' => $this->getConfigData('retorno')));
	    	$sArr = array_merge($sArr, array('redirect' => 'true'));
		}

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
            $debug = Mage::getModel('mercadopago/api_debug')
                    ->setApiEndpoint($this->getMercadoPagoUrl())
                    ->setRequestBody($sReq)
                    ->save();
        }

        return $rArr;
    }

    //  define a url do pagamentodigital
    public function getMercadoPagoUrl()
    {
         $url='https://www.mercadopago.com/mlb/buybutton';
         return $url;
    }

    // @todo Indexa: esta funcao eh inutil, por enquanto
	public function getDebug()
    {
        return Mage::getStoreConfig('mercadopago/wps/debug_flag');
    }
}