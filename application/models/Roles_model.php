<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Roles_model extends CRM_Model
{

    private $perm_statements = array(
        'view',
        'view_own',
        'edit',
        'create',
        'delete'
    );

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add new employee role
     *
     * @param mixed $data
     */
    public function add($data)
    {
        $permissions = array();
        if (isset($data['view'])) {
            $permissions['view'] = $data['view'];
            unset($data['view']);
        }

        if (isset($data['view_own'])) {
            $permissions['view_own'] = $data['view_own'];
            unset($data['view_own']);
        }
        if (isset($data['edit'])) {
            $permissions['edit'] = $data['edit'];
            unset($data['edit']);
        }
        if (isset($data['create'])) {
            $permissions['create'] = $data['create'];
            unset($data['create']);
        }
        if (isset($data['delete'])) {
            $permissions['delete'] = $data['delete'];
            unset($data['delete']);
        }

        $this->db->insert('tblroles', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $_all_permissions = $this->roles_model->get_permissions();
            foreach ($_all_permissions as $permission) {
                $this->db->insert('tblrolepermissions', array(
                    'permissionid' => $permission['permissionid'],
                    'roleid' => $insert_id,
                    'can_view' => 0,
                    'can_view_own' => 0,
                    'can_edit' => 0,
                    'can_create' => 0,
                    'can_delete' => 0
                ));
            }

            foreach ($this->perm_statements as $c) {
                foreach ($permissions as $key => $p) {
                    if ($key == $c) {
                        foreach ($p as $perm) {
                            $this->db->where('roleid', $insert_id);
                            $this->db->where('permissionid', $perm);
                            $this->db->update('tblrolepermissions', array(
                                'can_' . $c => 1
                            ));
                        }
                    }
                }
            }

            logActivity('New Role Added [ID: ' . $insert_id . '.' . $data['name'] . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update employee role
     *
     * @param array $data
     *            role data
     * @param mixed $id
     *            role id
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;
        $permissions = array();
        if (isset($data['view'])) {
            $permissions['view'] = $data['view'];
            unset($data['view']);
        }

        if (isset($data['view_own'])) {
            $permissions['view_own'] = $data['view_own'];
            unset($data['view_own']);
        }
        if (isset($data['edit'])) {
            $permissions['edit'] = $data['edit'];
            unset($data['edit']);
        }
        if (isset($data['create'])) {
            $permissions['create'] = $data['create'];
            unset($data['create']);
        }
        if (isset($data['delete'])) {
            $permissions['delete'] = $data['delete'];
            unset($data['delete']);
        }
        $update_staff_permissions = false;
        if (isset($data['update_staff_permissions'])) {
            $update_staff_permissions = true;
            unset($data['update_staff_permissions']);
        }
        $this->db->where('roleid', $id);
        $this->db->update('tblroles', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        $all_permissions = $this->roles_model->get_permissions();
        if (total_rows('tblrolepermissions', array(
                'roleid' => $id
            )) == 0) {
            foreach ($all_permissions as $p) {
                $_ins = array();
                $_ins['roleid'] = $id;
                $_ins['permissionid'] = $p['permissionid'];
                $this->db->insert('tblrolepermissions', $_ins);
            }
        } elseif (total_rows('tblrolepermissions', array(
                'roleid' => $id
            )) != count($all_permissions)) {
            foreach ($all_permissions as $p) {
                if (total_rows('tblrolepermissions', array(
                        'roleid' => $id,
                        'permissionid' => $p['permissionid']
                    )) == 0) {
                    $_ins = array();
                    $_ins['roleid'] = $id;
                    $_ins['permissionid'] = $p['permissionid'];
                    $this->db->insert('tblrolepermissions', $_ins);
                }
            }
        }

        $_permission_restore_affected_rows = 0;
        foreach ($all_permissions as $permission) {
            foreach ($this->perm_statements as $c) {
                $this->db->where('roleid', $id);
                $this->db->where('permissionid', $permission['permissionid']);
                $this->db->update('tblrolepermissions', array(
                    'can_' . $c => 0
                ));
                if ($this->db->affected_rows() > 0) {
                    $_permission_restore_affected_rows++;
                }
            }
        }

        $_new_permissions_added_affected_rows = 0;
        foreach ($permissions as $key => $val) {
            foreach ($val as $p) {
                $this->db->where('roleid', $id);
                $this->db->where('permissionid', $p);
                $this->db->update('tblrolepermissions', array(
                    'can_' . $key => 1
                ));
                if ($this->db->affected_rows() > 0) {
                    $_new_permissions_added_affected_rows++;
                }
            }
        }
        if ($_new_permissions_added_affected_rows != $_permission_restore_affected_rows) {
            $affectedRows++;
        }

        if ($update_staff_permissions == true) {
            $this->load->model('staff_model');
            $staff = $this->staff_model->get('', '', array(
                'role' => $id
            ));
            foreach ($staff as $m) {
                if ($this->staff_model->update_permissions($permissions, $m['staffid'])) {
                    $affectedRows++;
                }
            }
        }

        if ($affectedRows > 0) {
            logActivity('Role Updated [ID: ' . $id . '.' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Get employee role by id
     *
     * @param mixed $id
     *            Optional role id
     * @return mixed array if not id passed else object
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('roleid', $id);

            return $this->db->get('tblroles')->row();
        }

        return $this->db->get('tblroles')->result_array();
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get2($id = '')
    {
        $current_user_role = $this->get_current_user_role($this->session->userdata('staff_user_id'));
        if (is_numeric($id)) {
            $this->db->where('roleid', $id);
            return $this->db->get('tblroles')->row();
        }  // end if
        else {
            switch ($current_user_role) {
                case 3:
                    // It is manager
                    $query = "select * from tblroles where roleid=1";
                    $result = $this->db->query($query);
                    foreach ($result->result() as $row) {
                        $item = array('roleid' => $row->roleid, 'name' => $row->name);
                        $data[] = $item;
                    }
                    return $data;
                    break;
                case 4:
                    // It is director
                    $query = "select * from tblroles where roleid<4";
                    $result = $this->db->query($query);
                    foreach ($result->result() as $row) {
                        $item = array('roleid' => $row->roleid, 'name' => $row->name);
                        $data[] = $item;
                    }
                    return $data;
                    break;
                default:
                    return $this->db->get('tblroles')->result_array();
            } // end of switch
        } // end else

    }

    /**
     * @param $staffid
     * @return string
     */
    public function get_user_team($staffid)
    {
        $query = "select * from tblcustomfieldsvalues where fieldid=9  and relid=$staffid";
        $result = $this->db->query($query);
        $num = $result->num_rows();
        if ($num > 0) {
            foreach ($result->result() as $row) {
                $teamname = $row->value;
            }
        } // end if
        else {
            $teamname = '';
        }
        return $teamname;
    }


    /**
     * @param $teamname
     * @param bool $array_output
     * @return array|string
     */
    public function get_team_clients($teamname, $array_output = false)
    {
        $users=array();
        if ($teamname != '') {
            $query = "select * from tblcustomfieldsvalues where fieldid=9 and value='$teamname'";
            $result = $this->db->query($query);
            foreach ($result->result() as $row) {
                $users[] = $row->relid;
            } // end foreach
            if (count($users)>0) {
                $users_list = implode(',', $users);
                $query = "select * from tblcustomeradmins where staff_id in ($users_list)";
                $result = $this->db->query($query);
                foreach ($result->result() as $row) {
                    $clients[] = $row->customer_id;
                }
            } // end if ount($users)>0
            else {
                $clients[]=0;
            }
        } // if $teamname!=''
        else {
            $clients[] = 0;
        }
        if ($array_output == false) {
            $team_clients = implode(',', $clients);
        } // end if
        else {
            $team_clients = $clients;
        } // end else
        return $team_clients;
    }

    /**
     * @param $staffid
     * @return string
     */
    public function get_user_clients($staffid)
    {
        $query = "select * from tblcustomeradmins where staff_id=$staffid";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $clients[] = $row->customer_id;
        }
        $team_users = implode(',', $clients);
        return $team_users;
    }


    /**
     * @param $id
     * @return mixed
     */
    public function get_current_user_role($id)
    {
        $query = "select * from tblstaff WHERE  staffid=$id";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $role = $row->role;
        }
        return $role;
    }

    /**
     * @param $clientid
     * @param $teamname
     * @return bool
     */
    public function is_team_client($clientid, $teamname)
    {
        $query = "select * from tblcustomfieldsvalues where fieldid=9 and value='$teamname'";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $users[] = $row->relid;
        } // end foreach
        $users_list = implode(',', $users);

        $query = "select * from tblcustomeradmins where staff_id in ($users_list)";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $clients[] = $row->customer_id;
        }
        return in_array($clientid, $clients);

    }

    /**
     * @param $clientid
     * @param $staffid
     * @return bool
     */
    public function is_my_client($clientid, $staffid)
    {
        $query = "select * from tblcustomeradmins where staff_id=$staffid";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $clients[] = $row->customer_id;
        }
        return in_array($clientid, $clients);
    }

    /**
     * @return string
     */
    function get_teams_tab()
    {
        $list = "";
        $query = "select * from tblcustomfields where id=9";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $options = $row->options;
        }
        $optArr = explode(',', $options);
        foreach ($optArr as $item) {
            $list .= "<div class='row'>";
            $list .= "<span class='col-md-1'><input type='checkbox' class='teamItem'  value='" . trim($item) . "'></span>";
            $list .= "<span class='col-md-11'><input type='text' style='width: 100%;' value='$item' id='" . trim($item) . "'></span>";
            $list .= "</div><br>";
        } // end foreach
        $list .= "<br><div class='row'>";
        $list .= "<span class='col-md-1'>&nbsp;</span>";
        $list .= "<span class='col-md-4'>";
        $list .= "<button class='btn-default' id='addTeam'>Add</button>&nbsp;&nbsp;";
        $list .= "<button class='btn-default' id='updateTeams'>Update</button>&nbsp;&nbsp;";
        $list .= "<button class='btn-default' id='deleteTeams'>Delete</button>";
        $list .= "</span>";
        $list .= "</div>";
        return $list;
    }


    /**
     * @param $id
     * @param $newItem
     */
    public function update_custom_field_relations($id, $newItem)
    {
        $query = "update tblcustomfieldsvalues set value='$newItem' where id=$id";
        $this->db->query($query);
    }

    /**
     * @param $item
     * @param $newitem
     */
    public function process_teams_custom_field($item, $newitem)
    {
        $query = "select * from tblcustomfields where id=9";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $oldStr = $row->options;
        }

        $newStr = str_replace($item, $newitem, $oldStr);
        $query = "update tblcustomfields set options='$newStr' where id=9";
        $this->db->query($query);

        $query = "select * from tblcustomfieldsvalues where fieldid=9 and value='$item'";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $this->update_custom_field_relations($row->id, $newitem);
        }
    }

    /**
     * @return string
     */
    public function get_add_team_modal_dialog()
    {
        $list = "";

        $list .= "<div class=\"container\">
            
              <!-- Modal -->
              <div class=\"modal fade\" id=\"myModal\" role=\"dialog\">
                <div class=\"modal-dialog\">
                
                  <!-- Modal content-->
                  <div class=\"modal-content\">
                    <div class=\"modal-header\">
                      <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                      <h4 class=\"modal-title\">Add New Team</h4>
                    </div>
                    <div class=\"modal-body\">
                      <div class='row'>
                      <span class='col-md-12'><input type='text' id='team_name' style='width: 100%;' placeholder='Team Name'></span>
                      </div><br>
                      <div class='row'>
                      <span class='col-md-12' id='team_err' style='color: red;'></span>
                      </div>
                    </div>
                    <div class=\"modal-footer\">
                      <button type='button' class='btn btn-default' id='add_team_done'>OK</button>&nbsp;
                      <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Close</button>
                    </div>
                  </div>
                  
                </div>
              </div>
              
            </div>";

        return $list;
    }


    /**
     * @param $name
     */
    public function add_new_team($name)
    {
        $query = "select * from tblcustomfields where id=9";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $oldStr = $row->options;
        }
        $tmpArr = explode(',', $oldStr);
        $oldArr = array();
        foreach ($tmpArr as $data) {
            $item = trim($data);
            array_push($oldArr, $item);
        }
        // Add new one team
        array_push($oldArr, $name);
        $newStr = implode(',', $oldArr);
        $query = "update tblcustomfields set options='$newStr' where id=9";
        $this->db->query($query);
    }

    /**
     * @param $data
     */
    public function update_teams_data($data)
    {
        $items = json_decode($data); // array of objects
        foreach ($items as $item) {
            $this->process_teams_custom_field($item->oldVal, $item->newVal);
        }
    }

    /**
     * @param $item
     * @return mixed
     */
    public function is_team_has_members($item)
    {
        $query = "select * from tblcustomfieldsvalues where fieldid=9 and value='$item'";
        $result = $this->db->query($query);
        return $result->num_rows();
    }

    /**
     * @param $item
     */
    public function delete_selected_team($item)
    {
        $query = "select * from tblcustomfields where id=9";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $oldStr = $row->options;
        }

        $tmpArr = explode(',', $oldStr);
        $oldArr = array();
        foreach ($tmpArr as $data) {
            $item = trim($data);
            array_push($oldArr, $item);
        }

        $removeArr = array();
        array_push($removeArr, trim($item));

        $newArr = array_diff($oldArr, $removeArr);

        $newStr = implode(',', $newArr);

        $query = "update tblcustomfields set options='$newStr' where id=9";
        $this->db->query($query);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function delete_teams($data)
    {
        $items = json_decode($data);
        foreach ($items as $item) {
            $status = $this->is_team_has_members($item->value);
            if ($status > 0) {
                return $status;
            } // end if
            else {
                $this->delete_selected_team($item->value);
            } // end else
        } // end foreach
        return $status;
    }

    /**
     * Delete employee role
     *
     * @param mixed $id
     *            role id
     * @return mixed
     */
    public function delete($id)
    {
        $current = $this->get($id);
        // Check first if role is used in table
        if (is_reference_in_table('role', 'tblstaff', $id)) {
            return array(
                'referenced' => true
            );
        }
        $affectedRows = 0;
        $this->db->where('roleid', $id);
        $this->db->delete('tblroles');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('roleid', $id);
        $this->db->delete('tblrolepermissions');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            logActivity('Role Deleted [ID: ' . $id);

            return true;
        }

        return false;
    }

    /**
     * Get employee role permissions
     *
     * @param mixed $id
     *            permission id
     * @return mixed if id passed return object else array
     */
    public function get_permissions($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('permissionid', $id);

            return $this->db->get('tblpermissions')->row();
        }
        $this->db->order_by('name', 'asc');

        return $this->db->get('tblpermissions')->result_array();
    }

    /**
     * Get specific role permissions
     *
     * @param mixed $id
     *            role id
     * @return array
     */
    public function get_role_permissions($id)
    {
        $this->db->where('roleid', $id);
        $this->db->join('tblpermissions', 'tblpermissions.permissionid = tblrolepermissions.permissionid', 'left');

        return $this->db->get('tblrolepermissions')->result_array();
    }

    /**
     * Get staff permission / Staff can have other permissions too different from the role which is assigned
     *
     * @param mixed $id
     *            Optional - staff id
     * @return array
     */
    public function get_staff_permissions($id = '')
    {
        // If not id is passed get from current user
        if ($id == false) {
            $id = get_staff_user_id();
        }
        $this->db->where('staffid', $id);

        return $this->db->get('tblstaffpermissions')->result_array();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get_contact_permissions($id)
    {
        $this->db->where('userid', $id);

        return $this->db->get('tblcontactpermissions')->result_array();
    }
}
