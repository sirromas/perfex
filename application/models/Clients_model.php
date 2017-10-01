<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Clients_model extends CRM_Model
{
    private $contact_data = array('firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction');

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * With this function staff can login as client in the clients area
     * @param  mixed $id client id
     */
    public function login_as_client($id)
    {
        $this->db->where('userid', $id);
        $this->db->where('is_primary', 1);
        $primary = $this->db->get('tblcontacts')->row();
        if (!$primary) {
            set_alert('danger', _l('no_primary_contact'));
            redirect($_SERVER['HTTP_REFERER']);
        }
        $client    = $this->get($id);
        $user_data = array(
            'client_user_id' => $client->userid,
            'contact_user_id' => get_primary_contact_user_id($client->userid),
            'client_logged_in' => true,
            'logged_in_as_client' => true
        );
        $this->session->set_userdata($user_data);
    }

    /**
     * @param  mixed $id client id (optional)
     * @param  integer $active (optional) get all active or inactive
     * @return mixed
     * Get client object based on passed clientid if not passed clientid return array of all clients
     */
    public function get($id = '', $where = array('tblclients.active' => 1))
    {
        $this->db->select(implode(',', prefixed_table_fields_array('tblclients')) . ',CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company');

        $this->db->join('tblcountries', 'tblcountries.country_id = tblclients.country', 'left');
        $this->db->join('tblcontacts', 'tblcontacts.userid = tblclients.userid AND is_primary = 1', 'left');

        if (is_numeric($id)) {
            $this->db->where('tblclients.userid', $id);
            $client = $this->db->get('tblclients')->row();

            if(get_option('company_requires_vat_number_field') == 0){
                $client->vat = null;
            }
            return $client;
        }

        $this->db->where($where);
        $this->db->order_by('company', 'asc');

        return $this->db->get('tblclients')->result_array();
    }

    /**
     * Get customers contacts
     * @param  mixed $customer_id
     * @param  array  $where       perform where in query
     * @return array
     */
    public function get_contacts($customer_id = '', $where = array('active' => 1))
    {
        $this->db->where($where);
        if ($customer_id != '') {
            $this->db->where('userid', $customer_id);
        }
        $this->db->order_by('is_primary', 'DESC');

        return $this->db->get('tblcontacts')->result_array();
    }

    /**
     * Get single contacts
     * @param  mixed $id contact id
     * @return object
     */
    public function get_contact($id)
    {
        $this->db->where('id', $id);

        return $this->db->get('tblcontacts')->row();
    }

    /**
     * Get customer staff members that are added as customer admins
     * @param  mixed $id customer id
     * @return array
     */
    public function get_admins($id)
    {
        $this->db->where('customer_id', $id);

        return $this->db->get('tblcustomeradmins')->result_array();
    }

    /**
     * Get unique staff id's of customer admins
     * @return array
     */
    public function get_customers_admin_unique_ids()
    {
        return $this->db->query('SELECT DISTINCT(staff_id) FROM tblcustomeradmins')->result_array();
    }

    /**
     * Assign staff members as admin to customers
     * @param  array $data $_POST data
     * @param  mixed $id   customer id
     * @return boolean
     */
    public function assign_admins($data, $id)
    {
        $affectedRows = 0;

        if (count($data) == 0) {
            $this->db->where('customer_id', $id);
            $this->db->delete('tblcustomeradmins');
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        } else {
            $current_admins     = $this->get_admins($id);
            $current_admins_ids = array();
            foreach ($current_admins as $c_admin) {
                array_push($current_admins_ids, $c_admin['staff_id']);
            }
            foreach ($current_admins_ids as $c_admin_id) {
                if (!in_array($c_admin_id, $data['customer_admins'])) {
                    $this->db->where('staff_id', $c_admin_id);
                    $this->db->where('customer_id', $id);
                    $this->db->delete('tblcustomeradmins');
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
            foreach ($data['customer_admins'] as $n_admin_id) {
                if (total_rows('tblcustomeradmins', array(
                    'customer_id' => $id,
                    'staff_id' => $n_admin_id
                )) == 0) {
                    $this->db->insert('tblcustomeradmins', array(
                        'customer_id' => $id,
                        'staff_id' => $n_admin_id,
                        'date_assigned' => date('Y-m-d H:i:s')
                    ));
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
        }
        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    /**
     * Update contact data
     * @param  array  $data           $_POST data
     * @param  mixed  $id             contact id
     * @param  boolean $client_request is request from customers area
     * @return mixed
     */
    public function update_contact($data, $id, $client_request = false)
    {
        if (isset($data['fakeusernameremembered'])) {
            unset($data['fakeusernameremembered']);
        }
        if (isset($data['fakepasswordremembered'])) {
            unset($data['fakepasswordremembered']);
        }

        $hook_data['data'] = $data;
        $hook_data['id']   = $id;
        $hook_data         = do_action('before_update_contact', $hook_data);
        $data              = $hook_data['data'];
        $id                = $hook_data['id'];

        $affectedRows = 0;
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $this->load->helper('phpass');
            $hasher                       = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
            $data['password']             = $hasher->HashPassword($data['password']);
            $data['last_password_change'] = date('Y-m-d H:i:s');
        }
        $permissions = array();
        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
        }
        if (isset($data['send_set_password_email'])) {
            $send_set_password_email = true;
            unset($data['send_set_password_email']);
        }
        $contact = $this->get_contact($id);
        if (isset($data['is_primary'])) {
            $data['is_primary'] = 1;
        } else {
            $data['is_primary'] = 0;
        }
        // Contact cant change if is primary or not
        if ($client_request == true) {
            unset($data['is_primary']);
            if (isset($data['email'])) {
                unset($data['email']);
            }
        }
        if (isset($send_set_password_email)) {
            $success = $this->authentication_model->set_password_email($data['email'], 0);
            if ($success) {
                $set_password_email_sent = true;
            }
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }
        $this->db->where('id', $id);
        $this->db->update('tblcontacts', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if (isset($data['is_primary']) && $data['is_primary'] == 1) {
                $this->db->where('userid', $contact->userid);
                $this->db->where('id !=', $id);
                $this->db->update('tblcontacts', array(
                    'is_primary' => 0
                ));
            }
        }
        if ($client_request == false) {
            $customer_permissions = $this->roles_model->get_contact_permissions($id);
            if (sizeof($customer_permissions) > 0) {
                foreach ($customer_permissions as $customer_permission) {
                    if (!in_array($customer_permission['permission_id'], $permissions)) {
                        $this->db->where('userid', $id);
                        $this->db->where('permission_id', $customer_permission['permission_id']);
                        $this->db->delete('tblcontactpermissions');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                }
                foreach ($permissions as $permission) {
                    $this->db->where('userid', $id);
                    $this->db->where('permission_id', $permission);
                    $_exists = $this->db->get('tblcontactpermissions')->row();
                    if (!$_exists) {
                        $this->db->insert('tblcontactpermissions', array(
                            'userid' => $id,
                            'permission_id' => $permission
                        ));
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                }
            } else {
                foreach ($permissions as $permission) {
                    $this->db->insert('tblcontactpermissions', array(
                        'userid' => $id,
                        'permission_id' => $permission
                    ));
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
        }
        if ($affectedRows > 0 && !isset($set_password_email_sent)) {
            logActivity('Contact Updated [' . $data['firstname'] . ' ' . $data['lastname'] . ']');

            return true;
        } elseif ($affectedRows > 0 && isset($set_password_email_sent)) {
            return array(
                'set_password_email_sent_and_profile_updated' => true
            );
        } elseif ($affectedRows == 0 && isset($set_password_email_sent)) {
            return array(
                'set_password_email_sent' => true
            );
        }

        return false;
    }

    /**
     * Add new contact
     * @param array  $data               $_POST data
     * @param mixed  $customer_id        customer id
     * @param boolean $not_manual_request is manual from admin area customer profile or register,convert to lead
     */
    public function add_contact($data, $customer_id, $not_manual_request = false)
    {
        if (isset($data['fakeusernameremembered'])) {
            unset($data['fakeusernameremembered']);
        }
        if (isset($data['fakepasswordremembered'])) {
            unset($data['fakepasswordremembered']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        if (isset($data['send_set_password_email'])) {
            $send_set_password_email = true;
            unset($data['send_set_password_email']);
        }
        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
        }
        $send_welcome_email = true;
        if (isset($data['donotsendwelcomeemail'])) {
            $send_welcome_email = false;
            unset($data['donotsendwelcomeemail']);
        } elseif (strpos($_SERVER['HTTP_REFERER'], 'register') !== false) {
            $send_welcome_email = true;
        }
        // If client register set this auto contact as primary
        if ($not_manual_request == 1) {
            $data['is_primary'] = 1;
        }
        if (isset($data['is_primary'])) {
            $data['is_primary'] = 1;
            $this->db->where('userid', $customer_id);
            $this->db->update('tblcontacts', array(
                'is_primary' => 0
            ));
        } else {
            $data['is_primary'] = 0;
        }
        $ps_not_hashed  = '';
        $data['userid'] = $customer_id;
        if (isset($data['password'])) {
            $ps_not_hashed = $data['password'];
            $this->load->helper('phpass');
            $hasher              = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
            $data['password']    = $hasher->HashPassword($data['password']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');

        $_data = array(
            'data' => $data,
            'not_manual_request' => $not_manual_request
        );

        $_data = do_action('before_create_contact', $_data);
        $data  = $_data['data'];

        $this->db->insert('tblcontacts', $data);
        $contact_id = $this->db->insert_id();

        if ($contact_id) {
            if (isset($custom_fields)) {
                handle_custom_fields_post($contact_id, $custom_fields);
            }
            // request from admin area
            if (!isset($permissions) && $not_manual_request == false) {
                $permissions = array();
            } elseif ($not_manual_request == true) {
                $permissions         = array();
                $_permissions        = $this->perfex_base->get_contact_permissions();
                $default_permissions = @unserialize(get_option('default_contact_permissions'));
                foreach ($_permissions as $permission) {
                    if (is_array($default_permissions) && in_array($permission['id'], $default_permissions)) {
                        array_push($permissions, $permission['id']);
                    }
                }
            }
            foreach ($permissions as $permission) {
                $this->db->insert('tblcontactpermissions', array(
                    'userid' => $contact_id,
                    'permission_id' => $permission
                ));
            }

             $lastAnnouncement = $this->db->query("SELECT announcementid FROM tblannouncements WHERE showtousers = 1 AND announcementid = (SELECT MAX(announcementid) FROM tblannouncements)")->row();
             if($lastAnnouncement){
                // Get all announcements and set it to read.
                $this->db->select('announcementid')
                ->from('tblannouncements')
                ->where('showtousers', 1)
                ->where('announcementid !=', $lastAnnouncement->announcementid);

                $announcements = $this->db->get()->result_array();
                foreach ($announcements as $announcement) {
                    $this->db->insert('tbldismissedannouncements', array(
                        'announcementid' => $announcement['announcementid'],
                        'staff' => 0,
                        'userid' => $contact_id
                    ));
                }
            }
            if ($send_welcome_email == true) {
                $this->load->model('emails_model');
                $merge_fields = array();
                $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($data['userid'], $contact_id, $ps_not_hashed));
                $this->emails_model->send_email_template('new-client-created', $data['email'], $merge_fields);
            }

            if (isset($send_set_password_email)) {
                $this->authentication_model->set_password_email($data['email'], 0);
            }

            logActivity('Contact Created [' . $data['firstname'] . ' ' . $data['lastname'] . ']');
            do_action('contact_created', $contact_id);

            return $contact_id;
        }

        return false;
    }

    /**
     * @param array $_POST data
     * @param client_request is this request from the customer area
     * @return integer Insert ID
     * Add new client to database
     */
    public function add($data, $client_or_lead_convert_request = false)
    {
        if (isset($data['fakeusernameremembered'])) {
            unset($data['fakeusernameremembered']);
        }
        if (isset($data['fakepasswordremembered'])) {
            unset($data['fakepasswordremembered']);
        }

        $contact_data = array();
        foreach ($this->contact_data as $field) {
            if (isset($data[$field])) {
                $contact_data[$field] = $data[$field];
                // Phonenumber is also used for the company profile
                if ($field != 'phonenumber') {
                    unset($data[$field]);
                }
            }
        }
        // From customer profile register
        if (isset($data['contact_phonenumber'])) {
            $contact_data['phonenumber'] = $data['contact_phonenumber'];
            unset($data['contact_phonenumber']);
        }
        if (isset($data['passwordr'])) {
            unset($data['passwordr']);
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        if (isset($data['groups_in'])) {
            $groups_in = $data['groups_in'];
            unset($data['groups_in']);
        }
        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }
        if (!isset($data['show_primary_contact'])) {
            $data['show_primary_contact'] = 0;
        }
        if (isset($data['billing_country']) && $data['billing_country'] == '' || !isset($data['billing_country'])) {
            $data['billing_country'] = 0;
        }
        if (isset($data['default_currency']) && $data['default_currency'] == '' || !isset($data['default_currency'])) {
            $data['default_currency'] = 0;
        }
        if (isset($data['shipping_country']) && $data['shipping_country'] == '' || !isset($data['shipping_country'])) {
            $data['shipping_country'] = 0;
        }
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data                = do_action('before_client_added', $data);
        $this->db->insert('tblclients', $data);
        $userid = $this->db->insert_id();
        if ($userid) {
            if (isset($custom_fields)) {
                $_custom_fields = $custom_fields;
                // Possible request from the register area with 2 types of custom fields for contact and for comapny/customer
                if (count($custom_fields) == 2) {
                    unset($custom_fields);
                    $custom_fields['customers']                = $_custom_fields['customers'];
                    $contact_data['custom_fields']['contacts'] = $_custom_fields['contacts'];
                } elseif (count($custom_fields) == 1) {
                    if (isset($_custom_fields['contacts'])) {
                        $contact_data['custom_fields']['contacts'] = $_custom_fields['contacts'];
                        unset($custom_fields);
                    }
                }
                handle_custom_fields_post($userid, $custom_fields);
            }
            // If request from client area or lead convert to client add as contact too
            if ($client_or_lead_convert_request == true) {
                $contact_id = $this->add_contact($contact_data, $userid, $client_or_lead_convert_request);
            }
            if (isset($groups_in)) {
                foreach ($groups_in as $group) {
                    $this->db->insert('tblcustomergroups_in', array(
                        'customer_id' => $userid,
                        'groupid' => $group
                    ));
                }
            }
            do_action('after_client_added', $userid);
            $_new_client_log = $data['company'];
            if ($_new_client_log == '' && isset($contact_id)) {
                $_new_client_log = get_contact_full_name($contact_id);
            }

            $_is_staff = null;
            if (!is_client_logged_in() && is_staff_logged_in()) {
                $_new_client_log .= ' From Staff: ' . get_staff_user_id();
                $_is_staff = get_staff_user_id();
            }

            logActivity('New Client Created [' . $_new_client_log . ']', $_is_staff);
        }

        return $userid;
    }

    /**
     * @param  array $_POST data
     * @param  integer ID
     * @return boolean
     * Update client informations
     */
    public function update($data, $id, $client_request = false)
    {
        if (isset($data['fakeusernameremembered'])) {
            unset($data['fakeusernameremembered']);
        }
        if (isset($data['fakepasswordremembered'])) {
            unset($data['fakepasswordremembered']);
        }

        if (isset($data['DataTables_Table_0_length'])) {
            unset($data['DataTables_Table_0_length']);
        }

        if (isset($data['DataTables_Table_1_length'])) {
            unset($data['DataTables_Table_1_length']);
        }

        if (isset($data['onoffswitch'])) {
            unset($data['onoffswitch']);
        }

        if (isset($data['update_all_other_transactions'])) {
            $update_all_other_transactions = true;
            unset($data['update_all_other_transactions']);
        }
        $affectedRows = 0;
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }
        if (isset($data['groups_in'])) {
            $groups_in = $data['groups_in'];
            unset($data['groups_in']);
        }
        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }
        if (isset($data['billing_country']) && $data['billing_country'] == '' || !isset($data['billing_country'])) {
            $data['billing_country'] = 0;
        }
        if (isset($data['default_currency']) && $data['default_currency'] == '' || !isset($data['default_currency'])) {
            $data['default_currency'] = 0;
        }
        if (isset($data['shipping_country']) && $data['shipping_country'] == '' || !isset($data['shipping_country'])) {
            $data['shipping_country'] = 0;
        }

        if (!isset($data['show_primary_contact'])) {
            $data['show_primary_contact'] = 0;
        }

        $_data = do_action('before_client_updated', array(
            'userid' => $id,
            'data' => $data
        ));
        $data  = $_data['data'];
        $this->db->where('userid', $id);
        $this->db->update('tblclients', $data);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            do_action('after_client_updated', $id);
        }
        if (isset($update_all_other_transactions)) {
            // Update all unpaid invoices
            $this->db->where('clientid', $id);
            $this->db->where('status !=', 2);
            $invoices = $this->db->get('tblinvoices')->result_array();
            foreach ($invoices as $invoice) {
                $this->db->where('id', $invoice['id']);
                $this->db->update('tblinvoices', array(
                    'billing_street' => $data['billing_street'],
                    'billing_city' => $data['billing_city'],
                    'billing_state' => $data['billing_state'],
                    'billing_zip' => $data['billing_zip'],
                    'billing_country' => $data['billing_country'],
                    'shipping_street' => $data['shipping_street'],
                    'shipping_city' => $data['shipping_city'],
                    'shipping_state' => $data['shipping_state'],
                    'shipping_zip' => $data['shipping_zip'],
                    'shipping_country' => $data['shipping_country']
                ));
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }
            }
            // Update all estimates
            $this->db->where('clientid', $id);
            $estimates = $this->db->get('tblestimates')->result_array();
            foreach ($estimates as $estimate) {
                $this->db->where('id', $estimate['id']);
                $this->db->update('tblestimates', array(
                    'billing_street' => $data['billing_street'],
                    'billing_city' => $data['billing_city'],
                    'billing_state' => $data['billing_state'],
                    'billing_zip' => $data['billing_zip'],
                    'billing_country' => $data['billing_country'],
                    'shipping_street' => $data['shipping_street'],
                    'shipping_city' => $data['shipping_city'],
                    'shipping_state' => $data['shipping_state'],
                    'shipping_zip' => $data['shipping_zip'],
                    'shipping_country' => $data['shipping_country']
                ));
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }
            }
        }

        if (!isset($groups_in)) {
            $groups_in = false;
        }

        if ($this->handle_update_groups($id, $groups_in)) {
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            logActivity('Customer Info Updated [' . $data['company'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Update customer groups where belongs
     * @param  mixed $id        customer id
     * @param  mixed $groups_in
     * @return boolean
     */
    public function handle_update_groups($id, $groups_in)
    {
        if ($groups_in == false) {
            unset($groups_in);
        }
        $affectedRows    = 0;
        $customer_groups = $this->get_customer_groups($id);
        if (sizeof($customer_groups) > 0) {
            foreach ($customer_groups as $customer_group) {
                if (isset($groups_in)) {
                    if (!in_array($customer_group['groupid'], $groups_in)) {
                        $this->db->where('customer_id', $id);
                        $this->db->where('id', $customer_group['id']);
                        $this->db->delete('tblcustomergroups_in');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                } else {
                    $this->db->where('customer_id', $id);
                    $this->db->delete('tblcustomergroups_in');
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
            if (isset($groups_in)) {
                foreach ($groups_in as $group) {
                    $this->db->where('customer_id', $id);
                    $this->db->where('groupid', $group);
                    $_exists = $this->db->get('tblcustomergroups_in')->row();
                    if (!$_exists) {
                        if (empty($group)) {
                            continue;
                        }
                        $this->db->insert('tblcustomergroups_in', array(
                            'customer_id' => $id,
                            'groupid' => $group
                        ));
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                }
            }
        } else {
            if (isset($groups_in)) {
                foreach ($groups_in as $group) {
                    if (empty($group)) {
                        continue;
                    }
                    $this->db->insert('tblcustomergroups_in', array(
                        'customer_id' => $id,
                        'groupid' => $group
                    ));
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
        }

        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    /**
     * Used to update company details from customers area
     * @param  array $data $_POST data
     * @param  mixed $id
     * @return boolean
     */
    public function update_company_details($data, $id)
    {
        $affectedRows = 0;
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }
        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }
        if (isset($data['billing_country']) && $data['billing_country'] == '') {
            $data['billing_country'] = 0;
        }
        if (isset($data['shipping_country']) && $data['shipping_country'] == '') {
            $data['shipping_country'] = 0;
        }
        $this->db->where('userid', $id);
        $this->db->update('tblclients', $data);
        if ($this->db->affected_rows() > 0) {
            logActivity('Customer Info Updated From Clients Area [' . $data['company'] . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @return boolean
     * Delete client, also deleting rows from, dismissed client announcements, ticket replies, tickets, autologin, user notes
     */
    public function delete($id)
    {
        $affectedRows = 0;

        if (is_reference_in_table('clientid', 'tblinvoices', $id) || is_reference_in_table('clientid', 'tblestimates', $id)) {
            return array(
                'referenced' => true
            );
        }

        do_action('before_client_deleted', $id);

        $this->db->where('userid', $id);
        $this->db->delete('tblclients');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;

            // Delete all tickets start here
            $this->db->where('userid', $id);
            $tickets = $this->db->get('tbltickets')->result_array();
            $this->load->model('tickets_model');
            foreach ($tickets as $ticket) {
                $this->tickets_model->delete($ticket['ticketid']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'customer');
            $this->db->delete('tblnotes');

            // Delete all user contacts
            $this->db->where('userid', $id);
            $contacts = $this->db->get('tblcontacts')->result_array();
            foreach ($contacts as $contact) {
                $this->delete_contact($contact['id']);
            }
            // Get all client contracts
            $this->load->model('contracts_model');
            $this->db->where('client', $id);
            $contracts = $this->db->get('tblcontracts')->result_array();
            foreach ($contracts as $contract) {
                $this->contracts_model->delete($contract['id']);
            }
            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'customers');
            $this->db->delete('tblcustomfieldsvalues');

            // Get customer related tasks
            $this->db->where('rel_type', 'customer');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get('tblstafftasks')->result_array();

            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            $this->db->where('rel_type', 'customer');
            $this->db->where('rel_id', $id);
            $this->db->delete('tblreminders');

            $this->db->where('customer_id', $id);
            $this->db->delete('tblcustomeradmins');

            $this->db->where('customer_id', $id);
            $this->db->delete('tblvault');

            $this->db->where('customer_id', $id);
            $this->db->delete('tblcustomergroups_in');

            // Delete all projects
            $this->load->model('projects_model');
            $this->db->where('clientid', $id);
            $projects = $this->db->get('tblprojects')->result_array();
            foreach ($projects as $project) {
                $this->projects_model->delete($project['id']);
            }
            $this->load->model('proposals_model');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'customer');
            $proposals = $this->db->get('tblproposals')->result_array();
            foreach ($proposals as $proposal) {
                $this->proposals_model->delete($proposal['id']);
            }
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'customer');
            $attachments = $this->db->get('tblfiles')->result_array();
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('clientid', $id);
            $expenses = $this->db->get('tblexpenses')->result_array();

            $this->load->model('expenses_model');
            foreach ($expenses as $expense) {
                $this->expenses_model->delete($expense['id']);
            }
        }
        if ($affectedRows > 0) {
            do_action('after_client_deleted',$id);
            logActivity('Client Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete customer contact
     * @param  mixed $id contact id
     * @return boolean
     */
    public function delete_contact($id)
    {
        $this->db->select('userid');
        $this->db->where('id', $id);
        $result      = $this->db->get('tblcontacts')->row();
        $customer_id = $result->userid;
        do_action('before_delete_contact', $id);
        $this->db->where('id', $id);
        $this->db->delete('tblcontacts');
        if ($this->db->affected_rows() > 0) {
            if (is_dir(get_upload_path_by_type('contact_profile_images') . $id)) {
                delete_dir(get_upload_path_by_type('contact_profile_images') . $id);
            }

            $this->db->where('contact_id', $id);
            $this->db->delete('tblcustomerfiles_shares');

            $this->db->where('userid', $id);
            $this->db->where('staff', 0);
            $this->db->delete('tbldismissedannouncements');

            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'contacts');
            $this->db->delete('tblcustomfieldsvalues');

            $this->db->where('userid', $id);
            $this->db->delete('tblcontactpermissions');

            // Delete autologin if found
            $this->db->where('user_id', $id);
            $this->db->where('staff', 0);
            $this->db->delete('tbluserautologin');

            return true;
        }

        return false;
    }

    /**
     * Get customer default currency
     * @param  mixed $id customer id
     * @return mixed
     */
    public function get_customer_default_currency($id)
    {
        $this->db->where('userid', $id);
        $result = $this->db->get('tblclients')->row();
        if ($result) {
            return $result->default_currency;
        }

        return false;
    }

    /**
     *  Get customer billing details
     * @param   mixed $id   customer id
     * @return  array
     */
    public function get_customer_billing_and_shipping_details($id)
    {
        $this->db->select('billing_street,billing_city,billing_state,billing_zip,billing_country,shipping_street,shipping_city,shipping_state,shipping_zip,shipping_country');
        $this->db->from('tblclients');
        $this->db->where('userid', $id);

        return $this->db->get()->result_array();
    }

    /**
     * Get customer files uploaded in the customer profile
     * @param  mixed $id    customer id
     * @param  array  $where perform where
     * @return array
     */
    public function get_customer_files($id, $where = array())
    {
        $this->db->where($where);
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'customer');
        $this->db->order_by('dateadded', 'desc');

        return $this->db->get('tblfiles')->result_array();
    }

    /**
     *  Get customer attachment
     * @param   mixed $id   customer id
     * @return  array
     */
    public function get_all_customer_attachments($id)
    {
        $attachments             = array();
        $attachments['invoice']  = array();
        $attachments['estimate'] = array();
        $attachments['proposal'] = array();
        $attachments['contract'] = array();
        $attachments['lead']     = array();
        $attachments['task']     = array();
        $attachments['customer'] = array();
        $attachments['ticket']   = array();
        $attachments['expense']  = array();

        $has_permission_expenses_view = has_permission('expenses', '', 'view');
        $has_permission_expenses_own  = has_permission('expenses', '', 'view_own');
        if ($has_permission_expenses_view || $has_permission_expenses_own) {
            // Expenses
            $this->db->select('clientid,id');
            $this->db->where('clientid', $id);
            if (!$has_permission_expenses_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }
            $this->db->from('tblexpenses');
            $expenses = $this->db->get()->result_array();
            foreach ($expenses as $expense) {
                $this->db->where('rel_id', $expense['id']);
                $this->db->where('rel_type', 'expense');
                $_attachments = $this->db->get('tblfiles')->result_array();
                if (count($_attachments) > 0) {
                    foreach ($_attachments as $_att) {
                        array_push($attachments['expense'], $_att);
                    }
                }
            }
        }


        $has_permission_invoices_view = has_permission('invoices', '', 'view');
        $has_permission_invoices_own  = has_permission('invoices', '', 'view_own');
        if ($has_permission_invoices_view || $has_permission_invoices_own) {
            // Invoices
            $this->db->select('clientid,id');
            $this->db->where('clientid', $id);

            if (!$has_permission_invoices_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }

            $this->db->from('tblinvoices');
            $invoices = $this->db->get()->result_array();
            foreach ($invoices as $invoice) {
                $this->db->where('rel_id', $invoice['id']);
                $this->db->where('rel_type', 'invoice');
                $_attachments = $this->db->get('tblfiles')->result_array();
                if (count($_attachments) > 0) {
                    foreach ($_attachments as $_att) {
                        array_push($attachments['invoice'], $_att);
                    }
                }
            }
        }

        $permission_estimates_view = has_permission('estimates', '', 'view');
        $permission_estimates_own  = has_permission('estimates', '', 'view_own');

        if ($permission_estimates_view || $permission_estimates_own) {
            // Estimates
            $this->db->select('clientid,id');
            $this->db->where('clientid', $id);
            if (!$permission_estimates_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }
            $this->db->from('tblestimates');
            $estimates = $this->db->get()->result_array();
            foreach ($estimates as $estimate) {
                $this->db->where('rel_id', $estimate['id']);
                $this->db->where('rel_type', 'estimate');
                $_attachments = $this->db->get('tblfiles')->result_array();
                if (count($_attachments) > 0) {
                    foreach ($_attachments as $_att) {
                        array_push($attachments['estimate'], $_att);
                    }
                }
            }
        }

        $has_permission_proposals_view = has_permission('proposals', '', 'view');
        $has_permission_proposals_own  = has_permission('proposals', '', 'view_own');

        if ($has_permission_proposals_view || $has_permission_proposals_own) {
            // Proposals
            $this->db->select('rel_id,id');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'customer');
            if (!$has_permission_proposals_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }
            $this->db->from('tblproposals');
            $proposals = $this->db->get()->result_array();
            foreach ($proposals as $proposal) {
                $this->db->where('rel_id', $proposal['id']);
                $this->db->where('rel_type', 'proposal');
                $_attachments = $this->db->get('tblfiles')->result_array();
                if (count($_attachments) > 0) {
                    foreach ($_attachments as $_att) {
                        array_push($attachments['proposal'], $_att);
                    }
                }
            }
        }

        $permission_contracts_view = has_permission('contracts', '', 'view');
        $permission_contracts_own  = has_permission('contracts', '', 'view_own');
        if ($permission_contracts_view || $permission_contracts_own) {
            // Contracts
            $this->db->select('client,id');
            $this->db->where('client', $id);
            if (!$permission_contracts_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }
            $this->db->from('tblcontracts');
            $contracts = $this->db->get()->result_array();
            foreach ($contracts as $contract) {
                $this->db->where('rel_id', $contract['id']);
                $this->db->where('rel_type', 'contract');
                $_attachments = $this->db->get('tblfiles')->result_array();
                if (count($_attachments) > 0) {
                    foreach ($_attachments as $_att) {
                        array_push($attachments['contract'], $_att);
                    }
                }
            }
        }

        $customer = $this->get($id);
        if ($customer->leadid != null) {
            $this->db->where('rel_id', $customer->leadid);
            $this->db->where('rel_type', 'lead');
            $_attachments = $this->db->get('tblfiles')->result_array();
            if (count($_attachments) > 0) {
                foreach ($_attachments as $_att) {
                    array_push($attachments['lead'], $_att);
                }
            }
        }
        $this->db->select('ticketid,userid');
        $this->db->where('userid', $id);
        $this->db->from('tbltickets');
        $tickets = $this->db->get()->result_array();
        foreach ($tickets as $ticket) {
            $this->db->where('ticketid', $ticket['ticketid']);
            $_attachments = $this->db->get('tblticketattachments')->result_array();
            if (count($_attachments) > 0) {
                foreach ($_attachments as $_att) {
                    array_push($attachments['ticket'], $_att);
                }
            }
        }

        $has_permission_tasks_view = has_permission('tasks', '', 'view');
        $this->db->select('rel_id,id');
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'customer');

        if (!$has_permission_tasks_view) {
            $this->db->where(get_tasks_where_string(false));
        }

        $this->db->from('tblstafftasks');
        $tasks = $this->db->get()->result_array();
        foreach ($tasks as $task) {
            $this->db->where('rel_type', 'task');
            $this->db->where('rel_id', $task['id']);
            $_attachments = $this->db->get('tblfiles')->result_array();
            if (count($_attachments) > 0) {
                foreach ($_attachments as $_att) {
                    array_push($attachments['task'], $_att);
                }
            }
        }

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'customer');
        $client_main_attachments = $this->db->get('tblfiles')->result_array();

        $attachments['customer'] = $client_main_attachments;

        return $attachments;
    }

    /**
     * Delete customer attachment uploaded from the customer profile
     * @param  mixed $id attachment id
     * @return boolean
     */
    public function delete_attachment($id)
    {
        $this->db->where('id', $id);
        $attachment = $this->db->get('tblfiles')->row();
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('customer') . $attachment->rel_id . '/' . $attachment->file_name);
            }

            $this->db->where('id', $id);
            $this->db->delete('tblfiles');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                $this->db->where('file_id', $id);
                $this->db->delete('tblcustomerfiles_shares');
                logActivity('Customer Attachment Deleted [CustomerID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('customer') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('customer') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    delete_dir(get_upload_path_by_type('customer') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Update contact status Active/Inactive
     */
    public function change_contact_status($id, $status)
    {
        $hook_data['id']     = $id;
        $hook_data['status'] = $status;
        $hook_data           = do_action('change_contact_status', $hook_data);
        $status              = $hook_data['status'];
        $id                  = $hook_data['id'];
        $this->db->where('id', $id);
        $this->db->update('tblcontacts', array(
            'active' => $status
        ));
        if ($this->db->affected_rows() > 0) {
            logActivity('Contact Status Changed [ContactID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Update client status Active/Inactive
     */
    public function change_client_status($id, $status)
    {
        $this->db->where('userid', $id);
        $this->db->update('tblclients', array(
            'active' => $status
        ));

        if ($this->db->affected_rows() > 0) {
            logActivity('Customer Status Changed [CustomerID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  mixed $_POST data
     * @return mixed
     * Change contact password, used from client area
     */
    public function change_contact_password($data)
    {
        $hook_data['data'] = $data;
        $hook_data         = do_action('before_contact_change_password', $hook_data);
        $data              = $hook_data['data'];

        // Get current password
        $this->db->where('id', get_contact_user_id());
        $client = $this->db->get('tblcontacts')->row();
        $this->load->helper('phpass');
        $hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
        if (!$hasher->CheckPassword($data['oldpassword'], $client->password)) {
            return array(
                'old_password_not_match' => true
            );
        }
        $update_data['password']             = $hasher->HashPassword($data['newpasswordr']);
        $update_data['last_password_change'] = date('Y-m-d H:i:s');
        $this->db->where('id', get_contact_user_id());
        $this->db->update('tblcontacts', $update_data);
        if ($this->db->affected_rows() > 0) {
            logActivity('Contact Password Changed [ContactID: ' . get_contact_user_id() . ']');

            return true;
        }

        return false;
    }

    /**
     * Get customer groups where customer belongs
     * @param  mixed $id customer id
     * @return array
     */
    public function get_customer_groups($id)
    {
        $this->db->where('customer_id', $id);

        return $this->db->get('tblcustomergroups_in')->result_array();
    }

    /**
     * Get all customer groups
     * @param  string $id
     * @return mixed
     */
    public function get_groups($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get('tblcustomersgroups')->row();
        }
        $this->db->order_by('name', 'asc');

        return $this->db->get('tblcustomersgroups')->result_array();
    }

    /**
     * Delete customer groups
     * @param  mixed $id group id
     * @return boolean
     */
    public function delete_group($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('tblcustomersgroups');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('groupid', $id);
            $this->db->delete('tblcustomergroups_in');
            logActivity('Customer Group Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Add new customer groups
     * @param array $data $_POST data
     */
    public function add_group($data)
    {
        $this->db->insert('tblcustomersgroups', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            logActivity('New Customer Group Created [ID:' . $insert_id . ', Name:' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Edit customer group
     * @param  array $data $_POST data
     * @return boolean
     */
    public function edit_group($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update('tblcustomersgroups', array(
            'name' => $data['name']
        ));
        if ($this->db->affected_rows() > 0) {
            logActivity('Customer Group Updated [ID:' . $data['id'] . ']');

            return true;
        }

        return false;
    }

    public function vault_entry_create($data, $customer_id)
    {
        $data['date_created'] = date('Y-m-d H:i:s');
        $data['customer_id'] = $customer_id;
        if(isset($data['share_in_projects'])){
            $data['share_in_projects'] = 1;
        } else {
            $data['share_in_projects'] = 0;
        }
        $this->db->insert('tblvault', $data);
        logActivity('Vault Entry Created [Customer ID: '.$customer_id.']');
    }

    public function vault_entry_update($id, $data)
    {
        $vault = $this->get_vault_entry($id);

        $last_updated_from = $data['last_updated_from'];
        unset($data['last_updated_from']);

        if(isset($data['share_in_projects'])){
            $data['share_in_projects'] = 1;
        } else {
            $data['share_in_projects'] = 0;
        }

        $this->db->where('id', $id);
        $this->db->update('tblvault', $data);

        if ($this->db->affected_rows() > 0) {
            $this->db->where('id', $id);
            $this->db->update('tblvault', array('last_updated'=>date('Y-m-d H:i:s'), 'last_updated_from'=>$last_updated_from));
            logActivity('Vault Entry Updated [Customer ID: '.$vault->customer_id.']');
        }
    }

    public function vault_entry_delete($id)
    {
        $vault = $this->get_vault_entry($id);

        $this->db->where('id', $id);
        $this->db->delete('tblvault');

        if ($this->db->affected_rows() > 0) {
            logActivity('Vault Entry Deleted [Customer ID: '.$vault->customer_id.']');
        }
    }

    public function get_vault_entries($customer_id, $where = array())
    {
        $this->db->where('customer_id', $customer_id);
        $this->db->order_by('date_created', 'desc');

        $this->db->where($where);

        return $this->db->get('tblvault')->result_array();
    }

    public function get_vault_entry($id)
    {
        $this->db->where('id', $id);

        return $this->db->get('tblvault')->row();
    }

    public function get_statement($customer_id, $from, $to)
    {
        $sql = 'SELECT
        tblinvoices.id as invoice_id,
        hash,
        tblinvoices.date as date,
        tblinvoices.duedate,
        concat(tblinvoices.date, \' \', RIGHT(tblinvoices.datecreated,LOCATE(\' \',tblinvoices.datecreated) - 3)) as tmp_date,
        tblinvoices.duedate as duedate,
        tblinvoices.total as invoice_amount
        FROM tblinvoices WHERE clientid ='.$customer_id;

        if ($from == $to) {
            $sqlDate = 'date="'.$from.'"';
        } else {
            $sqlDate = '(date BETWEEN "' . $from . '" AND "' . $to . '")';
        }

        $sql .= ' AND ' . $sqlDate;

        $invoices = $this->db->query($sql . '
            AND status != 6
            AND status != 5
            ORDER By date DESC')->result_array();

        // Replace error ambigious column in where clause
        $sqlDatePayments = str_replace('date', 'tblinvoicepaymentrecords.date', $sqlDate);

        $sql_payments = 'SELECT
        tblinvoicepaymentrecords.id as payment_id,
        tblinvoicepaymentrecords.date as date,
        concat(tblinvoicepaymentrecords.date, \' \', RIGHT(tblinvoicepaymentrecords.daterecorded,LOCATE(\' \',tblinvoicepaymentrecords.daterecorded) - 3)) as tmp_date,
        tblinvoicepaymentrecords.invoiceid as payment_invoice_id,
        tblinvoicepaymentrecords.amount as payment_total
        FROM tblinvoicepaymentrecords
        JOIN tblinvoices ON tblinvoices.id = tblinvoicepaymentrecords.invoiceid
        WHERE '.$sqlDatePayments.' AND tblinvoices.clientid = '.$customer_id.'
        ORDER by tblinvoicepaymentrecords.date DESC';

        $payments = $this->db->query($sql_payments)->result_array();

        // merge both results
        $merged = array_merge($invoices, $payments);

        // sort by date
        usort($merged, function ($a, $b) {
            // fakde date select sorting
            return strtotime($a['tmp_date']) - strtotime($b['tmp_date']);
        });

        // Define final result variable
        $result = array();
        // Store in result array key
        $result['result'] = $merged;

        // Invoiced amount during the period
        $result['invoiced_amount'] = $this->db->query('SELECT
        SUM(tblinvoices.total) as invoiced_amount
        FROM tblinvoices
        WHERE clientid = '.$customer_id . '
        AND ' . $sqlDate . ' AND status != 5 and status != 6')
            ->row()->invoiced_amount;

        if ($result['invoiced_amount'] === null) {
            $result['invoiced_amount'] = 0;
        }

        // Amount paid during the period
        $result['amount_paid'] = $this->db->query('SELECT
        SUM(tblinvoicepaymentrecords.amount) as amount_paid
        FROM tblinvoicepaymentrecords
        JOIN tblinvoices ON tblinvoices.id = tblinvoicepaymentrecords.invoiceid
        WHERE '.$sqlDatePayments.' AND tblinvoices.clientid = '.$customer_id)
            ->row()->amount_paid;

        if ($result['amount_paid'] === null) {
            $result['amount_paid'] = 0;
        }

        // Beginning balance is all invoices amount before the FROM date - payments received before FROM date
        $result['beginning_balance'] = $this->db->query('
            SELECT (CAST(SUM(tblinvoices.total) - (SELECT COALESCE(SUM(tblinvoicepaymentrecords.amount),0)
            FROM tblinvoicepaymentrecords
            JOIN tblinvoices ON tblinvoices.id = tblinvoicepaymentrecords.invoiceid
            WHERE tblinvoicepaymentrecords.date < "' . $from . '"
            AND tblinvoices.clientid='.$customer_id.') as SIGNED))
            as beginning_balance FROM tblinvoices
            WHERE date < "' . $from . '"
            AND clientid = '.$customer_id .'
            AND status != 6
            AND status != 5')
              ->row()->beginning_balance;

        if ($result['beginning_balance'] === null) {
            $result['beginning_balance'] = 0;
        }

        $result['balance_due'] = ($result['invoiced_amount'] - $result['amount_paid']) + $result['beginning_balance'];
        $result['client_id'] = $customer_id;
        $result['client'] = $this->get($customer_id);
        $result['from'] = $from;
        $result['to'] = $to;

        $customer_currency = $this->get_customer_default_currency($customer_id);
        $this->load->model('currencies_model');

        if ($customer_currency != 0) {
            $currency = $this->currencies_model->get($customer_currency);
        } else {
            $currency = $this->currencies_model->get_base_currency();
        }

        $result['currency'] = $currency;

        return $result;
    }

    public function send_statement_to_email($customer_id, $send_to, $from, $to, $cc = '')
    {
        $this->load->model('emails_model');
        $send = false;
        if (is_array($send_to) && count($send_to) > 0) {
            $statement = $this->get_statement($customer_id, to_sql_date($from), to_sql_date($to));
            $pdf    = statement_pdf($statement);
            $pdf_file_name = slug_it(_l('customer_statement').'-'.$statement['client']->company);
            $attach = $pdf->Output($pdf_file_name . '.pdf', 'S');
            $i              = 0;
            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    $this->emails_model->add_attachment(array(
                            'attachment' => $attach,
                            'filename' => $pdf_file_name . '.pdf',
                            'type' => 'application/pdf'
                        ));

                    $contact      = $this->clients_model->get_contact($contact_id);
                    $merge_fields = array();
                    $merge_fields = array_merge(
                        $merge_fields,
                        get_client_contact_merge_fields($statement['client']->userid,
                        $contact_id)
                    );

                    $merge_fields = array_merge($merge_fields, get_statement_merge_fields($statement));

                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }
                    if ($this->emails_model->send_email_template('client-statement', $contact->email, $merge_fields, '', $cc)) {
                        $send = true;
                    }
                }
                $i++;
            }
            if ($send) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
