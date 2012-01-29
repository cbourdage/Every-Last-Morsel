<?php
namespace SFTest\Model;

use Mockery as m,
    Storefront\Model as StorefrontModel;

/**
 * @group migration
 */
class CartTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SF_Model_Interface
     */
    protected $_model;
    
    protected function setUp()
    {
        $this->_model = new StorefrontModel\Cart();
    }
    
    protected function tearDown()
    {
        $this->_model->getStorage()->getManager()->getStorage()->clear();
        $this->_model = null;
        m::close();
    }

    protected function getProductMock()
    {
        $product = m::mock('Storefront\\Model\\Resource\\Product\\Product');
        $product->productId = 1;
        $product->categoryId = 2;
        $product->ident = 'Product-1';
        $product->name = 'Product 1';
        $product->description = str_repeat('Product 1 is great..', 10);
        $product->shortDescription = 'Product 1 is great..';
        $product->discountPercent = 0;
        $product->taxable = 'Yes';
        $product->deliveryMethod = 'Mail';
        $product->stockStatus = 'InStock';

        $product->shouldReceive('getPrice')->andReturn(10.00);

        return $product;
    }

    public function test_Cart_Is_Countable()
    {
        $this->assertEquals(0, count($this->_model));
    }

    public function test_Cart_Can_Add_An_Item()
    {
        $product = $this->getProductMock();

        $product2 = clone $product;
        $product2->productId = 2;

        $product3 = clone $product;
        $product3->productId = 3;

        $cartItem = $this->_model->addItem($product, 10);
        $cartItem = $this->_model->addItem($product, 5);
        $cartItem = $this->_model->addItem($product2, 10);
        $cartItem = $this->_model->addItem($product3, 10);

        // check return type and amount of items
        $this->assertType('Storefront\\Model\\Resource\\CartItem', $cartItem);
        $this->assertEquals(3, count($this->_model));

        // have items been corretly added
        $this->assertEquals(1, $this->_model[1]->productId);
        $this->assertEquals(2, $this->_model[2]->productId);
        $this->assertEquals(3, $this->_model[3]->productId);

        // adding same should update item
        $this->assertEquals(5, $this->_model[1]->qty);
    }

    public function test_Cart_Can_Remove_Item()
    {
        $product = $this->getProductMock();

        //remove using int
        $this->_model->addItem($product, 10);

        $this->assertEquals(1, count($this->_model));
        $this->_model->removeItem(1);
        $this->assertEquals(0, count($this->_model));

        //remove using object
        $this->_model->addItem($product, 10);
        $this->assertEquals(1, count($this->_model));
        $this->_model->removeItem($product);
        $this->assertEquals(0, count($this->_model));
    }

    public function test_Cart_Returns_The_Correct_Session_Namespace()
    {
        $this->_model = new StorefrontModel\Cart();
        $ns = $this->_model->getStorage();
        $this->assertType('Zend\\Session\\Container', $ns);
        $this->assertEquals('CartStorage', $ns->getName());
    }

    public function test_Cart_Saves_Data_To_Session()
    {
        $product = $this->getProductMock();

        $this->_model = new StorefrontModel\Cart();

        $this->_model->addItem($product, 10);
        $this->assertType('array', $this->_model->getStorage()->items);
    }

    public function test_Cart_Totals_Should_Be_Zero_On_New()
    {
        $this->assertEquals(0, $this->_model->getSubTotal());
        $this->assertEquals(0, $this->_model->getTotal());
    }

    public function test_Cart_Line_Item_Calculations()
    {
        $product = $this->getProductMock();

        $product2 = clone $product;
        $product2->productId = 2;
        $product2->discountPercent = 50;

        $product3 = clone $product;
        $product3->productId = 3;
        $product3->taxable = 'No';

        $cartItem = $this->_model->addItem($product, 2);
        $cartItem = $this->_model->addItem($product2, 1);
        $cartItem = $this->_model->addItem($product3, 1);

        // tax 15% and qty
        $this->assertEquals((11.50*2), $this->_model[1]->getLineCost());

        // tax 15% and 50% discount
        $this->assertEquals((11.50 / 2), $this->_model[2]->getLineCost());

        // no tax no discount
        $this->assertEquals(10, $this->_model[3]->getLineCost());
    }

    public function test_Cart_Total_Calculations()
    {
        $product = $this->getProductMock();

        $product2 = clone $product;
        $product2->productId = 2;
        $product2->discountPercent = 50;

        $product3 = clone $product;
        $product3->productId = 3;
        $product3->taxable = 'No';

        $cartItem = $this->_model->addItem($product, 2);
        $cartItem = $this->_model->addItem($product2, 1);
        $cartItem = $this->_model->addItem($product3, 1);

        $this->assertEquals((11.50*2) + (11.50/2) + 10, $this->_model->getSubTotal());
        $this->assertEquals((11.50*2) + (11.50/2) + 10, $this->_model->getTotal());

        $this->_model->setShippingCost(20.00);
        $this->assertEquals((11.50*2) + (11.50/2) + 10 + 20, $this->_model->getTotal());
    }

    public function test_Cart_Should_Not_Accept_Negative_Qty()
    {
        $product = $this->getProductMock();
        $cartItem = $this->_model->addItem($product, -10);
        $cartItem = $this->_model->addItem($product, 0);

        $this->assertEquals(0, count($this->_model));
    }

    public function test_Cart_Should_Remove_Item_If_Added_With_Zero_Qty()
    {
        $product = $this->getProductMock();
        $cartItem = $this->_model->addItem($product, 1);
        $cartItem = $this->_model->addItem($product, 0);

        $this->assertEquals(0, count($this->_model));
    }
}