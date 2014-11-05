<?php

/**
 * Mail base class.
 *
 * @category   apps
 * @package    mail
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\mail;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('mail');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\groups\Group_Factory as Group;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager;
use \clearos\apps\users\User_Factory as User;
use \clearos\apps\users\User_Manager_Factory as User_Manager;

clearos_load_library('groups/Group_Factory');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('users/User_Factory');
clearos_load_library('users/User_Manager_Factory');

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\groups\Group_Engine as Group_Engine;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\smtp\Postfix as Postfix;

clearos_load_library('base/Engine');
clearos_load_library('groups/Group_Engine');
clearos_load_library('network/Network_Utils');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('smtp/Postfix');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail base class.
 *
 * @category   apps
 * @package    mail
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail/
 */

class Base_Mail extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Base mail constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Sets base mail domain.
     *
     * @param string $domain domain
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain($domain));

        if ($this->ldaph == NULL)
            $this->_get_ldap_handle();

        // Set domain in master node object
        //---------------------------------

        $ldap_object['objectClass'] = array(
            'top',
            'account',
            'posixAccount',
            'clearMasterNode'
        );

        $ldap_object['clearMasterMailDomain'] = $domain;

        $ldap_driver = new LDAP_Driver();
        $dn = $ldap_driver->get_master_dn();

        if ($this->ldaph->exists($dn))
            $this->ldaph->modify($dn, $ldap_object);

        // Set domain for user and group mail attributes
        //----------------------------------------------
        // This is a bit non-intuitive.  The mail attribute is updated
        // by the mail extension.  To retrigger the extension so that it
        // updates the mail attribute, we update every user and group.

        // TODO: scaling issues for large number of users

        $user_manager = User_Manager::create();
        $users = $user_manager->get_list();

        foreach ($users as $username) {
            $user = User::create($username);

            $user_info = array();
            $user->update($user_info);
        }

        $group_manager = Group_Manager::create();

        $normal_groups = $group_manager->get_list(Group_Engine::FILTER_NORMAL);
        $windows_groups = $group_manager->get_list(Group_Engine::FILTER_WINDOWS);
        $builtin_groups = $group_manager->get_list(Group_Engine::FILTER_BUILTIN);

        $groups = array_merge($normal_groups, $windows_groups, $builtin_groups);

        foreach ($groups as $group_name) {
            $group = Group::create($group_name);

            $group_info = array();

            try {
                $group->update($group_info);
            } catch (Exception $e) {
                // TODO: not all groups need/have this attribute
            }
        }

        // Update Postfix domain
        //----------------------

        $postfix = new Postfix();
        $postfix->set_domain($domain);
    }

    /**
     * Resets any processes that need a configuration reload.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function reset()
    {
        clearos_profile(__METHOD__, __LINE__);

        $postfix = new Postfix();
        $postfix->reset(TRUE);
    }

    /**
     * Sets base mail domain.
     *
     * @param string $domain domain
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($hostname));

        $postfix = new Postfix();
        $postfix->set_hostname($hostname);
    }

    /**
     * Returns base mail domain.
     *
     * @return string mail domain
     * @throws Engine_Exception
     */

    public function get_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $ldap_driver = new LDAP_Driver();
        $dn = $ldap_driver->get_master_dn();
        
        $attributes = $this->ldaph->read($dn);

        $domain = empty($attributes['clearMasterMailDomain'][0]) ? '' : $attributes['clearMasterMailDomain'][0];

        return $domain;
    }

    /**
     * Returns mail server hostname.
     *
     * @return string mail server hostname
     * @throws Engine_Exception
     */

    public function get_hostname()
    {
        clearos_profile(__METHOD__, __LINE__);

        $postfix = new Postfix();
        $hostname = $postfix->get_hostname();

        return $hostname;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for domain.
     *
     * @param string $domain domain
     *
     * @return string error message if domain is invalid
     */

    public function validate_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('network_domain_invalid');
    }

    /**
     * Validation routine for hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($hostname))
            return lang('network_hostname_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Creates an LDAP handle.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $this->ldaph = $ldap->get_ldap_handle();
    }
}
