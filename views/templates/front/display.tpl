<h1 class="page-heading bottom-indent">Mes ventes</h1>

<p class="info-title">Vous trouverez ici les ventes des produits portant votre marque depuis la création de votre compte</p>

<div class="block-center">
	<table class="table table-bordered footab">
		<thead>
			<tr>
				<th>Nom du produit</th>
				<th>Quantité</th>
				<th>Réduction</th>
				<th>Date de vente</th>
				<th>Gain HT</th>
				<th>TVA</th>
				<th>Gain TTC</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$bd_artist_sales key=index item=bd_artist_sale}
				<tr>
					<td>
						{$bd_artist_sale.product_name}
					</td>
					<td>
						{$bd_artist_sale.product_quantity}
					</td>
					<td>
						{if $bd_artist_total_reductions.$index > 0 }
							{$bd_artist_total_reductions.$index} % 
						{else}
							/
						{/if}
					</td>						
					<td>
						{dateFormat date=$bd_artist_sale.invoice_date full=0}
					</td>
					<td>{$bd_artist_gains_ht.$index} &#128;
					</td>
					<td>{$bd_artist_tax.$index} &#128;</td>
					<td>{$bd_artist_gains_ttc.$index} &#128;</td>
				</tr>
			{/foreach}
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4">Total</td>
				<td>{$bd_artist_total_ht} &#128;</td>
				<td>{$bd_artist_total_tax} &#128;</td>
				<td>{$bd_artist_total_ttc} &#128;</td>
			</tr>
		</tfoot>	
	</table>
</div>
