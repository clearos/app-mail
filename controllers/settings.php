<?php

/**
 * Mail settings controller.
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail settings controller.
 *
 * @category   apps
 * @package    mail
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail/
 */

class Settings extends ClearOS_Controller
{
    /**
     * Mail settings settings controller.
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
        // Nothing to show if accounts system is not running
        //---------------------------------------------------

/*
        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            return;
        }
*/

        // Load dependencies
        //------------------

        $this->lang->load('mail');
        $this->load->library('mail/Base_Mail');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('domain', 'mail/Base_Mail', 'validate_domain', TRUE);
        $this->form_validation->set_policy('hostname', 'mail/Base_Mail', 'validate_hostname', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->base_mail->set_domain($this->input->post('domain'));
                $this->base_mail->set_hostname($this->input->post('hostname'));
                $this->base_mail->reset();

                $this->page->set_status_updated();
                redirect('/mail/settings');
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
            $data['hostname'] = $this->base_mail->get_hostname();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('mail/settings', $data, lang('mail_app_name'));
    }
}
