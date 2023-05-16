# Abstract

Dieses Plugin ermöglicht es Entwickler und Anwender, möglichst einfach auf bestimmte Entitäten zurückzugreifen. Für unsere
Fälle sind das die Entitäten `Order` und `Product`.

Bei der `Order` werden statt der komplizierten Struktur der Bestellungen die bereits vorab transformierten Bestellungen zurückgeliefert.
Bei `Product` können Produkte über einen einfachen API-Endpoint dynamisch angelegt werden

# Requests

## Order

### Suchen nach Bestellungen

Um Bestellungen abzufragen, können Request zu 100% kompatibel zu Shopware durchgeführt werden.

```
{{endpoint}}/api/mothership/search/order
```

Payload
```
{
    "page": 1,
    "limit": 100,
    "filter": [
        {
            "type": "equals",
            "field": "order.stateMachineState.technicalName",
            "value": "in_progress"
        },
        {
            "type": "range",
            "field": "order.orderDate",
            "parameters": {
                "gte": "2022-03-16T00:00:00.000+00:00"
            }
        }
    ]
}
```
### Einzelne Bestellung abrufen

TBD: Bestellungen können wie folgt abgerufen werden.
Zusätzlich kann der Header "mothership-transform": true gesetzt werden.
Dann wird die Bestellung direkt transformiert zurückgegeben.

```
{{endpoint}}/api/mothership/order/7b633863576f4acbb559bb59653cc35c
```

## Product

Inspiriert durch https://shopify.dev/api/admin-rest/2022-10/resources/product

Features:

- [x] Active
- [x] Price
- [x] Stock
- [x] Translatable
- [] EAN
- [x] Manufacturer
- [] ReleaseDate
- [x] CustomFields
- [x] Category
- [x] Tax
- [x] ProductNumber
- [] ManufacturerNumber
- [x] Properties
- [x] SalesChannel
- [x] CmsPageId
- [x] Variant

### Anlegen eines Produktes

Um ein Produkt anzulegen, muss ein POST-Request an folgenden Endpoint gesendet werden.

```
{{endpoint}}/api/mothership/product
```

Die Pflichtfelder sind wie folgt:

```
{
    "sku": "ms-general-product-number",
    "name": {
        "de-DE": "Mothership Name",
        "en-GB": "Mothership Name"
    },
    "price": {
        "EUR": 20.00,
        "CHF": 25.00
    },
    "tax": 19.00,
    "sales_channel": {
        "Aignermunich": "all",
        "Aigner-Club": "all"
    },
    "stock": 1
}
```

### Translations

Translations sind alle Felder, die in der Tabelle `product_translation` als `name`, `description`, `keywords`, `meta_description`, `meta_title`
definiert sind. Diese Werte können mit folgendem Payload erstellt werden.

```
    "name": {
      "de-DE": "T-Shirt",
      "en-GB": "Tee Shirt"  
    }
    
    "description": {
      "de-DE": "... text über T-Shirt"  
    }
    
    "keywords": {
      "de-DE": "... keyword ..."  
    }
```

### Aktivieren / Deaktivieren

Ein Produkt kann durch die Angabe des Feldes `active` aktiviert oder deaktiviert werden. Standardmäßig ist ein Produkt aktiviert.

```
    "active": true|false
```

### Preise

Preise müssen immer mit der Angabe der Währung angelegt werden. Eine unabhängige Anlage der Preise ohne Angabe der Steuerregel ist nicht möglich.

```
  "price": {
        "EUR": 20.00
    },
   "tax": 19.00
```

### Custom Fields

Custom-Fields sind in Shopware Felder, die sich nicht filtern lassen. Es ist wichtig zu wissen, dass diese typisiert sind und diese Typisierung
zwingend angegeben werden muss. Hintergrund: Bei der Anlage von Produkten wird geprüft, ob ein bestimmtes Custom-Field existiert und falls dies
nicht der Fall sein sollte, so wird es angelegt.

