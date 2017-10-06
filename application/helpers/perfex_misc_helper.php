<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check if client id is used in the system
 *
 * @param mixed $id
 *            client id
 * @return boolean
 */
function is_client_id_used($id)
{
    $total = 0;
    $total += total_rows('tblcontracts', array(
        'client' => $id
    ));
    $total += total_rows('tblestimates', array(
        'clientid' => $id
    ));
    $total += total_rows('tblexpenses', array(
        'clientid' => $id
    ));
    $total += total_rows('tblinvoices', array(
        'clientid' => $id
    ));
    $total += total_rows('tblproposals', array(
        'rel_id' => $id,
        'rel_type' => 'customer'
    ));
    $total += total_rows('tbltickets', array(
        'userid' => $id
    ));
    $total += total_rows('tblprojects', array(
        'clientid' => $id
    ));
    $total += total_rows('tblstafftasks', array(
        'rel_id' => $id,
        'rel_type' => 'customer'
    ));

    if ($total > 0) {
        return true;
    }

    return false;
}

/**
 * On each update there is message/code inserted in the database
 */
function show_just_updated_message()
{
    if (get_option('update_info_message') != '') {
        if (is_admin()) {
            $message = get_option('update_info_message');
            update_option('update_info_message', '');
            echo $message;
        }
    }
}

/**
 * Show message on dashboard when environment is set to development or testing
 *
 * @return void
 */
function show_development_mode_message()
{
    if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing') {
        if (is_admin()) {
            echo '<div class="col-md-12">';
            echo '<div class="alert alert-warning">';
            echo 'Environment set to <b>' . ENVIRONMENT . '</b>. Don\'t forget to set back to <b>production</b> in the main index.php file after finishing your tests.';
            echo '</div>';
            echo '</div>';
        }
    }
}

/**
 * CHeck missing key from the main english language
 *
 * @param string $language
 *            language to check
 * @return void
 */
function check_missing_language_strings($language)
{
    $langs = array();
    $CI = &get_instance();
    $CI->lang->load('english_lang', 'english');
    $english = $CI->lang->language;
    $langs[] = array(
        'english' => $english
    );
    $original = $english;
    $keys_original = array();
    foreach ($original as $k => $val) {
        $keys_original[$k] = true;
    }
    $CI->lang->is_loaded = array();
    $CI->lang->language = array();
    $CI->lang->load($language . '_lang', $language);
    $$language = $CI->lang->language;
    $langs[] = array(
        $language => $$language
    );
    $CI->lang->is_loaded = array();
    $CI->lang->language = array();
    $missing_keys = array();
    for ($i = 0; $i < count($langs); $i++) {
        foreach ($langs[$i] as $lang => $data) {
            if ($lang != 'english') {
                $keys_current = array();
                foreach ($data as $k => $v) {
                    $keys_current[$k] = true;
                }
                foreach ($keys_original as $k_original => $val_original) {
                    if (!array_key_exists($k_original, $keys_current)) {
                        $keys_missing = true;
                        array_push($missing_keys, $k_original);
                        echo '<b>Missing language key</b> from language:' . $lang . ' - <b>key</b>:' . $k_original . '<br />';
                    }
                }
            }
        }
    }
    if (isset($keys_missing)) {
        echo '<br />--<br />Language keys missing please create <a href="https://help.perfexcrm.com/overwrite-translation-text/" target="_blank">custom_lang.php</a> and add the keys listed above.';
        echo '<br /> Here is how you should add the keys (You can just copy paste this text above and add your translations)<br /><br />';
        foreach ($missing_keys as $key) {
            echo '$lang[\'' . $key . '\'] = \'Add your translation\';<br />';
        }
    } else {
        echo '<h1>No Missing Language Keys Found</h1>';
    }
    die();
}

/**
 * Parse email template with the merge fields
 *
 * @param mixed $template
 *            template
 * @param array $merge_fields
 * @return object
 */
function parse_email_template($template, $merge_fields = array())
{
    $CI = &get_instance();
    if (!is_object($template) || $CI->input->post('template_name')) {
        $original_template = $template;
        if ($CI->input->post('template_name')) {
            $template = $CI->input->post('template_name');
        }
        $CI->db->where('slug', $template);
        $template = $CI->db->get('tblemailtemplates')->row();

        if ($CI->input->post('email_template_custom')) {
            $template->message = $CI->input->post('email_template_custom', false);
            // Replace the subject too
            $template->subject = $original_template->subject;
        }
    }
    $template = _parse_email_template_merge_fields($template, $merge_fields);

    return do_action('email_template_parsed', $template);
}

/**
 * @param $template
 * @param $merge_fields
 * @return mixed
 */
function _parse_email_template_merge_fields($template, $merge_fields)
{
    $merge_fields = array_merge($merge_fields, get_other_merge_fields());
    foreach ($merge_fields as $key => $val) {
        if (stripos($template->message, $key) !== false) {
            $template->message = str_ireplace($key, $val, $template->message);
        } else {
            $template->message = str_ireplace($key, '', $template->message);
        }
        if (stripos($template->fromname, $key) !== false) {
            $template->fromname = str_ireplace($key, $val, $template->fromname);
        } else {
            $template->fromname = str_ireplace($key, '', $template->fromname);
        }
        if (stripos($template->subject, $key) !== false) {
            $template->subject = str_ireplace($key, $val, $template->subject);
        } else {
            $template->subject = str_ireplace($key, '', $template->subject);
        }
    }

    return $template;
}

/**
 * Return locale for media usafe plugin
 *
 * @param string $locale
 *            current locale
 * @return string
 */
function get_media_locale($locale)
{
    $lng = $locale;
    if ($lng == 'ja') {
        $lng = 'jp';
    } elseif ($lng == 'pt') {
        $lng = 'pt_BR';
    } elseif ($lng == 'ug') {
        $lng = 'ug_CN';
    } elseif ($lng == 'zh') {
        $lng = 'zh_TW';
    }

    return $lng;
}

/**
 * Get system favourite colors
 *
 * @return array
 */
function get_system_favourite_colors()
{
    // don't delete any of these colors are used all over the system
    $colors = array(
        '#28B8DA',
        '#03a9f4',
        '#c53da9',
        '#757575',
        '#8e24aa',
        '#d81b60',
        '#0288d1',
        '#7cb342',
        '#fb8c00',
        '#84C529',
        '#fb3b3b'
    );

    $colors = do_action('system_favourite_colors', $colors);

    return $colors;
}

/**
 * Get goal types for the goals feature
 *
 * @return array
 */
function get_goal_types()
{
    $types = array(
        array(
            'key' => 1,
            'lang_key' => 'goal_type_total_income',
            'subtext' => 'goal_type_income_subtext'
        ),
        array(
            'key' => 2,
            'lang_key' => 'goal_type_convert_leads'
        ),
        array(
            'key' => 3,
            'lang_key' => 'goal_type_increase_customers_without_leads_conversions',
            'subtext' => 'goal_type_increase_customers_without_leads_conversions_subtext'
        ),
        array(
            'key' => 4,
            'lang_key' => 'goal_type_increase_customers_with_leads_conversions',
            'subtext' => 'goal_type_increase_customers_with_leads_conversions_subtext'
        ),
        array(
            'key' => 5,
            'lang_key' => 'goal_type_make_contracts_by_type_calc_database',
            'subtext' => 'goal_type_make_contracts_by_type_calc_database_subtext'
        ),
        array(
            'key' => 7,
            'lang_key' => 'goal_type_make_contracts_by_type_calc_date',
            'subtext' => 'goal_type_make_contracts_by_type_calc_date_subtext'
        ),
        array(
            'key' => 6,
            'lang_key' => 'goal_type_total_estimates_converted',
            'subtext' => 'goal_type_total_estimates_converted_subtext'
        )
    );

    return do_action('get_goal_types', $types);
}

/**
 * Translate goal type based on passed key
 *
 * @param mixed $key
 * @return string
 */
