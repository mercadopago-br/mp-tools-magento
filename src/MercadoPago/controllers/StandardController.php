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
 * MercadoPago Standard Checkout Controller
 */
class MercadoPago_StandardController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;

    /**
     *  Get order
     *
     *  @param    none
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }

    /**
     * Get singleton with mercadopago strandard order transaction information
     *
     * @return MercadoPago_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('mercadopago/standard');
    }

    /**
     * When a customer chooses Paypal on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setPaypalStandardQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('mercadopago/standard_redirect')->toHtml());
        $session->unsQuoteId();
    }

    /**
     * Retorno dos dados feito pelo MercadoPago
     */
    public function obrigadoAction()
    {
        $standard = $this->getStandard();
        # É um $_GET, trate normalmente
        if (!$this->getRequest()->isPost()) {
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getPaypalStandardQuoteId(true));
            /**
             * set the quote as inactive after back from mercadopago
             */
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            /**
             * send confirmation email to customer
             */
            $order = Mage::getModel('sales/order');
            $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
            if($order->getId()){
                $order->sendNewOrderEmail();
            }

            $url = $standard->getConfigData('retorno');
            $this->_redirect($url);
        } else {
            // Vamos ao retorno automático
            if (!defined('RETORNOMERCADOPAGO_NOT_AUTORUN')) {
                define('RETORNOMERCADOPAGO_NOT_AUTORUN', true);
                define('MERCADOPAGO_AMBIENTE_DE_TESTE', true);
            }
            // Incluindo a biblioteca escrita pela Visie
            include_once(dirname(__FILE__).'/retorno.php');
            // Brincanco com a biblioteca
            RetornoMercadoPago::verifica($_POST, false, array($this, 'retornoMercadoPago'));
        }
    }
    
    public function retornoMercadoPago($referencia, $status, $valorFinal, $produtos, $post)
    {
        $salesOrder = Mage::getSingleton('sales/order');
        $order = $salesOrder->loadByIncrementId($referencia);

        $valoresCoincidentes = (bool) ((double) $order->getBase_grand_total() == $valorFinal);
        if ($order->getId() AND $valoresCoincidentes) { // Claro! Conseguiu pegar o produto
            // Verificando o Status passado pelo MercadoPago
            if (in_array(strtolower($status), array('completo', 'aprovado'))) {
                if (!$order->canInvoice()) {
                    //when order cannot create invoice, need to have some logic to take care
                    $order->addStatusToHistory(
                        $order->getStatus(), // keep order status/state
                        'Error in creating an invoice',
                        $notified = false
                    );
                } else {
                    $order->getPayment()->setTransactionId($post->TransacaoID);
                    $invoice = $order->prepareInvoice();
                    $invoice->register()->pay();
                    Mage::getModel('core/resource_transaction')
                       ->addObject($invoice)
                       ->addObject($invoice->getOrder())
                       ->save();
                    $order->setState(
                       Mage_Sales_Model_Order::STATE_COMPLETE, true,
                       sprintf('Invoice #%s created. Pago com %s.', $invoice->getIncrementId(), $post->TipoPagamento),
                       $notified = true
                    );
                }
            } else {
                // Não está completa, vamos processar...
                $comment = $status;
                if ( strtolower(trim($status))=='cancelado' ) {
                    $changeTo = Mage_Sales_Model_Order::STATE_CANCELED;
                } else {
                    // Esquecer o Cancelado e o Aprovado/Concluído
                    $changeTo = Mage_Sales_Model_Order::STATE_HOLDED;
                    $comment .= ' - ' . $post->TipoPagamento;
                }

                $order->addStatusToHistory(
                    $changeTo,
                    $comment,
                    $notified = false
                );
            }
            $order->save();
            // Enviar o e-mail assim que receber a confirmação
            if (in_array(strtolower($status), array('completo', 'aprovado'))) {
                $order->sendNewOrderEmail();
            }
        }
        
    }
}