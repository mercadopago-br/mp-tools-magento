<?php

class MercadoPago_StandardController extends Mage_Core_Controller_Front_Action
{

    /**
     * Order instance
     */
    protected $_order;

    /**
     *  Get order
     *
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }

    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

   
    public function getStandard()
    {
        return Mage::getSingleton('mercadopago/standard');
    }

   
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setMercadopagoStandardQuoteId($session->getQuoteId());

        $this->getResponse()->setHeader("Content-Type", "text/html; charset=ISO-8859-1", true);

        $this->getResponse()->setBody($this->getLayout()->createBlock('mercadopago/standard_redirect')->toHtml());
        $session->unsQuoteId();
    }

    /**
     * When a customer cancel payment from MercadoPago.
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getMercadoPagoStandardQuoteId(true));

        // cancel order
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        $this->_redirect('checkout/cart');
    }

   
    public function  successAction()
    {
		
    	if (!$this->getRequest()->isPost())
		{
			$this->_redirect('');
			return;
		}

        $dados_post = $this->getRequest()->getPost();
		$dados_post_status = utf8_encode($dados_post['status']);

	

		if($this->getStandard()->getDebug())
		{


		}

		if ( trim($dados_post_status) == "Transação em Andamento" )
		{
			if (Mage::getSingleton('checkout/session')->getLastOrderId())
			{
				$order = Mage::getModel('sales/order');
				$order->setEmailSent(true);
				$order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
				$order->sendNewOrderEmail();
				$order->save();
			}
			
			$session = Mage::getSingleton('checkout/session');
	        $session->setQuoteId($session->getMercadoPagoStandardQuoteId(true));
	        /**
	         * set the quote as inactive after back from PD
	         */
	        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
	        //Mage::getSingleton('checkout/session')->unsQuoteId();

	        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
        }
        else
        {

			$token = $this->getStandard()->getConfigData('token');
        	
	        $post =	"transacao={$dados_post['id_transacao']}" .
					"&status={$dados_post['status']}" .
					"&valor_original={$dados_post['valor_original']}" .
					"&valor_loja={$dados_post['valor_loja']}" .
					"&token={$token}";

			$enderecoPost = "https://www.mercadopago.com/mlb/buybutton";
			
			ob_start();
			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_URL, $enderecoPost);
			curl_setopt ($ch, CURLOPT_POST, 1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  
			curl_exec ($ch);
			$resposta = ob_get_contents();
			ob_end_clean();

        	// comments for errors
			$comment = "";
			if (isset($dados_post['cod_status']))
				$comment .= " - " . $dados_post['cod_status'];

			if (isset($dados_post_status))
				$comment .= " - " . $dados_post_status;

			if(trim($resposta) == "VERIFICADO")
			{
	        	$order = Mage::getModel('sales/order');
	        	$order->loadByIncrementId($dados_post['id_pedido']);
	        	
	        	if (!$order->getId())
	        	{
	        		// no order ID, but nothing to do about it.
	        	}
	        	else
	        	{
	        		if ($dados_post['valor_original'] != $order->getGrandTotal())
	        		{
	                    //when grand total does not equal, need to have some logic to take care
	                    
	        			$frase = 'Total pago ao MercadoPago é diferente do valor original.';
	        			
	                    $order->addStatusToHistory(
	                        $order->getStatus(),//continue setting current order status
	                        Mage::helper('mercadopago')->__($frase),
	                        true
	                    );
	                    $order->sendOrderUpdateEmail(true, $frase);
	                }
	                else
	                {
				        if ( $dados_post_status == "Transação Concluída" )
				        {
				        	// if it has a virtualproduct in the order do we have to act differently?
							$items = $order->getAllItems();
							
							$thereIsVirtual = false;
							foreach ($items as $itemId => $item)
							{
								if ($item["is_virtual"] == "1" or $item["is_downloadable"] == "1")
									$thereIsVirtual = true;
							}
							
							// what to do - from admin
							$toInvoice = $this->getStandard()->getConfigData('acaopadraovirtual') == "1" ? true : false;
							
							if ($thereIsVirtual && !$toInvoice)
							{
									$frase = 'Pagamento (fatura) confirmado automaticamente pelo MercadoPago.';
									$order->addStatusToHistory(
										$order->getStatus(),//continue setting current order status
										Mage::helper('mercadopago')->__($frase),
										true
									);
									$order->sendOrderUpdateEmail(true, $frase);
							}
							else
							{
								if (!$order->canInvoice())
								{
									//when order cannot create invoice, need to have some logic to take care
									$order->addStatusToHistory(
										$order->getStatus(),//continue setting current order status
										Mage::helper('mercadopago')->__('Erro ao criar pagamento (fatura).')
									);
									
								}
								else 
								{
									//need to save transaction id
									$order->getPayment()->setTransactionId($dados_post['id_transacao']);
									
									//need to convert from order into invoice
									$invoice = $order->prepareInvoice();
									
									if ($this->getStandard()->canCapture())
									{
										$invoice->register()->capture();
									}
									
									Mage::getModel('core/resource_transaction')
										->addObject($invoice)
										->addObject($invoice->getOrder())
										->save();
									
									$frase = 'Pagamento (fatura) '.$invoice->getIncrementId().' foi criado. MercadoPago confirmou automaticamente o pagamento do pedido.';
										
									if ( $thereIsVirtual ) {
										$order->addStatusToHistory(
											$order->getStatus(),
											Mage::helper('mercadopago')->__($frase),
											true
										);
									} else {
										$order->addStatusToHistory(
											'processing', //update order status to processing after creating an invoice
											Mage::helper('mercadopago')->__($frase),
											true
										);
									}
									$invoice->sendEmail(true, $frase);
								}
							}
						}
						elseif ( trim($dados_post_status) == "Transação Cancelada" )
						{
							
							
							$frase = 'MercadoPago cancelou automaticamente o pedido (transação foi cancelada, pagamento foi negado, pagamento foi estornado ou ocorreu um chargeback).';
							
							$order->addStatusToHistory(
								Mage_Sales_Model_Order::STATE_CANCELED,
								Mage::helper('mercadopago')->__($frase),
								true
							);
							$order->sendOrderUpdateEmail(true, $frase);
							
							$order->cancel();
						}
						else
						{
							// STATUS ERROR

							$order->addStatusToHistory(
								$order->getStatus(),
								Mage::helper('mercadopago')->__('MercadoPago enviou automaticamente um status inválido: %s', $comment));
						}
	                }
	                
	                $order->save();
	        	}
			}
			else
			{
				// VERIFICATION ERROR
				
				$order = Mage::getModel('sales/order');
	        	$order->loadByIncrementId($dados_post['id_pedido']);
	        	
	        	if (!$order->getId())
	        	{
	        		// no order ID, but nothing to do about it.
	        	}
	        	else
		        {

					$order->addStatusToHistory(
						$order->getStatus(),
						Mage::helper('mercadopago')->__('MercadoPago enviou automaticamente uma verificação inválida: %s', $comment));
					$order->save();
		        }
			}
        }
    }
}