function format_goal_type($key)
{
    foreach (get_goal_types() as $type) {
        if ($type['key'] == $key) {
            return _l($type['lang_key']);
        }
    }

    return $type;
}

/**
 * Set session alert / flashdata
 *
 * @param string $type
 *            Alert type
 * @param string $message
 *            Alert message
 */
function set_alert($type, $message)
{
    $CI = &get_instance();
    $CI->session->set_flashdata('message-' . $type, $message);
}

/**
 * Redirect to blank page
 *
 * @param string $message
 *            Alert message
 * @param string $alert
 *            Alert type
 */
function blank_page($message = '', $alert = 'danger')
{
    set_alert($alert, $message);
    redirect(admin_url('not_found'));
}

/**
 * Redirect to access danied page and log activity
 *
 * @param string $permission
 *            If permission based to check where user tried to acces
 */
function access_denied($permission = '')
{
    set_alert('danger', _l('access_denied'));
    logActivity('Tried to access page where don\'t have permission [' . $permission . ']');
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        redirect($_SERVER['HTTP_REFERER']);
    } else {
        redirect(admin_url('access_denied'));
    }
}

/**
 * Set debug message - message wont be hidden in X seconds from javascript
 *
 * @since Version 1.0.1
 * @param string $message
 *            debug message
 */
function set_debug_alert($message)
{
    $CI = &get_instance();
    $CI->session->set_flashdata('debug', $message);
}

/**
 * Available date formats
 *
 * @return array
 */
function get_available_date_formats()
{
    $date_formats = array(
        'd-m-Y|%d-%m-%Y' => 'd-m-Y',
        'd/m/Y|%d/%m/%Y' => 'd/m/Y',
        'm-d-Y|%m-%d-%Y' => 'm-d-Y',
        'm.d.Y|%m.%d.%Y' => 'm.d.Y',
        'm/d/Y|%m/%d/%Y' => 'm/d/Y',
        'Y-m-d|%Y-%m-%d' => 'Y-m-d',
        'd.m.Y|%d.%m.%Y' => 'd.m.Y'
    );

    return do_action('get_available_date_formats', $date_formats);
}

/**
 * Get weekdays as array
 *
 * @return array
 */
function get_weekdays()
{
    return array(
        _l('wd_monday'),
        _l('wd_tuesday'),
        _l('wd_wednesday'),
        _l('wd_thursday'),
        _l('wd_friday'),
        _l('wd_saturday'),
        _l('wd_sunday')
    );
}

/**
 * Get non translated week days for query help
 * Do not edit this
 *
 * @return array
 */
function get_weekdays_original()
{
    return array(
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    );
}

/**
 * Short Time ago function
 *
 * @param datetime $time_ago
 * @return mixed
 */
function time_ago($time_ago)
{
    $time_ago = strtotime($time_ago);
    $cur_time = time();
    $time_elapsed = $cur_time - $time_ago;
    $seconds = $time_elapsed;
    $minutes = round($time_elapsed / 60);
    $hours = round($time_elapsed / 3600);
    $days = round($time_elapsed / 86400);
    $weeks = round($time_elapsed / 604800);
    $months = round($time_elapsed / 2600640);
    $years = round($time_elapsed / 31207680);
    // Seconds
    if ($seconds <= 60) {
        return _l('time_ago_just_now');
    }    // Minutes
    elseif ($minutes <= 60) {
        if ($minutes == 1) {
            return _l('time_ago_minute');
        } else {
            return _l('time_ago_minutes', $minutes);
        }
    }    // Hours
    elseif ($hours <= 24) {
        if ($hours == 1) {
            return _l('time_ago_hour');
        } else {
            return _l('time_ago_hours', $hours);
        }
    }    // Days
    elseif ($days <= 7) {
        if ($days == 1) {
            return _l('time_ago_yesterday');
        } else {
            return _l('time_ago_days', $days);
        }
    }    // Weeks
    elseif ($weeks <= 4.3) {
        if ($weeks == 1) {
            return _l('time_ago_week');
        } else {
            return _l('time_ago_weeks', $weeks);
        }
    }    // Months
    elseif ($months <= 12) {
        if ($months == 1) {
            return _l('time_ago_month');
        } else {
            return _l('time_ago_months', $months);
        }
    }     // Years
    else {
        if ($years == 1) {
            return _l('time_ago_year');
        } else {
            return _l('time_ago_years', $years);
        }
    }
}

/**
 * Slug function
 *
 * @param string $str
 * @param array $options
 *            Additional Options
 * @return mixed
 */
