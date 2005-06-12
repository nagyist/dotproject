<?php /* SYSTEM $Id$ */

/**
* Preferences class
*/
class CPreferences {
	var $pref_user = NULL;
	var $pref_name = NULL;
	var $pref_value = NULL;

	function CPreferences() {
		// empty constructor
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return "CPreferences::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			return NULL;
		}
	}

	function check() {
		// TODO MORE
		return NULL; // object is ok
	}

	function store() {
		$msg = $this->check();
		if( $msg ) {
			return "CPreference::store-check failed<br />$msg";
		}
		if (($msg = $this->delete())) {
			return "CPreference::store-delete failed<br />$msg";
		}
		if (!($ret = db_insertObject( 'user_preferences', $this, 'pref_user' ))) {
			return "CPreference::store failed <br />" . db_error();
		} else {
			return NULL;
		}
	}

	function delete() {
		$q  = new DBQuery;
		$q->setDelete('user_preferences');
		$q->addWhere("pref_user = $this->pref_user");
		$q->addWhere("pref_name = '$this->pref_name'");
		if (!$q->exec()) {
			$q->clear();
			return db_error();
		} else {
			$q->clear();
			return NULL;
		}
	}
}

/**
* Module class
*/
class CModule extends CDpObject {
	var $mod_id=null;
	var $mod_name=null;
	var $mod_directory=null;
	var $mod_version=null;
	var $mod_setup_class=null;
	var $mod_type=null;
	var $mod_active=null;
	var $mod_ui_name=null;
	var $mod_ui_icon=null;
	var $mod_ui_order=null;
	var $mod_ui_active=null;
	var $mod_description=null;
	var $permissions_item_label=null;
	var $permissions_item_field=null;
	var $permissions_item_table=null;

	function CModule() {
		$this->CDpObject( 'modules', 'mod_id' );
	}

	function install() {
		$q  = new DBQuery;
		$q->addTable('modules');
		$q->addQuery('mod_directory');
		$q->addWhere("mod_directory = '$this->mod_directory'");
		
		
		if ($q->loadHashList()) {
			// the module is already installed
			// TODO: check for older version - upgrade
			$q->clear();
			return false;
		}
		$q->clear();
		
		$q  = new DBQuery;
		$q->addTable('modules');
		$q->addQuery('max(mod_ui_order)');
		$mmuo = $q->loadList();
		$this->mod_ui_order = $mmuo[0]['max(mod_ui_order)'] + 1;
		$q->clear();

		$perms =& $GLOBALS['AppUI']->acl();
		$perms->addModule($this->mod_directory, $this->mod_name);
		// Determine if it is an admin module or not, then add it to the correct set
		if (! isset($this->mod_admin))
			$this->mod_admin = 0;
		if ($this->mod_admin) {
			$perms->addGroupItem($this->mod_directory, "admin");
		} else {
			$perms->addGroupItem($this->mod_directory, "non_admin");
		}
		if (isset($this->permissions_item_table) && $this->permissions_item_table)
		  $perms->addModuleSection($this->permissions_item_table);
		$this->store();
		return true;
	}

	function remove() {
		$q  = new DBQuery;
		$q->setDelete('modules');
		$q->addWhere("mod_id = $this->mod_id");
		if (!$q->exec()) {
			$q->clear();
			return db_error();
		} else {
			$q->clear();
			$perms =& $GLOBALS['AppUI']->acl();
			if (! isset($this->mod_admin))
				$this->mod_admin = 0;
			if ($this->mod_admin) {
				$perms->deleteGroupItem($this->mod_directory, "admin");
			} else {
				$perms->deleteGroupItem($this->mod_directory, "non_admin");
			}
			$perms->deleteModule($this->mod_directory);
			if (isset($this->permissions_item_table) && $this->permissions_item_table)
			  $perms->deleteModuleSection($this->permissions_item_table);
			return NULL;
		}
	}

	function move( $dirn ) {
		$temp = $this->mod_ui_order;
		if ($dirn == 'moveup') {
			$temp--;
		
			$q  = new DBQuery;
			$q->addTable('modules');
			$q->addUpdate('mod_ui_order', '(mod_ui_order+1)');
			$q->addWhere("mod_ui_order = $temp");
			$q->exec();
			$q->clear();

		} else if ($dirn == 'movedn') {
			$temp++;
			
			$q  = new DBQuery;
			$q->addTable('modules');
			$q->addUpdate('mod_ui_order', '(mod_ui_order-1)');
			$q->addWhere("mod_ui_order = $temp");
			$q->exec();
			$q->clear();
		}
				
		$q  = new DBQuery;
		$q->addTable('modules');
		$q->addUpdate('mod_ui_order', "$temp");
		$q->addWhere("mod_ui_order = $this->mod_id");
		$q->exec();
		$q->clear();

		$this->mod_id = $temp;
	}
// overridable functions
	function moduleInstall() {
		return null;
	}
	function moduleRemove() {
		return null;
	}
	function moduleUpgrade() {
		return null;
	}
}

/**
* Configuration class
*/
class CConfig extends CDpObject {

	function CConfig() {
		$this->CDpObject( 'config', 'config_id' );
	}

	function getChildren($id) {
		$this->_query->clear();
		$this->_query->addTable('config_list');
		$this->_query->addOrder('config_list_id');
		$this->_query->addWhere('config_id = ' . $id);
		$sql = $this->_query->prepare();
		$this->_query->clear();
		return db_loadHashList($sql, 'config_list_id');
	}

}


class bcode {
        var $_billingcode_id=NULL;
        var $company_id;
        var $billingcode_desc;
        var $billingcode_name;
        var $billingcode_value;
        var $billingcode_status;

        function bcode() {
        }

        function bind( $hash ) {
                if (!is_array($hash)) {
                        return "Billing Code::bind failed";
                } else {
                        bindHashToObject( $hash, $this );
                        return NULL;
                }
        }

        function delete() {
		$q  = new DBQuery;
		$q->addTable('billingcode');
		$q->addUpdate('billingcode_status', "1");
		$q->addWhere("billingcode_id='".$this->_billingcode_id."'");
                if (!$q->exec()) {
			$q->clear();
                        return db_error();
                } else {
			$q->clear();
                        return NULL;
                }
        }

        function store() {
                if (!($ret = db_insertObject ( 'billingcode', $this, 'billingcode_id' ))) {
                        return "Billing Code::store failed <br />" . db_error();
                } else {
                        return NULL;
                }
        }
}