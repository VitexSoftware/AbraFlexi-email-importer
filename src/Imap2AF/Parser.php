<?php

/**
 * Imap2AbraFlexi ISDOC Parser
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019-2020 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

/**
 * Description of Importer
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Parser extends \Ease\Sand {

    /**
     * XML Loader
     * @var \Lightools\Xml\XmlLoader
     */
    public $loader;

    /**
     * XML Data as DOM
     * @var DOMDocument 
     */
    protected $xmlDomDocument;

    /**
     * 
     * @param string filen to load
     */
    public function __construct($init = null) {
        $this->loader = new \Lightools\Xml\XmlLoader();
        if (!empty($init) && file_exists($init)) {
            $this->loadFile($init);
        }
    }

    /**
     * CleanUP processed inputfile
     *
     * @param array $invoiceFiles
     */
    public function cleanUp($invoiceFiles) {
        foreach ($invoiceFiles as $invoiceID => $invoiceExtID) {

            $fileToDelete = $this->invoiceFiles[$invoiceExtID];

            if (unlink($fileToDelete)) {
                $this->addStatusMessage(sprintf('Invoice %s file %s deleted',
                                $invoiceExtID, $this->invoiceFiles[$invoiceExtID]));
            } else {
                $this->addStatusMessage(sprintf('Invoice %s file %s delete failed',
                                $invoiceExtID, $this->invoiceFiles[$invoiceExtID]),
                        'warning');
            }

            $dirToDelete = dirname($fileToDelete);
            $pdfToDelete = $dirToDelete . '/' . str_replace('.isdoc', '.pdf',
                            basename($fileToDelete));

            if (file_exists($pdfToDelete)) {
                if (unlink($pdfToDelete)) {
                    $this->addStatusMessage(sprintf('Invoice %s file %s deleted',
                                    $invoiceExtID, $pdfToDelete));
                    rmdir($dirToDelete);
                } else {
                    $this->addStatusMessage(sprintf('Invoice %s file %s delete failed',
                                    $invoiceExtID, $pdfToDelete), 'warning');
                }
            }


            if (isset($this->invoiceFiles[$invoiceExtID . 'x'])) {
                if (unlink($this->invoiceFiles[$invoiceExtID . 'x'])) {
                    $this->addStatusMessage(sprintf('Original Invoice %s file %s deleted',
                                    $invoiceExtID,
                                    $this->invoiceFiles[$invoiceExtID . 'x']));
                } else {
                    $this->addStatusMessage(sprintf('Original Invoice %s file %s delete failed',
                                    $invoiceExtID,
                                    $this->invoiceFiles[$invoiceExtID . 'x']), 'warning');
                }
            }
        }
    }

    /**
     * Convert DOM to Array
     *
     * @param \DOMNode $root
     * 
     * @return array
     */
    public static function domToArray($root) {
        $result = array();

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == constant('XML_TEXT_NODE')) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1 ? $result['_value'] : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = self::domToArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = self::domToArray($child);
                }
            }
        }

        return $result;
    }

    /**
     * Unpack isdocx file
     *
     * @param string $filename path to .isdocx
     * 
     * @return string extracted .isdoc
     */
    public function unpackIsdocX($filename) {
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
                    $buf = \zip_entry_read($zip_entry,
                            \zip_entry_filesize($zip_entry));
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
     * Load ISDOC or ISDOCx File
     *
     * @param string $inputFile real filename on disk
     * 
     * @return boolean parsing status
     */
    public function loadFile($inputFile) {
        $this->addStatusMessage('loading: ' . $inputFile, 'debug');
        return pathinfo($inputFile, PATHINFO_EXTENSION) == 'isdocx' ? $this->loadISDOCx($inputFile) : $this->loadISDOC($inputFile); //TODO: Check Mime  
    }

    /**
     * Load ISDOCx File
     *
     * @param string $inputFile real filename on disk
     * 
     * @return boolean parsing status
     */
    public function loadISDOCx($inputFile) {
        return $this->loadISDOC($this->unpackIsdocX($inputFile));
    }

    /**
     * Load ISDOC File
     *
     * @param string $filrPath real filename on disk
     * 
     * @return boolean parsing status
     */
    public function loadISDOC($filename) {
        $this->xmlDomDocument = $this->loader->loadXml(file_get_contents($filename));
        return $this->xmlDomDocument->hasChildNodes();
    }

    /**
     * Current ISDOC as DOM object
     * 
     * @return \DOMDocument
     */
    public function getXmlDomDocument() {
        return $this->xmlDomDocument;
    }

}
