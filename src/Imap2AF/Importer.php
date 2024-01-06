<?php

/**
 * Imap2AbraFlexi Import
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019-2020 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

use AbraFlexi\Adresar;
use AbraFlexi\Cenik;
use AbraFlexi\Company;
use AbraFlexi\Exception;
use AbraFlexi\FakturaPrijata;
use AbraFlexi\Functions as AF;
use AbraFlexi\Nastaveni;
use AbraFlexi\Priloha;
use AbraFlexi\RO;
use AbraFlexi\RW;
use AbraFlexi\SkladovaKarta;
use AbraFlexi\Stitek;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use Ease\Functions;
use Lightools\Xml\XmlLoader;

/**
 * Description of Importer
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Importer extends FakturaPrijata
{
    /**
     * Default values for new Pricelist Item
     * @var array
     */
    public $newItemDefaults = ['typZasobyK' => 'typZasoby.zbozi', 'skladove' => false];

    /**
     * Items to skip
     * @var array
     */
    public $storageBlacklist = ['Zaokrouhlení dokladu', 'Balné', 'Zaokrouhleno',
        'Autorská odměna'];

    /**
     * AbraFlexi pricelist Object
     * @var Cenik
     */
    private $priceList;

    /**
     * XML Loader
     * @var XmlLoader
     */
    public $loader;

    /**
     * Invoice Suplier
     * @var Adresar
     */
    private $suplier;

    /**
     * My Company Info
     * @var Nastaveni
     */
    private $myInfo = null;

    /**
     *
     * @var Convertor
     */
    public $parser = null;

    /**
     *
     * @var array
     */
    public $configuration = [];

    /**
     * Listing of Wanted/Unwanted IC numbers
     * @var array [IC]=boolean
     */
    private $wantList = [];

    /**
     * Invoice source label used for ext:$source:hash
     * @var string
     */
    private $source;

    /**
     *
     * @var array
     */
    private $invoicesToImport;

    /**
     *
     * @var array
     */
    private $taxes = [];

    /**
     *
     * @var array
     */
    private $invoiceFiles = [];

    /**
     * Import engine
     *
     * @param string $source   import invoices as ext:$source:hash
     * @param array  $options
     */
    public function __construct($source, $options = array())
    {
        parent::__construct(null, $options);
        $this->source = $source;
        $want = Functions::cfg('ACCEPT_PROVIDER_IDS');
        if (empty($want) === false) {
            foreach (strstr($want, ',') ? explode(',', $want) : [$want] as $id) {
                $this->wantList[$id] = true;
            }
        }
        $donwant = Functions::cfg('DENY_PROVIDER_IDS');
        if (empty($donwant) === false) {
            foreach (strstr($donwant, ',') ? explode(',', $donwant) : [$donwant] as $id) {
                $this->wantList[$id] = false;
            }
        }


        $this->parser = new Convertor();
        $this->suplier = new Adresar();
        $this->loader = new XmlLoader();
        $this->myInfo = new Nastaveni(1);
    }

    /**
     * Import mail messages from all or given senders
     *
     * @param array $isdocs
     * @param array $senders
     */
    public function importIsdocFiles($isdocs, $senders)
    {
        $this->invoicesToImport = $isdocs;
        if (!empty($isdocs)) {
            $this->mainLoop($this->invoicesToImport, $senders);
        }
    }

    /**
     * Compile Invoice
     *
     * @return FakturaPrijata
     */
    public function xmlDomToInvoice()
    {
        $invoiceSuplier = $this->parser->invoiceSuplier();
        $invoiceCustomer = $this->parser->invoiceCustomer();
        $invoiceItems = $this->parser->invoiceItems();
        $paymentMeans = $this->parser->paymentMeans();
        $invoiceInfo = $this->parser->invoiceInfo();
        $invoiceInfo['ic'] = $invoiceCustomer['ic'];
        $suplierAbraFlexiID = $this->getSuplierAbraFlexiID($invoiceSuplier);
        $invoice = new FakturaPrijata($invoiceInfo);
        $invoice->setDataValue('typDokl', AF::code($this->conf('ABRAFLEXI_DOCTYPE')));
        $invoice->setDataValue('datSplat', $paymentMeans['datSplat']);
        $invoice->setDataValue('banka', $this->conf('ABRAFLEXI_BANK') ? AF::code($this->conf('ABRAFLEXI_BANK')) : null);
        $checker = new Adresar();
        if ($checker->recordExists(['ic' => $invoiceSuplier['ic']])) {
            $invoice->setDataValue('firma', 'in:' . $invoiceSuplier['ic']);
        }

        foreach ($invoiceItems as $invoiceItemID => $invoiceItem) {
            $invoiceItem['dodavatel'] = $suplierAbraFlexiID;
            $invoiceItem['origin'] = $invoice->getDataValue('cisDosle');
            if ($invoiceItem['typPolozkyK'] == 'typPolozky.katalog') {
                $invoiceItem['sklad'] = $this->conf('ABRAFLEXI_STORAGE') ? AF::code($this->conf('ABRAFLEXI_STORAGE')) : null;
            }
            $invoice->addArrayToBranch($invoiceItem, 'polozkyFaktury');
        }
        return $invoice;
    }

    public function invoiceItems()
    {
        $invoiceItems = [];
        foreach ($this->parser->invoiceItems() as $invoiceItem) {
            $invoiceItems[] = $this->domInvoiceItemToArray($invoiceItem);
        }
        return $invoiceItems;
    }

    /**
     * Convert Dom based invoice item Element to Array
     *
     * @param DOMElement $item
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
        $itemArrayRaw = Convertor::domToArray($item);
        $itemArray['nazev'] = $itemArrayRaw['Item']['Description'];
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


        if (
                array_key_exists(
                    'CatalogueItemIdentification',
                    $itemArrayRaw['Item']
                )
        ) {
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
        if (!empty($itemArrayRaw['Note'])) {
            $itemArray['poznam'] = $itemArrayRaw['Note'];
        }


        return $itemArray;
    }

    /**
     * Insert invoice items to pricelist & storage
     *
     * @param array $invoiceItems
     *
     * @return array PricelistIDs
     */
    public function importInvoiceItems(&$invoiceItems)
    {
        $pricelistIDs = [];
        $this->priceList = new Cenik();
        $invoiceItems = $this->recountForPricelist($invoiceItems);
        foreach ($invoiceItems as $invoiceItemID => $invoiceItem) {
            $pricelistIDs[$invoiceItemID] = null;
            if (array_search($invoiceItem['nazev'], $this->storageBlacklist) !== false) {
                $this->addStatusMessage(sprintf(
                    _('Blacklisted item %s import to pricelist skipped'),
                    $invoiceItem['nazev']
                ), 'info');
                continue;
            }

            if ($this->abraFlexiPricelistPresence($invoiceItem)) {
                $pricelistId = $this->priceList->lastResult['cenik'][0]['id'];
                $pricelistIDs[$invoiceItemID] = $pricelistId;
                $this->addStatusMessage(sprintf(
                    _('Allready known item %s'),
                    $invoiceItem['nazev']
                ));
            } else {
                unset($invoiceItem['sklad']); //FIXME: choose storage properly

                if ($invoiceItem['typPolozkyK'] == 'typPolozky.katalog') {
                    $newPriceListItem = $this->addItemToPriceList($invoiceItem);
                    if (is_null($newPriceListItem)) {
                        $this->addStatusMessage(sprintf(_('Item %s insert to AbraFlexi Pricelist failed'), $invoiceItem['nazev']), 'error');
                    } else {
                        $pricelistIDs[$invoiceItemID] = $newPriceListItem;
                        $this->addStatusMessage(sprintf(
                            _('Item was added to AbraFlexi Pricelist as %s'),
                            $newPriceListItem
                        ), 'success');
//                        $newStorageItem = $this->addItemToStorage($this->priceList,
//                                0);
//                        if (is_null($newStorageItem) || ($newStorageItem['success'] != 'true')) {
//                            $this->addStatusMessage(sprintf(_('Item %s inject to AbraFlexi Storage failed'),
//                                            $invoiceItem['nazev']), 'error');
//                        } else {
//                            $this->addStatusMessage(sprintf(_('Item was added to AbraFlexi Storage as %s'),
//                                            $newPriceListItem), 'success');
//                        }
                    }
                }
            }
        }
        return $pricelistIDs;
    }

    /**
     * Insert given invoice to AbraFlexi
     *
     * @param FakturaPrijata $invoice
     *
     * @return boolean insertation status
     */
    public function importInvoice(&$invoice)
    {
//                $invoice->setDataValue('stitky', 'IMAP2AF');

        if ($this->conf('FORCE_INCOMING_INVOICE_TYPE')) {
            $invoice->setDataValue('typDokl', $this->conf('FORCE_INCOMING_INVOICE_TYPE'));
        }

        $invoiceInserted = $invoice->sync();
        if ($invoiceInserted) {
            $this->addStatusMessage(sprintf(
                _('Invoice was inserted to AbraFlexi as %s'),
                $invoice->getRecordIdent()
            ), 'success');
        } else {
            $this->addStatusMessage(sprintf(
                _('Invoice %s insert to AbraFlexi failed'),
                $invoice->getRecordIdent()
            ), 'error');
        }
        return $invoiceInserted;
    }

    /**
     * Check invoice recipent validity. We want invoice for us or from company
     * IDs in wantlist
     *
     * @param FakturaPrijata $invoice
     *
     * @return boolean
     */
    public function isForMe(FakturaPrijata $invoice)
    {
        $suppliersId = str_replace('in:', '', $invoice->getDataValue('firma'));
        return (array_key_exists($suppliersId, $this->wantList) && ($this->wantList[$suppliersId] === true) ||
                $this->myInfo->getDataValue('ic') === $invoice->getDataValue('ic'));
    }

    /**
     * Main LOOP
     *
     * @param array $inputFiles Listo of ISDOC files paths
     * @param array $senders List of sender emails for each invoice
     */
    public function mainLoop(array $inputFiles, array $senders)
    {

        foreach ($inputFiles as $inputFile => $inputFilePath) {
            $renamed = sys_get_temp_dir() . '/' . $inputFile;
            if (rename($inputFilePath, $renamed) && $this->parser->loadFile($renamed)) {
                $invoice = $this->xmlDomToInvoice();
                $invoice->setDataValue('id', 'ext:' . $this->source . ':' . md5_file($renamed));
                if ($this->isForMe($invoice) === false) {
                    $invoice->addStatusMessage(sprintf(
                        _('Invoice for somebody else %s - skipping'),
                        $invoice->getDataValue('cisDosle') . ' ' . str_replace('in:', 'ico:', $invoice->getDataValue('firma'))
                    ), 'info');
                    continue;
                }

                if ($this->isKnownInvoice($invoice)) {
                    $invoice->addStatusMessage(sprintf(
                        _('Already known invoice %s - skipping'),
                        $invoice->getMyKey()
                    ), 'warning');
                    continue;
                }


                $invoiceItems = $invoice->getSubItems();
                if (!empty($invoiceItems)) {
                    $pricelistIDs = $this->importInvoiceItems($invoiceItems);
                    if (count($pricelistIDs)) {
                        foreach ($invoiceItems as $no => $data) {
                            if (!is_null($pricelistIDs[$no])) {
                                $invoiceItems[$no]['cenik'] = (int) $pricelistIDs[$no];
                            }
                            unset($invoiceItems[$no]['sklad']); //FIXME: proper keyname choo
                        }
                    }
                    if ($this->invoiceItems() != $invoiceItems) {
                        $invoice->setSubitems($invoiceItems);
                    }
                } else {
                    $this->addStatusMessage(_('No items to process loaded'), 'warning');
                }
                $invoice->unsetDataValue('sklad');
                $invoicesImported = $this->importInvoice($invoice);
                $path_parts = pathinfo($inputFilePath);
                $unzippedDir = $path_parts['dirname'] . '/' . $path_parts['basename'] . 'unzipped';
                if (file_exists($unzippedDir . '/manifest.xml')) {
                    $d = dir($unzippedDir);
                    while (false !== ($attachment = $d->read())) {
                        if (($attachment != 'manifest.xml') && !is_dir($unzippedDir . '/' . $attachment)) {
                            $attached = Priloha::addAttachmentFromFile($invoice, $unzippedDir . '/' . $attachment);
                            if ($attached->getRecordIdent()) {
                                $this->addStatusMessage(sprintf(
                                    _('%s version of invoice %s attached'),
                                    $attachment,
                                    $invoice->getRecordCode()
                                ), 'success');
                            }
                        }
                    }
                    $d->close();
                }

                if (isset($this->configuration['cleanprocessed']) && ( $this->configuration['cleanprocessed'] != 'false')) {
                    $this->cleanUp($invoicesImported);
                }
            }
        }
    }

    /**
     *
     * @param string $unitCode
     *
     * @return boolean Measure Unit presence status
     */
    public function handleMeasureUnit($unitCode)
    {
        $checker = new RW(AF::code($unitCode), ['evidence' => 'merna-jednotka', 'ignore404' => true]);
        return ($checker->lastResponseCode == 404) ? $checker->sync(['id' => AF::code($unitCode), 'nazev' => mb_strtolower($unitCode), 'poznam' => _('imported from invoice by mail')]) : true;
    }

    /**
     * Add item to PriceList
     *
     * @param Cenik $pricelist
     *
     * @return SkladovaKarta
     */
    public function addItemToStorage($pricelist, $count = 1)
    {
        $storager = new SkladovaKarta();
        $storager->setDataValue('stavMJ', $count);
        $storager->setDataValue('stitky', 'FLEXICEN');
        $storager->setDataValue('ucetObdobi', 'code:' . date('Y'));
        $storager->setDataValue('cenik', $pricelist->getDataValue('id'));
        $storager->setDataValue('sklad', 'code:' . $this->configuration['storage']);
        $storager->sync();
        return $storager;
    }

    /**
     * CleanUP processed inputfile
     *
     * @param array $invoiceFiles
     */
    public function cleanUp($invoiceFiles)
    {
        foreach ($invoiceFiles as $invoiceID => $invoiceExtID) {
            $fileToDelete = $this->invoiceFiles[$invoiceExtID];
            if (unlink($fileToDelete)) {
                $this->addStatusMessage(sprintf(
                    'Invoice %s file %s deleted',
                    $invoiceExtID,
                    $this->invoiceFiles[$invoiceExtID]
                ));
            } else {
                $this->addStatusMessage(
                    sprintf(
                        'Invoice %s file %s delete failed',
                        $invoiceExtID,
                        $this->invoiceFiles[$invoiceExtID]
                    ),
                    'warning'
                );
            }

            $dirToDelete = dirname($fileToDelete);
            $pdfToDelete = $dirToDelete . '/' . str_replace(
                '.isdoc',
                '.pdf',
                basename($fileToDelete)
            );
            if (file_exists($pdfToDelete)) {
                if (unlink($pdfToDelete)) {
                    $this->addStatusMessage(sprintf(
                        'Invoice %s file %s deleted',
                        $invoiceExtID,
                        $pdfToDelete
                    ));
                    rmdir($dirToDelete);
                } else {
                    $this->addStatusMessage(sprintf(
                        'Invoice %s file %s delete failed',
                        $invoiceExtID,
                        $pdfToDelete
                    ), 'warning');
                }
            }


            if (isset($this->invoiceFiles[$invoiceExtID . 'x'])) {
                if (unlink($this->invoiceFiles[$invoiceExtID . 'x'])) {
                    $this->addStatusMessage(sprintf(
                        'Original Invoice %s file %s deleted',
                        $invoiceExtID,
                        $this->invoiceFiles[$invoiceExtID . 'x']
                    ));
                } else {
                    $this->addStatusMessage(sprintf(
                        'Original Invoice %s file %s delete failed',
                        $invoiceExtID,
                        $this->invoiceFiles[$invoiceExtID . 'x']
                    ), 'warning');
                }
            }
        }
    }

    /**
     * Query AbraFlexi priceList for given item name
     *
     * @param string $invoiceItemRaw Looking for
     *
     * @return boolean
     */
    public function abraFlexiPricelistPresence($invoiceItemRaw)
    {
        $productKnown = false;
        $invoiceItem = [];
        Functions::divDataArray($invoiceItemRaw, $invoiceItem, 'eanKod');
        Functions::divDataArray($invoiceItemRaw, $invoiceItem, 'kratkyPopis');
        Functions::divDataArray($invoiceItemRaw, $invoiceItem, 'nazev');
        if (count($invoiceItem)) {
            foreach ($invoiceItem as $column => $value) {
                $fbpl = $this->priceList->getColumnsFromAbraFlexi(
                    ['id'],
                    [$column => $value]
                );
                if ($this->priceList->lastResponseCode == 200) {
                    if (!empty(current($fbpl))) {
                        $productKnown = true;
                        break;
                    }
                }
            }
        }
        return $productKnown;
    }

    /**
     * Insert PriceList item to AbraFlexi
     *
     * @param array $invoiceItem
     *
     * @return int|null item AbraFlexi ID
     */
    public function addItemToPriceList($invoiceItem)
    {
        $result = null;
        if (!isset($invoiceItem['stavMJ'])) {
            $this->addStatusMessage(
                'item ' . serialize($invoiceItem) . ' without count',
                'warning'
            );
            $invoiceItem['stavMJ'] = 1;
        }

        $providerInfo = [
            'nakupCena' => $invoiceItem['cenaMj'],
            'kodIndi' => $invoiceItem['kratkyPopis'],
            'stavMJ' => $invoiceItem['stavMJ'],
            'poznam' => _('Imported from invoice by email'),
            'mena' => 'code:CZK',
            'primarni' => true,
            'firma' => $invoiceItem['dodavatel']
        ];
        unset($invoiceItem['stavMJ']);
        if (isset($invoiceItem['jednotka'])) {
            $this->handleMeasureUnit($invoiceItem['jednotka']);
            $invoiceItem['mj1'] = 'code:' . $invoiceItem['jednotka'];
            unset($invoiceItem['jednotka']);
        }
        unset($invoiceItem['dan']);
        unset($invoiceItem['procentodane']);
        $this->priceList->dataReset();
        $this->priceList->takeData(array_merge(
            $this->newItemDefaults,
            $invoiceItem
        ));
        $this->priceList->addArrayToBranch($providerInfo, 'dodavatele');
        $inserted = $this->priceList->insertToAbraFlexi();
        if ($this->priceList->lastResponseCode == 201) {
            $result = intval($inserted[0]['id']);
            $this->priceList->addStatusMessage(
                sprintf(
                    _('PriceList item %s as %s'),
                    $invoiceItem['nazev'],
                    $this->priceList->getRecordIdent() . ' ' . $inserted[0]['ref']
                ),
                'success'
            );
        } else {
            $this->priceList->addStatusMessage(sprintf(
                _('PriceList item %s insertation failed'),
                $invoiceItem['nazev']
            ), 'error');
        }
        return $result;
    }

    /**
     * Obtain AbraFlexi AddressBook ID of suplier. If not exist create new one.
     *
     * @param array $invoiceSuplier
     * @return int Suplier AbraFlexi AddressBook ID
     */
    public function getSuplierAbraFlexiID($invoiceSuplier)
    {
        $suplierID = $this->abraFlexiSuplierPresence($invoiceSuplier);
        if (is_null($suplierID)) {
            $this->suplier->dataReset();
            $invoiceSuplier['poznam'] = _('Imported from mail');
            $this->suplier->takeData($invoiceSuplier);
            $inserted = $this->suplier->insertToAbraFlexi();
            if ($this->suplier->lastResponseCode == 201) {
                $this->suplier->addStatusMessage(
                    sprintf(
                        _('AddressBook item %s as %s'),
                        $invoiceSuplier['nazev'],
                        $this->suplier->url . $inserted[0]['ref']
                    ),
                    'success'
                );
                $suplierID = intval($inserted[0]['id']);
            } else {
                $this->suplier->addStatusMessage(sprintf(
                    _('AddressBook item %s insertation failed'),
                    $invoiceSuplier['nazev']
                ), 'error');
            }
        }
        return $suplierID;
    }

    /**
     * Obtain Payment info by Parsing ISDOC Dom
     *
     * @param DOMDocument $xmlDomDocument
     *
     * @return array
     */
    public function getPaymentMeans($xmlDomDocument)
    {
        return Convertor::domPaymentMeansToArray($xmlDomDocument->getElementsByTagName('PaymentMeans'));
    }

    /**
     * Obtain Suplier AbraFlexi AddressBook ID or NULL
     *
     * @param array $invoiceSuplier
     *
     * @return int Suplier AbraFlexi AddressBook ID
     */
    public function abraFlexiSuplierPresence($invoiceSuplier)
    {
        $suplierID = null;
        $suplierFound = $this->suplier->getColumnsFromAbraFlexi(
            ['id'],
            ['ic' => $invoiceSuplier['ic']]
        );
        if (array_key_exists(0, $suplierFound) && array_key_exists('id', $suplierFound[0])) {
            $suplierID = intval($suplierFound[0]['id']);
        }

        return $suplierID;
    }

    /**
     * Parse ISDOC invoice DOMDocument to
     *
     * @param DOMDocument $xmlDomDocument
     *
     * @return array of \AbraFlexi\FakturaPrijata properties
     */
    public function getInvoiceInfo($xmlDomDocument)
    {
        //Remove Branches - See https://bugs.php.net/bug.php?id=61858

        $element = $xmlDomDocument->documentElement;
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
        //$taxTotal = $this->domTaxTotalToArray($xmlDomDocument->getElementsByTagName('TaxTotal'));

        $element->removeChild($element->getElementsByTagName('TaxTotal')->item(0));
        //$legalMonetaryTotal = $this->domLMTotalToArray($xmlDomDocument->getElementsByTagName('LegalMonetaryTotal'));

        $element->removeChild($element->getElementsByTagName('LegalMonetaryTotal')->item(0));
        $element->removeChild($element->getElementsByTagName('PaymentMeans')->item(0));
        $invoiceInfo = Convertor::domInvoiceToArray($xmlDomDocument->getElementsByTagName('Invoice'));
//        return array_merge($invoiceInfo, $taxTotal);
        return $invoiceInfo;
    }

    /**
     * Convert Dom based invoice LegalMonetaryTotal Element to Array
     *
     * @param DOMNodeList $taxTotal
     *
     * @return array
     */
    public function domLMTotalToArray($taxTotal)
    {
        $lmTotalArray = [];
        $lmTotalArrayRaw = Convertor::domToArray($taxTotal->item(0));
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
     * Check for Invoice Presence in AbraFlexi
     *
     * @param FakturaPrijata $invoice
     * @return boolean TRUE for known invoice; FALSE for unknown invoice
     */
    public function isKnownInvoice($invoice)
    {
        $conditions['cisDosle'] = $invoice->getDataValue('cisDosle');
        try {
            $found = $invoice->getFlexiData('', $conditions);
        } catch (Exception $exc) {
            $this->addStatusMessage($exc->getMessage(), 'error');
        }
        return ($invoice->lastResponseCode == 200) && !empty($found);
    }

    /**
     * Unpack isdocx file
     *
     * @param string $filename path to .isdocx
     *
     * @return string|boolean extracted .isdoc
     */
    public function unpackIsdocX($filename)
    {
        $dir = sys_get_temp_dir() . '/';
        $zip = \zip_open($filename);
        if ($zip) {
            $unpackTo = $dir . basename($filename) . 'unzipped';
            if (!file_exists($unpackTo)) {
                mkdir($unpackTo);
                chmod($unpackTo, 0777);
            }
            while ($zip_entry = \zip_read($zip)) {
                if (\zip_entry_open($zip, $zip_entry, "r")) {
                    $buf = \zip_entry_read(
                        $zip_entry,
                        \zip_entry_filesize($zip_entry)
                    );
                    $unpacked = $unpackTo . "/" . \zip_entry_name($zip_entry);
                    $fp = fopen($unpacked, "w+");
                    chmod($unpackTo . "/" . \zip_entry_name($zip_entry), 0777);
                    fwrite($fp, $buf);
                    fclose($fp);
                    \zip_entry_close($zip_entry);
                    if (substr($unpacked, -6) == '.isdoc') {
                        $filename = $unpacked;
                    }
                } else {
                    return false;
                }
            }
            zip_close($zip);
        }
        return $filename;
    }

    /**
     * Check connection config
     *
     * @return boolean
     */
    public function isConnected()
    {
        $connectStatus = false;
        $companer = new Company();
        $companies = $companer->getFlexiData();
        if (isset($companies['company'])) {
            foreach ($companies['company'] as $company) {
                if ($company['dbNazev'] == constant('ABRAFLEXI_COMPANY')) {
                    $connectStatus = true;
                }
            }
        }
        return $connectStatus;
    }

    /**
     * Create AbraFlexi label
     */
    public function createLabel()
    {
        $stitek = new Stitek();
        $stitekData = [
            "kod" => "FLEXICEN",
            "nazev" => "FLEXICEN",
            "vsbAdr" => true,
            "vsbKatalog" => true,
            "vsbSkl" => true
        ];
        $stitekID = $stitek->getColumnsFromAbraFlexi('id', $stitekData);
        if (!isset($stitekID[0]['id'])) {
            $stitek->insertToAbraFlexi($stitekData);
        }
    }

    /**
     * Recount items prices to contain prices of nonstorage items
     *
     * @param array $invoiceItems
     * @return array
     */
    public function recountForPricelist($invoiceItems)
    {
        $storagePrices = (float) 0;
        $otherPrices = (float) 0;
        $topStoragePriceIndex = 0;
        $topStoragePriceValue = 0;
        $recountItems = [];
        $addPriceItems = [];
        foreach ($invoiceItems as $itemID => $itemData) {
            if (isset($itemData['sumCelkem'])) {
                if (array_search($itemData['nazev'], $this->storageBlacklist) !== false) {
                    $otherPrices += $itemData['sumCelkem'];
                    $recountItems[] = $itemData['nazev'];
                } else {
                    $sumCelkem = floatval($itemData['sumCelkem']);
                    if ($sumCelkem > $topStoragePriceValue) {
                        $topStoragePriceValue = $sumCelkem;
                        $topStoragePriceIndex = $itemID;
                    }
                    $addPriceItems[] = $itemData['nazev'];
                    $storagePrices += $sumCelkem;
                }
            }
        }

        if (count($recountItems)) {
            $addPrice = $otherPrices / $storagePrices;
            $this->addStatusMessage('Add ' . $addPrice . ' from [' . implode(
                ', ',
                $recountItems
            ) . '] to [' . implode(', ', $addPriceItems) . ']');
            foreach ($invoiceItems as $itemID => $itemData) {
                if (array_search($itemData['nazev'], $this->storageBlacklist) === false) {
                    $invoiceItems[$itemID] = $this->addRecountedPrice(
                        $itemData,
                        $addPrice
                    );
                }
            }
        }
        return $invoiceItems;
    }

    /**
     * Add Counted price to invoice item
     *
     * @param array $itemData
     * @return array
     */
    public function addRecountedPrice($itemData, $addPrice)
    {
        if (isset($itemData['sumCelkem'])) {
            $newPrice = floatval($itemData['sumCelkem']) + (floatval($itemData['sumCelkem']) * $addPrice);
            $itemData['cenaMjNakl'] = $newPrice;
            $itemData['cenaMjNeskl'] = $newPrice;
        }
        return $itemData;
    }

    /**
     * Configuration value
     *
     * @param string $key
     *
     * @return string
     */
    public function conf($key)
    {
        return array_key_exists($key, $this->configuration) ? $this->configuration[$key] : Functions::cfg($key);
    }

    /**
     *
     * @return boolean
     */
    public function checkSetup()
    {
        $storageStatus = $bankStatus = true;
        $bank = $this->conf('ABRAFLEXI_BANK');
        if (empty($bank)) {
            $this->addStatusMessage(_('Default bank account is not set'), 'warning');
        } else {
            $bankStatus = $this->checkBank(AF::code($bank));
            if ($bankStatus === false) {
                $this->addStatusMessage(sprintf(_('Default bank %s not exists'), $bank), 'error');
            }
        }

        $storage = $this->conf('ABRAFLEXI_STORAGE');
        if (empty($storage)) {
            $this->addStatusMessage(_('Default storage is not set'), 'warning');
        } else {
            $storageStatus = $this->checkStorage(AF::code($storage));
            if ($storageStatus === false) {
                $this->addStatusMessage(sprintf(_('Default storage %s not exists'), $storage), 'warning');
            }
        }

        return $storageStatus && $bankStatus;
    }

    /**
     * Check given storage avilbility
     *
     * @param string $storage
     *
     * @return boolean
     */
    public function checkStorage($storage)
    {
        $prober = new RO($storage, ['evidence' => 'sklad', 'ignore404' => true]);
        return $prober->lastResponseCode == 200;
    }

    /**
     * Check given bank account availbility
     *
     * @param string $bank
     *
     * @return boolean
     */
    public function checkBank($bank)
    {
        $prober = new RO($bank, ['evidence' => 'bankovni-ucet', 'ignore404' => true]);
        return $prober->lastResponseCode == 200;
    }
}
