<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    ThumbsTable.php
 * @createdat    Aug 4, 2013 2:27:14 PM
 */

namespace Paster\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

class ThumbsTable 
{
    public $adapter;
    
    public function __construct(Adapter $adapter) 
    {
        $this->adapter = $adapter;
    }
    
    public function thumbsCount( $pasteid, $vote = null )  
    {
        $select         = new Select( 'thumbs' );
        $select         ->columns(array('pasteid', 'thumbs_up_count' => new Expression('COUNT("pasteid")')));
        if( !$vote )
        {
            $select     ->where( array(
                'pasteid'   => $pasteid,
            ) );            
        }
        else
        {
            $select     ->where( array(
                'pasteid'   => $pasteid,
                'vote'      => $vote,
            ) );
        }
        $select         ->group('thumbs.pasteid');
        $sql            = new Sql( $this->adapter );
        $query          = $sql->getSqlStringForSqlObject( $select );
        $result         = $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE )->current();
        return( (int)$result['thumbs_up_count'] );
    }
    
    public function insertThumb($pasteid, $ip, $vote)
    {
        $pasteid = (int)$pasteid;
        $sql = new Sql( $this->adapter );
        $insert = $sql->insert( 'thumbs' );
        $insert->columns( array( 'pasteid', 'voterip', 'vote' ) );
        $insert->values( array( $pasteid, $ip, $vote ) );
        $query = $sql->getSqlStringForSqlObject( $insert );
        $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
    }
    
    public function deleteThumbs($where)
    {
        $sql = new Sql( $this->adapter );
        $delete = $sql->delete( 'thumbs' );
        $delete->where( $where );
        $query = $sql->getSqlStringForSqlObject( $delete );
        $this->adapter->query( $query, Adapter::QUERY_MODE_EXECUTE );
    }
}

?>