function slug_it($str, $options = array())
{
    // Make sure string is in UTF-8 and strip invalid UTF-8 characters
    $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
    $defaults = array(
        'delimiter' => '-',
        'limit' => null,
        'lowercase' => true,
        'replacements' => array(
            '
            /\b(ѓ)\b/i' => 'gj',
            '/\b(ч)\b/i' => 'ch',
            '/\b(ш)\b/i' => 'sh',
            '/\b(љ)\b/i' => 'lj'
        ),
        'transliterate' => true
    );
    // Merge options
    $options = array_merge($defaults, $options);
    $char_map = array(

        // Latin
        'À' => 'A',
        'Á' => 'A',
        'Â' => 'A',
        'Ã' => 'A',
        'Ä' => 'A',
        'Å' => 'A',
        'Æ' => 'AE',
        'Ç' => 'C',
        'È' => 'E',
        'É' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
        'Ì' => 'I',
        'Í' => 'I',
        'Î' => 'I',
        'Ï' => 'I',
        'Ð' => 'D',
        'Ñ' => 'N',
        'Ò' => 'O',
        'Ó' => 'O',
        'Ô' => 'O',
        'Õ' => 'O',
        'Ö' => 'O',
        'Ő' => 'O',
        'Ø' => 'O',
        'Ù' => 'U',
        'Ú' => 'U',
        'Û' => 'U',
        'Ü' => 'U',
        'Ű' => 'U',
        'Ý' => 'Y',
        'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ð' => 'd',
        'ñ' => 'n',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'ő' => 'o',
        'ø' => 'o',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ű' => 'u',
        'ý' => 'y',
        'þ' => 'th',
        'ÿ' => 'y',

        // Latin symbols
        '©' => '(c)',

        // Greek
        'Α' => 'A',
        'Β' => 'B',
        'Γ' => 'G',
        'Δ' => 'D',
        'Ε' => 'E',
        'Ζ' => 'Z',
        'Η' => 'H',
        'Θ' => '8',
        'Ι' => 'I',
        'Κ' => 'K',
        'Λ' => 'L',
        'Μ' => 'M',
        'Ν' => 'N',
        'Ξ' => '3',
        'Ο' => 'O',
        'Π' => 'P',
        'Ρ' => 'R',
        'Σ' => 'S',
        'Τ' => 'T',
        'Υ' => 'Y',
        'Φ' => 'F',
        'Χ' => 'X',
        'Ψ' => 'PS',
        'Ω' => 'W',
        'Ά' => 'A',
        'Έ' => 'E',
        'Ί' => 'I',
        'Ό' => 'O',
        'Ύ' => 'Y',
        'Ή' => 'H',
        'Ώ' => 'W',
        'Ϊ' => 'I',
        'Ϋ' => 'Y',
        'α' => 'a',
        'β' => 'b',
        'γ' => 'g',
        'δ' => 'd',
        'ε' => 'e',
        'ζ' => 'z',
        'η' => 'h',
        'θ' => '8',
        'ι' => 'i',
        'κ' => 'k',
        'λ' => 'l',
        'μ' => 'm',
        'ν' => 'n',
        'ξ' => '3',
        'ο' => 'o',
        'π' => 'p',
        'ρ' => 'r',
        'σ' => 's',
        'τ' => 't',
        'υ' => 'y',
        'φ' => 'f',
        'χ' => 'x',
        'ψ' => 'ps',
        'ω' => 'w',
        'ά' => 'a',
        'έ' => 'e',
        'ί' => 'i',
        'ό' => 'o',
        'ύ' => 'y',
        'ή' => 'h',
        'ώ' => 'w',
        'ς' => 's',
        'ϊ' => 'i',
        'ΰ' => 'y',
        'ϋ' => 'y',
        'ΐ' => 'i',

        // Turkish
        'Ş' => 'S',
        'İ' => 'I',
        'Ç' => 'C',
        'Ü' => 'U',
        'Ö' => 'O',
        'Ğ' => 'G',
        'ş' => 's',
        'ı' => 'i',
        'ç' => 'c',
        'ü' => 'u',
        'ö' => 'o',
        'ğ' => 'g',

        // Russian
        'А' => 'A',
        'Б' => 'B',
        'В' => 'V',
        'Г' => 'G',
        'Д' => 'D',
        'Е' => 'E',
        'Ё' => 'Yo',
        'Ж' => 'Zh',
        'З' => 'Z',
        'И' => 'I',
        'Й' => 'J',
        'К' => 'K',
        'Л' => 'L',
        'М' => 'M',
        'Н' => 'N',
        'О' => 'O',
        'П' => 'P',
        'Р' => 'R',
        'С' => 'S',
        'Т' => 'T',
        'У' => 'U',
        'Ф' => 'F',
        'Х' => 'H',
        'Ц' => 'C',
        'Ч' => 'Ch',
        'Ш' => 'Sh',
        'Щ' => 'Sh',
        'Ъ' => '',
        'Ы' => 'Y',
        'Ь' => '',
        'Э' => 'E',
        'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'yo',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'j',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'h',
        'ц' => 'c',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'sh',
        'ъ' => '',
        'ы' => 'y',
        'ь' => '',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya',

        // Ukrainian
        'Є' => 'Ye',
        'І' => 'I',
        'Ї' => 'Yi',
        'Ґ' => 'G',
        'є' => 'ye',
        'і' => 'i',
        'ї' => 'yi',
        'ґ' => 'g',

        // Czech
        'Č' => 'C',
        'Ď' => 'D',
        'Ě' => 'E',
        'Ň' => 'N',
        'Ř' => 'R',
        'Š' => 'S',
        'Ť' => 'T',
        'Ů' => 'U',
        'Ž' => 'Z',
        'č' => 'c',
        'ď' => 'd',
        'ě' => 'e',
        'ň' => 'n',
        'ř' => 'r',
        'š' => 's',
        'ť' => 't',
        'ů' => 'u',
        'ž' => 'z',

        // Polish
        'Ą' => 'A',
        'Ć' => 'C',
        'Ę' => 'e',
        'Ł' => 'L',
        'Ń' => 'N',
        'Ó' => 'o',
        'Ś' => 'S',
        'Ź' => 'Z',
        'Ż' => 'Z',
        'ą' => 'a',
        'ć' => 'c',
        'ę' => 'e',
        'ł' => 'l',
        'ń' => 'n',
        'ó' => 'o',
        'ś' => 's',
        'ź' => 'z',
        'ż' => 'z',

        // Latvian
        'Ā' => 'A',
        'Č' => 'C',
        'Ē' => 'E',
        'Ģ' => 'G',
        'Ī' => 'i',
        'Ķ' => 'k',
        'Ļ' => 'L',
        'Ņ' => 'N',
        'Š' => 'S',
        'Ū' => 'u',
        'Ž' => 'Z',
        'ā' => 'a',
        'č' => 'c',
        'ē' => 'e',
        'ģ' => 'g',
        'ī' => 'i',
        'ķ' => 'k',
        'ļ' => 'l',
        'ņ' => 'n',
        'š' => 's',
        'ū' => 'u',
        'ž' => 'z'
    );
    // Make custom replacements
    $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
    // Transliterate characters to ASCII
    if ($options['transliterate']) {
        $str = str_replace(array_keys($char_map), $char_map, $str);
    }
    // Replace non-alphanumeric characters with our delimiter
    $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
    // Remove duplicate delimiters
    $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
    // Truncate slug to max. characters
    $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
    // Remove delimiter from ends
    $str = trim($str, $options['delimiter']);

    return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}

/**
 * Get projcet billing type
 *
 * @param mixed $project_id
 * @return mixed
 */
function get_project_billing_type($project_id)
{
    $CI = &get_instance();
    $CI->db->where('id', $project_id);
    $project = $CI->db->get('tblprojects')->row();
    if ($project) {
        return $project->billing_type;
    }

    return false;
}

/**
 * Get client id by lead id
 *
 * @since Version 1.0.1
 * @param mixed $id
 *            lead id
 * @return mixed client id
 */
function get_client_id_by_lead_id($id)
{
    $CI = &get_instance();
    $CI->db->select('userid')
        ->from('tblclients')
        ->where('leadid', $id);

    return $CI->db->get()->row()->userid;
}

/**
 * Check if the user is lead creator
 *
 * @since Version 1.0.4
 * @param mixed $leadid
 *            leadid
 * @param mixed $id
 *            staff id (Optional)
 * @return boolean
 */
function is_lead_creator($leadid, $id = '')
{
    if (!is_numeric($id)) {
        $id = get_staff_user_id();
    }
    $is = total_rows('tblleads', array(
        'addedfrom' => $id,
        'id' => $leadid
    ));
    if ($is > 0) {
        return true;
    }

    return false;
}


/**
 * @param $date_1
 * @param $date_2
 * @param string $differenceFormat
 * @return string
 */
function dateDifference($date_1, $date_2, $differenceFormat = '%a')
{
    $datetime1 = date_create($date_1);
    $datetime2 = date_create($date_2);
    $interval = date_diff($datetime1, $datetime2);
    return $interval->format($differenceFormat);
}


/**
 * @param $clientid
 * @return int|string
 */
function get_client_link_color($clientid)
{
    $CI = &get_instance();
    $query = "select * from tblcustomfieldsvalues 
                where relid=$clientid and fieldid=2 
                            and fieldto='customers'";
    $result = $CI->db->query($query);
    foreach ($result->result() as $row) {
        $lastvisit = $row->value;
    }

    if ($lastvisit != '') {
        $now = date('Y-m-d', time());
        $days = dateDifference($lastvisit, $now);
        if ($days > 0 && $days <= 7) {
            $color = '#84c529';
        }
        if ($days > 7 && $days <= 15) {
            $color = '#f7c80c';
        }
        if ($days > 15) {
            $color = 'red';
        }
    } // end if
    else {
        $color = '';
    }
    return $color;
}


/**
 * When ticket will be opened automatically set to open
 *
 * @param integer $current
 *            Current status
 * @param integer $id
 *            ticketid
 * @param boolean $admin
 *            Admin opened or client opened
 */
function set_ticket_open($current, $id, $admin = true)
{
    if ($current == 1) {
        return;
    }
    $CI = &get_instance();
    $CI->db->where('ticketid', $id);
    $field = 'adminread';
    if ($admin == false) {
        $field = 'clientread';
    }
    $CI->db->update('tbltickets', array(
        $field => 1
    ));
}

/**
 * Get timezones list
 *
 * @return array timezones
 */
function get_timezones_list()
{
    return array(
        'EUROPE' => DateTimeZone::listIdentifiers(DateTimeZone::EUROPE),
        'AMERICA' => DateTimeZone::listIdentifiers(DateTimeZone::AMERICA),
        'INDIAN' => DateTimeZone::listIdentifiers(DateTimeZone::INDIAN),
        'AUSTRALIA' => DateTimeZone::listIdentifiers(DateTimeZone::AUSTRALIA),
        'ASIA' => DateTimeZone::listIdentifiers(DateTimeZone::ASIA),
        'AFRICA' => DateTimeZone::listIdentifiers(DateTimeZone::AFRICA),
        'ANTARCTICA' => DateTimeZone::listIdentifiers(DateTimeZone::ANTARCTICA),
        'ARCTIC' => DateTimeZone::listIdentifiers(DateTimeZone::ARCTIC),
        'ATLANTIC' => DateTimeZone::listIdentifiers(DateTimeZone::ATLANTIC),
        'PACIFIC' => DateTimeZone::listIdentifiers(DateTimeZone::PACIFIC),
        'UTC' => DateTimeZone::listIdentifiers(DateTimeZone::UTC)
    );
}

