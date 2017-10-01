<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home extends Admin_controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('home_model');
    }

    /* This is admin home view */
    public function index()
    {
        // When user comes to dashboard in case the setup menu is opened close it
        // It's obvious that the user wont use the setup menu anymore if located in the dashboard.
        $this->session->set_userdata(array(
            'setup-menu-open' => ''
        ));

        $this->load->model('departments_model');
        $this->load->model('todo_model');
        $data['departments']               = $this->departments_model->get();

        $data['todos']                     = $this->todo_model->get_todo_items(0);
        // Only show last 5 finished todo items
        $this->todo_model->setTodosLimit(5);
        $data['todos_finished']            = $this->todo_model->get_todo_items(1);
        $data['upcoming_events_next_week'] = $this->home_model->get_upcoming_events_next_week();
        $data['upcoming_events']           = $this->home_model->get_upcoming_events();
        $data['title']                     = _l('dashboard_string');
        $this->load->model('currencies_model');
        $data['currencies']                           = $this->currencies_model->get();
        $data['base_currency']                        = $this->currencies_model->get_base_currency();
        $data['activity_log']                         = $this->misc_model->get_activity_log();
        // Tickets charts
        $tickets_awaiting_reply_by_status = $this->home_model->tickets_awaiting_reply_by_status();
        $tickets_awaiting_reply_by_department = $this->home_model->tickets_awaiting_reply_by_department();

        $data['tickets_reply_by_status']              = json_encode($tickets_awaiting_reply_by_status);
        $data['tickets_awaiting_reply_by_department'] = json_encode($tickets_awaiting_reply_by_department);

        $data['tickets_reply_by_status_no_json']              = $tickets_awaiting_reply_by_status;
        $data['tickets_awaiting_reply_by_department_no_json'] = $tickets_awaiting_reply_by_department;

        $data['projects_status_stats']                = json_encode($this->home_model->projects_status_stats());
        $data['leads_status_stats']                   = json_encode($this->home_model->leads_status_stats());
        $data['google_ids_calendars']                 = $this->misc_model->get_google_calendar_ids();
        $data['bodyclass']                            = 'home invoices_total_manual';
        $this->load->model('announcements_model');
        $data['staff_announcements'] = $this->announcements_model->get();
        $data['total_undismissed_announcements'] = $this->announcements_model->get_total_undismissed_announcements();

        $this->load->model('projects_model');
        $data['projects_activity'] = $this->projects_model->get_activity('', do_action('projects_activity_dashboard_limit',20));
        // To load js files
        $data['calendar_assets']   = true;
        $this->load->model('utilities_model');
        $this->load->model('estimates_model');
        $data['estimate_statuses'] = $this->estimates_model->get_statuses();

        $this->load->model('proposals_model');
        $data['proposal_statuses'] = $this->proposals_model->get_statuses();

        $wps_currency = 'undefined';
        if (is_using_multiple_currencies()) {
            $wps_currency = $data['base_currency']->id;
        }
        $data['weekly_payment_stats'] = json_encode($this->home_model->get_weekly_payments_statistics($wps_currency));

        $data['is_home']             = true;
        $this->load->view('admin/home', $data);

    }

    /* Chart weekly payments statistics on home page / ajax */
    public function weekly_payments_statistics($currency)
    {
        if ($this->input->is_ajax_request()) {
            echo json_encode($this->home_model->get_weekly_payments_statistics($currency));
            die();
        }
    }
}


