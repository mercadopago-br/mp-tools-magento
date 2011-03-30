<?php

class MercadoPago_Model_Source_Aceita
{
	public function toOptionArray ()
	{
       return array(
          'PAC'   => 'Apenas PAC (encomenda comum)',
          'SEDEX' => 'Apenas SEDEX',
          'AMBOS' => 'Ambos (SEDEX e PAC)',
       );
	}

}
