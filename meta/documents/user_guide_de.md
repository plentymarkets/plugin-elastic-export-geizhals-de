# User Guide für das ElasticExportGeizhalsDE Plugin

<div class="container-toc"></div>

## 1 Bei Geizhals.de registrieren

Der Geizhals-Preisvergleich ist eine unabhängige Preisvergleichs- und Informationsplattform mit Schwerpunkt Hardware und Unterhaltungselektronik.

## 2 Elastic Export GeizhalsDE-Plugin in plentymarkets einrichten

Um dieses Format nutzen zu können, benötigen Sie das Plugin Elastic Export.

Auf der Handbuchseite [Daten exportieren](https://www.plentymarkets.eu/handbuch/datenaustausch/daten-exportieren/#4) werden die einzelnen Formateinstellungen beschrieben.

In der folgenden Tabelle finden Sie spezifische Hinweise zu den Einstellungen, Formateinstellungen und empfohlenen Artikelfiltern für das Format **GeizhalsDE-Plugin**.
<table>
    <tr>
        <th>
            Einstellung
        </th>
        <th>
            Erläuterung
        </th>
    </tr>
    <tr>
        <td class="th" colspan="2">
            Einstellungen
        </td>
    </tr>
    <tr>
        <td>
            Format
        </td>
        <td>
            <b>GeizhalsDE-Plugin</b> wählen.
        </td>
    </tr>
    <tr>
        <td>
            Bereitstellung
        </td>
        <td>
            <b>URL</b> wählen.
        </td>
    </tr>
    <tr>
        <td>
            Dateiname
        </td>
        <td>
            Der Dateiname muss auf <b>.csv</b> oder <b>.txt</b> enden, damit Geizhals.de die Datei erfolgreich importieren kann.
        </td>
    </tr>
    <tr>
        <td class="th" colspan="2">
            Artikelfilter
        </td>
    </tr>
    <tr>
        <td>
            Aktiv
        </td>
        <td>
            <b>Aktiv</b> wählen.
        </td>
    </tr>
    <tr>
        <td>
            Märkte
        </td>
        <td>
            Eine oder mehrere Auftragsherkünfte wählen. Die gewählten Auftragsherkünfte müssen an der Variante aktiviert sein, damit der Artikel exportiert wird.
        </td>
    </tr>
    <tr>
        <td class="th" colspan="2">
            Formateinstellungen
        </td>
    </tr>
    <tr>
        <td>
            Auftragsherkunft
        </td>
        <td>
            Die Auftragsherkunft wählen, die beim Auftragsimport zugeordnet werden soll.
        </td>
    </tr>
    <tr>
        <td>
            Vorschautext
        </td>
        <td>
            Diese Option ist für dieses Format nicht relevant.
        </td>
    </tr>
    <tr>
        <td>
            Bild
        </td>
        <td>
            Diese Option ist für dieses Format nicht relevant.
        </td>
    </tr>
    <tr>
        <td>
            Angebotspreis
        </td>
        <td>
            Diese Option ist für dieses Format nicht relevant.
        </td>
    </tr>
    <tr>
        <td>
            MwSt.-Hinweis
        </td>
        <td>
            Diese Option ist für dieses Format nicht relevant.
        </td>
    </tr>
</table>


## 3 Übersicht der verfügbaren Spalten

<table>
    <tr>
        <th>
            Spaltenbezeichnung
        </th>
        <th>
            Erläuterung
        </th>
    </tr>
    <tr>
        <td>
            Herstellername
        </td>
        <td>
            <b>Pflichtfeld</b><br>
            <b>Inhalt:</b> Der <b>Herstellers</b> des Artikels. Der <b>Externe Name</b> unter <b>Einstellungen » Artikel » Hersteller</b> wird bevorzugt, wenn vorhanden.
        </td>
    </tr>
    <tr>
        <td>
            Produktcode
        </td>
        <td>
            <b>Inhalt:</b> Die <b>Varianten-ID</b> der Variante.
        </td>
    </tr>
    <tr>
        <td>
            Produktbezeichnung
        </td>
        <td>
            <b>Pflichtfeld</b><br>
            <b>Inhalt:</b> Entsprechend der Formateinstellung <b>Artikelname</b>.
        </td>
    </tr>
    <tr>
        <td>
            Preis
        </td>
        <td>
            <b>Pflichtfeld</b><br>
            <b>Inhalt:</b> Der <b>Verkaufspreis</b> der Variante.
        </td>
    </tr>
    <tr>
        <td>
            Deeplink
        </td>
        <td>
            <b>Pflichtfeld</b><br>
            <b>Inhalt:</b> Der <b>URL-Pfad</b> des Artikels abhängig vom gewählten <b>Mandanten</b> in den Formateinstellungen.
        </td>
    </tr>
    <tr>
        <td>
            Versand Vorkasse
        </td>
        <td>
            <b>Inhalt:</b> Entsprechend der Formateinstellung <b>Versandkosten</b> mit <b>Versand Vorkasse</b>.
        </td>
    </tr>
    <tr>
        <td>
            Versand Nachnahme
        </td>
        <td>
            <b>Inhalt:</b> Entsprechend der Formateinstellung <b>Versandkosten</b> mit <b>Versand Nachnahme</b>.
        </td>
    </tr>
    <tr>
        <td>
            Verfügbarkeit
        </td>
        <td>
            <b>Pflichtfeld</b><br>
            <b>Inhalt:</b> Der <b>Name der Artikelverfügbarkeit</b> unter <b>Einstellungen » Artikel » Artikelverfügbarkeit</b> oder die Übersetzung gemäß der Formateinstellung <b>Artikelverfügbarkeit überschreiben</b>.
        </td>
    </tr>
    <tr>
        <td>
            Herstellernummer
        </td>
        <td>
            <b>Pflichtfeld</b><br>
            <b>Inhalt:</b> Das <b>Modell</b> unter <b>Artikel » Artikel bearbeiten » Artikel öffnen » Variante öffnen » Einstellungen » Grundeinstellungen</b>.
        </td>
    </tr>
    <tr>
        <td>
            EAN
        </td>
        <td>
            <b>Pflichtfeld</b><br>
            <b>Inhalt:</b> Entsprechend der Formateinstellung <b>Barcode</b>.
        </td>
    </tr>
    <tr>
        <td>
            Kategorie
        </td>
        <td>
            <b>Inhalt:</b> Der <b>Kategoriepfad der Standardkategorie</b> für den in den Formateinstellungen definierten <b>Mandanten</b>.
        </td>
    </tr>
    <tr>
        <td>
            Grundpreis
        </td>
        <td>
            <b>Inhalt:</b> Die <b>Grundpreisinformation</b> im Format "Preis / Einheit". (Beispiel: 10.00 EUR / Kilogramm)
        </td>
    </tr>
    <tr>
        <td>
            Beschreibung
        </td>
        <td>
            <b>Inhalt:</b> Entsprechend der Formateinstellung <b>Beschreibung</b>.
        </td>
    </tr>
</table>

## 4 Lizenz

Das gesamte Projekt unterliegt der GNU AFFERO GENERAL PUBLIC LICENSE – weitere Informationen finden Sie in der [LICENSE.md](https://github.com/plentymarkets/plugin-elastic-export-geizhals-de/blob/master/LICENSE.md).
