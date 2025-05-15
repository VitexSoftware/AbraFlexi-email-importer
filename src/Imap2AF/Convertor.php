<?php

declare(strict_types=1);

/**
 * This file is part of the Imap2AbraFlexi package
 *
 * https://github.com/VitexSoftware/AbraFlexi-email-importer
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AbraFlexi\Imap2AF;

/**
 * Convert parsed invoice into AbraFlexi format.
 *
 * @author vitex
 */
class Convertor extends Parser
{
    /**
     * Current CZ taxes.
     *
     * @var array<string>
     */
    public array $taxes = [
        0 => 'typSzbDph.dphOsv',
        10 => 'typSzbDph.dphSniz2',
        15 => 'typSzbDph.dphSniz',
        21 => 'typSzbDph.dphZakl',
    ];

    /**
     * Do not save into store.
     *
     * @var array<string>
     */
    public array $storageBlacklist = [];

    /**
     * Configuration.
     *
     * @var array<string>
     */
    private array $configuration = [];

    /**
     * Convert Dom based invoice Payment Element to Array.
     *
     * @param \DOMNodeList $payment
     *
     * @return array<string>
     */
    public static function domPaymentMeansToArray($payment): array
    {
        $paymentArray = [];
        $paymentArrayRaw = current(self::domToArray($payment->item(0)));

        $paymentArray['datSplat'] = $paymentArrayRaw['Details']['PaymentDueDate'];

        return $paymentArray;
    }

    /**
     * Convert Dom based invoice item Element to Array.
     *
     * @param \DOMNodeList $suplier
     *
     * @return array<string, string>
     */
    public function domSuplierToArray($suplier)
    {
        $suplierArray = [];
        $suplierArrayRaw = current(self::domToArray($suplier->item(0)));

        $suplierArray['nazev'] = $suplierArrayRaw['PartyName']['Name'];
        $suplierArray['ulice'] = $suplierArrayRaw['PostalAddress']['StreetName'].' '.$suplierArrayRaw['PostalAddress']['BuildingNumber'];
        $suplierArray['mesto'] = $suplierArrayRaw['PostalAddress']['CityName'];
        $suplierArray['psc'] = $suplierArrayRaw['PostalAddress']['PostalZone'];

        if (\is_array($suplierArrayRaw['Contact'])) {
            $suplierArray['tel'] = \array_key_exists('Telephone', $suplierArrayRaw['Contact']) ? $suplierArrayRaw['Contact']['Telephone'] : '';
            $suplierArray['email'] = \array_key_exists('ElectronicMail', $suplierArrayRaw['Contact']) ? $suplierArrayRaw['Contact']['ElectronicMail'] : '';
        }

        $suplierArray['stat'] = empty($suplierArrayRaw['PostalAddress']['Country']['IdentificationCode']) ? '' : 'code:'.$suplierArrayRaw['PostalAddress']['Country']['IdentificationCode'];
        $suplierArray['ic'] = $suplierArrayRaw['PartyIdentification']['ID'];
        $suplierArray['dic'] = $suplierArrayRaw['PartyTaxScheme']['CompanyID'];
        $suplierArray['platceDph'] = ($suplierArrayRaw['PartyTaxScheme']['TaxScheme'] === 'VAT');
        $suplierArray['typVztahuK'] = 'typVztahu.dodavatel';

        return $suplierArray;
    }

    /**
     * Convert Dom based invoice item Element to Array.
     *
     * @param \DOMNodeList $customer
     *
     * @return array<string, string>
     */
    public function domCustomerToArray($customer)
    {
        $customerArray = [];

        if ($customer->count()) {
            $customerArrayRaw = current(self::domToArray($customer->item(0)));
            $customerArray['nazev'] = $customerArrayRaw['PartyName']['Name'];
            $customerArray['ulice'] = $customerArrayRaw['PostalAddress']['StreetName'].' '.(empty($customerArrayRaw['PostalAddress']['BuildingNumber']) ? '' : $customerArrayRaw['PostalAddress']['BuildingNumber']);
            $customerArray['mesto'] = $customerArrayRaw['PostalAddress']['CityName'];
            $customerArray['psc'] = $customerArrayRaw['PostalAddress']['PostalZone'];
            $customerArray['tel'] = \array_key_exists('Telephone', $customerArrayRaw['Contact']) ? $customerArrayRaw['Contact']['Telephone'] : '';
            $customerArray['email'] = \array_key_exists('ElectronicMail', $customerArrayRaw['Contact']) ? $customerArrayRaw['Contact']['ElectronicMail'] : '';
            $customerArray['stat'] = empty($customerArrayRaw['PostalAddress']['Country']['IdentificationCode']) ? '' : 'code:'.$customerArrayRaw['PostalAddress']['Country']['IdentificationCode'];
            $customerArray['ic'] = $customerArrayRaw['PartyIdentification']['ID'];
            $customerArray['dic'] = $customerArrayRaw['PartyTaxScheme']['CompanyID'];
            $customerArray['platceDph'] = ($customerArrayRaw['PartyTaxScheme']['TaxScheme'] === 'VAT');
        } else {
            $this->addStatusMessage(_('No customer data in invoice ?!?'), 'warning');
            $customerArray['ic'] = false;
        }

        return $customerArray;
    }

