# TCPDF Extension

[TCPDF](http://www.tcpdf.org) is a PHP library to generate PDF documents. It is very
feature rich, but not easy to use.

This library builds on top of the TCPDF library. At the current state it provides
only a smart API to **create tables in a comfortable way**.

## Features

* More comfortable creation of tables

## Installation

The easiest way to use this library is by installing it via
[Composer](http://getcomposer.org/download/).

Add this to your project's composer.json:

```json
{
    "require": {
        "naitsirch/tcpdf-extension": "dev-master"
    }
}
```

## Usage

### Creating tables

First you have to create an instance of TCPDF.

```php
use TCPDF;

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetTitle('My PDF file');
$pdf->SetMargins(20, 20, 20);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->SetAutoPageBreak(true, 9);
$pdf->SetFont('dejavusans', '', 10);
```

Please look into the [documentation](http://www.tcpdf.org/doc/code/classTCPDF.html)
of the TCPDF class if you are not familiar with it.

In the next step you can create the table.

```php
use Tcpdf\Extension\Table\Table;

$pdf->AddPage(); // add a new page to the document
$table = new Table($pdf);
$table
    ->newRow()
        ->newCell()
            ->setText('Last Name')
            ->setFontWeight('bold')
        ->end()
        ->newCell()
            ->setText('First Name')
            ->setFontWeight('bold')
        ->end()
        ->newCell()
            ->setText('DOB')
            ->setFontWeight('bold')
        ->end()
        ->newCell()
            ->setText('Email')
            ->setFontWeight('bold')
        ->end()
    ->end()
    ->newRow()
        ->newCell('Foo')->end()
        ->newCell('John')->end()
        ->newCell('1956-04-14')->end()
        ->newCell('johnny@example.com')->end()
    ->end()
;
$table->end(); // this prints the table to the PDF. Don't forget!
```

The above code shows how to create a very simple table. But you are able to customize
table cells in different ways:

```php
$table
    ->newRow()
        ->newCell('Last Name')         // you can set the cell content like this
            ->setText('Override Text') // or like this
            ->setFontWeight('bold')    // set font weight 'bold' or 'normal'
            ->setAlign('L')            // text alignment ('L', 'C', 'R' or 'J')
            ->setVerticalAlign('top')  // vertical alignment ('top', 'bottom' or 'middle')
            ->setBorder(1)             // border format (like in TCPDF::MultiCell)
            ->setRowspan(1)            // rowspan like in HTML
            ->setColspan(2)            // colspan like in HTML
            ->setFontSize(10)          // unit for font size is same as defined in TCPDF
            ->setMinHeight(10)         // defining min-height of the cell like in CSS
            ->setPadding(2, 4)         // setting cell padding (inner margin) like in CSS
            ->setPadding(2, 4, 5, 6)   // or like this
            ->setWidth(125)            // unit for width is same as defined in TCPDF
        ->end()
```

#### Background

Define a background color:

```php
$table
    ->newRow()
        ->newCell('Last Name')
            ->setBackgroundColor('#ff4400')                 // hexadecimal RGB color code
            ->setBackgroundColor(array(250, 80, 10)         // decimal RGB color array
        ->end()
    ->end()
```

It is possible to define a background image for each table cell.

```php
$table
    ->newRow()
        ->newCell('Last Name')
            ->setBackgroundDpi(300)                         // define the resolution for the printing
            ->setBackgroundImage('/path/to/my/image.png')   // pass the path to your image
            ->setBackgroundImage($binaryImageString)        // or pass the binary file content of your image
        ->end()
    ->end()
```
