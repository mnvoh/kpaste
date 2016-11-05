<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    NewsTable.php
 * @createdat    Aug 5, 2013 4:16:10 PM
 */

namespace KpasteCore\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\ResultSet\ResultSet;

class NewsTable 
{
    private     $tableGateway;
    
    public function __construct( TableGateway $tableGateway )
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchNews( $limit = false, $where = null, $paginated = false )
    {
        $select             = new Select( 'news' );
        if( $limit )        $select->limit( $limit );
        if( $where )        $select->where( $where );
        $select->order('newsdate DESC');
        if( $paginated )
        {
            $resultset = new ResultSet();
            $resultset->setArrayObjectPrototype( new News() );
            $pagAdapter = new DbSelect(
                $select,
                $this->tableGateway->getAdapter(),
                $resultset
            );
            return new Paginator( $pagAdapter );
        }
        return $this->tableGateway->selectWith( $select );
    }
    
    public function saveNews( News $news )
    {
        $data   = array(
            'userid'    => $news->userid,
            'title'     => $news->title,
            'news'      => $news->news,
            'newsdate'  => $news->newsdate,
        );
        
        $newsid     = (int)$news->newsid;
        
        if( $newsid )
        {
            $this->tableGateway->update( $data, array( 'newsid' => $newsid ) );
        }
        else
        {
            $this->tableGateway->insert( $data );
        }
    }
    
    public function deleteNews( $newsid )
    {
        $this->tableGateway->delete( array( 'newsid' => $newsid ) );
    }
}

?>
