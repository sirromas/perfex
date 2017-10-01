<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Emails extends Admin_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('emails_model');
    }

    /* List all email templates */
    public function index()
    {
        if (!has_permission('email_templates', '', 'view')) {
            access_denied('email_templates');
        }

        $this->db->where('language', 'english');
        $email_templates_english = $this->db->get('tblemailtemplates')->result_array();
        foreach ($this->perfex_base->get_available_languages() as $av_language) {
            if ($av_language != 'english') {
                foreach ($email_templates_english as $template) {
                    if (total_rows('tblemailtemplates', array(
                        'slug' => $template['slug'],
                        'language' => $av_language
                    )) == 0) {
                        $data              = array();
                        $data['slug']      = $template['slug'];
                        $data['type']      = $template['type'];
                        $data['language']  = $av_language;
                        $data['name']      = $template['name'] . ' [' . $av_language . ']';
                        $data['subject']   = $template['subject'];
                        $data['message']   = '';
                        $data['fromname']  = $template['fromname'];
                        $data['plaintext'] = $template['plaintext'];
                        $data['active']    = $template['active'];
                        $data['order']     = $template['order'];
                        $this->db->insert('tblemailtemplates', $data);
                    }
                }
            }
        }

        $data['staff']     = $this->emails_model->get(array(
            'type' => 'staff',
            'language' => 'english'
        ));
        $data['tasks']     = $this->emails_model->get(array(
            'type' => 'tasks',
            'language' => 'english'
        ));
        $data['client']    = $this->emails_model->get(array(
            'type' => 'client',
            'language' => 'english'
        ));
        $data['tickets']   = $this->emails_model->get(array(
            'type' => 'ticket',
            'language' => 'english'
        ));
        $data['invoice']   = $this->emails_model->get(array(
            'type' => 'invoice',
            'language' => 'english'
        ));
        $data['estimate']  = $this->emails_model->get(array(
            'type' => 'estimate',
            'language' => 'english'
        ));
        $data['contracts'] = $this->emails_model->get(array(
            'type' => 'contract',
            'language' => 'english'
        ));
        $data['proposals'] = $this->emails_model->get(array(
            'type' => 'proposals',
            'language' => 'english'
        ));
        $data['projects']  = $this->emails_model->get(array(
            'type' => 'project',
            'language' => 'english'
        ));
        $data['leads']     = $this->emails_model->get(array(
            'type' => 'leads',
            'language' => 'english'
        ));
        $data['title']     = _l('email_templates');
        $this->load->view('admin/emails/email_templates', $data);
    }

    /* Edit email template */
    public function email_template($id)
    {
        if (!has_permission('email_templates', '', 'view')) {
            access_denied('email_templates');
        }
        if (!$id) {
            redirect(admin_url('emails'));
        }

        if ($this->input->post()) {
            if (!has_permission('email_templates', '', 'edit')) {
                access_denied('email_templates');
            }
            $success = $this->emails_model->update($this->input->post(null, false), $id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('email_template')));
            }
            redirect(admin_url('emails/email_template/' . $id));
        }

        // English is not included here
        $data['available_languages'] = $this->perfex_base->get_available_languages();

        if (($key = array_search('english', $data['available_languages'])) !== false) {
            unset($data['available_languages'][$key]);
        }

        $data['available_merge_fields'] = get_available_merge_fields();
        $data['template']               = $this->emails_model->get_email_template_by_id($id);
        $title                          = $data['template']->name;
        $data['title']                  = $title;
        $this->load->view('admin/emails/template', $data);
    }

    /* Since version 1.0.1 - test your smtp settings */
    public function sent_smtp_test_email()
    {
        if ($this->input->post()) {
            // Simulate fake template to be parsed
            $template = new StdClass();
            $template->message = get_option('email_header').'This is test SMTP email. <br />If you received this message that means that your SMTP settings is set correctly.'.get_option('email_footer');
            $template->fromname = get_option('companyname');
            $template->subject = 'SMTP Setup Testing';

            $template = parse_email_template($template);

            do_action('before_send_test_smtp_email');
            $this->email->initialize();
            $this->email->set_newline("\r\n");
            $this->email->from(get_option('smtp_email'), $template->fromname);
            $this->email->to($this->input->post('test_email'));
            $this->email->subject($template->subject);
            $this->email->message($template->message);
            if ($this->email->send()) {
                set_alert('success', 'Seems like your SMTP settings is set correctly. Check your email now.');
            } else {
                set_debug_alert('<h1>Your SMTP settings are not set correctly here is the debug log.</h1><br />' . $this->email->print_debugger());
            }
        }
    }
}
