# ElasticExportGeizhalsDE plugin user guide

<div class="container-toc"></div>

## 1 Registering with Geizhals.de

Geizhals.de is an independent price comparison and information platform that specialises in hardware and consumer electronics.

## 2 Setting up the data format GeizhalsDE-Plugin in plentymarkets

The plugin Elastic Export is required to use this format.

Refer to the [Exporting data formats for price search engines](https://knowledge.plentymarkets.com/en/basics/data-exchange/exporting-data#30) page of the manual for further details about the individual format settings.

The following table lists details for settings, format settings and recommended item filters for the format **GeizhalsDE-Plugin**.
<table>
    <tr>
        <th>
            Settings
        </th>
        <th>
            Explanation
        </th>
    </tr>
    <tr>
        <td class="th" colspan="2">
            Settings
        </td>
    </tr>
    <tr>
        <td>
            Format
        </td>
        <td>
            Choose <b>GeizhalsDE-Plugin</b>.
        </td>        
    </tr>
    <tr>
        <td>
            Provisioning
        </td>
        <td>
            Choose <b>URL</b>.
        </td>        
    </tr>
    <tr>
        <td>
            File name
        </td>
        <td>
            The file name must have the ending <b>.csv</b> or <b>.txt</b> for Geizhals.de to be able to import the file successfully.
        </td>        
    </tr>
    <tr>
        <td class="th" colspan="2">
            Item filter
        </td>
    </tr>
    <tr>
        <td>
            Active
        </td>
        <td>
            Choose <b>active</b>.
        </td>        
    </tr>
    <tr>
        <td>
            Markets
        </td>
        <td>
            Choose one or multiple order referrers. The chosen order referrer has to be active at the variation for the item to be exported.
        </td>        
    </tr>
    <tr>
        <td class="th" colspan="2">
            Format settings
        </td>
    </tr>
    <tr>
        <td>
            Order referrer
        </td>
        <td>
            Choose the order referrer that should be assigned during the order import.
        </td>        
    </tr>
    <tr>
        <td>
            Preview text
        </td>
        <td>
            This option is not relevant for this format.
        </td>        
    </tr>
    <tr>
        <td>
            Image
        </td>
        <td>
            This option is not relevant for this format.
        </td>        
    </tr>
    <tr>
        <td>
            Offer price
        </td>
        <td>
            This option is not relevant for this format.
        </td>        
    </tr>
    <tr>
        <td>
            VAT note
        </td>
        <td>
            This option is not relevant for this format.
        </td>        
    </tr>
</table>

## 3 Overview of available columns

<table>
    <tr>
		<th>
			Column name
		</th>
		<th>
			Explanation
		</th>
	</tr>
	<tr>
        <td>
            Herstellername
        </td>
        <td>
            <b>Required</b><br>
            <b>Content:</b> The <b>name of the manufacturer</b> of the item. The <b>external name</b> within <b>Settings » Items » Manufacturer</b> will be preferred if existing.
        </td>        
    </tr>
    <tr>
        <td>
            Produktcode
        </td>
        <td>
            <b>Content:</b> The <b>Variation-ID</b> of the variation.
        </td>        
    </tr>
    <tr>
        <td>
            Produktbezeichnung
        </td>
        <td>
            <b>Required</b><br>
            <b>Content:</b> According to the format setting <b>item name</b>.
        </td>        
    </tr>
	<tr>
		<td>
			Preis
		</td>
		<td>
		    <b>Required</b><br>
			<b>Content:</b> The <b>sales price</b> of the variation.
		</td>        
	</tr>
	<tr>
		<td>
			Deeplink
		</td>
		<td>
		    <b>Required</b><br>
			<b>Content:</b> The <b>URL path</b> of the item, depending on the chosen <b>client</b> in the format settings.
		</td>        
	</tr>
	<tr>
		<td>
			Versand Vorkasse
		</td>
		<td>
			<b>Content:</b> According to the format setting <b>Shipping costs</b>, including <b>Payment in Advance costs</b>.
		</td>        
	</tr>
	<tr>
		<td>
			Versand Nachnahme
		</td>
		<td>
			<b>Content:</b> According to the format setting <b>Shipping costs</b>, including <b>Cash on Delivery costs</b>.
		</td>        
	</tr>
    <tr>
        <td>
            Verfügbarkeit
        </td>
        <td>
            <b>Required</b><br>
            <b>Content:</b>The <b>name of the item availability</b> under <b>Settings » Item » Item availability</b> or the translation according to the format setting <b>Item availability</b>.
        </td>        
    </tr>
    <tr>
        <td>
            Herstellernummer
        </td>
        <td>
            <b>Required</b><br>
            <b>Content:</b> The <b>Model</b> within <b>Items » Edit item » Open item » Open variation » Settings » Basic settings</b>.
        </td>        
    </tr>
	<tr>
		<td>
			EAN
		</td>
		<td>
		    <b>Required</b><br>
		    <b>Content:</b> According to the format setting <b>Barcode</b>.
		</td>        
	</tr>
	<tr>
		<td>
			Kategorie
		</td>
		<td>
			<b>Content:</b> The names of the categories that are linked to the variation, separeted with ">".
		</td>        
	</tr>
	<tr>
		<td>
			Grundpreis
		</td>
		<td>
			<b>Content:</b> The <b>base price information</b>. The format is "price / unit". (Example: 10.00 EUR / kilogram)
		</td>        
	</tr>
	<tr>
        <td>
            Beschreibung
        </td>
        <td>
            <b>Content:</b> According to the format setting <b>Description</b>.
        </td>        
    </tr>
</table>

## License

This project is licensed under the GNU AFFERO GENERAL PUBLIC LICENSE.- find further information in the [LICENSE.md](https://github.com/plentymarkets/plugin-elastic-export-geizhals-de/blob/master/LICENSE.md).
