<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/adminpanel/classes/traits/path_logic.php');

class block_learningpaths extends block_base {
    
    use \local_adminpanel\traits\path_logic;

    public function init() {
        $this->title = get_string('pluginname', 'block_learningpaths');
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        try {
            $pathdata = self::get_user_path_data($USER->id);
            
            // Integración con local_adminpanel para cursos asignados por organización
            if (class_exists('\local_adminpanel\data\adminpanel_data')) {
                $data_manager = new \local_adminpanel\data\adminpanel_data();
                if (method_exists($data_manager, 'get_user_organization_path_details')) {
                    $org_details = $data_manager->get_user_organization_path_details($USER->id);
                    if (!empty($org_details['steps'])) {
                        $virtual_path = $org_details['path'];
                        $virtual_path['steps'] = $org_details['steps'];
                        $pathdata[] = $virtual_path;
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->content->text = 'Error loading path: ' . $e->getMessage();
            return $this->content;
        }

        if (empty($pathdata)) {
            $this->content->text = get_string('no_path', 'block_learningpaths');
            return $this->content;
        }

        $data = [
            'paths' => array_values($pathdata)
        ];

        $this->content->text = $OUTPUT->render_from_template('block_learningpaths/main_view', $data);

        return $this->content;
    }
}
