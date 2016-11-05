<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    CouponsTable.php
 * @createdat   Oct 16, 2013 4:16:10 PM
 */

namespace KpasteCore\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\ResultSet\ResultSet;

class CouponsTable 
{
    private     $tableGateway;
    
    public function __construct( TableGateway $tableGateway )
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchCoupons( $where = null, $paginated = false )
    {
        $select             = new Select( 'coupons' );
        if( $where )        $select->where( $where );
        $select->order('count DESC');
        if( $paginated )
        {
            $resultset = new ResultSet();
            $resultset->setArrayObjectPrototype( new Coupons() );
            $pagAdapter = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultset
            );
            return new Paginator( $pagAdapter );
        }
        return $this->tableGateway->selectWith( $select );
    }
    
    public function saveCoupon( Coupons $coupon )
    {
        $data   = array(
            'couponid'   => preg_replace("/[^a-zA-Z0-9]+/", "", $coupon->couponid),
            'discount'   => (int)$coupon->discount,
            'count'      => (int)$coupon->count,
        );
        
        if( $this->fetchCoupons(array('couponid' => $data['couponid']))->count() )
        {
            $this->tableGateway->update( $data, array('couponid' => $data['couponid']) );
        }
        else
        {
            $this->tableGateway->insert( $data );
        }
    }
    
    public function couponUsed($couponid, $userid)
    {
        $coupon = $this->fetchCoupons(array('couponid' => $couponid))->current();
        if($coupon->count > 0)
        {
            $coupon->count--;
            $this->saveCoupon($coupon);
            
            $userid = (int)$userid;
            $couponid = preg_replace("/[^a-zA-Z0-9]+/", "", $couponid);
            $query = "INSERT INTO `advertisers_used_coupons` (`couponid`, `userid`) VALUES('$couponid', $userid);";
            $this->tableGateway->adapter->query($query, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
            return true;
        }

        return false;
    }
    
    public function deleteCoupon( $couponid )
    {
        $this->tableGateway->delete( array( 'couponid' => $couponid ) );
    }
}

?>
