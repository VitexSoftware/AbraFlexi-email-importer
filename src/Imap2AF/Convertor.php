<?php

/**
 * Imap2AbraFlexi Isdoc to AbraFlexi convertor
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019-2024 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

/**
 * Convert parsed invoice into AbraFlexi format
 *
 * @author vitex
 */
class Convertor extends Parser
{
    /**
     * Current CZ taxes
     * @var array<string>
     */
    public $taxes = [
        0 => 'typSzbDph.dphOsv',
        10 => 'typSzbDph.dphSniz2',
        15 => 'typSzbDph.dphSniz',
        21 => 'typSzbDph.dphZakl'
    ];
    
    /**
     * Do not save into store
     * @var array<string>
     */
    public $storageBlacklist = [];

    /**
     * Configuration
     * @var array<string>
     */
    private $configuration = [];

    /**
     * Convert Dom based invoice Payment Element to Array
     *
     * @param \DOMNodeList $payment
     *
     * @return array
     */
    public static function domPaymentMeansToArray($payment)
    {
        $paymentArray = [];
        $paymentArrayRaw = current(self::domToArray($payment->item(0)));

        $paymentArray['datSplat'] = $paymentArrayRaw['Details']['PaymentDueDate'];

        return $paymentArray;
    }

    /**
     * Convert Dom based invoice item Element to Array
     *
     * @param \DOMNodeList $suplier
     *
     * @return array
     */
    public function domSuplierToArray($suplier)
    {
        $suplierArray = [];
        $suplierArrayRaw = current(self::domToArray($suplier->item(0)));

        $suplierArray['nazev'] = $suplierArrayRaw['PartyName']['Name'];
        $suplierArray['ulice'] = $suplierArrayRaw['PostalAddress']['StreetName'] . ' ' . $suplierArrayRaw['PostalAddress']['BuildingNumber'];
        $suplierArray['mesto'] = $suplierArrayRaw['PostalAddress']['CityName'];
        $suplierArray['psc'] = $suplierArrayRaw['PostalAddress']['PostalZone'];
        if (is_array($suplierArrayRaw['Contact'])) {
            $suplierArray['tel'] = array_key_exists('Telephone', $suplierArrayRaw['Contact']) ? $suplierArrayRaw['Contact']['Telephone'] : '';
            $suplierArray['email'] = array_key_exists('ElectronicMail', $suplierArrayRaw['Contact']) ? $suplierArrayRaw['Contact']['ElectronicMail'] : '';
        }
        $suplierArray['stat'] = empty($suplierArrayRaw['PostalAddress']['Country']['IdentificationCode']) ? '' : 'code:' . $suplierArrayRaw['PostalAddress']['Country']['IdentificationCode'];
        $suplierArray['ic'] = $suplierArrayRaw['PartyIdentification']['ID'];
        $suplierArray['dic'] = $suplierArrayRaw['PartyTaxScheme']['CompanyID'];
        $suplierArray['platceDph'] = ($suplierArrayRaw['PartyTaxScheme']['TaxScheme'] == 'VAT');
        $suplierArray['typVztahuK'] = 'typVztahu.dodavatel';

        return $suplierArray;
    }

    /**
     * Convert Dom based invoice item Element to Array
     *
     * @param \DOMNodeList $customer
     *
     * @return array
     */
    public function domCustomerToArray($customer)
    {
        $customerArray = [];

        if ($customer->count()) {
            $customerArrayRaw = current(self::domToArray($customer->item(0)));
            $customerArray['nazev'] = $customerArrayRaw['PartyName']['Name'];
            $customerArray['ulice'] = $customerArrayRaw['PostalAddress']['StreetName'] . ' ' . (empty($customerArrayRaw['PostalAddress']['BuildingNumber']) ? '' : $customerArrayRaw['PostalAddress']['BuildingNumber']);
            $customerArray['mesto'] = $customerArrayRaw['PostalAddress']['CityName'];
            $customerArray['psc'] = $customerArrayRaw['PostalAddress']['PostalZone'];
            $customerArray['tel'] = array_key_exists('Telephone', $customerArrayRaw['Contact']) ? $customerArrayRaw['Contact']['Telephone'] : '';
            $customerArray['email'] = array_key_exists('ElectronicMail', $customerArrayRaw['Contact']) ? $customerArrayRaw['Contact']['ElectronicMail'] : '';
            $customerArray['stat'] = empty($customerArrayRaw['PostalAddress']['Country']['IdentificationCode']) ? '' : 'code:' . $customerArrayRaw['PostalAddress']['Country']['IdentificationCode'];
            $customerArray['ic'] = $customerArrayRaw['PartyIdentification']['ID'];
            $customerArray['dic'] = $customerArrayRaw['PartyTaxScheme']['CompanyID'];
            $customerArray['platceDph'] = ($customerArrayRaw['PartyTaxScheme']['TaxScheme'] == 'VAT');
        } else {
            $this->addStatusMessage(_('No customer data in invoice ?!?'), 'warning');
            $customerArray['ic'] = false;
        }
        return $customerArray;
    }

