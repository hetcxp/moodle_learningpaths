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

        // Implementación de Caché (MUC).
        $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'block_learningpaths', 'path_data');
        $pathdata = $cache->get($USER->id);

        if ($pathdata === false) {
            try {
                // 1. Obtener datos base de las rutas manuales.
                $pathdata = $this->get_user_path_data($USER->id) ?: [];
                
                // 2. Mapeo de cursos por organización para fusión.
                $org_courses_map = [];
                $remaining_org_courses = [];

                if (class_exists('\local_adminpanel\data\adminpanel_data')) {
                    $data_manager = new \local_adminpanel\data\adminpanel_data();
                    if (method_exists($data_manager, 'get_user_organization_path_details')) {
                        $org_details = $data_manager->get_user_organization_path_details($USER->id);
                        if (!empty($org_details['steps'])) {
                            foreach ($org_details['steps'] as $step) {
                                foreach ($step['courses'] as $oc) {
                                    $org_courses_map[$oc['id']] = $oc['organization'];
                                    $remaining_org_courses[$oc['id']] = $oc;
                                }
                            }
                        }
                    }
                }

                // 3. Fusión: Enriquecer rutas existentes con el identificador de organización.
                foreach ($pathdata as &$path) {
                    if (isset($path['courses'])) {
                        foreach ($path['courses'] as &$course) {
                            if (isset($org_courses_map[$course['id']])) {
                                $course['organization'] = $org_courses_map[$course['id']];
                                unset($remaining_org_courses[$course['id']]);
                            }
                        }
                    }
                }

                // 4. Si quedan cursos de organización "huérfanos", añadirlos en una ruta virtual única.
                if (!empty($remaining_org_courses)) {
                    if (isset($org_details)) {
                        $virtual_path = $org_details['path'];
                        $virtual_path['pathname'] = $virtual_path['name'];
                        $virtual_path['courses'] = array_values($remaining_org_courses);
                        $pathdata[] = $virtual_path;
                    }
                }

                // Asegurar que todos los paths tengan un valor de progreso para la UI.
                foreach ($pathdata as &$path) {
                    if (!isset($path['progress'])) {
                        $path['progress'] = 0; // Fallback si el trait no lo calcula.
                    }
                }

                $cache->set($USER->id, $pathdata);
                
            } catch (\Exception $e) {
                $this->content->text = 'Error loading path: ' . $e->getMessage();
                return $this->content;
            }
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
