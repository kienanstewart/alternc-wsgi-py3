<?php

/**
 * Class for integrating mod_wsgi (py3) functionality with AlternC.
 */
class m_wsgipy3 {

    function hook_subdomain_type_extra_fields($domain_type, $domain, $subdomain_id = FALSE) {
        global $hooks;
        $result = array();
        if ($this->domain_type_is_python($domain_type)) {
            $ehe = ehe($domain_type['name']);
            $default_values = $this->get_subdomain_settings($subdomain_id);

            $value = $subdomain_id ? ehe($default_values['venv']) : "";
            $title = ehe(_('Virtual Environment'));
            $result['venv'] = <<<EOF
<label for="venv_{$ehe}">{$title}</label><input type="text" class="int" name="venv_{$ehe}" id="venv_{$ehe}" value="{$value}" size="28" onKeyPress="getElementById('r_{$ehe}').checked=true;"/>
EOF;

            # AlternC front end functions output directly, but here we catch it
            # for later.
            ob_start();
            display_browser($default_values['venv'], "venv_{$domain_type['name']}");
            $result['venv'] .= ob_get_contents();
            ob_end_clean();

            $value = $subdomain_id ? ehe($default_values['app_subdir']) : "";
            $title = ehe(_('Application sub-directory'));
            $result['app_subdir'] = <<<EOF
<label for="venv_{$ehe}">{$title}</label><input type="text" class="int" name="app_subdir_{$ehe}" id="app_subdir_{$ehe}" value="{$value}" size="28" onKeyPress="getElementById('r_{$ehe}').checked=true;"/>
EOF;
        }
        return $result;
    }

    function domain_type_is_python($domain_type) {
        global $hooks;
        $result = $hooks->invoke('hook_domain_type_is_python', array($domain_type));
        foreach ($result as $module => $r) {
            if (!empty($r)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    function hook_domain_type_is_python($domain_type) {
        if ($domain_type['name'] == 'wsgi') {
            return array(TRUE);
        }
    }

    /**
     * Gets the python settings for a given subdomain.
     *
     * @param $subdomain_id
     *
     * @returns array
     *   An array of python settings indexed by key.
     */
    function get_subdomain_settings($subdomain_id) {
        global $db;
        $db->query('SELECT * FROM subdomain_wsgi WHERE id = ?;', array($subdomain_id));
        if (!$db->next_record()) {
            return FALSE;
        }
        return array(
            'venv' => $db->f('venv'),
            'app_subdir' => $db->f('app_subdir'),
        );
    }

    /**
     * Sets the python settings for a given subdomain.
     *
     * @param $subdomain_id
     * @param $venv
     * @param $app_subdir
     *
     * @returns bool | array
     */
    function set_subdomain_settings($subdomain_id, $venv = '', $app_subdir = '') {
        global $db;
        if ($this->get_subdomain_settings($subdomain_id) !== FALSE) {
            $db->query('UPDATE subdomain_wsgi SET venv = ?, app_subdir = ? WHERE id = ?;',
                       array($venv, $app_subdir, $subdomain_id));
        }
        else {
            $db->query('INSERT INTO subdomain_wsgi (id, venv, app_subdir) VALUES (?, ?, ?);',
                       array($subdomain_id, $venv, $app_subdir));
        }
    }

    function delete_subdomain_settings($subdomain_id) {
        global $db;
        if ($subdomain_id > 0) {
            $db->query('DELETE FROM subdomain_wsgi WHERE id = ?;', array($subdomain_id));
        }
    }

    function hook_dom_del_subdomain($subdomain_id) {
        $this->delete_subdomain_settings($subdomain_id);
    }

    function hook_dom_subdoedit_fields($type = NULL) {
        global $dom;
        if ($type) {
            return array(
                "venv_{$type}" => array('post', 'string', ''),
                "app_subdir_{$type}" => array('post', 'string', ''),
            );
        }
    }

    function hook_dom_subdoedit_set($context) {
        // Deletion of old data is handled later, when update_domains is run.
        // @TODO: Validation
        $valid = TRUE;
        // Set
        if ($valid) {
            $this->set_subdomain_settings(
                $context['sub_domain_id'],
                $context["venv_{$context['type']}"],
                $context["app_subdir_{$context['type']}"]
            );
        }
    }

    function hook_web_template_tokens($subdomain) {
        $s = $this->get_subdomain_settings($subdomain['id']);
        if ($s) {
            return array(
                '%%VENV%%' => $s['venv'],
                '%%APP_SUBDIR%%' => $s['app_subdir'],
            );
        }
    }
}

?>
