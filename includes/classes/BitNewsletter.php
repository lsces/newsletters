<?php
/**
 * $Header$
 *
 * @copyright (c) 2004 bitweaver.org
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id$
 *
 * Virtual base class (as much as one can have such things in PHP) for all
 * derived tikiwiki classes that require database access.
 * @package newsletters
 *
 * @date created 2004/10/20
 *
 * @author drewslater <andrew@andrewslater.com>, spiderr <spider@steelsun.com>
 *
 * @version $Revision$
 */

/**
 * required setup
 */
namespace Bitweaver\Newsletters;
use Bitweaver\BitBase;
use Bitweaver\KernelTools;
use Bitweaver\Liberty\LibertyContent;
use Bitweaver\Users\RoleUser;

define( 'BITNEWSLETTER_CONTENT_TYPE_GUID', 'bitnewsletter' );

/**
 * @package newsletters
 */
class BitNewsletter extends LibertyContent {

	public $mNewsletterId;

	public function __construct( $pNlId=NULL, $pContentId=NULL ) {
		parent::__construct();
		$this->registerContentType( BITNEWSLETTER_CONTENT_TYPE_GUID, [
			'content_type_guid' => BITNEWSLETTER_CONTENT_TYPE_GUID,
			'content_name'      => 'Newsletter',
			'handler_class'     => 'BitNewsletter',
			'handler_package'   => 'newsletters',
			'handler_file'      => 'BitNewsletter.php',
			'maintainer_url'    => 'http://www.bitweaver.org',
		] );
		$this->mNewsletterId = $this->verifyId( $pNlId ) ? $pNlId : NULL;
		$this->mContentId = $pContentId;
		$this->mContentTypeGuid = BITNEWSLETTER_CONTENT_TYPE_GUID;

		// Permission setup
		//$this->mViewContentPerm  = '';
		$this->mUpdateContentPerm  = 'p_newsletters_create';
		$this->mAdminContentPerm = 'p_newsletters_admin';
	}

	public function load( $pContentId = NULL, $pPluginParams = NULL ): void {
		if( $this->verifyId( $this->mNewsletterId ) || $this->verifyId( $this->mContentId ) ) {
			global $gBitSystem;

			$bindVars = []; $selectSql = ''; $joinSql = ''; $whereSql = '';

			$lookupColumn = $this->verifyId( $this->mNewsletterId ) ? 'nl_id' : 'content_id';
			$bindVars[] = $this->verifyId( $this->mNewsletterId )? $this->mNewsletterId : $this->mContentId;

			$this->getServicesSql( 'content_load_function', $selectSql, $joinSql, $whereSql, $bindVars );

/*			if( $pUserId ) {
				error_log( 'BitNewsleters: user id loading not implemented yet' );
				$whereSql = "";
				$joinSql = "";
			}
*/
			$query = "SELECT * $selectSql
					  FROM `".BIT_DB_PREFIX."newsletters` n
					  	INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON( n.`content_id`=lc.`content_id` )
					  	$joinSql
					  WHERE n.`$lookupColumn`=? $whereSql";
			$result = $this->mDb->query($query,$bindVars);
			if ($result->numRows()) {
				$this->mInfo = $result->fetchRow();
				$this->mNewsletterId = $this->mInfo['nl_id'];
				$this->mContentId = $this->mInfo['content_id'];
			}
		}
	}

	public function loadEditions() {
		if( $this->isValid() ) {
			$this->mEditions = $this->getEditions();
		}
	}

