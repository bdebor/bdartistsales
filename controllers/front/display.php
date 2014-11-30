<?php

class bdartistsalesdisplayModuleFrontController extends ModuleFrontController
{

	public function initContent()
	{
		parent::initContent();
		$this->setTemplate('display.tpl');
		$artist_email = $this->context->cookie->email;
		$artist_sales = BdArtistSales::getOrdersWithArtistEmail($artist_email);
		$id_category = $artist_sales[0]['id_category'];
		$artist_tax = BdArtistSales::get('TAX_'.$id_category) / 100; // pour le moment cela correspond à la TVA, ce point reste à éclaircir !!!

		$gains_ttc = [];
		$gains_ht = [];
		$artist_tax2 = [];
		$total_ttc = 0;
		$total_ht = 0;
		$total_artist_tax = 0;
		$total_reductions = [];

		$artist_sales = array_filter(
	        $artist_sales,
	        function($artist_sales)
	        {
	            return $artist_sales['invoice_date'] != '0000-00-00 00:00:00';
	        }
	    ); // On ne retient que les ventes dont le paiement a été accepté.
	    
		foreach ($artist_sales as $index => $artist_sale) 
		{			
			$reduction_amount = $artist_sale['reduction_amount']; // réduction en unités monétaires sur le produit, elle s'applique sur le prix_ttc
			$reduction_percent = $artist_sale['reduction_percent'] / 100; // réduction en pourcentage sur le produit (/100)
			$cart_reduction = $artist_sale['total_discounts_tax_incl'] / $artist_sale['total_products_wt']; // réduction en pourcentage sur le panier (/100), total_discounts_tax_incl = réduction sur le prix_ttc, total_products_wt = prix_ttc avant réduction

			$percentage = BdArtistSales::get('PERCENT_'.$id_category.'_'.$artist_sale['product_id']) / 100; // pourcentage de l'artiste sur le produit (/100)

			$product_price_ht = $artist_sale['product_price'];
		
			$gains_ttc[$index] = 
			(
				(
					$product_price_ht *  $artist_sale['product_quantity'] * (1 + $artist_tax) 
					* (1 - $reduction_percent) 
					- $reduction_amount *  $artist_sale['product_quantity']
				) * (1 - $cart_reduction)
			) * $percentage; // gains de l'artiste sur chaque produit, un produit peut apparaitre plusieurs fois dans une commande, calcul des gains_ttc en premier car $reduction_amount s'applique sur le prix_ttc, $gains_ttc = (prix_ttc - reductions) * $percentage

			$gains_ht[$index] = $gains_ttc[$index] / (1 + $artist_tax);
			$artist_tax2[$index] = $gains_ttc[$index] - $gains_ht[$index];

			$gains_ttc[$index] = number_format($gains_ttc[$index], 2); // valeur affichée arrondie à 2 chiffres après la virgule
			$total_ttc += $gains_ttc[$index]; // le tableau des ventes de l'artiste affiche le total des gains
			$gains_ht[$index] = number_format($gains_ht[$index], 2);
			$total_ht += $gains_ht[$index];
			$artist_tax2[$index] = number_format($artist_tax2[$index], 2);
			$total_artist_tax += $artist_tax2[$index];

			$reduction_amount_in_percent = $artist_sale['reduction_amount'] / ($product_price_ht * (1 + $artist_tax)); // pour le calcul du % de réduction total, $total_reductions
			$total_reductions[$index] = number_format(100*(1-((1-$reduction_amount_in_percent) * (1-$reduction_percent) * (1-$cart_reduction))), 2);

		}

		$this->context->smarty->assign(
			array(
				'bd_artist_sales' => $artist_sales,
				'bd_artist_gains_ttc' => $gains_ttc,
				'bd_artist_gains_ht' => $gains_ht,
				'bd_artist_tax' => $artist_tax2,
				'bd_artist_total_ttc' => number_format($total_ttc, 2),
				'bd_artist_total_ht' => number_format($total_ht, 2),
				'bd_artist_total_tax' => number_format($total_artist_tax, 2),
				'bd_artist_total_reductions' => $total_reductions
			)
		);
	} 
}





