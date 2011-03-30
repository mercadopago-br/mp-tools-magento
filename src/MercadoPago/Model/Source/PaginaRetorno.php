<?php

#doc
#	classname:	MercadoPago_Model_Source_PaginaRetorno
#	scope:		PUBLIC
#
#/doc

class MercadoPago_Model_Source_PaginaRetorno
{
	public function toOptionArray ()
	{
		$collection = Mage::getModel('cms/page')->getCollection();
		$pages = array();
		foreach ($collection as $page) {
			$pages[$page->getIdentifier()] = $page->getTitle();
		}
		return $pages;
	}

}
###