	public function store( &$pParamHash ): bool { //$nl_id, $name, $description, $allow_user_sub, $allow_any_sub, $unsub_msg, $validate_addr) {
		if( $this->verify( $pParamHash ) ) {
			$this->mDb->StartTrans();
			if( parent::store( $pParamHash ) ) {
				if( $this->mNewsletterId ) {
					$result = $this->mDb->associateUpdate( BIT_DB_PREFIX."newsletters", $pParamHash['newsletter_store'], array ( "nl_id" => $this->mNewsletterId ) );
				} else {
					$pParamHash['newsletter_store']['content_id'] = $pParamHash['content_id'];
					$result = $this->mDb->associateInsert( BIT_DB_PREFIX."newsletters", $pParamHash['newsletter_store'] );
				}
				$this->mDb->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

	public function verify( &$pParamHash ): bool {
		// It is possible a derived class set this to something different
		if( empty( $pParamHash['content_type_guid'] ) ) {
			$pParamHash['content_type_guid'] = $this->mContentTypeGuid;
		}
		$pParamHash['newsletter_store']["allow_user_sub"] = (isset($pParamHash["allow_user_sub"]) && $pParamHash["allow_user_sub"] == 'on') ? 'y' : 'n';
		$pParamHash['newsletter_store']["allow_any_sub"] = (isset($pParamHash["allow_any_sub"]) && $pParamHash["allow_any_sub"] == 'on') ? 'y': 'n';
		$pParamHash['newsletter_store']["unsub_msg"] = (isset($pParamHash["unsub_msg"]) && $pParamHash["unsub_msg"] == 'on') ? 'y' : 'n';
		$pParamHash['newsletter_store']["validate_addr"] = (isset($pParamHash["validate_addr"]) && $pParamHash["validate_addr"] == 'on') ? 'y' : 'n';
		return( count( $this->mErrors ) == 0 );
	}

	public function getSubscriberInfo( $pLookup ) {
		$ret = [];
		if( $this->isValid() ) {
			$bindVars = [];
			$whereSql = '';
			if( !empty( $pLookup['email'] ) ) {
				$whereSql .= ' AND `email`=? ` ';
				$bindVars[] = $pLookup['email'];
			}
			if( !empty( $pLookup['user_id'] ) ) {
				$whereSql .= ' AND `user_id`=? ` ';
				$bindVars[] = $pLookup['user_id'];
			}
			$whereSql = preg_replace( '/^[\s]AND/', '', $whereSql );
			$query = "SELECT `content_id` AS `hash_key`, ms.* from `".BIT_DB_PREFIX."mail_subscriptions` ms WHERE $whereSql ";
			if( $res = $this->mDb->query( $query, $bindVars ) ) {
				$ret = $res->GetAssoc();
			}
		}
		return $ret;
	}

	public function getSubscribers( $pAll=FALSE) {
		$ret = [];
		if( $this->isValid() ) {
			$whereSql = $pAll ? '' : '  `unsubscribe_date` is NULL AND ';
			$query = "select * from `".BIT_DB_PREFIX."mail_subscriptions` WHERE $whereSql `content_id`=?";
			if( $res = $this->mDb->query( $query, [ $this->mContentId ] ) ) {
				$ret = $res->GetRows();
			}
		}
		return $ret;
	}

	public function removeSubscription( $email, $notify = FALSE, $del_record = FALSE ) {
		if ($del_record) {
			$this->mDb->query("DELETE FROM `".BIT_DB_PREFIX."mail_subscriptions` WHERE `content_id`=? AND `email`=?", [ $this->mContentId, $email ]);
		} else {
			$urlCode = $this->mDb->getOne("select `sub_code` from `".BIT_DB_PREFIX."mail_subscriptions` where `content_id`=? and `email`=?", [ $this->mContentId, $email ]);
			$this->unsubscribe($urlCode, $notify);
		}
	}

	public function subscribe( $pSubscribeHash ) { // $notify = FALSE, $remind = FALSE ) {
		$ret = FALSE;
		if( $this->isValid() ) {
			global $gBitSystem;
			global $gBitSmarty;
			global $gBitUser;

			// Check for duplicates
			$all_subs = $this->getSubscribers( TRUE );
			$duplicate = FALSE;
			foreach($all_subs as $sub) {
				if( $sub['email'] == $pSubscribeHash['email'] ) {
					$duplicate = TRUE;
					$urlCode = $sub['sub_code'];
				} elseif( !empty( $pSubscribeHash['user_id'] ) && $sub['user_id'] == $pSubscribeHash['user_id'] ) {
				}
			}

			$urlCode = (!$duplicate) ? md5( RoleUser::genPass() ) : $urlCode;
			$now = date("U");
			// Generate a code and store it and send an email  with the
			// URL to confirm the subscription put valid as 'n'
			if (!$duplicate) {
				if( @BitBase::verifyId( $pSubscribeHash['user_id'] ) ) {
					// we have user_id subscribing, use the id, NULL the email
					$subUserId = $pSubscribeHash['user_id'];
					$subEmail = NULL;
				} else {
					// we have user_id subscribing, use the id, NULL the email
					$subUserId = NULL;
					$subEmail = $pSubscribeHash['email'];
				}
				$query = "insert into `".BIT_DB_PREFIX."mail_subscriptions` (`content_id`, `user_id`, `email`,`sub_code`,`is_valid`,`subscribed_date`) VALUES (?,?,?,?,?,?)";
				$result = $this->mDb->query( $query, [ $this->mContentId, $subUserId, $subEmail, $urlCode, 'n', (int) $now ] );
			}
			if( ( !empty( $pSubscribeHash['notify'] ) && $this->getField( 'validate_addr' ) == 'y') || !empty( $pSubscribeHash['remind'] ) ) {
				// Generate a code and store it and send an email  with the
				$gBitSmarty->assign( 'sub_code', $urlCode );
				$mail_data = $gBitSmarty->fetch('bitpackage:newsletters/confirm_newsletter_subscription.tpl');
				@mail($subEmail, KernelTools::tra('Newsletter subscription information at') . ' ' . $gBitSystem->getConfig( "bitmailer_from" ), $mail_data,
					"From: " . $gBitSystem->getConfig( "sender_email" ) . "\r\nContent-type: text/plain;charset=utf-8\r\n");
			}
			$ret = TRUE;
		}
		return $ret;
	}

	public function unsubscribe( $pMixed, $notify = TRUE ) {
		global $gBitSystem;
		global $gBitSmarty;
		global $gBitUser;

		$ret = FALSE;
		$now = date("U");

		if( is_numeric( $pMixed ) ) {
			$query = "SELECT `content_id` FROM `".BIT_DB_PREFIX."newsletters` WHERE `nl_id`=?";
			if( $subRow['content_id'] = $this->mDb->getOne( $query, [ $pMixed ] ) ) {
				$subRow['col_name'] = 'user_id';
				$subRow['col_val'] = $gBitUser->mUserId;
			}
		} elseif( is_string( $pMixed ) ) {
			$query = "SELECT * FROM `".BIT_DB_PREFIX."mail_queue` WHERE `url_code`=?";
			if( $subRow = $this->mDb->getRow( $query, [ $pMixed ] ) ) {
				$subRow['col_name'] = !empty( $subRow['user_id'] ) ? 'user_id' : 'email';
				$subRow['col_val'] = !empty( $subRow['user_id'] ) ? $subRow['user_id'] : $subRow['email'];
			}
		}

		if( !empty( $subRow ) ) {
			$this->mContentId = $subRow['content_id'];
			$this->load();
			if( $this->mDb->getRow( "SELECT * FROM `".BIT_DB_PREFIX."mail_subscriptions` WHERE `$subRow[col_name]`=?", [ $subRow['col_val'] ] ) ) {
				$query = "UPDATE `".BIT_DB_PREFIX."mail_subscriptions` SET `unsubscribe_date`=?, `content_id`=? WHERE `$subRow[col_name]`=? AND `unsubscribe_date` IS NULL";
			} else {
				$query = "INSERT INTO `".BIT_DB_PREFIX."mail_subscriptions` (`unsubscribe_date`,`content_id`,`$subRow[col_name]`) VALUES(?,?,?)";
			}
			$result = $this->mDb->query( $query, [ $now, $subRow['content_id'], $subRow['col_val'] ] );
			if( $notify ) {
				// Now send a bye bye email
				$gBitSmarty->assign('sub_code', $subRow["sub_code"]);
				$mail_data = $gBitSmarty->fetch('bitpackage:newsletters/newsletter_byebye.tpl');
				@mail($subRow["email"], KernelTools::tra('Thank you from') . ' ' . $gBitSystem->getConfig( "bitmailer_from" ), $mail_data,
					"From: " . $gBitSystem->getConfig( "sender_email" ) . "\r\nContent-type: text/plain;charset=utf-8\r\n");
			}
			$ret = TRUE;
		}
		return $ret;
	}

/*
	function add_all_users($nl_id) {
		$query = "select `email` from `".BIT_DB_PREFIX."users_users`";
		$result = $this->mDb->query($query,[]);
		while ($res = $result->fetchRow()) {
			$email = $res["email"];
			if (!empty($email)) {
				$this->newsletter_subscribe($nl_id, $email);
			}
		}
	}

	function updateUsers() {
		if( $this->isValid() ) {
			$users = $this->mDb->getOne( "select count(*) from `".BIT_DB_PREFIX."mail_subscriptions` where `nl_id`=?", [ $this->mNewsletterId ] );
			$query = "update `".BIT_DB_PREFIX."newsletters` set `users`=? where `nl_id`=?";
			$result = $this->mDb->query( $query, [ $users, $this->mNewsletterId ] );
		}
	}
*/

	public static function getList( &$pListHash ) {
		global $gBitDb;
		if ( empty( $pParamHash["sort_mode"] ) ) {
			$pListHash['sort_mode'] = 'created_desc';
		}
		BitBase::prepGetList( $pListHash );
		$bindVars = [];
		$joinSql = '';
		$mid = '';

		if( @BitBase::verifyId( $pListHash['nl_id'] ) ) {
			$mid .= ' AND n.nl_id=? ';
			$bindVars[] = $pListHash['nl_id'];
		}

		if( !empty( $pListHash['find'] ) ) {
			$findesc = '%' . $pListHash['find'] . '%';
			$mid .= " AND (`name` like ? or `description` like ?)";
			$bindVars[] = $findesc;
			$bindVars[] = $findesc;
		}

		if( !empty( $pListHash['registration_optin'] ) ) {
			$joinSql = " INNER JOIN `".BIT_DB_PREFIX."liberty_content_prefs` lcp ON (lcp.`content_id`=n.`content_id` AND lcp.`pref_name`='registration_optin' AND lcp.`pref_value`='y') ";
		}

		$query = "SELECT *
				  FROM `".BIT_DB_PREFIX."newsletters` n INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON( n.`content_id`=lc.`content_id`)
					$joinSql
				  WHERE n.`content_id`=lc.`content_id` $mid
				  ORDER BY ".$gBitDb->convertSortmode( $pListHash['sort_mode'] );
		$result = $gBitDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] );

		$query_cant = "select count(*) from `".BIT_DB_PREFIX."newsletters` $mid";

		$ret = [];
		while( $res = $result->fetchRow() ) {
			$res['display_url'] = BitNewsletter::getDisplayUrlFromHash( $res );
			$res["confirmed"] = $gBitDb->getOne( "SELECT COUNT(*) FROM `".BIT_DB_PREFIX."mail_subscriptions` WHERE `unsubscribe_date` IS NULL and `content_id`=?", [ (int) $res['content_id'] ] );
			$res["unsub_count"] = $gBitDb->getOne( "SELECT COUNT(*) FROM `".BIT_DB_PREFIX."mail_subscriptions` WHERE `content_id`=?", [ (int) $res['content_id'] ] );
			$ret[$res['content_id']] = $res;
		}

		return $ret;
	}

/*	function list_newsletter_subscriptions($nl_id, $offset, $maxRecords, $sort_mode, $find) {
		$bindVars = [ (int)$nl_id ];
		if ($find) {
			$findesc = '%' . $find . '%';
			$mid = " where `nl_id`=? and (`name` like ? or `description` like ?)";
			$bindVars[] = $findesc;
			$bindVars[] = $findesc;
		} else {
			$mid = " where `nl_id`=? ";
		}

		$query = "select * from `".BIT_DB_PREFIX."mail_subscriptions` $mid order by ".$this->mDb->convertSortmode("$sort_mode");
		$query_cant = "select count(*) from mail_subscriptions $mid";
		$result = $this->mDb->query($query,$bindVars,$maxRecords,$offset);
		$cant = $this->mDb->getOne($query_cant,$bindVars);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}
		$retval = [];
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}

*/

	public function expunge(): bool {
		$ret = FALSE;
		if( $this->isValid() ) {
			$this->mDb->StartTrans();
			$query = "DELETE FROM `".BIT_DB_PREFIX."newsletters` where `nl_id`=?";
			$result = $this->mDb->query( $query, [ $this->mNewsletterId ] );
			// Clear out all individual subscriptions/unsubscriptions, but preserve the unsubscribe_all's
			$query = "DELETE FROM `".BIT_DB_PREFIX."mail_subscriptions` WHERE `content_id`=? AND `unsubscribe_all` IS NOT NULL";
			$result = $this->mDb->query( $query, [ $this->mContentId ] );
			$query = "UPDATE `".BIT_DB_PREFIX."mail_subscriptions` SET `content_id`=NULL WHERE `content_id`=? AND `unsubscribe_all` IS NOT NULL";
			$result = $this->mDb->query( $query, [ $this->mContentId ] );
			if( parent::expunge() ) {
				$ret = TRUE;
				$this->mDb->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return $ret;
	}

	public function isValid() {
		return( $this->verifyId( $this->mNewsletterId ) );
	}


	/**
	 * Generate a valid url for the Newsletter
	 *
	 * @param	array	$pParamHash $pNewsletterId of the item to use
	 * @return	string	Url String
	 */
	public static function getDisplayUrlFromHash( &$pParamHash ) {
		global $gBitSystem;
		$ret = NULL;
		if( BitBase::verifyId( $pParamHash['nl_id'] ) ) {
			if( $gBitSystem->isFeatureActive( 'pretty_urls' ) ) {
				$ret = NEWSLETTERS_PKG_URL.$pParamHash['nl_id'];
			} else {
				$ret = NEWSLETTERS_PKG_URL.'index.php?nl_id='.$pParamHash['nl_id'];
			}
		} else {
			$ret = NEWSLETTERS_PKG_URL.'index.php';
		}
		return $ret;
	}


	public function getEditions( $pNewsletterId = NULL ) {
		$ret = [];
		if( empty( $pNewsletterId ) ) {
			$nlId = $this->mNewsletterId;
		} elseif( BitBase::verifyId( $pNewsletterId ) ) {
			$nlId = $pNewsletterId;
		}
		if( !empty( $nlId ) ) {
			$listHash = [ 'nl_id' => $nlId ];
			$ret = BitNewsletterEdition::getList( $listHash );
		}
		return $ret;
	}
	
	public function getUserSubscriptions( $pUserId, $pEmail ) {
		global $gBitDb;
		$query = "SELECT `content_id` AS hash_key, ms.* FROM `".BIT_DB_PREFIX."mail_subscriptions` ms WHERE `user_id`=? OR `email`=?";
		$ret = $gBitDb->getAssoc( $query, [ $pUserId, $pEmail ] );
		return $ret;
	}


}

