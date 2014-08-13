<?php

/**
 * Mail domain controller.
 *
 * @category   apps
 * @package    mail
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\accounts\Accounts_Driver_Not_Set_Exception as Accounts_Driver_Not_Set_Exception;
use \clearos\apps\accounts\Accounts_Engine as Accounts_Engine;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail domain controller.
 *
 * @category   apps
 * @package    mail
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail/
 */

class Domain extends ClearOS_Controller
{
    /**
     * Domain settings controller.
     *
     * @return view
     */

    function index()
    {
        $this->_common('view');
    }

    /**
     * Edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_common('edit');
    }

    /**
     * View view.
     *
     * @return view
     */

    function view()
    {
        $this->_common('view');
    }

    /**
     * Common view/edit handler.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _common($form_type)
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            $this->status->widget('mail');
            return;
        }

        // Load dependencies
        //------------------

        $this->lang->load('mail');
        $this->load->library('mail/Base_Mail');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('domain', 'mail/Base_Mail', 'validate_domain', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->base_mail->set_domain($this->input->post('domain'));

                $this->page->set_status_updated();
                redirect('/mail/domain');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['domain'] = $this->base_mail->get_domain();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('domain', $data, lang('mail_mail_domain'));
    }
}
