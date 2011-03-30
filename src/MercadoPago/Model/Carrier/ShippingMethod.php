<?php
require_once(dirname(__FILE__).'/frete.php');

class MercadoPago_Model_Carrier_ShippingMethod
    extends Mage_Shipping_Model_Carrier_Abstract
{
    protected $_code = 'MercadoPago';

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->isActive()) {
            return false;
        }

        $valids = $this->getConfigData('aceita');
        if ($valids=='AMBOS') {
            $valids = array('PAC', 'SEDEX');
        } else {
            $valids = array($valids);
        }

        $result = Mage::getModel('shipping/rate_result');
        $method = Mage::getModel('shipping/rate_result_method');
        
        $peso    = $request->getPackageWeight();
        $destino = $request->getDestPostcode();
        $valor   = $request->getOrderSubtotal();

        $frete = $this->pegaFrete($peso, $destino, $valor); // Peso, destino, valor

        if ($frete === array('Sedex' => '', 'PAC' => '',)) {
            $valor_fixo = $this->getConfigData();
            $frete = array(
                'Sedex' => $valor_fixo,
                'PAC' => $valor_fixo,
            );
        }

        // Setando valores para SEDEX
        if (in_array('SEDEX', $valids)) {
            $method->setCarrier($this->_code);
            $method->setCarrierTitle('MercadoPago');

            $method->setMethod($this->_code.':SEDEX');
            $method->setMethodTitle('SEDEX');
            //$method->setPrice($frete['Sedex']);
            $method->setPrice();

            $result->append($method);
        }
        // Setando valores para PAC
        if (in_array('PAC', $valids)) {
            $method = Mage::getModel('shipping/rate_result_method');

            $method->setCarrier($this->_code);
            $method->setCarrierTitle('MercadoPago');

            $method->setMethod($this->_code.':PAC');
            $method->setMethodTitle('PAC');
            $method->setPrice($frete['PAC']);

            $result->append($method);
        }

        return $result;
    }

    public function isZipCodeRequired()
    {
        return true;
    }

    public function pegaFrete($peso, $destino, $valor = '0')
    {
        $origem = $this->getConfigData('origem');
        $origem = preg_replace('/\D/', '', $origem);
        $origem = substr($origem, 0, 5).'-'.substr($origem, 5);
        $peso   = (int) $peso;
        $frete  = new MpgFrete;
        return $frete->gerar($origem, $peso, $valor, $destino);
    }
}