/**
 * Get available locaes predefined for the system
 * If you add a language and the locale do not exist in this array you can use action hook to add new locale
 *
 * @return array
 */
function get_locales()
{
    $locales = array(
        "Arabic" => 'ar',
        "Bulgarian" => 'bg',
        "Catalan" => 'ca',
        "Czech" => 'cs',
        "Danish" => 'da',
        "Albanian" => 'sq',
        "German" => 'de',
        "Deutsch" => 'de',
        'Dutch' => 'nl',
        "Greek" => 'el',
        "English" => 'en',
        "Finland" => 'fi',
        "Spanish" => 'es',
        "Persian" => 'fa',
        "Finnish" => 'fi',
        "French" => 'fr',
        "Hebrew" => 'he',
        "Hindi" => 'hi',
        'Indonesian' => 'id',
        "Hindi" => 'hi',
        "Croatian" => 'hr',
        "Hungarian" => 'hu',
        "Icelandic" => 'is',
        "Italian" => 'it',
        "Japanese" => 'ja',
        "Korean" => 'ko',
        "Lithuanian" => 'lt',
        "Latvian" => 'lv',
        "Norwegian" => 'nb',
        "Netherlands" => 'nl',
        "Polish" => 'pl',
        "Portuguese" => 'pt',
        "Romanian" => 'ro',
        "Russian" => 'ru',
        "Slovak" => 'sk',
        "Slovenian" => 'sl',
        "Serbian" => 'sr',
        "Swedish" => 'sv',
        "Thai" => 'th',
        "Turkish" => 'tr',
        "Ukrainian" => 'uk',
        "Vietnamese" => 'vi'
    );

    $locales = do_action('before_get_locales', $locales);

    return $locales;
}

/**
 * Tinymce language set can be complicated and this function will scan the available languages
 * Will return lang filename in the tinymce plugins folder if found or if $locale is en will return just en
 *
 * @param [type] $locale
 *            [description]
 * @return [type] [description]
 */
function get_tinymce_language($locale)
{
    $av_lang = list_files(FCPATH . 'assets/plugins/tinymce/langs/');
    $_lang = '';
    if ($locale == 'en') {
        return $_lang;
    }

    if ($locale == 'hi') {
        return 'hi_IN';
    } elseif ($locale == 'he') {
        return 'he_IL';
    } elseif ($locale == 'sv') {
        return 'sv_SE';
    }

    foreach ($av_lang as $lang) {
        $_temp_lang = explode('.', $lang);
        if ($locale == $_temp_lang[0]) {
            return $locale;
        } elseif ($locale . '_' . strtoupper($locale) == $_temp_lang[0]) {
            return $locale . '_' . strtoupper($locale);
        }
    }

    return $_lang;
}

/**
 * @param string $locale
 */