Theoretisch könnte die Implementierung also so umgebaut werden, dass eine Anlage von custom fields während der Produkt-Anlage nicht notwendig ist,
widerspricht aber der Idee der Simple-API, dass ein Produkt alle Informationen enthalten soll, um angelegt werden zu können.

```

  "custom_fields": {
        "ms_boolean": {
            "type": "boolean",
            "values": {
                "de-DE": true
            }
        },
        "ms_integer": {
            "type": "int",
            "values": {
                "de-DE": 1
            }
        },
        "ms_float": {
            "type": "float",
            "values": {
                "de-DE": "2"
            }
        },
        "ms_text": {
            "type": "text",
            "values": {
                "de-DE": "test"
            }
        },
        "ms_textarea": {
            "type": "text_area",
            "values": {
                "de-DE": "test"
            }
        }
    },

```


### Bilder

Das Handling von Bilder in Shopware ist eine sehr schwierige Aufgabe, da hierzu sehr viele unterschiedliche Schritte durchgeführt werden müssen.
Die Simple-API macht das Handling deutlich einfacher, indem es folgende Annahmen trifft:

- Alle Bilder werden in der Reihenfolge angelegt, in der sie im Payload übergeben werden.
- Bei Bilder, die nicht im Payload vorhanden sind, werden falls bereits einem Produkt zugeordnet, die Bild-Zuordnungen zum Produkt entfernt.
- Es werden keine Bilder physisch entfernt!
- Die Cover-ID kann optional als Argument hinzugefügt werden. Es kann dabei immer nur ein Produkt eine Cover-ID besitzen.

```
    "images": [
        {
            "url": "https://aignerimage.de/etienne-aigner-ag-res.cloudinary.com/image/fetch/w_1920,h_822,f_auto,q_auto:eco,d_ph.gif/https://backend.aignermunich.de/media/g0/66/59/1676453576/Website_Startseite_Header_Mobil_02-23_1920x822_DE.jpg"
  
        },
           {
            "url": "https://via.placeholder.com/57x57.png",
                      "isCover": true
        }
    ]
```

### Kategorien

Um ein Produkt einer Kategorie zuzuordnen, muss der Code der Kategorie übergeben werden. Shopware unterstützt grundsätzlich
keine `codes`. Dieser müssen stattdessen als Custom-Field in der Tabelle `category_translation` hinterlegt werden.

Beispiel aus der Tabelle `category_translation` in der Spalte `custom_fields`:

```
{"code": "men_belts"}
```

Es ist also notwendig, dass die angelegten Kategorien auch einen `Code` besitzen, da nur auf diese Art und Weise eine benutzerfreundliche
Zuordnung von Kategorien möglich ist. Ist diese Bedingung erfüllt, kann die Kategorie wie folgt zugeordnet werden:

```
...
categories : ['men_belts', 'women_belts', ...]
...
```

### Layout-Zuweisung

Jedem Produkt in Shopware kann ein CMS-Layout zugeordnet werden. Üblicherweise handelt es sich dabei um ein Produkt-Template, welches
in der Tabelle `cms_page` hinterlegt ist. Die Zuweisung erfolgt über die ID der CMS-Seite vom Typen `product_detail`.

```
"cms_page_id" : "7a6d253a67204037966f42b0119704d5"
```

Soll gar keine CMS-Seite zugeordnet werden, so kann der Wert `null` übergeben werden oder aber das Attribut wird komplett weggelassen.

```
"cms_page_id" : null
```

### Varianten

Das Handling von Varianten ist in Shopware ziemlich tricky, da hierzu unterschiedliche Aspekte berücksichtigt werden müssen.
Grundsätzlich lässt sich die Variante anlegen, indem eine weitere Produktdefinition innerhalb des Attributs `variants` angelegt wird.

