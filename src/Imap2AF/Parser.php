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
 * Description of Importer.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Parser extends \Ease\Sand
{
    /**
     * XML Loader.
     */
    public \Lightools\Xml\XmlLoader $loader;

    /**
     * XML Data as DOM.
     */
    protected \DOMDocument $xmlDomDocument;

    /**
     * Keep track of invoice files.
     *
     * @var array<string>
     */
    private array $invoiceFiles = [];

    /**
     * ISDOC Parser.
     *
     * @param string $init file to load
     */
    public function __construct($init = '')
    {
        $this->setObjectName();
        $this->loader = new \Lightools\Xml\XmlLoader();

        if (!empty($init) && file_exists($init)) {
            $this->loadFile($init);
        }
    }

    /**
     * CleanUP processed inputfile.
     *
     * @param array<string> $invoiceFiles
     */
    public function cleanUp(array $invoiceFiles): void
    {
        foreach ($invoiceFiles as $invoiceID => $invoiceExtID) {
            $fileToDelete = $this->invoiceFiles[$invoiceExtID];

            if (unlink($fileToDelete)) {
                $this->addStatusMessage(sprintf(
                    'Invoice %s file %s deleted',
                    $invoiceExtID,
                    $this->invoiceFiles[$invoiceExtID],
                ));
            } else {
                $this->addStatusMessage(
                    sprintf(
                        'Invoice %s file %s delete failed',
                        $invoiceExtID,
                        $this->invoiceFiles[$invoiceExtID],
                    ),
                    'warning',
                );
            }

            $dirToDelete = \dirname($fileToDelete);
            $pdfToDelete = $dirToDelete.'/'.str_replace(
                '.isdoc',
                '.pdf',
                basename($fileToDelete),
            );

            if (file_exists($pdfToDelete)) {
                if (unlink($pdfToDelete)) {
                    $this->addStatusMessage(sprintf(
                        'Invoice %s file %s deleted',
                        $invoiceExtID,
                        $pdfToDelete,
                    ));
                    rmdir($dirToDelete);
                } else {
                    $this->addStatusMessage(sprintf(
                        'Invoice %s file %s delete failed',
                        $invoiceExtID,
                        $pdfToDelete,
                    ), 'warning');
                }
            }

            if (isset($this->invoiceFiles[$invoiceExtID.'x'])) {
                if (unlink($this->invoiceFiles[$invoiceExtID.'x'])) {
                    $this->addStatusMessage(sprintf(
                        'Original Invoice %s file %s deleted',
                        $invoiceExtID,
                        $this->invoiceFiles[$invoiceExtID.'x'],
                    ));
                } else {
                    $this->addStatusMessage(sprintf(
                        'Original Invoice %s file %s delete failed',
                        $invoiceExtID,
                        $this->invoiceFiles[$invoiceExtID.'x'],
                    ), 'warning');
                }
            }
        }
    }

    /**
     * Convert DOM to Array.
     *
     * @return array<mixed>|string parsed values
     */
    public static function domToArray(\DOMNode $root)
    {
        $result = [];

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;

            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;

            if ($children->length === 1) {
                $child = $children->item(0);

                if ($child->nodeType === \constant('XML_TEXT_NODE')) {
                    $result['_value'] = $child->nodeValue;

                    return \count($result) === 1 ? $result['_value'] : $result;
                }
            }

            $groups = [];

            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = self::domToArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = [$result[$child->nodeName]];
                        $groups[$child->nodeName] = 1;
                    }

                    $result[$child->nodeName][] = self::domToArray($child);
                }
            }
        }

        return empty($result) ? '' : $result;
    }

    /**
     * Unpack isdocx file.
     *
     * @param string $filename path to .isdocx
     *
     * @return string extracted .isdoc
     */
    public function unpackIsdocX(string $filename): string
    {
        $unpackto = '';
        $dir = sys_get_temp_dir().'/';
        $zip = new \ZipArchive();

        if ($zip->open($filename) === true) {
            $unpackTo = $dir.basename($filename).'unzipped/';

            if (!file_exists($unpackTo)) {
                mkdir($unpackTo);
                chmod($unpackTo, 0o777);
            }

            $zip->extractTo($unpackTo);

            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $unpacked = $zip->getNameIndex($i);

                if (substr($unpacked, -6) === '.isdoc') {
                    $filename = $unpacked;
                }
            }

            $zip->close();
            $unpacked = $unpackTo.$filename;
        } else {
            throw new \Ease\Exception(_('Error unpacking archive').': '.$filename);
        }

        return $unpacked;
    }

    /**
     * Load ISDOC or ISDOCx File.
     *
     * @param string $inputFile real filename on disk
     *
     * @return bool parsing status
     */
    public function loadFile($inputFile)
    {
        $this->addStatusMessage('loading: '.$inputFile, 'debug');

        return pathinfo($inputFile, \PATHINFO_EXTENSION) === 'isdocx' ? $this->loadISDOCx($inputFile) : $this->loadISDOC($inputFile); // TODO: Check Mime
    }

    /**
     * Load ISDOCx File.
     *
     * @param string $inputFile real filename on disk
     *
     * @return bool parsing status
     */
    public function loadISDOCx($inputFile)
    {
        return $this->loadISDOC($this->unpackIsdocX($inputFile));
    }

    /**
     * Load ISDOC File.
     *
     * @param string $filename real filename on disk
     *
     * @return bool parsing status
     */
    public function loadISDOC(string $filename): bool
    {
        $this->xmlDomDocument = $this->loader->loadXml(file_get_contents($filename));

        return $this->xmlDomDocument->hasChildNodes();
    }

    /**
     * Current ISDOC as DOM object.
     */
    public function getXmlDomDocument(): \DOMDocument
    {
        return $this->xmlDomDocument;
    }
}
