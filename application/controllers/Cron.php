<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Cron extends CRM_Controller
{
    public function __construct()
    {
        parent::__construct();
        update_option('cron_has_run_from_cli', 1);
        $this->load->model('cron_model');
    }

    public function index()
    {
        $last_cron_run = get_option('last_cron_run');
        if ($last_cron_run == '' || (time() > ($last_cron_run + 300))) {
            do_action('before_cron_run');
            $this->cron_model->run();
            do_action('after_cron_run');
        }
    }
}