Im Folgenden ein Ausschnitt von einem Payload eines konfigurierbaren Produkts.
```
...
"stock": 1,
"images": [
    {
        "url": "https://aignerimage.de/etienne-aigner-ag-res.cloudinary.com/image/fetch/w_1920,h_822,f_auto,q_auto:eco,d_ph.gif/https://backend.aignermunich.de/media/g0/66/59/1676453576/Website_Startseite_Header_Mobil_02-23_1920x822_DE.jpg"
    },
    {
        "url": "https://via.placeholder.com/57x57.png",
        "isCover": true
    }
],
"variants": [...]
...
```

Innerhalb des Attributs `variants` werden die Varianten als Array angelegt. Jede Variante ist ein eigenständiges Produkt, welches
durch einen eigene SKU identifziert wird. Die Payload sind weitgehend identisch mit der Payload des Parents.

Es gibt jedoch eine Besonderheit, nämlich die `axis`. Es handelt sich um die konkreten Variantenausprägung eines
Produkts. In dem Fall um ein T-Shirt in der Farbe `green` und in der Größe `l`.

Die `axis` ist immer ein Objekt und hat immer die gleiche Struktur. Die Keys sind immer die Eigenschaften während die
Values immer die möglichen Werte sind. Shopware ist in der Art- und Weise, wie Varianten angelegt werden, sehr frei und
erlaubt jede beliebe Kombination von Produkt-Optionen.

```
 {
    "sku": "ms-variants.2",
    "name": {
        "de-DE": "T-shirt",
        "en-GB": "T-shirt"
    },
    "description": {
        "de-DE": "T-shirt",
        "en-GB": "Super-Shirt"
    },
    "price": {
        "EUR": 20.00
    },
    "tax": 19.00,
    "stock": 20,
    "properties": {
        "color": [
            "green"
        ],
        "size": [
            "l"
        ]
    },
    "axis": {
        "color": [
            "green"
        ],
        "size": [
            "l"
        ]
    }
}
```

Eine bereits angelegte Variante kann auch einfach entfernt werden, indem das Array wieder entfernt wird. Für ein komplexes
Produkt ist also ein vollständiger Payload notwendig.


### Asynchrone Abarbeitung



## Tests

Es gibt mehrere Wege, die Tests auszuführen. Empfohlen ist die Einrichtung in PHPStorm, jedoch sind auch alternative
Wege unten beschrieben.


### Ausführung innerhalb PHPStorm

1. Richte in den Einstellungen Docker ein
2. Für PHP-Cli füge einen Remote PHP command hinzu ("From Docker...", "docker-compose", Service "shop", Executable "php")
3. In den Einstellungen PHP / Test Framework füge „PHPUnit by Remote Interpreter“ hinzu
4. Setze:
    - *Composer autoloader* = `/var/www/html/custom/plugins/sw6/vendor/autoload.php`
    - *Default configuration file* = `/var/www/html/custom/plugins/sw6/phpunit.xml`
5. Rechtsklick auf die `phpunit.xml` und führe „Run phpunit.xml“ aus (wichtig!)
6. Sofern Xdebug korrekt in PHPStorm konfiguriert wurde, sollte auch Debugging und Coverage korrekt funktionieren

Falls es nach diesen Schritten noch nicht funktionieren sollte, prüfe zusätzlich folgendes:

7. Öffne die Run-Configuration für die Testsuite (dort wo die "Default Configuration File" gesetzt wurde)
8. Suche "Custom Working Directory" und setze hier den Pfad zum Application-Root (z.B. `/var/www/html/)
9. In den Einstellungen für den Command Line Interpreter, wähle "connect to existing Container" anstelle von "always start a new Container"
10. Falls es immer noch nicht funktionieren sollte, lösche die Testsuite und führe Schritt 5 anstelle von Schritt 3 aus und gehe die Schritte erneut durch

### Manuelle Ausführung

```
docker exec shop bash -c './custom/plugins/sw6/vendor/phpunit/phpunit/phpunit -c ./custom/plugins/sw6/phpunit.xml'
```
