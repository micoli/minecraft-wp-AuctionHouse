<script>
	wp_ajax_url='{php} print get_site_url();{/php}/wp-admin/admin-ajax.php';
</script>
{literal}
<style type="text/css"><!--
#auction_house td {
	white-space:nowrap;
}
--></style>
{/literal}
<h2 class="currentUser"></h2>
Balance : <span class="currentBalance"></span><br/>
Inventory : <span class="inventory">{strip}
{assign var=sepa value=""}
{foreach from=$data.inventory item=stack}
	{$sepa}{$stack.img}({$stack.amount})
	{assign var=sepa value=",&nbsp;"}
{/foreach}
{/strip}</span>
<hr>
<table id="auction_house" cellspacing="0" cellpadding="0" width="100%"  class="display">
	<thead>
		<tr>
			<th>Item</th>
			<th>Buyer</th>
			<th>Seller</th>
			
			<th>Price</th>
			<th>Quantity</th>
			
			<th>Buy</th>
			<th>Bid</th>
			<th>Expiration</th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
