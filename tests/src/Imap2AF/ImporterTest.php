<?php

namespace Test\AbraFlexi\Imap2AF;

use AbraFlexi\Imap2AF\Importer;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2020-12-02 at 18:09:08.
 */
class ImporterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var Importer
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Importer('test');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::importIsdocFiles
     * @todo   Implement testimportIsdocFiles().
     */
    public function testimportIsdocFiles()
    {
        $this->assertEquals('', $this->object->importIsdocFiles());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::xmlDomToInvoice
     * @todo   Implement testxmlDomToInvoice().
     */
    public function testxmlDomToInvoice()
    {
        $this->assertEquals('', $this->object->xmlDomToInvoice());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::invoiceItems
     * @todo   Implement testinvoiceItems().
     */
    public function testinvoiceItems()
    {
        $this->assertEquals('', $this->object->invoiceItems());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::domInvoiceItemToArray
     * @todo   Implement testdomInvoiceItemToArray().
     */
    public function testdomInvoiceItemToArray()
    {
        $this->assertEquals('', $this->object->domInvoiceItemToArray());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::importInvoiceItems
     * @todo   Implement testimportInvoiceItems().
     */
    public function testimportInvoiceItems()
    {
        $this->assertEquals('', $this->object->importInvoiceItems());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::importInvoice
     * @todo   Implement testimportInvoice().
     */
    public function testimportInvoice()
    {
        $this->assertEquals('', $this->object->importInvoice());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::isForMe
     * @todo   Implement testisForMe().
     */
    public function testisForMe()
    {
        $this->assertEquals('', $this->object->isForMe());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::mainLoop
     * @todo   Implement testmainLoop().
     */
    public function testmainLoop()
    {
        $this->assertEquals('', $this->object->mainLoop());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::handleMeasureUnit
     * @todo   Implement testhandleMeasureUnit().
     */
    public function testhandleMeasureUnit()
    {
        $this->assertEquals('', $this->object->handleMeasureUnit());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::addItemToStorage
     * @todo   Implement testaddItemToStorage().
     */
    public function testaddItemToStorage()
    {
        $this->assertEquals('', $this->object->addItemToStorage());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::cleanUp
     * @todo   Implement testcleanUp().
     */
    public function testcleanUp()
    {
        $this->assertEquals('', $this->object->cleanUp());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::abraFlexiPricelistPresence
     * @todo   Implement testabraFlexiPricelistPresence().
     */
    public function testabraFlexiPricelistPresence()
    {
        $this->assertEquals('', $this->object->abraFlexiPricelistPresence());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::addItemToPriceList
     * @todo   Implement testaddItemToPriceList().
     */
    public function testaddItemToPriceList()
    {
        $this->assertEquals('', $this->object->addItemToPriceList());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::getSuplierAbraFlexiID
     * @todo   Implement testgetSuplierAbraFlexiID().
     */
    public function testgetSuplierAbraFlexiID()
    {
        $this->assertEquals('', $this->object->getSuplierAbraFlexiID());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::getPaymentMeans
     * @todo   Implement testgetPaymentMeans().
     */
    public function testgetPaymentMeans()
    {
        $this->assertEquals('', $this->object->getPaymentMeans());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::abraFlexiSuplierPresence
     * @todo   Implement testabraFlexiSuplierPresence().
     */
    public function testabraFlexiSuplierPresence()
    {
        $this->assertEquals('', $this->object->abraFlexiSuplierPresence());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::getInvoiceInfo
     * @todo   Implement testgetInvoiceInfo().
     */
    public function testgetInvoiceInfo()
    {
        $this->assertEquals('', $this->object->getInvoiceInfo());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::domLMTotalToArray
     * @todo   Implement testdomLMTotalToArray().
     */
    public function testdomLMTotalToArray()
    {
        $this->assertEquals('', $this->object->domLMTotalToArray());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::isKnownInvoice
     * @todo   Implement testisKnownInvoice().
     */
    public function testisKnownInvoice()
    {
        $this->assertEquals('', $this->object->isKnownInvoice());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::unpackIsdocX
     * @todo   Implement testunpackIsdocX().
     */
    public function testunpackIsdocX()
    {
        $this->assertEquals('', $this->object->unpackIsdocX());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::isConnected
     * @todo   Implement testisConnected().
     */
    public function testisConnected()
    {
        $this->assertEquals('', $this->object->isConnected());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::createLabel
     * @todo   Implement testcreateLabel().
     */
    public function testcreateLabel()
    {
        $this->assertEquals('', $this->object->createLabel());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::recountForPricelist
     * @todo   Implement testrecountForPricelist().
     */
    public function testrecountForPricelist()
    {
        $this->assertEquals('', $this->object->recountForPricelist());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::addRecountedPrice
     * @todo   Implement testaddRecountedPrice().
     */
    public function testaddRecountedPrice()
    {
        $this->assertEquals('', $this->object->addRecountedPrice());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::conf
     * @todo   Implement testconf().
     */
    public function testconf()
    {
        $this->assertEquals('', $this->object->conf());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::checkSetup
     */
    public function testcheckSetup()
    {
        $this->assertTrue($this->object->checkSetup());
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::checkStorage
     * @todo   Implement testcheckStorage().
     */
    public function testcheckStorage()
    {
        $this->assertEquals('', $this->object->checkStorage());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Imap2AF\Importer::checkBank
     * @todo   Implement testcheckBank().
     */
    public function testcheckBank()
    {
        $this->assertEquals('', $this->object->checkBank());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
