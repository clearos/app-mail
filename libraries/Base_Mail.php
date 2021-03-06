<?php

/**
 * Mail base class.
 *
 * @category   apps
 * @package    mail
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2018 ClearFoundation
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

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Domain as Domain;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\smtp\Postfix as Postfix;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('network/Domain');
clearos_load_library('network/Network_Utils');
clearos_load_library('smtp/Postfix');

// Exceptions
//-----------

use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/File_Not_Found_Exception');
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
 * @copyright  2012-2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail/
 */

class Base_Mail extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const APP_CONFIG = '/etc/clearos/mail.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $config = array();
    protected $is_loaded = FALSE;

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
     * Auto-configures default mail domain.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function auto_configure()
    {
        clearos_profile(__METHOD__, __LINE__);

        $current_domain = $this->get_domain();

        if (empty($current_domain)) {
            $domain = new Domain();
            $default_domain = $domain->get_default();

            if (!empty($default_domain))
                $this->set_domain($default_domain);
        }
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

        // Update mail domain
        //-------------------

        $this->_set_parameter('domain', $domain);

        // Update Postfix domain
        //----------------------

        $postfix = new Postfix();
        $postfix->set_domain($domain);

        // Set domain for user and group mail attributes
        //----------------------------------------------

        if (clearos_load_library('mail_extension/Mail_Domain')) {
            $mail_domain = new \clearos\apps\mail_extension\Mail_Domain();
            $mail_domain->set_domain($domain);
        }
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
     * @param string $hostname hostname
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

    public function convert_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!clearos_load_library('openldap/LDAP_Driver'))
            return;

        $ldap = new \clearos\apps\openldap\LDAP_Driver();
        $ldaph = $ldap->get_ldap_handle();
        $dn = $ldap->get_master_dn();
        
        $attributes = $ldaph->read($dn);

        $domain = empty($attributes['clearMasterMailDomain'][0]) ? '' : $attributes['clearMasterMailDomain'][0];

        if (!empty($domain))
            $this->set_domain($domain);

        return $domain;
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

        $this->_load_config();

        $domain = (empty($this->config['domain'])) ? '' : $this->config['domain'];

        if (empty($domain))
            $domain = $this->convert_domain();

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
     * Loads configuration files.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $config_file = new Configuration_File(self::APP_CONFIG);
            $this->config = $config_file->load();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Sets a parameter in the config file.
     *
     * @param string $key   name of the key in the config file
     * @param string $value value for the key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->is_loaded = FALSE;

        $file = new File(self::APP_CONFIG);

        if (! $file->exists())
            $file->create("root", "root", "0644");

        $match = $file->replace_lines("/^$key\s*=\s*/", "$key = $value\n");

        if (!$match)
            $file->add_lines("$key = $value\n");
    }
}
