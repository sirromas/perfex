<?php
/**
 * Created by PhpStorm.
 * User: moyo
 * Date: 10/4/17
 * Time: 19:08
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/class.pdo.database.php';

class Import
{

    public $db;
    public $customers_file;
    public $products_file;
    public $users_file;

    function __construct()
    {
        $this->db = new pdo_db();
        $this->customers_file = $_SERVER['DOCUMENT_ROOT'] . '/data/customer_list.csv';
        $this->products_file = $_SERVER['DOCUMENT_ROOT'] . '/data/product_list.csv';
        $this->users_file = $_SERVER['DOCUMENT_ROOT'] . '/data/users.csv';
    }



    /*****************************************************************************************************************
     *
     *
     *                                           Employees import
     *
     *
     *****************************************************************************************************************/

    /**
     * @param $item
     * @return array
     */
    function get_user_names($item)
    {
        $names = explode(' ', $item);
        if (count($names) == 2) {
            $fname = $names[0];
            $lname = $names[1];
        } // end if
        else {
            $fname = $names[0];
            $lname = '';
        }
        $data = array('fname' => $fname, 'lname' => $lname);
        return $data;
    }

    /**
     * @param $item
     */
    function create_new_staff_account($item)
    {
        $query = "insert into tblstaff 
                (email,
                firstname,
                lastname,
                password,
                datecreated) 
                VALUES ('$item->email',
                        '$item->fname',
                        '$item->lname',
                        '$item->pwd',
                        '$item->date')";
        $this->db->query($query);
    }

    /**
     *
     */
    function import_users_data()
    {
        $path = $this->users_file;
        $row = 0;
        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row > 0) {
                    $pwd = 'n/a';
                    $date = date('m-d-Y H:i:s', time());
                    $names = $this->get_user_names($data[2]);
                    $fname = $names['fname'];
                    $lname = $names['lname'];
                    $email = $data[3];

                    $item = new stdClass();
                    $item->fname = $fname;
                    $item->lname = $lname;
                    $item->email = $email;
                    $item->pwd = $pwd;
                    $item->date = $date;
                    $this->create_new_staff_account($item);
                    echo "User fname: " . $fname . " User lname: " . $lname . " User email: " . $email . " User pwd: " . $pwd . " User created: " . $date;
                    echo "<br>------------------------------------------------------------------------------------<br>";
                } // end if $row>0
                $row++;
            }
            fclose($handle);
        }
    }

    /*****************************************************************************************************************
     *
     *
     *                                          Clients import
     *
     *
     *****************************************************************************************************************/

    /**
     * @param $staff_id
     * @param $customer_id
     */
    function assign_client_admin($staff_id, $customer_id)
    {
        $date = date('m-d-Y H:i:s', time());
        $query = "insert into tblcustomeradmins 
                (staff_id,
                customer_id,
                date_assigned) 
                values ($staff_id,$customer_id,'$date')";
        echo "Query: " . $query;
        echo "<br>-----------------------------------------------------------------------------------------<br>";
        $this->db->query($query);
    }

    /**
     * @param $name
     * @return mixed
     */
    function get_client_admin($name)
    {
        echo "Function get_client_admin called ...<br>";
        $names = explode(' ', $name);
        if (count($names) == 2) {
            $query = "select * from tblstaff 
                    where firstname='" . $names[0] . "' 
                    and lastname='" . $names[1] . "'";
        } // end if
        else {
            $query = "select * from tblstaff 
                    where firstname='" . $names[0] . "'";
        } // end else
        $result = $this->db->query($query);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['staffid'];
        }
        return $id;
    }

    /**
     * @param $client_id
     * @param $region_name
     */
    function assign_client_region($client_id, $region_name)
    {
        $query = "insert into tblcustomfieldsvalues 
                  (relid,
                  fieldid,
                  fieldto,
                  value) values ($client_id,1,'customers','$region_name')";
        echo "Query: " . $query;
        echo "<br>-----------------------------------------------------------------------------------------<br>";
        $this->db->query($query);
    }


    /**
     * @param $client_id
     * @param $visit_date
     */
    function assign_client_last_visit($client_id, $visit_date)
    {
        $query = "insert into tblcustomfieldsvalues 
                  (relid,
                  fieldid,
                  fieldto,
                  value) values ($client_id,2,'customers','$visit_date')";
        echo "Query: " . $query;
        echo "<br>-----------------------------------------------------------------------------------------<br>";
        $this->db->query($query);
    }

    /**
     * @param $item
     */
    function add_new_client($item)
    {
        $date = date('m-d-Y', time());
        $query = "insert into tblclients 
                (company,
                city,
                zip,
                state,
                address,
                datecreated) 
                values ('$item->company',
                        '$item->city',
                        '$item->zip',
                        '$item->state',
                        '$item->address',
                        '$date')";
        echo "Query: " . $query;
        echo "<br>-----------------------------------------------------------------------------------------<br>";
        $this->db->query($query);
        $stmt = $this->db->query("SELECT LAST_INSERT_ID()");
        $lastid_arr = $stmt->fetch(PDO::FETCH_NUM);
        $lastId = $lastid_arr[0];
        return $lastId;
    }

    /**
     *
     */
    function import_clients()
    {
        echo "Import clients started ....<br>";
        $path = $this->customers_file;
        echo "Path: " . $path . "<br>";

        $row = 0;
        if (($handle = fopen($path, "r")) !== FALSE) {
            echo "Opened the file ....<br>";
            while (($data = fgetcsv($handle, 1000000, ",")) !== FALSE) {
                if ($row > 0) {
                    $company = $data[0];
                    $address = $data[1];
                    $city = $data[2];
                    $state = $data[3];
                    $zip = $data[4];
                    $staff = $data[5];
                    if ($staff != '') {
                        $staffid = $this->get_client_admin($staff);
                    } // end if
                    else {
                        $staffid = 0;
                    }
                    $lastvisit = $data['8'];
                    if ($lastvisit != '') {
                        $d = new DateTime($lastvisit);
                        $formatted_date = $d->format('Y-m-d');
                    } // end if
                    else {
                        $formatted_date = '';
                    }

                    $item = new stdClass();
                    $item->company = $company;
                    $item->address = $address;
                    $item->city = $city;
                    $item->state = $state;
                    $item->zip = $zip;
                    $item->staff = $staff;
                    $item->staffid = $staffid;
                    $item->date = $formatted_date;

                    echo "Company: " . $company .
                        " Address: " . $address .
                        " City: " . $city .
                        " Region: " . $state .
                        " ZIP:" . $zip .
                        " Staff: " . $staff .
                        " Staff ID: " . $staffid .
                        " Last visit: " . $formatted_date . "";
                    echo "<br>-----------------------------------------------------------------------------------------<br>";
                    $client_id = $this->add_new_client($item);
                    if ($client_id > 0) {
                        // Assign client admin
                        if ($staffid > 0) {
                            $this->assign_client_admin($staffid, $client_id);
                        }
                        // Assign client last visit
                        $this->assign_client_last_visit($client_id, $formatted_date);

                        // Assign client region
                        if ($state != '') {
                            $this->assign_client_region($client_id, $state);
                        }
                    } // end if $client_id>0
                } // end if $row > 0
                $row++;
            } // end while

        } // end if $handle
        else {
            echo "I was not able to open file ....<br>";
        }
    }

    /*****************************************************************************************************************
     *
     *
     *                                          Products import
     *
     *
     *****************************************************************************************************************/



    function add_new_product_item($item)
    {
        $query = "insert into tblitems 
                (description,
                long_description,
                rate,
                unit) 
                values ('$item->d1','$item->d2','0','1')";
        echo "Query: " . $query;
        echo "<br>-----------------------------------------------------------------------------------------<br>";
        $this->db->query($query);
    }

    /**
     *
     */
    function import_products()
    {

        echo "Import products started ....<br>";
        $path = $this->products_file;
        echo "Path: " . $path . "<br>";

        $row = 0;
        if (($handle = fopen($path, "r")) !== FALSE) {
            echo "Opened the file ....<br>";
            while (($data = fgetcsv($handle, 1000000, ",")) !== FALSE) {
                if ($row > 0) {
                    $d1 = $data[1];
                    $d2 = $data[2];
                    $item = new stdClass();
                    $item->d1 = $d1;
                    $item->d2 = $d2;
                    $this->add_new_product_item($item);
                } // end if $row > 0
                $row++;
            } // end while
        } // end if handle
    }


}