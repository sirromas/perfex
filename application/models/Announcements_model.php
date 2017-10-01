<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Announcements_model extends CRM_Model
{
    public function __construct()
    {
        parent::__construct();
    }

   /**
    * Get announcements
    * @param  string $id    optional id
    * @param  array  $where perform where
    * @param  string $limit
    * @return mixed
    */
    public function get($id = '', $where = array(), $limit = '')
    {
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('announcementid', $id);

            return $this->db->get('tblannouncements')->row();
        }
        if (is_client_logged_in()) {
            $this->db->where('showtousers', 1);
        } elseif (is_staff_logged_in()) {
            $this->db->where('showtostaff', 1);
        }
        $this->db->order_by('dateadded', 'desc');
        if (is_numeric($limit)) {
            $this->db->limit($limit);
        }

        return $this->db->get('tblannouncements')->result_array();
    }

    /**
     * Get total dismissed announcements for logged in user
     * @return mixed
     */
    public function get_total_undismissed_announcements()
    {
        $total_left                    = 0;
        $announcements                 = $this->get();
        $total_announcements           = count($announcements);
        $total_dismissed_announcements = 0;
        $staff                         = 1;
        $userid                        = get_staff_user_id();
        if (is_client_logged_in()) {
            $staff  = 0;
            $userid = get_client_user_id();
        }
        foreach ($announcements as $announcement) {
            if (total_rows('tbldismissedannouncements', array(
                'announcementid' => $announcement['announcementid'],
                'userid' => $userid,
                'staff' => $staff
            )) > 0) {
                $total_dismissed_announcements++;
            }
        }
        if ($total_dismissed_announcements != $total_announcements) {
            $total_left = $total_announcements - $total_dismissed_announcements;
        }

        return $total_left;
    }

    /**
     * @param $_POST array
     * @return Insert ID
     * Add new announcement calling this function
     */
    public function add($data)
    {
        $data['dateadded'] = date('Y-m-d H:i:s');

        if (isset($data['showname'])) {
            $data['showname'] = 1;
        } else {
            $data['showname'] = 0;
        }
        if (isset($data['showtostaff'])) {
            $data['showtostaff'] = 1;
        } else {
            $data['showtostaff'] = 0;
        }
        if (isset($data['showtousers'])) {
            $data['showtousers'] = 1;
        } else {
            $data['showtousers'] = 0;
        }
        $data['message'] = $data['message'];
        $data['userid']  = get_staff_full_name(get_staff_user_id());
        $data            = do_action('before_announcement_added', $data);
        $this->db->insert('tblannouncements', $data);
        $insert_id = $this->db->insert_id();
        do_action('after_announcement_added', $insert_id);
        logActivity('New Announcement Added [' . $data['name'] . ']');

        return $insert_id;
    }

    /**
     * @param  $_POST array
     * @param  integer
     * @return boolean
     * This function updates announcement
     */
    public function update($data, $id)
    {
        if (isset($data['showname'])) {
            $data['showname'] = 1;
        } else {
            $data['showname'] = 0;
        }
        if (isset($data['showtostaff'])) {
            $data['showtostaff'] = 1;
        } else {
            $data['showtostaff'] = 0;
        }
        if (isset($data['showtousers'])) {
            $data['showtousers'] = 1;
        } else {
            $data['showtousers'] = 0;
        }
        $data['message'] = $data['message'];
        $_data           = do_action('before_announcement_updated', array(
            'data' => $data,
            'id' => $id
        ));
        $data            = $_data['data'];
        $this->db->where('announcementid', $id);
        $this->db->update('tblannouncements', $data);
        if ($this->db->affected_rows() > 0) {
            do_action('after_announcement_updated', $id);
            logActivity('Announcement Updated [' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer
     * @return boolean
     * Delete Announcement
     * All Dimissed announcements from database will be cleaned
     */
    public function delete($id)
    {
        do_action('before_announcement_deleted', $id);
        $this->db->where('announcementid', $id);
        $this->db->delete('tblannouncements');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('announcementid', $id);
            $this->db->delete('tbldismissedannouncements');

            logActivity('Announcement Deleted [' . $id . ']');

            return true;
        }

        return false;
    }
}