    /**
     * Obtain Supplier info by Parsing ISDOC Dom
     *
     * @return array
     */
    public function getInvoiceSuplier()
    {
        return $this->domSuplierToArray($this->xmlDomDocument->getElementsByTagName('AccountingSupplierParty'));
    }

    /**
     * Convert Dom based invoice TaxTotal Element to Array
     *
     * @param \DOMNodeList $taxTotal
     *
     * @return array
     */
    public function domTaxTotalToArray($taxTotal)
    {
        $taxTotalArray = [];
        $taxTotalArrayRaw = self::domToArray($taxTotal->item(0));

        $taxSubTotal = $taxTotalArrayRaw['TaxSubTotal'];

        if (array_key_exists('TaxableAmount', $taxSubTotal)) {
            $taxTotalArray['sumZklZakl'] = $taxTotalArrayRaw['TaxSubTotal']['TaxableAmount'];
            $taxTotalArray['sumCelkZakl'] = $taxTotalArrayRaw['TaxSubTotal']['TaxInclusiveAmount'];
            $taxTotalArray['sumCelkem'] = $taxTotalArrayRaw['TaxSubTotal']['DifferenceTaxInclusiveAmount'];
            $taxTotalArray['sumDphZakl'] = $taxTotalArrayRaw['TaxSubTotal']['DifferenceTaxAmount'];
        } else {
            foreach ($taxSubTotal as $subTotalTax) {
            }
        }


        $taxTotalArray['sumDphCelkem'] = $taxTotalArrayRaw['TaxAmount'];

        return $taxTotalArray;
    }

    /**
     * Convert Dom based invoice Element to Array
     *
     * @param \DOMNodeList $invoice
     * @param array $invoiceArray Invoice content override
     *
     * @return array
     */
    public static function domInvoiceToArray($invoice, $invoiceArray = [])
    {
        $invoiceArrayRaw = self::domToArray($invoice->item(0));

        $invoiceArray['id'] = 'ext:fc:' . $invoiceArrayRaw['ID'];
        $invoiceArray['cisDosle'] = $invoiceArrayRaw['ID'];
        $invoiceArray['uuid'] = $invoiceArrayRaw['UUID'];
        $invoiceArray['datVyst'] = $invoiceArrayRaw['IssueDate'];
        if (isset($invoiceArrayRaw['Note'])) {
            $invoiceArray['poznam'] = $invoiceArrayRaw['Note'];
            $invoiceArray['popis'] = $invoiceArray['poznam'];
        }
        $invoiceArray['mena'] = 'code:' . $invoiceArrayRaw['LocalCurrencyCode'];
        $invoiceArray['typDokl'] = 'code:FAKTURA';

//        $invoiceArray['datSplat'] = '';
        return $invoiceArray;
    }

    /**
     * Obtain Supplier info by Parsing ISDOC Dom
     *
     * @return array
     */
    public function invoiceSuplier()
    {
        return $this->domSuplierToArray($this->xmlDomDocument->getElementsByTagName('AccountingSupplierParty'));
    }

    /**
     * Obtain Customer info by Parsing ISDOC Dom
     *
     * @return array
     */
    public function invoiceCustomer()
    {
        return $this->domCustomerToArray($this->xmlDomDocument->getElementsByTagName('AccountingCustomerParty'));
    }

    /**
     * Obtain Payment info by Parsing ISDOC Dom
     *
     * @return array
     */
    public function paymentMeans()
    {
        return $this->domPaymentMeansToArray($this->xmlDomDocument->getElementsByTagName('PaymentMeans'));
    }

    /**
     *
     * @return array
     */
    public function invoiceItems()
    {
        $invoiceItems = [];
        $invoiceLines = $this->xmlDomDocument->getElementsByTagName('InvoiceLine');
        if (count($invoiceLines)) {
            foreach ($invoiceLines as $invoiceItem) {
                $invoiceItems[] = $this->domInvoiceItemToArray($invoiceItem);
            }
        }
        return $invoiceItems;
    }

