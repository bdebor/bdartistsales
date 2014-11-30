<?php

class bdartistsalesdisplayModuleFrontController extends ModuleFrontController
{

	public function initContent()
	{
		parent::initContent();
		$this->setTemplate('display.tpl');
		$artist_email = $this->context->cookie->email;/*{*/
		$artist_name = BdArtistSales::getArtistNameWithEmail($artist_email);
		$artist_sales = BdArtistSales::getOrdersWithArtistName($artist_name);

		$tax = Configuration::get('BDARTISTSALES_TAX'.$artist_sales[0]['id_feature_value']);
		$percentage = Configuration::get('BDARTISTSALES_PERCENTAGE'.$artist_sales[0]['id_feature_value']);
		$gains = [];

		$artist_sales = array_filter(
	        $artist_sales,
	        function($artist_sales)
	        {
	            return $artist_sales['delivery_date'] != '0000-00-00 00:00:00';
	        }
	    );
	    
		foreach ($artist_sales as $index => $artist_sale) 
		{
			$gains[$index] = number_format($artist_sale['product_price'] * $percentage/100, 2);

		}

		$total = 0;
		foreach ($gains as $gain)
		{
			$total += $gain;
		}

		$this->context->smarty->assign(
				array(
					'bd_artist_sales' => $artist_sales,
					'bd_artist_gains' => $gains,
					'bd_artist_total' => $total
				)
			);
		/*}*/
	} 
}