function app_select_plugin_js($locale = 'en')
{
    echo "<script src='" . base_url('assets/plugins/app-build/bootstrap-select.min.js') . "'></script>" . PHP_EOL;

    if ($locale != 'en') {
        if (file_exists(FCPATH . 'assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '.min.js') . "'></script>" . PHP_EOL;
        } elseif (file_exists(FCPATH . 'assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '_' . strtoupper($locale) . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '_' . strtoupper($locale) . '.min.js') . "'></script>" . PHP_EOL;
        }
    }
}

/**
 * @param string $locale
 */
function app_jquery_validation_plugin_js($locale = 'en')
{
    echo "<script src='" . base_url('assets/plugins/jquery-validation/jquery.validate.min.js') . "'></script>" . PHP_EOL;
    if ($locale != 'en') {
        if (file_exists(FCPATH . 'assets/plugins/jquery-validation/localization/messages_' . $locale . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/jquery-validation/localization/messages_' . $locale . '.min.js') . "'></script>" . PHP_EOL;
        } elseif (file_exists(FCPATH . 'assets/plugins/jquery-validation/localization/messages_' . $locale . '_' . strtoupper($locale) . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/jquery-validation/localization/messages_' . $locale . '_' . strtoupper($locale) . '.min.js') . "'></script>" . PHP_EOL;
        }
    }
}

/**
 * Check if visitor is on mobile
 *
 * @return boolean
 */
function is_mobile()
{
    $CI = &get_instance();
    if ($CI->agent->is_mobile()) {
        return true;
    }

    return false;
}

/**
 * All permissions available in the app with conditions
 *
 * @return array
 */
function get_permission_conditions()
{
    return do_action('staff_permissions_conditions', array(
        'contracts' => array(
            'view' => true,
            'view_own' => true,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'tasks' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true,
            'help' => _l('help_tasks_permissions')
        ),
        'checklist_templates' => array(
            'view' => false,
            'view_own' => false,
            'edit' => false,
            'create' => true,
            'delete' => true
        ),
        'reports' => array(
            'view' => true,
            'view_own' => false,
            'edit' => false,
            'create' => false,
            'delete' => false
        ),
        'settings' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => false,
            'delete' => false
        ),
        'projects' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true,
            'help' => _l('help_project_permissions')
        ),
        'surveys' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'staff' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'customers' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'email_templates' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => false,
            'delete' => false
        ),
        'roles' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'expenses' => array(
            'view' => true,
            'view_own' => true,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'bulk_pdf_exporter' => array(
            'view' => true,
            'view_own' => false,
            'edit' => false,
            'create' => false,
            'delete' => false
        ),
        'goals' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'knowledge_base' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'proposals' => array(
            'view' => true,
            'view_own' => true,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'estimates' => array(
            'view' => true,
            'view_own' => true,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'payments' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'invoices' => array(
            'view' => true,
            'view_own' => true,
            'edit' => true,
            'create' => true,
            'delete' => true
        ),
        'items' => array(
            'view' => true,
            'view_own' => false,
            'edit' => true,
            'create' => true,
            'delete' => true
        )
    ));
}

/**
 * Function that will search possible proposal templates in applicaion/views/admin/proposal/templates
 * Will return any found files and user will be able to add new template
 *
 * @return array
 */
function get_proposal_templates()
{
    $proposal_templates = array();
    if (is_dir(VIEWPATH . 'admin/proposals/templates')) {
        foreach (list_files(VIEWPATH . 'admin/proposals/templates') as $template) {
            $proposal_templates[] = $template;
        }
    }

    return $proposal_templates;
}

/**
 * Translated datatables language based on app languages
 * This feature is used on both admin and customer area
 *
 * @return array
 */
function get_datatables_language_array()
{
    $lang = array(
        'emptyTable' => preg_replace("/{(\d+)}/", _l("dt_entries"), _l("dt_empty_table")),
        'info' => preg_replace("/{(\d+)}/", _l("dt_entries"), _l("dt_info")),
        'infoEmpty' => preg_replace("/{(\d+)}/", _l("dt_entries"), _l("dt_info_empty")),
        'infoFiltered' => preg_replace("/{(\d+)}/", _l("dt_entries"), _l("dt_info_filtered")),
        'lengthMenu' => '_MENU_',
        'loadingRecords' => _l('dt_loading_records'),
        'processing' => '<div class="dt-loader"></div>',
        'search' => '<div class="input-group"><span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>',
        'searchPlaceholder' => _l('dt_search'),
        'zeroRecords' => _l('dt_zero_records'),
        'paginate' => array(
            'first' => _l('dt_paginate_first'),
            'last' => _l('dt_paginate_last'),
            'next' => _l('dt_paginate_next'),
            'previous' => _l('dt_paginate_previous')
        ),
        'aria' => array(
            'sortAscending' => _l('dt_sort_ascending'),
            'sortDescending' => _l('dt_sort_descending')
        )
    );

    return do_action('datatables_language_array', $lang);
}

/**
 * Translated jquery-comment language based on app languages
 * This feature is used on both admin and customer area
 *
 * @return array
 */
function get_project_discussions_language_array()
{
    $lang = array(
        'discussion_add_comment' => _l('discussion_add_comment'),
        'discussion_newest' => _l('discussion_newest'),
        'discussion_oldest' => _l('discussion_oldest'),
        'discussion_attachments' => _l('discussion_attachments'),
        'discussion_send' => _l('discussion_send'),
        'discussion_reply' => _l('discussion_reply'),
        'discussion_edit' => _l('discussion_edit'),
        'discussion_edited' => _l('discussion_edited'),
        'discussion_you' => _l('discussion_you'),
        'discussion_save' => _l('discussion_save'),
        'discussion_delete' => _l('discussion_delete'),
        'discussion_view_all_replies' => _l('discussion_view_all_replies'),
        'discussion_hide_replies' => _l('discussion_hide_replies'),
        'discussion_no_comments' => _l('discussion_no_comments'),
        'discussion_no_attachments' => _l('discussion_no_attachments'),
        'discussion_attachments_drop' => _l('discussion_attachments_drop')
    );

    return $lang;
}

/**
 * Feature that will render all JS necessary data in admin head
 *
 * @return void
 */
function render_admin_js_variables()
{
    $date_format = get_option('dateformat');
    $date_format = explode('|', $date_format);
    $date_format = $date_format[0];
    $CI = &get_instance();

    $js_vars = array(
        'site_url' => site_url(),
        'admin_url' => admin_url(),
        'max_php_ini_upload_size_bytes' => file_upload_max_size(),
        'google_api' => '',
        'calendarIDs' => '',
        'is_admin' => is_admin(),
        'is_staff_member' => is_staff_member(),
        'has_permission_tasks_checklist_items_delete' => has_permission('checklist_templates', '', 'delete'),
        'app_is_mobile' => is_mobile(),
        'app_user_browser' => strtolower($CI->agent->browser()),
        'app_date_format' => $date_format,
        'app_decimal_places' => get_decimal_places(),
        'app_scroll_responsive_tables' => get_option('scroll_responsive_tables'),
        'app_company_is_required' => get_option('company_is_required'),
        'app_default_view_calendar' => get_option('default_view_calendar'),
        'app_show_table_columns_visibility' => do_action('show_table_columns_visibility', 0),
        'app_maximum_allowed_ticket_attachments' => get_option('maximum_allowed_ticket_attachments'),
        'app_show_setup_menu_item_only_on_hover' => get_option('show_setup_menu_item_only_on_hover'),
        'app_calendar_events_limit' => get_option('calendar_events_limit'),
        'app_auto_check_for_new_notifications' => get_option('auto_check_for_new_notifications'),
        'app_tables_pagination_limit' => get_option('tables_pagination_limit'),
        'app_newsfeed_maximum_files_upload' => get_option('newsfeed_maximum_files_upload'),
        'app_newsfeed_maximum_file_size' => get_option('newsfeed_maximum_file_size'),
        'app_time_format' => get_option('time_format'),
        'app_decimal_separator' => get_option('decimal_separator'),
        'app_thousand_separator' => get_option('thousand_separator'),
        'app_currency_placement' => get_option('currency_placement'),
        'app_timezone' => get_option('default_timezone'),
        'app_calendar_first_day' => get_option('calendar_first_day'),
        'app_allowed_files' => get_option('allowed_files'),
        'app_show_table_export_button' => get_option('show_table_export_button'),
        'app_desktop_notifications' => get_option('desktop_notifications')
    );

    $lang = array(
        'invoice_task_billable_timers_found' => _l('invoice_task_billable_timers_found'),
        'validation_extension_not_allowed' => _l('validation_extension_not_allowed'),
        'tag' => _l('tag'),
        'options' => _l('options'),
        'email_exists' => _l('email_exists'),
        'new_notification' => _l('new_notification'),
        'estimate_number_exists' => _l('estimate_number_exists'),
        'invoice_number_exists' => _l('invoice_number_exists'),
        'confirm_action_prompt' => _l('confirm_action_prompt'),
        'calendar_expand' => _l('calendar_expand'),
        'proposal_save' => _l('proposal_save'),
        'contract_save' => _l('contract_save'),
        'media_files' => _l('media_files'),
        'item_field_not_formatted' => _l('numbers_not_formatted_while_editing'),
        'filter_by' => _l('filter_by'),
        'you_can_not_upload_any_more_files' => _l('you_can_not_upload_any_more_files'),
        'cancel_upload' => _l('cancel_upload'),
        'remove_file' => _l('remove_file'),
        'browser_not_support_drag_and_drop' => _l('browser_not_support_drag_and_drop'),
        'drop_files_here_to_upload' => _l('drop_files_here_to_upload'),
        'file_exceeds_max_filesize' => _l('file_exceeds_max_filesize') . ' (' . bytesToSize('', file_upload_max_size()) . ')',
        'file_exceeds_maxfile_size_in_form' => _l('file_exceeds_maxfile_size_in_form') . ' (' . bytesToSize('', file_upload_max_size()) . ')',
        'unit' => _l('unit'),
        'dt_length_menu_all' => _l("dt_length_menu_all"),
        'dt_button_column_visibility' => _l('dt_button_column_visibility'),
        'dt_button_reload' => _l('dt_button_reload'),
        'dt_button_excel' => _l('dt_button_excel'),
        'dt_button_csv' => _l('dt_button_csv'),
        'dt_button_pdf' => _l('dt_button_pdf'),
        'dt_button_print' => _l('dt_button_print'),
        'dt_button_export' => _l('dt_button_export')
    );

    $js_vars = do_action('before_render_app_js_vars_admin', $js_vars);
    $lang = do_action('before_render_app_js_lang_admin', $lang);

    echo '<script>';

    $firstKey = key($js_vars);

    $vars = 'var ' . $firstKey . '="' . $js_vars[$firstKey] . '",';

    unset($js_vars[$firstKey]);

    foreach ($js_vars as $var => $val) {
        $vars .= $var . '="' . $val . '",';
    }

    echo rtrim($vars, ',') . ';';

    echo 'var appLang = {};';
    foreach ($lang as $key => $val) {
        echo 'appLang["' . $key . '"] = "' . $val . '";';
    }

    echo '</script>';
}

/**
 * For html5 form accepted attributes
 * This function is used for the tickets form attachments
 *
 * @return string
 */
function get_ticket_form_accepted_mimes()
{
    $ticket_allowed_extensions = get_option('ticket_attachments_file_extensions');
    $_ticket_allowed_extensions = explode(',', $ticket_allowed_extensions);
    $all_form_ext = $ticket_allowed_extensions;
    if (is_array($_ticket_allowed_extensions)) {
        foreach ($_ticket_allowed_extensions as $ext) {
            $all_form_ext .= ',' . get_mime_by_extension($ext);
        }
    }

    return $all_form_ext;
}

/**
 * For html5 form accepted attributes
 * This function is used for the form attachments
 *
 * @return string
 */
function get_form_accepted_mimes()
{
    $allowed_extensions = get_option('allowed_files');
    $_allowed_extensions = explode(',', $allowed_extensions);
    $all_form_ext = '';
    $CI = &get_instance();
    // Chrome doing conflict when the regular extensions are appended to the accept attribute which cause top popup
    // to select file to stop opening
    if ($CI->agent->browser() != 'Chrome') {
        $all_form_ext .= $allowed_extensions;
    }
    if (is_array($_allowed_extensions)) {
        if ($all_form_ext != '') {
            $all_form_ext .= ', ';
        }
        foreach ($_allowed_extensions as $ext) {
            $all_form_ext .= get_mime_by_extension($ext) . ', ';
        }
    }

    $all_form_ext = rtrim($all_form_ext, ', ');

    return $all_form_ext;
}

/**
 * Function that will parse filters for datatables and will return based on a couple conditions.
 * The returned result will be pushed inside the $where variable in the table SQL
 *
 * @param array $filter
 * @return string
 */
function prepare_dt_filter($filter)
{
    $filter = implode(' ', $filter);
    if (_startsWith($filter, 'AND')) {
        $filter = substr($filter, 3);
    } elseif (_startsWith($filter, 'OR')) {
        $filter = substr($filter, 2);
    }

    return $filter;
}

/**
 * Function that will check if iconv php extension is required based on the usage
 *
 * @return mixed
 */
function _maybe_iconv_needs_to_be_enabled()
{
    if (!function_exists('iconv')) {
        $CI = &get_instance();
        $leadsEmailIntegrationConfigured = false;
        $imapAutoImportingTicketsConfigured = false;
        if (total_rows('tblleadsintegration', array(
                'active' => 1,
                'email !=' => '',
                'imap_server !=' => '',
                'password !=' => ''
            )) > 0) {
            $leadsEmailIntegrationConfigured = true;
        }
        if (total_rows('tbldepartments', array(
                'email !=' => '',
                'host !=' => '',
                'password !=' => ''
            )) > 0) {
            $imapAutoImportingTicketsConfigured = true;
        }
        if ($imapAutoImportingTicketsConfigured || $leadsEmailIntegrationConfigured) {
            $usedFeatures = '';
            if ($imapAutoImportingTicketsConfigured && !$leadsEmailIntegrationConfigured) {
                $usedFeatures = ' auto importing tickets via the IMAP method';
            } elseif ($leadsEmailIntegrationConfigured && !$imapAutoImportingTicketsConfigured) {
                $usedFeatures = ' leads email integration feature';
            } else {
                $usedFeatures = ' auto importing tickets via the IMAP method and leads email integration features';
            }
            ?>
            <div class="col-md-12">
                <div class="alert alert-danger">
                    You need to enable <b>iconv</b> php extension in order to use <b><?php echo $usedFeatures; ?></b>.
                    You can enable it via php.ini or contact your hosting provider to
                    enable this extension.
                </div>
            </div>
            <?php

        }
    }
}

/**
 * Check few timezones statements
 *
 * @return void
 */
function _maybe_timezone_not_set()
{
    if (get_option('default_timezone') == '') {
        echo '<div class="col-md-12">';
        echo '<div class="alert alert-danger">';
        echo '<strong>Default timezone not set. Navigate to Setup->Settings->Localization to set default system timezone.</strong>';
        echo '</div>';
        echo '</div>';
    } else {
        if (!in_array(get_option('default_timezone'), array_flatten(get_timezones_list()))) {
            echo '<div class="col-md-12">';
            echo '<div class="alert alert-danger">';
            echo '<strong>We updated the timezone logic for the app. Seems like your previous timezone do not fit with the new logic. Navigate to Setup->Settings->Localization to set new proper timezone.</strong>';
            echo '</div>';
            echo '</div>';
        }
    }
}

/**
 * Notice for Cloudflare rocket loader usage
 * The application wont work good if cloudflare rocket loader is enabled
 *
 * @return null
 */
function _maybe_using_cloudflare_rocket_loader()
{
    $CI = &get_instance();
    $header = $CI->input->get_request_header('Cf-Ray');

    if ($header && !empty($header) && get_option('show_cloudflare_notice') == '1' && is_admin()) {
        ob_start();
        ?>
        <div class="col-md-12">
            <div class="alert alert-warning font-medium">
                <div class="mtop15"></div>
                <h4>
                    <strong>Cloudflare usage detected</strong>
                </h4>
                <hr/>
                <ul>
                    <li>When using Cloudflare with the application <strong>you must
                            disable ROCKET LOADER</strong> feature from Cloudflare options in
                        order everything to work properly. <br/>
                        <strong>
                            <small>NOTE: The script can't check if Rocket Loader is
                                enabled/disabled in your Cloudflare account. If Rocket Loader is
                                already disabled you can ignore this warning.
                            </small>
                        </strong></li>
                    <li><br/>
                        <ul>
                            <li><strong>&nbsp;&nbsp;- Disable Rocket Loader for whole domain
                                    name</strong></li>
                            <li>&nbsp;&nbsp;&nbsp;&nbsp;Login to your Cloudflare account and
                                click on the <strong>Speed</strong> tab from the top dashboard,
                                search for Rocket Loader and <strong>set to Off</strong>.
                            </li>
                            <br/>
                            <li><strong>&nbsp;&nbsp;- Disable Rocket Loader with page rule for
                                    application installation url</strong></li>
                            <li>&nbsp;&nbsp;&nbsp;&nbsp;If you do not want to turn off Rocket
                                Loader for the whole domain you can add <a
                                        href="https://support.cloudflare.com/hc/en-us/articles/200168306-Is-there-a-tutorial-for-Page-Rules-"
                                        target="_blank">page rule</a> that will disable the Rocket Loader
                                only for the application, follow the steps below in order to
                                achieve this. <br/>
                                <br/>
                                <p class="no-margin">
                                    &nbsp;&nbsp;- Login to your Cloudflare account and click on the <strong>Page
                                        Rules</strong> tab from the top dashboard
                                </p>
                                <p class="no-margin">
                                    &nbsp;&nbsp;- Click on <strong>Create Page Rule</strong>
                                </p>
                                <p class="no-margin">
                                    &nbsp;&nbsp;- In the url field add the following url:
                                    <strong><?php echo rtrim(site_url(), '/') . '/'; ?>*</strong>
                                </p>
                                <p class="no-margin">
                                    &nbsp;&nbsp;- Click <strong>Add Setting</strong> and search for <strong>Rocket
                                        Loader</strong>
                                </p>
                                <p class="no-margin">
                                    &nbsp;&nbsp;- After you select Rocket Loader <strong>set value to
                                        Off</strong>
                                </p>
                                <p class="no-margin">
                                    &nbsp;&nbsp;- Click <strong>Save and Deploy</strong>
                                </p>
                            </li>
                        </ul>
                    </li>
                </ul>
                <br/>
                <br/>
                <a href="<?php echo admin_url('misc/dismiss_cloudflare_notice'); ?>"
                   class="alert-link">Got it! Don't show this message again</a>&nbsp;&nbsp;&nbsp;&nbsp;<a
                        href="<?php echo admin_url('misc/dismiss_cloudflare_notice'); ?>"
                        class="alert-link">Rocket loader is already disabled</a>
            </div>
        </div>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        echo $contents;
    }
}

/**
 * Flatten multidimensional array
 *
 * @param array $array
 * @return array
 */
function array_flatten(array $array)
{
    $return = array();
    array_walk_recursive($array, function ($a) use (&$return) {
        $return[] = $a;
    });

    return $return;
}

/**
 * Check if there is usage of some features that requires cron job to be setup
 * If the script found results will output a message inside the admin area only for admins
 *
 * @return void
 */
function is_cron_setup_required()
{
    if (get_option('cron_has_run_from_cli') == 0) {
        if (is_admin()) {
            $used_features = array();
            $using_cron_features = 0;
            $feature = total_rows('tblreminders');
            $using_cron_features += $feature;
            if ($feature > 0) {
                array_push($used_features, 'Reminders');
            }
            $feature = get_option('auto_backup_enabled');
            $using_cron_features += $feature;
            if ($feature > 0) {
                array_push($used_features, 'Auto database backup');
            }

            $feature = total_rows('tblsurveysemailsendcron');
            $using_cron_features += $feature;
            if ($feature > 0) {
                array_push($used_features, 'Surveys');
            }
            $feature = total_rows('tblleadsintegration', array(
                'active' => 1
            ));
            $using_cron_features += $feature;

            if ($feature > 0) {
                array_push($used_features, 'Auto importing leads from email.');
            }
            $feature = total_rows('tblinvoices', array(
                'recurring >' => 0
            ));
            $using_cron_features += $feature;
            if ($feature > 0) {
                array_push($used_features, 'Recurring Invoices');
            }
            $feature = total_rows('tblexpenses', array(
                'recurring' => 1
            ));
            $using_cron_features += $feature;
            if ($feature > 0) {
                array_push($used_features, 'Recurring Expenses');
            }

            $feature = total_rows('tblstafftasks', array(
                'recurring' => 1
            ));
            $using_cron_features += $feature;
            if ($feature > 0) {
                array_push($used_features, 'Recurring Tasks');
            }

            $feature = total_rows('tblevents');
            $using_cron_features += $feature;

            if ($feature > 0) {
                array_push($used_features, 'Custom Calendar Events');
            }

            $feature = total_rows('tbldepartments', array(
                'host !=' => '',
                'password !=' => '',
                'email !=' => ''
            ));
            $using_cron_features += $feature;
            if ($feature > 0) {
                array_push($used_features, 'Auto Import Tickets via method IMAP (Setup->Support->Departments)');
            }

            if ($using_cron_features > 0 && get_option('hide_cron_is_required_message') == 0) {
                echo '<div class="col-md-12">';
                echo '<div class="alert alert-danger">';
                echo 'You are using some features that requires cron job setup to work properly.';
                echo '<br />Please follow the cron <a href="https://help.perfexcrm.com/setup-cron-job/" target="_blank">setup guide</a> in order all features to work well.';
                echo '<br /><br /><br />';
                echo '<p class="bold">You are using the following features that CRON Job setup is required:</p>';
                $i = 1;
                foreach ($used_features as $feature) {
                    echo '&nbsp;' . $i . '. ' . $feature . '<br />';
                    $i++;
                }
                echo '<br /><br /><a href="' . admin_url('misc/dismiss_cron_setup_message') . '" class="alert-link">Don\'t show this message again</a>';
                echo '</div>';
                echo '</div>';
            }
        }
    }
}

/**
 * All email client templates slugs used for sending the emails
 * If you create new email template you can and must add the slug here with action hook.
 * Those are used to identify in what language should the email template to be sent
 *
 * @return array
 */
function get_client_email_templates_slugs()
{
    $client_email_templates_slugs = array(
        'new-client-created',
        'client-statement',
        'invoice-send-to-client',
        'new-ticket-opened-admin',
        'ticket-reply',
        'ticket-autoresponse',
        'assigned-to-project',
        'invoice-payment-recorded',
        'invoice-overdue-notice',
        'invoice-already-send',
        'estimate-send-to-client',
        'contact-forgot-password',
        'contact-password-reseted',
        'contact-set-password',
        'estimate-already-send',
        'contract-expiration',
        'proposal-send-to-customer',
        'proposal-client-thank-you',
        'proposal-comment-to-client',
        'estimate-thank-you-to-customer',
        'send-contract',
        'auto-close-ticket',
        'new-project-discussion-created-to-customer',
        'new-project-file-uploaded-to-customer',
        'new-project-discussion-comment-to-customer',
        'project-finished-to-customer',
        'estimate-expiry-reminder',
        'estimate-expiry-reminder',
        'task-marked-as-finished-to-contacts',
        'task-added-attachment-to-contacts',
        'task-commented-to-contacts'
    );

    return do_action('client_email_templates', $client_email_templates_slugs);
}

/**
 * All email staff templates slugs used for sending the emails
 * If you create new email template you can and must add the slug here with action hook.
 * Those are used to identify in what language should the email template to be sent
 *
 * @return array
 */
function get_staff_email_templates_slugs()
{
    $staff_email_templates_slugs = array(
        'new-ticket-created-staff',
        'two-factor-authentication',
        'ticket-reply-to-admin',
        'ticket-assigned-to-admin',
        'task-assigned',
        'task-added-as-follower',
        'task-commented',
        'staff-password-reseted',
        'staff-forgot-password',
        'task-marked-as-finished',
        'task-added-attachment',
        'task-unmarked-as-finished',
        'estimate-declined-to-staff',
        'estimate-accepted-to-staff',
        'proposal-client-accepted',
        'proposal-client-declined',
        'proposal-comment-to-admin',
        'task-deadline-notification',
        'invoice-payment-recorded-to-staff',
        'new-project-discussion-created-to-staff',
        'new-project-file-uploaded-to-staff',
        'new-project-discussion-comment-to-staff',
        'staff-added-as-project-member',
        'new-staff-created',
        'new-client-registered-to-admin',
        'new-lead-assigned'
    );

    return do_action('staff_email_templates', $staff_email_templates_slugs);
}

/**
 * Function that will return in what language the email template should be sent
 *
 * @param string $template_slug
 *            the template slug
 * @param string $email
 *            email that this template will be sent
 * @return string
 */
function get_email_template_language($template_slug, $email)
{
    $CI = &get_instance();
    $language = get_option('active_language');

    if (total_rows('tblcontacts', array(
            'email' => $email
        )) > 0 && in_array($template_slug, get_client_email_templates_slugs())) {
        $CI->db->where('email', $email);

        $contact = $CI->db->get('tblcontacts')->row();
        $lang = get_client_default_language($contact->userid);
        if ($lang != '') {
            $language = $lang;
        }
    } elseif (total_rows('tblstaff', array(
            'email' => $email
        )) > 0 && in_array($template_slug, get_staff_email_templates_slugs())) {
        $CI->db->where('email', $email);
        $staff = $CI->db->get('tblstaff')->row();

        $lang = get_staff_default_language($staff->staffid);
        if ($lang != '') {
            $language = $lang;
        }
    } elseif (class_exists('Emails_model')) {
        // check for leads default language
        if ($CI->emails_model->get_rel_type() == 'proposal') {
            $CI->db->select('rel_type,rel_id')->where('id', $CI->emails_model->get_rel_id());

            $proposal = $CI->db->get('tblproposals')->row();
            if ($proposal && $proposal->rel_type == 'lead') {
                $CI->db->select('default_language')->where('id', $proposal->rel_id);

                $lead = $CI->db->get('tblleads')->row();

                if ($lead && !empty($lead->default_language)) {
                    $language = $lead->default_language;
                }
            }
        }
    }

    $hook_data['language'] = $language;
    $hook_data['template_slug'] = $template_slug;
    $hook_data['email'] = $email;

    $hook_data = do_action('email_template_language', $hook_data);
    $language = $hook_data['language'];

    return $language;
}

/**
 * Return tasks summary formated data
 *
 * @param string $where
 *            additional where to perform
 * @return array
 */
function tasks_summary_data($rel_id = null, $rel_type = null)
{
    $CI = &get_instance();
    $tasks_summary = array();
    $statuses = $CI->tasks_model->get_statuses();
    foreach ($statuses as $status) {
        $tasks_where = 'status = ' . $status['id'];
        if (!has_permission('tasks', '', 'view')) {
            $tasks_where .= ' ' . get_tasks_where_string();
        }
        $tasks_my_where = 'id IN(SELECT taskid FROM tblstafftaskassignees WHERE staffid=' . get_staff_user_id() . ') AND status=' . $status['id'];
        if ($rel_id && $rel_type) {
            $tasks_where .= ' AND rel_id=' . $rel_id . ' AND rel_type="' . $rel_type . '"';
            $tasks_my_where .= ' AND rel_id=' . $rel_id . ' AND rel_type="' . $rel_type . '"';
        }
        $summary = array();
        $summary['total_tasks'] = total_rows('tblstafftasks', $tasks_where);
        $summary['total_my_tasks'] = total_rows('tblstafftasks', $tasks_my_where);
        $summary['color'] = $status['color'];
        $summary['name'] = $status['name'];
        $summary['status_id'] = $status['id'];
        $tasks_summary[] = $summary;
    }

    return $tasks_summary;
}

/**
 * Based on the template slug and email the function will fetch a template from database
 * The template will be fetched on the language that should be sent
 *
 * @param string $template_slug
 * @param string $email
 * @return object
 */
function get_email_template_for_sending($template_slug, $email)
{
    $CI = &get_instance();

    $language = get_email_template_language($template_slug, $email);

    if (!is_dir(APPPATH . 'language/' . $language)) {
        $language = 'english';
    }

    $CI->db->where('language', $language);
    $CI->db->where('slug', $template_slug);
    $template = $CI->db->get('tblemailtemplates')->row();

    // Template languages not yet inserted
    // Users needs to visit Setup->Email Templates->Any template to initialize all languages
    if (!$template) {
        $CI->db->where('language', 'english');
        $CI->db->where('slug', $template_slug);
        $template = $CI->db->get('tblemailtemplates')->row();
    } else {
        if ($template && $template->message == '') {
            // Template message blank use the active language default template
            $CI->db->where('language', get_option('active_language'));
            $CI->db->where('slug', $template_slug);
            $template = $CI->db->get('tblemailtemplates')->row();

            if ($template->message == '') {
                $CI->db->where('language', 'english');
                $CI->db->where('slug', $template_slug);
                $template = $CI->db->get('tblemailtemplates')->row();
            }
        }
    }

    return $template;
}

/**
 * Return specific alert bootstrap class
 *
 * @return string
 */
function get_alert_class()
{
    $CI = &get_instance();
    $alert_class = "";
    if ($CI->session->flashdata('message-success')) {
        $alert_class = "success";
    } elseif ($CI->session->flashdata('message-warning')) {
        $alert_class = "warning";
    } elseif ($CI->session->flashdata('message-info')) {
        $alert_class = "info";
    } elseif ($CI->session->flashdata('message-danger')) {
        $alert_class = "danger";
    }

    return $alert_class;
}

/**
 * Generate random alpha numeric string
 *
 * @param integer $length
 *            the length of the string
 * @return string
 */
function generate_two_factor_auth_key()
{
    $key = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));

    for ($i = 0; $i < 16; $i++) {
        $key .= $keys[array_rand($keys)];
    }

    $key .= uniqid();

    return $key;
}

/**
 * Function that will replace the dropbox link size for the images
 * This function is used to preview dropbox image attachments
 *
 * @param string $url
 * @param string $bounding_box
 * @return string
 */
function optimize_dropbox_thumbnail($url, $bounding_box = '800')
{
    $url = str_replace('bounding_box=75', 'bounding_box=' . $bounding_box, $url);

    return $url;
}

/**
 * Prepare label when splitting weeks for charts
 *
 * @param array $weeks
 *            week
 * @param mixed $week
 *            week day - number
 * @return string
 */
function split_weeks_chart_label($weeks, $week)
{
    $week_start = $weeks[$week][0];
    end($weeks[$week]);
    $key = key($weeks[$week]);
    $week_end = $weeks[$week][$key];

    $week_start_year = date('Y', strtotime($week_start));
    $week_end_year = date('Y', strtotime($week_end));

    $week_start_month = date('m', strtotime($week_start));
    $week_end_month = date('m', strtotime($week_end));

    $label = '';

    $label .= date('d', strtotime($week_start));

    if ($week_start_month != $week_end_month && $week_start_year == $week_end_year) {
        $label .= ' ' . _l(date('F', mktime(0, 0, 0, $week_start_month, 1)));
    }

    if ($week_start_year != $week_end_year) {
        $label .= ' ' . _l(date('F', mktime(0, 0, 0, date('m', strtotime($week_start)), 1))) . ' ' . date('Y', strtotime($week_start));
    }

    $label .= ' - ';
    $label .= date('d', strtotime($week_end));
    if ($week_start_year != $week_end_year) {
        $label .= ' ' . _l(date('F', mktime(0, 0, 0, date('m', strtotime($week_end)), 1))) . ' ' . date('Y', strtotime($week_end));
    }

    if ($week_start_year == $week_end_year) {
        $label .= ' ' . _l(date('F', mktime(0, 0, 0, date('m', strtotime($week_end)), 1)));
        $label .= ' ' . date('Y', strtotime($week_start));
    }

    return $label;
}

/**
 * Get ranges weeks between 2 dates
 *
 * @param object $start_time
 *            date object
 * @param objetc $end_time
 *            date object
 * @return array
 */
function get_weekdays_between_dates($start_time, $end_time)
{
    $interval = new DateInterval('P1D');
    $end_time = $end_time->modify('+1 day');
    $dateRange = new DatePeriod($start_time, $interval, $end_time);
    $weekNumber = 1;
    $weeks = array();

    foreach ($dateRange as $date) {
        $weeks[$weekNumber][] = $date->format('Y-m-d');
        if ($date->format('w') == 0) {
            $weekNumber++;
        }
    }

    return $weeks;
}

/**
 * @param $custom_fields
 * @return array
 */
function format_external_form_custom_fields($custom_fields)
{
    $cfields = array();
    foreach ($custom_fields as $f) {
        $_field_object = new stdClass();
        $type = $f['type'];
        $className = 'form-control';

        if ($f['type'] == 'colorpicker') {
            $type = 'color';
        } elseif ($f['type'] == 'date_picker') {
            $type = 'date';
        } elseif ($f['type'] == 'date_picker_time') {
            $type = 'datetime';
        } elseif ($f['type'] == 'checkbox') {
            $type = 'checkbox-group';
            $className = '';
            if ($f['display_inline'] == 1) {
                $className .= 'form-inline-checkbox';
            }
        } elseif ($f['type'] == 'input') {
            $type = 'text';
        } elseif ($f['type'] == 'multiselect') {
            $type = 'select';
        }

        $field_array = array(
            'type' => $type,
            'label' => $f['name'],
            'className' => $className,
            'name' => 'form-cf-' . $f['id']
        );

        if ($f['type'] == 'multiselect') {
            $field_array['multiple'] = true;
        }

        if ($f['required'] == 1) {
            $field_array['required'] = true;
        }

        if ($f['type'] == 'checkbox' || $f['type'] == 'select' || $f['type'] == 'multiselect') {
            $field_array['values'] = array();
            $options = explode(',', $f['options']);
            // leave first field blank
            if ($f['type'] == 'select') {
                array_push($field_array['values'], array(
                    'label' => '',
                    'value' => ''
                ));
            }
            foreach ($options as $option) {
                $option = trim($option);
                if ($option != '') {
                    array_push($field_array['values'], array(
                        'label' => $option,
                        'value' => $option
                    ));
                }
            }
        }

        $_field_object->label = $f['name'];
        $_field_object->name = 'form-cf-' . $f['id'];
        $_field_object->fields = array();
        $_field_object->fields[] = $field_array;
        $cfields[] = $_field_object;
    }

    return $cfields;
}

/**
 *
 */
function show_pdf_unable_to_get_image_size_error()
{
    ?>
    <div style="font-size: 17px;">
        <hr/>
        <p>
            This error can be shown if the <b>PDF library can't read the image
                from your server</b>.
        </p>
        <p>
            Very often this is happening <b>when you are using custom PDF logo url
                in Setup -> Settings -> PDF</b>, first make sure that the url you
            added in Setup->Settings->PDF for the custom pdf logo is valid and the
            image exists if the problem still exists you will need to use a <b>direct
                path</b> to the image to include in the PDF documents. Follow the
            steps mentioned below:
        </p>
        <p>
            <strong>Method 1 (easy)</strong>
        </p>
        <ul>
            <li>Upload the logo image in the installation directory eq. <?php echo FCPATH; ?>mylogo.jpg</li>
            <li><a href="<?php echo admin_url('settings?group=pdf'); ?>"
                   target="_blank">Navigate to Setup -> Settings -> PDF</a> -> Custom
                PDF Company Logo URL and only add the filename like: <b>mylogo.jpg</b>,
                now Custom PDF Company Logo URL should be only filename not full URL.
            </li>
            <li>Try to re-generate PDF document again.</li>
        </ul>
        <p>
            <strong>Method 2 (advanced)</strong>
        </p>
        <small>Try this method if method 1 is still not working.</small>
        <ul>
            <li>Consult with your hosting provider to confirm that the server is
                able to use PHP's <a
                        href="http://php.net/manual/en/function.file-get-contents.php"
                        target="_blank">file_get_contents</a> or <a
                        href="http://php.net/manual/en/curl.examples-basic.php"
                        target="_blank">cUrl</a> to download the file.
            </li>
            <li>Try to re-generate PDF document again.</li>
        </ul>
    </div>
    <?php
}