    /**
     * Parse ISDOC invoice DOMDocument to
     *
     * @return array of \AbraFlexi\FakturaPrijata properties
     */
    public function invoiceInfo()
    {
//Remove Branches - See https://bugs.php.net/bug.php?id=61858

        $element = $this->xmlDomDocument->documentElement;

        $element->removeChild($element->getElementsByTagName('AccountingSupplierParty')->item(0));
        $element->removeChild($element->getElementsByTagName('AccountingCustomerParty')->item(0));

        $buyerCustomerParty = $element->getElementsByTagName('BuyerCustomerParty')->item(0);
        if (is_object($buyerCustomerParty)) {
            $element->removeChild($buyerCustomerParty);
        }

        $delivery = $element->getElementsByTagName('Delivery')->item(0);
        if (is_object($delivery)) {
            $element->removeChild($delivery);
        }
        $element->removeChild($element->getElementsByTagName('InvoiceLines')->item(0));

//$taxTotal = $this->domTaxTotalToArray($this->xmlDomDocument->getElementsByTagName('TaxTotal'));

        $element->removeChild($element->getElementsByTagName('TaxTotal')->item(0));

//$legalMonetaryTotal = $this->domLMTotalToArray($this->xmlDomDocument->getElementsByTagName('LegalMonetaryTotal'));

        $element->removeChild($element->getElementsByTagName('LegalMonetaryTotal')->item(0));
        $element->removeChild($element->getElementsByTagName('PaymentMeans')->item(0));

        $invoiceInfo = $this->domInvoiceToArray($this->xmlDomDocument->getElementsByTagName('Invoice'));

//        return array_merge($invoiceInfo, $taxTotal);
        return $invoiceInfo;
    }

    /**
     * Convert Dom based invoice LegalMonetaryTotal Element to Array
     *
     * @param \DOMNodeList $taxTotal
     *
     * @return array
     */
    public function domLMTotalToArray($taxTotal)
    {
        $lmTotalArray = [];
        $lmTotalArrayRaw = self::domToArray($taxTotal->item(0));

        /*
          <TaxExclusiveAmount>3550.44000</TaxExclusiveAmount>
          <TaxInclusiveAmount>4296.00000</TaxInclusiveAmount>
          <AlreadyClaimedTaxExclusiveAmount>0</AlreadyClaimedTaxExclusiveAmount>
          <AlreadyClaimedTaxInclusiveAmount>0</AlreadyClaimedTaxInclusiveAmount>
          <DifferenceTaxExclusiveAmount>3550.44000</DifferenceTaxExclusiveAmount>
          <DifferenceTaxInclusiveAmount>4296.00000</DifferenceTaxInclusiveAmount>
          <PayableRoundingAmount>0</PayableRoundingAmount>
          <PaidDepositsAmount>0</PaidDepositsAmount>
          <PayableAmount>4296.00000</PayableAmount>
         */

        $lmTotalArray['sumCelkZakl'] = $lmTotalArrayRaw['TaxInclusiveAmount'];
        $lmTotalArray['sumZklZakl'] = $lmTotalArrayRaw['TaxableAmount'];
        $lmTotalArray['sumCelkem'] = $lmTotalArrayRaw['PayableAmount'];

        return $lmTotalArray;
    }

