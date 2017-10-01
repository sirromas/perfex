<?php
$CI =& get_instance();

    $config['useragent']    = "CodeIgniter";
    $config['mailpath']     = "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"
    $config['wordwrap']     = TRUE;
    $config['mailtype']     = 'html';
    $charset = strtoupper(get_option('smtp_email_charset'));
    $charset = trim($charset);
    if($charset == ''){
        $charset = 'UTF-8';
    }
    $config['charset']      = $charset;
    $config['newline']      = "\r\n";
    $config['crlf']         = "\r\n";
    $config['protocol']     = get_option('email_protocol');
    $config['smtp_host']    = trim(get_option('smtp_host'));
    $config['smtp_port']    = trim(get_option('smtp_port'));
    $config['smtp_timeout'] = '30';
    if(get_option('smtp_username') == ''){
        $config['smtp_user']    = trim(get_option('smtp_email'));
    } else {
        $config['smtp_user']    = trim(get_option('smtp_username'));
    }
    $config['smtp_pass']    = $CI->encryption->decrypt(get_option('smtp_password'));
    $config['smtp_crypto'] = get_option('smtp_encryption');