    /**
     * Obtain Supplier info by Parsing ISDOC Dom.
     *
     * @return array<mixed>
     */
    public function getInvoiceSuplier()
    {
        return $this->domSuplierToArray($this->xmlDomDocument->getElementsByTagName('AccountingSupplierParty'));
    }

    /**
     * Convert Dom based invoice TaxTotal Element to Array.
     *
     * @param \DOMNodeList $taxTotal
     *
     * @return array<string, string>
     */
    public function domTaxTotalToArray($taxTotal)
    {
        $taxTotalArray = [];
        $taxTotalArrayRaw = self::domToArray($taxTotal->item(0));

        $taxSubTotal = $taxTotalArrayRaw['TaxSubTotal'];

        if (\array_key_exists('TaxableAmount', $taxSubTotal)) {
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
     * Convert Dom based invoice Element to Array.
     *
     * @param \DOMNodeList $invoice
     * @param array        $invoiceArray Invoice content override
     *
     * @return array
     */
    public static function domInvoiceToArray($invoice, $invoiceArray = [])
    {
        $invoiceArrayRaw = self::domToArray($invoice->item(0));

        $invoiceArray['id'] = 'ext:fc:'.$invoiceArrayRaw['ID'];
        $invoiceArray['cisDosle'] = $invoiceArrayRaw['ID'];
        $invoiceArray['uuid'] = $invoiceArrayRaw['UUID'];
        $invoiceArray['datVyst'] = $invoiceArrayRaw['IssueDate'];

        if (isset($invoiceArrayRaw['Note'])) {
            $invoiceArray['poznam'] = $invoiceArrayRaw['Note'];
            $invoiceArray['popis'] = $invoiceArray['poznam'];
        }

        $invoiceArray['mena'] = 'code:'.$invoiceArrayRaw['LocalCurrencyCode'];
        $invoiceArray['typDokl'] = 'code:FAKTURA';

        //        $invoiceArray['datSplat'] = '';
        return $invoiceArray;
    }

    /**
     * Obtain Supplier info by Parsing ISDOC Dom.
     *
     * @return array<mixed>
     */
    public function invoiceSuplier(): array
    {
        return $this->domSuplierToArray($this->xmlDomDocument->getElementsByTagName('AccountingSupplierParty'));
    }

    /**
     * Obtain Customer info by Parsing ISDOC Dom.
     *
     * @return array<mixed>
     */
    public function invoiceCustomer(): array
    {
        return $this->domCustomerToArray($this->xmlDomDocument->getElementsByTagName('AccountingCustomerParty'));
    }

    /**
     * Obtain Payment info by Parsing ISDOC Dom.
     *
     * @return array<mixed>
     */
    public function paymentMeans(): array
    {
        return $this->domPaymentMeansToArray($this->xmlDomDocument->getElementsByTagName('PaymentMeans'));
    }

    /**
     * @return array<mixed>
     */
    public function invoiceItems(): array
    {
        $invoiceItems = [];
        $invoiceLines = $this->xmlDomDocument->getElementsByTagName('InvoiceLine');

        if (\count($invoiceLines)) {
            foreach ($invoiceLines as $invoiceItem) {
                $invoiceItems[] = $this->domInvoiceItemToArray($invoiceItem);
            }
        }

        return $invoiceItems;
    }

    /**
     * Parse ISDOC invoice DOMDocument to.
     *
     * @return array<string, string> of \AbraFlexi\FakturaPrijata properties
     */
    public function invoiceInfo(): array
    {
        // Remove Branches - See https://bugs.php.net/bug.php?id=61858

        $element = $this->xmlDomDocument->documentElement;

        $element->removeChild($element->getElementsByTagName('AccountingSupplierParty')->item(0));
        $element->removeChild($element->getElementsByTagName('AccountingCustomerParty')->item(0));

        $buyerCustomerParty = $element->getElementsByTagName('BuyerCustomerParty')->item(0);

        if (\is_object($buyerCustomerParty)) {
            $element->removeChild($buyerCustomerParty);
        }

        $delivery = $element->getElementsByTagName('Delivery')->item(0);

        if (\is_object($delivery)) {
            $element->removeChild($delivery);
        }

        $element->removeChild($element->getElementsByTagName('InvoiceLines')->item(0));

        // $taxTotal = $this->domTaxTotalToArray($this->xmlDomDocument->getElementsByTagName('TaxTotal'));

        $element->removeChild($element->getElementsByTagName('TaxTotal')->item(0));

        // $legalMonetaryTotal = $this->domLMTotalToArray($this->xmlDomDocument->getElementsByTagName('LegalMonetaryTotal'));

        $element->removeChild($element->getElementsByTagName('LegalMonetaryTotal')->item(0));
        $element->removeChild($element->getElementsByTagName('PaymentMeans')->item(0));

        return $this->domInvoiceToArray($this->xmlDomDocument->getElementsByTagName('Invoice'));
        //        return array_merge($invoiceInfo, $taxTotal);
    }

    /**
     * Convert Dom based invoice LegalMonetaryTotal Element to Array.
     *
     * @param \DOMNodeList $taxTotal
     *
     * @return array<string, string>
     */
    public function domLMTotalToArray($taxTotal): array
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
     * Convert Dom based invoice item Element to Array.
     *
     * @return array<string, string>
     */
    public function domInvoiceItemToArray(\DOMElement $item): array
    {
        $itemArray = [
            'typPolozkyK' => 'typPolozky.text',
            'typCenyDphK' => 'typCeny.bezDph',
            'typSzbDphK' => 'typSzbDph.dphOsv',
        ];
        $itemArrayRaw = self::domToArray($item);

        // Název a označení
        if (\is_array($itemArrayRaw['Item'])) {
            $itemArray['nazev'] = $itemArrayRaw['Item']['Description'] ?? '';
        } else {
            $itemArray['nazev'] = 'n/a';
        }

        // Množství a MJ
        if (isset($itemArrayRaw['InvoicedQuantity'])) {
            $itemArray['mnozstvi'] = (float) $itemArrayRaw['InvoicedQuantity'];

            if (isset($itemArrayRaw['InvoicedQuantity_attr']['unitCode'])) {
                $itemArray['mj'] = $itemArrayRaw['InvoicedQuantity_attr']['unitCode'];
            }
        }

        // Sleva
        if (isset($itemArrayRaw['AllowanceCharge']['MultiplierFactorNumeric'])) {
            $itemArray['sleva'] = (float) $itemArrayRaw['AllowanceCharge']['MultiplierFactorNumeric'] * 100;
        }

        // Cena za MJ
        $itemArray['cenaMj'] = isset($itemArrayRaw['UnitPrice']) ? (float) $itemArrayRaw['UnitPrice'] : (isset($itemArrayRaw['UnitPriceTaxInclusive']) ? (float) $itemArrayRaw['UnitPriceTaxInclusive'] : 0);

        // Sazba DPH
        if (isset($itemArrayRaw['ClassifiedTaxCategory']['Percent'])) {
            $itemArray['sazbaDph'] = (float) $itemArrayRaw['ClassifiedTaxCategory']['Percent'];
            $itemArray['typSzbDphK'] = $this->taxes[(int) $itemArray['sazbaDph']] ?? 'typSzbDph.dphOsv';
        }

        // Rounding
        if (empty($itemArray['cenaMj'])) {
            $itemArray['typPolozkyK'] = 'typPolozky.ucetni';
            unset($itemArray['cenaMj']);
            $itemArray['sumZkl'] = isset($itemArrayRaw['LineExtensionAmount']) ? (float) $itemArrayRaw['LineExtensionAmount'] : 0;
        } else {
            $itemArray['sumZkl'] = $itemArrayRaw['LineExtensionAmount'];
        }

        // Záruka (pokud je v ISDOC)
        if (isset($itemArrayRaw['Warranty'])) {
            $itemArray['zaruka'] = $itemArrayRaw['Warranty'];
        }

        return $itemArray;
    }
}