    /**
     * Convert Dom based invoice item Element to Array
     *
     * @param \DOMElement $item
     *
     * @return array
     */
    public function domInvoiceItemToArray($item)
    {
        $itemArray = [
            'typPolozkyK' => 'typPolozky.text',
            'ucetni' => false,
            'typCenyDphK' => 'typCeny.bezDph',
            'typSzbDphK' => 'typSzbDph.dphOsv',
            'kratkyPopis' => ''
        ];
        $itemArrayRaw = self::domToArray($item);

        if (is_array($itemArrayRaw['Item'])) {
            $itemArray['nazev'] = array_key_exists('Description', $itemArrayRaw['Item']) ? $itemArrayRaw['Item']['Description'] : '';
        } else {
            $itemArray['nazev'] = 'n/a';
        }
        $itemArray['cenaMj'] = $itemArrayRaw['UnitPriceTaxInclusive'];

        if (isset($itemArrayRaw['LineExtensionAmount']) && ($itemArrayRaw['LineExtensionAmount'] != '0.0')) {
            $itemArray['typPolozkyK'] = 'typPolozky.ucetni';
            $itemArray['ucetni'] = true;
            $itemArray['nakupCena'] = $itemArray['sumZkl'] = $itemArray['sumCelkem'] = (float) $itemArrayRaw['LineExtensionAmount'];
            $itemArray['cenaZaklVcDph'] = (float) $itemArrayRaw['LineExtensionAmountTaxInclusive'];
            $itemArray['dan'] = (float) $itemArrayRaw['LineExtensionTaxAmount'];
            if ($itemArray['dan'] != 0) {
                $itemArray['typCenyDphK'] = 'typCeny.sDph';
            }
            $itemArray['typSzbDphK'] = $this->taxes[intval($itemArrayRaw['ClassifiedTaxCategory']['Percent'])];

            if (isset($this->configuration['invoiceRoundingDefaults']) && isset($this->configuration['roundingList'])) {
                if (
                        array_search(
                            $itemArray['nazev'],
                            $this->configuration['roundingList']
                        ) !== false
                ) {
                    $this->addStatusMessage(sprintf(
                        _('Rouding item %s found. Defaults used'),
                        $itemArray['nazev']
                    ));
                    $itemArray = array_merge(
                        $itemArray,
                        $this->configuration['invoiceRoundingDefaults']
                    );
                }
            }
        } else {
            $itemArray['dan'] = 0;
        }

        if (array_key_exists('InvoicedQuantity', $itemArrayRaw) && is_array($itemArrayRaw['InvoicedQuantity'])) {
            $itemArray['typPolozkyK'] = 'typPolozky.obecny';
            if (isset($itemArrayRaw['InvoicedQuantity']['_value'])) {
                $itemArray['stavMJ'] = $itemArray['mnozMj'] = $itemArrayRaw['InvoicedQuantity']['_value'];
            }
            if ($itemArrayRaw['InvoicedQuantity']['@attributes']['unitCode']) {
                $itemArray['jednotka'] = strtoupper($itemArrayRaw['InvoicedQuantity']['@attributes']['unitCode']);
            }
        }

        if (array_key_exists('InvoicedQuantity', $itemArrayRaw) && is_array($itemArrayRaw['InvoicedQuantity']) && ($itemArray['dan'] > 0)) {
            $itemArray['typCenyDphK'] = 'typCeny.sDph';
            $itemArray['sumDph'] = $itemArrayRaw['LineExtensionTaxAmount'];
            $itemArray['sumCelkem'] = $itemArrayRaw['LineExtensionAmountTaxInclusive'];
        }

        if (is_array($itemArrayRaw['Item'])) {
            if (array_key_exists('CatalogueItemIdentification', $itemArrayRaw['Item'])) {
                if (
                        array_key_exists(
                            'ID',
                            $itemArrayRaw['Item']['CatalogueItemIdentification']
                        ) && $itemArray['ucetni'] && isset($itemArray['mnozMj']) && (floatval($itemArray['mnozMj']) > 0) && (array_search(
                            $itemArray['nazev'],
                            $this->storageBlacklist
                        ) == false)
                ) {
                    $itemArray['typPolozkyK'] = 'typPolozky.katalog';
                    if (!empty($itemArrayRaw['Item']['CatalogueItemIdentification']['ID'])) {
                        $itemArray['eanKod'] = $itemArrayRaw['Item']['CatalogueItemIdentification']['ID'];
                    }
                }
            }

            if (array_key_exists('SellersItemIdentification', $itemArrayRaw['Item'])) {
                if (
                        array_key_exists(
                            'ID',
                            $itemArrayRaw['Item']['SellersItemIdentification']
                        ) && $itemArray['ucetni'] && isset($itemArray['mnozMj']) && (floatval($itemArray['mnozMj']) > 0) && (array_search(
                            $itemArray['nazev'],
                            $this->storageBlacklist
                        ) == false)
                ) {
                    $itemArray['typPolozkyK'] = 'typPolozky.katalog';
                }
                if (
                        array_key_exists(
                            'SellersItemIdentification',
                            $itemArrayRaw['Item']
                        ) && !empty($itemArrayRaw['Item']['SellersItemIdentification']['ID'])
                ) {
                    $itemArray['kratkyPopis'] = $itemArrayRaw['Item']['SellersItemIdentification']['ID'];
                }
            }
        }
        if (!empty($itemArrayRaw['Note'])) {
            $itemArray['poznam'] = $itemArrayRaw['Note'];
        }


        return $itemArray;
    }
}
