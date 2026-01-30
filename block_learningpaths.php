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

        // DEBUGGING: Remove this in production
        error_log('DEBUG: block_learningpaths::get_content called for user ' . $USER->id);

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        // Temporary removal of login check for debugging visibility
        // if (!isloggedin() || isguestuser()) {
        //     return $this->content;
        // }

        try {
            $pathdata = self::get_user_path_data($USER->id);
            error_log('DEBUG: Path data count: ' . count($pathdata));
        } catch (\Exception $e) {
            error_log('DEBUG: Exception in get_user_path_data: ' . $e->getMessage());
            $this->content->text = 'Error loading path: ' . $e->getMessage();
            return $this->content;
        }

        if (empty($pathdata)) {
            $this->content->text = get_string('no_path', 'block_learningpaths');
            error_log('DEBUG: No path data found, setting text to: ' . $this->content->text);
            return $this->content;
        }

        $data = [
            'paths' => array_values($pathdata)
        ];

        $this->content->text = $OUTPUT->render_from_template('block_learningpaths/main_view', $data);
        error_log('DEBUG: Rendered content length: ' . strlen($this->content->text));

        return $this->content;
    }
}
