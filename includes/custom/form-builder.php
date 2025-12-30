<?php

if (! defined('ABSPATH')) exit;

class Contract_Builder_System
{

    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cb_form_templates';

        register_activation_hook(__FILE__, array($this, 'install_table'));
        add_action('init', array($this, 'install_table')); // For dev testing
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        add_shortcode('contract_template_builder', array($this, 'render_template_builder'));
        add_shortcode('contract_submission', array($this, 'render_submission_form'));

        add_action('wp_ajax_cb_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_cb_save_contract_entry', array($this, 'ajax_save_contract_entry'));
    }

    public function install_table()
    {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $this->table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                template_name tinytext NOT NULL,
                structure longtext NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }


    public function enqueue_assets()
    {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
        wp_enqueue_script('jquery');
        wp_register_script('cb-core', false);
        wp_enqueue_script('cb-core');

        $student_opts = $this->get_user_options_html('student');
        $teacher_opts = $this->get_user_options_html('teacher');
        $status_opts = get_contract_status_choices();

        wp_localize_script('cb-core', 'cbParams', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cb_system_nonce'),

            'opts_student' => $student_opts,
            'opts_teacher' => $teacher_opts,
            'opts_status'  => get_contract_status_choices(),

            'default_structure' => $this->get_default_structure(),
        ));


        // wp_localize_script('cb-core', 'cbParams', [
        //     'ajaxurl' => admin_url('admin-ajax.php'),
        //     'nonce' => wp_create_nonce('cb_system_nonce'),
        //     'opts_student' => $student_opts,
        //     'opts_teacher' => $teacher_opts,
        //     'default_structure' => $this->get_default_structure()
        // ]);


        $this->print_styles();
    }

    private function get_user_options_html($role)
    {
        if (empty($role)) return '<option value="">-- No Role --</option>';
        $users = get_users(array('role' => $role, 'meta_key' => 'parent_user_id', 'meta_value' => get_current_user_id()));
        $html = '<option value="">-- Select ' . ucfirst($role) . ' --</option>';
        foreach ($users as $user) {
            $html .= sprintf(
                '<option value="%d">%s (%s)</option>',
                $user->ID,
                $user->display_name,
                $user->user_email
            );
        }
        return $html;
    }

    // --- DEFAULT FIELDS DEFINITION ---
    private function get_default_structure()
    {
        return [
            [
                'id' => 'f_title',
                'name' => 'post_title',
                'type' => 'text',
                'label' => 'Contract Title',
                'placeholder' => 'Enter title',
                'required' => true,
                'is_system' => true
            ],
            [
                'id' => 'f_content',
                'name' => 'post_content',
                'type' => 'textarea',
                'label' => 'Contract Content',
                'placeholder' => 'Terms...',
                'required' => true,
                'is_system' => true
            ],
            [
                'id' => 'f_student',
                'name' => 'contract_student',
                'type' => 'user_select',
                'label' => 'Student',
                'role_limit' => 'student',
                'required' => true,
                'is_system' => true
            ],
            [
                'id' => 'f_teacher',
                'name' => 'contract_teacher',
                'type' => 'user_select',
                'label' => 'Teacher',
                'role_limit' => 'teacher',
                'required' => true,
                'is_system' => true
            ],
            [
                'id' => 'f_status',
                'name' => 'contract_status',
                'type' => 'select',
                'label' => 'Contract Status',
                'is_status' => true,
                'required' => true,
                'is_system' => true
            ],
            [
                'id' => 'f_image',
                'name' => '_thumbnail_id',
                'type' => 'image',
                'label' => 'Featured Image',
                'required' => false,
                'is_system' => true
            ],
        ];
    }


    // =========================================================
    // 1. TEMPLATE BUILDER
    // =========================================================
    public function render_template_builder($atts)
    {
        if (! is_user_logged_in()) return '<p>Please login to build templates.</p>';

        $atts = shortcode_atts(array('template_id' => 0), $atts);
        $t_id = intval($atts['template_id']);
        $t_name = '';

        // Default to the Standard Structure
        $structure = $this->get_default_structure();

        if ($t_id > 0) {
            global $wpdb;
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $t_id));
            if ($row) {
                $t_name = $row->template_name;
                $decoded = json_decode($row->structure, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $structure = $decoded;
                }
            }
        }

        $js_config = array(
            'mode' => 'builder',
            'structure' => $structure,
            'template_id' => $t_id,
            'container_id' => 'cb-builder-canvas'
        );

        ob_start();
?>
        <div id="cb-root" class="cb-app-root">
            <div class="cb-paper-header">
                <h2>Template Builder</h2>
                <div class="cb-form-group">
                    <label>Template Name</label>
                    <input type="text" id="cb-template-name" class="cb-input-control" value="<?php echo esc_attr($t_name); ?>" placeholder="E.g. Math Tutoring Agreement">
                </div>
            </div>

            <div class="cb-app-inner">
                <div class="cb-main-col">
                    <div class="cb-paper">
                        <form id="cb-builder-form">
                            <div id="cb-builder-canvas" class="cb-canvas-area"></div>
                        </form>
                    </div>
                    <div class="cb-toolbar-bottom">
                        <button id="cb-save-template" class="cb-btn cb-btn-xl cb-btn-primary"><i class="fa fa-save"></i> Save Template</button>
                        <span id="cb-msg-builder"></span>
                    </div>
                </div>

                <div class="cb-sidebar-col">
                    <?php $this->render_sidebar_palette(); ?>
                    <?php $this->render_sidebar_editor(); ?>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                CB_System.init(<?php echo json_encode($js_config); ?>);



                $('#cb-save-template').click(function(e) {
                    e.preventDefault();
                    var name = $('#cb-template-name').val();
                    if (!name) {
                        alert('Please enter a Template Name');
                        return;
                    }

                    CB_System.saveTemplate(name, function(res) {
                        if (res.success) {

                            // 🔥 FULL RESET
                            // state.template_id = 0;
                            // state.structure = [];

                            // Reset UI
                            $('#cb-template-name').val('');
                            $('#cb-msg-builder')
                                .css('color', '#10b981')
                                .text('Template Saved.');

                            // Re-render default template
                            // CB_System.loadStructure(cbParams.default_structure);
                            if (res.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = res.data.redirect;
                                }, 1000);
                            }

                        }
                    });

                });
            });
        </script>
    <?php
        $this->print_js_core();
        return ob_get_clean();
    }

    // =========================================================
    // 2. CONTRACT SUBMISSION
    // =========================================================
    public function render_submission_form($atts)
    {
        if (! is_user_logged_in()) return '<p>Please login to create contracts.</p>';


        $atts = shortcode_atts(array('id' => 0), $atts);
        $post_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
        $current_user_id = get_current_user_id();

        global $wpdb;
        $templates = $wpdb->get_results($wpdb->prepare("SELECT id, template_name, structure FROM $this->table_name WHERE user_id = %d ORDER BY created_at DESC", $current_user_id));

        $edit_data = [];
        $selected_template_id = '';
        $saved_schema = [];

        // With this cleaner version:
        if ($post_id > 0) {
            $post = get_post($post_id);
            if ($post) {
                $selected_template_id = get_post_meta($post_id, '_cb_template_id', true);

                // Get saved schema
                $raw_schema = get_post_meta($post_id, '_cb_contract_form_json', true);
                if ($raw_schema) {
                    $saved_schema = json_decode($raw_schema, true) ?: [];
                }

                // If no saved schema but have template ID, load from template
                if (empty($saved_schema) && $selected_template_id) {
                    $t_row = $wpdb->get_row($wpdb->prepare(
                        "SELECT structure FROM $this->table_name WHERE id = %d",
                        $selected_template_id
                    ));
                    if ($t_row) {
                        $saved_schema = json_decode($t_row->structure, true) ?: [];
                    }
                }
            }
        }

        $templates_js = [];
        foreach ($templates as $t) {
            $templates_js[$t->id] = [
                'name' => $t->template_name,
                'structure' => json_decode($t->structure, true)
            ];
        }

        $js_config = array(
            'mode' => ($post_id > 0) ? 'edit' : 'submission',
            'post_id' => $post_id,
            'templates' => $templates_js,
            'saved_schema'  => $saved_schema,
            'container_id' => 'cb-submission-canvas'
        );

        ob_start();
    ?>
        <div class="cb-app-root">
            <div class="cb-paper cb-submission-wrapper">
                <h3><?php echo ($post_id > 0) ? 'Edit Contract' : 'Create New Contract'; ?></h3>

                <!-- Template Selector -->
                <?php if ($post_id === 0): ?>
                    <div class="cb-form-group">
                        <label>Select Template</label>
                        <select id="cb-template-select" class="cb-input-view">
                            <option value="">-- Choose a Template --</option>
                            <?php foreach ($templates as $t): ?>
                                <option value="<?php echo $t->id; ?>">
                                    <?php echo esc_html($t->template_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>


                <hr style="border:0; border-top:1px solid #e2e8f0; margin: 20px 0;">

                <form id="cb-submission-form" enctype="multipart/form-data">
                    <div id="cb-submission-canvas" class="cb-canvas-area">
                        <p style="color:#94a3b8; text-align:center;">Select a template to load the form.</p>
                    </div>
                </form>

                <div class="cb-toolbar-bottom" style="margin-top:20px;">
                    <button id="cb-submit-contract" class="cb-btn cb-btn-xl cb-btn-primary" disabled>
                        <i class="fa fa-paper-plane"></i> <?php echo ($post_id > 0) ? 'Update Contract' : 'Create Contract'; ?>
                    </button>
                    <span id="cb-msg-submission"></span>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                var config = <?php echo json_encode($js_config); ?>;
                CB_System.init(config);

                var $select = $('#cb-template-select');
                var $submitBtn = $('#cb-submit-contract');

                $select.on('change', function() {
                    var tid = $(this).val();
                    if (tid && config.templates[tid]) {
                        CB_System.loadStructure(config.templates[tid].structure);
                        $submitBtn.prop('disabled', false);
                    } else {
                        $('#' + config.container_id).html('<p style="color:#94a3b8; text-align:center;">Select a template to load the form.</p>');
                        $submitBtn.prop('disabled', true);
                    }
                });

                // 🔥 EDIT MODE
                // EDIT MODE
                if (config.mode === 'edit' && config.saved_schema.length) {
                    CB_System.loadStructure(config.saved_schema);
                    $submitBtn.prop('disabled', false);

                    // Disable template selector in edit mode
                    if ($select.length) {
                        $select.prop('disabled', true).val(config.template_id || '');
                    }
                } else if ($select.val()) {
                    // Submission mode
                    $select.trigger('change');
                }

                $submitBtn.click(function(e) {
                    e.preventDefault();
                    console.log('Save clicked - Mode:', config.mode);

                    CB_System.saveContractEntry({
                        template_id: $select.val() || config.template_id,
                        post_id: config.post_id
                    }, function(res) {
                        console.log('Save response:', res);
                        if (res.success) {
                            $('#cb-msg-submission')
                                .css('color', '#10b981')
                                .text(res.data.message);
                            
                            $('#cb-submission-form')[0].reset();

                            // Optional: Redirect to view the contract
                            if (res.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = res.data.redirect;
                                }, 1500);
                            }
                        } else {
                            $('#cb-msg-submission')
                                .css('color', '#ef4444')
                                .text('Error: ' + (res.data || 'Unknown error'));
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        $('#cb-msg-submission')
                            .css('color', '#ef4444')
                            .text('Network error: ' + error);
                    });
                });
            });
        </script>
        <?php
        if (! has_action('wp_footer', array($this, 'print_js_core_flag'))) {
            $this->print_js_core();
        }
        return ob_get_clean();
    }

    // =========================================================
    // 3. AJAX HANDLERS
    // =========================================================

    public function ajax_save_template()
    {
        check_ajax_referer('cb_system_nonce', 'nonce');
        global $wpdb;

        $user_id = get_current_user_id();
        $name = sanitize_text_field($_POST['template_name']);
        $structure = stripslashes($_POST['structure']); // JSON string
        $t_id = intval($_POST['template_id']);

        $data = array(
            'user_id' => $user_id,
            'template_name' => $name,
            'structure' => $structure,
            'created_at' => current_time('mysql')
        );

        if ($t_id > 0) {
            $wpdb->update($this->table_name, $data, array('id' => $t_id, 'user_id' => $user_id));
            $new_id = $t_id;
        } else {
            $wpdb->insert($this->table_name, $data);
            $new_id = $wpdb->insert_id;
        }

        wp_send_json_success(array('id' => $new_id,
    'redirect' => add_query_arg(array('tab' => 'insert_contract'), home_url('/dashboard'))));
    }

    public function ajax_save_contract_entry()
    {
        check_ajax_referer('cb_system_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $template_id = intval($_POST['template_id']);

        global $wpdb;

        // For edit mode, get template ID from post meta if not provided
        if ($post_id > 0 && !$template_id) {
            $template_id = get_post_meta($post_id, '_cb_template_id', true);
        }

        $t_row = $wpdb->get_row($wpdb->prepare(
            "SELECT structure FROM $this->table_name WHERE id = %d",
            $template_id
        ));

        if (!$t_row) {
            wp_send_json_error('Invalid Template');
        }

        $structure = json_decode($t_row->structure, true);

        // 1. First Pass: Handle default WP fields
        $p_title = isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : 'Contract ' . date('Y-m-d');
        $p_content = isset($_POST['post_content']) ? wp_kses_post($_POST['post_content']) : '';
        $p_student = isset($_POST['contract_student']) ? intval($_POST['contract_student']) : null;
        $p_teacher = isset($_POST['contract_teacher']) ? intval($_POST['contract_teacher']) : null;
        $p_status = isset($_POST['contract_status']) ? sanitize_text_field($_POST['contract_status']) : '';

        $args = [
            'post_title'   => $p_title,
            'post_content' => $p_content,
            'post_type'    => 'contract',
            'post_status'  => 'publish',
        ];

        if ($post_id > 0) {
            $args['ID'] = $post_id;
            wp_update_post($args);
            $msg = 'Contract updated successfully.';
        } else {
            $post_id = wp_insert_post($args);
            $msg = 'Contract created successfully.';
        }

        if (is_wp_error($post_id)) wp_send_json_error('Save failed');

        update_post_meta($post_id, '_cb_template_id', $template_id);
        update_post_meta($post_id, 'contract_student', $p_student);
        update_post_meta($post_id, 'contract_teacher', $p_teacher);
        update_post_meta($post_id, 'contract_status', $p_status);

        // 2. Second Pass: Save all fields (including default ones)
        $form_json = [];
        $values_json = [];

        foreach ($structure as $field) {
            $key = $field['name'];
            $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $key));

            // Resolve value
            $value = null;

            if ($field['type'] === 'image') {
                if (!empty($_FILES[$key]['name'])) {
                    // Handle image upload
                    $attachment_id = media_handle_upload($key, 0);
                    if (!is_wp_error($attachment_id)) {
                        $value = $attachment_id;
                        update_post_meta($post_id, $key, $attachment_id);
                    }
                } else {
                    $value = get_post_meta($post_id, $key, true);
                }
            } elseif ($field['type'] === 'checkbox') {
                $value = isset($_POST[$key]) ? '1' : '0';
                update_post_meta($post_id, $key, $value);
            } elseif (isset($_POST[$key])) {
                $value = is_array($_POST[$key])
                    ? array_map('sanitize_text_field', $_POST[$key])
                    : sanitize_text_field($_POST[$key]);
                update_post_meta($post_id, $key, $value);
            } else {
                // For default fields that come from $_POST (like student/teacher) or blank
                $value = get_post_meta($post_id, $key, true) ?: '';
                update_post_meta($post_id, $key, $value);
            }

            // Full schema JSON
            $field_with_value = $field;
            $field_with_value['value'] = $value;
            $form_json[] = $field_with_value;

            // Flat key/value JSON
            $values_json[$key] = [
                'label' => $label,
                'meta_key' => $key,
                'value' => $value
            ];
        }

        // Save both JSONs
        update_post_meta($post_id, '_cb_contract_form_json', wp_json_encode($form_json));
        update_post_meta($post_id, '_cb_contract_values_json', wp_json_encode($values_json));

        if($post_id > 0) {
        $message = "New Contract Assigned:
        $p_title";
        spn_create_notification( $p_student, $p_teacher, $message );
    }
        // remove args
        remove_query_arg('pid');

        wp_send_json_success([
        'post_id' => $post_id, 
        'message' => $msg,
        'redirect' => add_query_arg(array('tab' => 'all_contracts'), home_url('/dashboard'))
    ]);
    }


    // =========================================================
    // 4. JS CORE
    // =========================================================
    public function print_js_core()
    {
        ?>
        <script>
            var CB_System = (function($) {
                var state = {
                    mode: 'builder',
                    structure: [],
                    template_id: 0,
                    values: {},
                    container_id: ''
                };

                function escAttr(str) {
                    return !str ? '' : String(str).replace(/"/g, '&quot;');
                }

                function init(config) {
                    state = $.extend(state, config);
                    renderAll();
                    if (state.mode === 'builder') attachBuilderEvents();
                }

                function loadStructure(newStructure) {
                    // 🔥 Deep clone to prevent mutation
                    state.structure = JSON.parse(JSON.stringify(newStructure || []));
                    renderAll();
                }

                function renderFieldHTML(field) {
                    var isBuilder = (state.mode === 'builder');
                    var val = state.values[field.name] || field.value || '';

                    var reqAttr = field.required ? 'required' : '';
                    var reqStar = field.required ? '<span class="cb-req">*</span>' : '';

                    var icons = {
                        'text': 'fa-font',
                        'textarea': 'fa-align-left',
                        'number': 'fa-hashtag',
                        'email': 'fa-envelope',
                        'date': 'fa-calendar',
                        'select': 'fa-list-ul',
                        'checkbox': 'fa-check-square',
                        'user_select': 'fa-user',
                        'image': 'fa-image'
                    };
                    var iconClass = icons[field.type] || 'fa-pen';

                    var inputHtml = '';

                    if (field.type === 'textarea') {
                        inputHtml = `<textarea name="${field.name}" class="cb-input-view" placeholder="${escAttr(field.placeholder)}" ${reqAttr}>${val}</textarea>`;
                    } else if (field.type === 'select') {
                        var optsHtml = '';

                        if (field.is_status && cbParams.opts_status) {

                            // Use associative array from cbParams.opts_status
                            for (var key in cbParams.opts_status) {
                                // if (cbParams.opts_status.hasOwnProperty(key)) {
                                var sel = (val == key) ? 'selected' : '';
                                optsHtml += `<option value="${escAttr(key)}" ${sel}>${escAttr(cbParams.opts_status[key])}</option>`;
                                // }
                            }
                        } else if (field.options) {
                            // Fallback: comma-separated options
                            field.options.split(',').forEach(function(opt) {
                                opt = opt.trim();
                                var sel = (val == opt) ? 'selected' : '';
                                optsHtml += `<option value="${escAttr(opt)}" ${sel}>${escAttr(opt)}</option>`;
                            });
                        }

                        inputHtml = `<select name="${field.name}" class="cb-input-view" ${reqAttr}>${optsHtml}</select>`;

                    } else if (field.type === 'user_select') {
                        var opts = (field.role_limit === 'teacher') ? cbParams.opts_teacher : cbParams.opts_student;
                        if (val) opts = opts.replace('value="' + val + '"', 'value="' + val + '" selected');
                        inputHtml = `<select name="${field.name}" class="cb-input-view" ${reqAttr}>${opts}</select>`;
                    } else if (field.type === 'checkbox') {
                        var chk = (val == '1') ? 'checked' : '';
                        inputHtml = `
                     <label class="cb-checkbox-label">
                        <input type="checkbox" name="${field.name}" value="1" ${chk} ${reqAttr}>
                        <span class="cb-desc">${field.placeholder || 'Yes'}</span>
                     </label>`;
                    } else if (field.type === 'image') {
                        inputHtml = `<input type="file" name="${field.name}" class="cb-input-file" accept="image/*" ${reqAttr}>`;
                        if (val && !isBuilder) {
                            inputHtml += `<div class="cb-img-preview"><small>Image ID: ${val}</small></div>`;
                        }
                    } else {
                        inputHtml = `<input type="${field.type}" name="${field.name}" class="cb-input-view" value="${escAttr(val)}" placeholder="${escAttr(field.placeholder)}" ${reqAttr}>`;
                    }

                    var actions = '';
                    if (isBuilder) {
                        // Prevent deleting mandatory core fields
                        var isLocked = field.is_system === true;
                        var delBtn = isLocked ? '' : `<button type="button" class="cb-action-btn cb-btn-del" title="Delete"><i class="fa fa-trash"></i></button>`;

                        actions = `
                    <div class="cb-field-actions">
                        <button type="button" class="cb-action-btn cb-btn-edit" title="Edit"><i class="fa fa-cog"></i></button>
                        ${delBtn}
                    </div>`;
                    }

                    return `
                <div class="cb-field-wrapper" id="wrapper-${field.id}" data-id="${field.id}">
                    <div class="cb-field-header">
                        <div class="cb-fh-left">
                            <span class="cb-field-icon"><i class="fa ${iconClass}"></i></span>
                            <label class="cb-label-display">${field.label}${reqStar}</label>
                        </div>
                        ${actions}
                    </div>
                    <div class="cb-field-body">
                        ${inputHtml}
                        ${isBuilder ? `<div class="cb-meta-tag">Key: <span>${field.name}</span></div>` : ''}
                    </div>
                </div>`;
                }

                function renderAll() {
                    var html = '';
                    state.structure.forEach(function(f) {
                        html += renderFieldHTML(f);
                    });
                    $('#' + state.container_id).html(html);
                }

                function attachBuilderEvents() {
                    $('.cb-tool-btn').off('click').on('click', function(e) {
                        e.preventDefault();
                        var type = $(this).data('type');
                        var role = $(this).data('role') || '';
                        var ts = Date.now();
                        var newField = {
                            id: 'f_' + ts,
                            name: 'meta_' + (role || type) + '_' + ts,
                            type: type,
                            label: 'New ' + (role || type),
                            placeholder: '',
                            required: false,
                            options: '',
                            role_limit: role,
                            value: ''
                        };
                        state.structure.push(newField);
                        renderAll();
                        $('html, body').animate({
                            scrollTop: $('#' + state.container_id).height()
                        }, 300);
                    });

                    $(document).on('click', '.cb-btn-del', function() {
                        var id = $(this).closest('.cb-field-wrapper').data('id');
                        state.structure = state.structure.filter(f => f.id !== id);
                        renderAll();
                        $('#cb-editor').hide();
                        $('#cb-palette').show();
                    });

                    $(document).on('click', '.cb-btn-edit', function() {
                        var id = $(this).closest('.cb-field-wrapper').data('id');
                        var field = state.structure.find(f => f.id === id);
                        if (!field) return;

                        $('#edit-field-id').val(id);
                        $('#edit-label').val(field.label);
                        $('#edit-name').val(field.name);
                        $('#edit-placeholder').val(field.placeholder || '');
                        $('#edit-required').prop('checked', field.required);
                        $('#edit-options').val(field.options || '');

                        var type = field.type;
                        $('#edit-placeholder-wrapper').toggle(['text', 'textarea', 'number', 'email'].includes(type));
                        $('#edit-options-wrapper').toggle(type === 'select');

                        // Lock Key editing for core fields
                        var isLocked = (['post_title', 'post_content', '_thumbnail_id', 'contract_student', 'contract_teacher'].includes(field.name));
                        $('#edit-name').prop('readonly', isLocked).css('opacity', isLocked ? 0.6 : 1);

                        $('#cb-palette').hide();
                        $('#cb-editor').show();
                    });

                    $('#cb-save-field').off('click').on('click', function() {
                        var id = $('#edit-field-id').val();
                        var field = state.structure.find(f => f.id === id);
                        if (field) {
                            field.label = $('#edit-label').val();
                            if (!['post_title'].includes(field.name)) field.name = $('#edit-name').val(); // Allow basic key edits unless title
                            field.placeholder = $('#edit-placeholder').val();
                            field.required = $('#edit-required').is(':checked');
                            field.options = $('#edit-options').val();
                            renderAll();
                            $('#cb-cancel-edit').click();
                        }
                    });

                    $('#cb-cancel-edit').off('click').on('click', function() {
                        $('#cb-editor').hide();
                        $('#cb-palette').show();
                    });
                }

                function saveTemplate(name, callback) {
                    $.post(cbParams.ajaxurl, {
                        action: 'cb_save_template',
                        nonce: cbParams.nonce,
                        template_id: state.template_id,
                        template_name: name,
                        structure: JSON.stringify(state.structure)
                    }, function(res) {

                        // 🔥 IMPORTANT: update template_id
                        if (res.success && res.data.id) {
                            state.template_id = res.data.id;

                        }

                        if (typeof callback === 'function') {
                            callback(res);
                        }

                    }, 'json');
                }


                function saveContractEntry(data, callback) {
                    var formData = new FormData(document.getElementById('cb-submission-form'));
                    formData.append('action', 'cb_save_contract_entry');
                    formData.append('nonce', cbParams.nonce);
                    formData.append('template_id', data.template_id);
                    formData.append('post_id', data.post_id || 0);

                    $.ajax({
                        url: cbParams.ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: callback,
                        dataType: 'json'
                    });
                }

                return {
                    init: init,
                    loadStructure: loadStructure,
                    saveTemplate: saveTemplate,
                    saveContractEntry: saveContractEntry
                };

            })(jQuery);
        </script>
    <?php
    }

    public function print_js_core_flag()
    {
        return true;
    }

    private function render_sidebar_palette()
    {
    ?>
        <div id="cb-palette" class="cb-card">
            <div class="cb-card-header">
                <h3><i class="fa fa-cubes"></i> Fields</h3>
            </div>
            <div class="cb-tools-grid">
                <button class="cb-tool-btn" data-type="text"><i class="fa fa-font"></i> Text</button>
                <button class="cb-tool-btn" data-type="textarea"><i class="fa fa-align-left"></i> Area</button>
                <button class="cb-tool-btn" data-type="number"><i class="fa fa-hashtag"></i> Number</button>
                <button class="cb-tool-btn" data-type="email"><i class="fa fa-envelope"></i> Email</button>
                <button class="cb-tool-btn" data-type="date"><i class="fa fa-calendar"></i> Date</button>
                <button class="cb-tool-btn" data-type="select"><i class="fa fa-list-ul"></i> Select</button>
                <button class="cb-tool-btn" data-type="checkbox"><i class="fa fa-check-square"></i> Check</button>
                <button class="cb-tool-btn" data-type="image"><i class="fa fa-image"></i> Image</button>
            </div>
            <div class="cb-tools-divider">Users</div>
            <div class="cb-tools-grid">
                <button class="cb-tool-btn" data-type="user_select" data-role="student"><i class="fa fa-user-graduate"></i> Student</button>
                <button class="cb-tool-btn" data-type="user_select" data-role="teacher"><i class="fa fa-chalkboard-teacher"></i> Teacher</button>
            </div>
        </div>
    <?php
    }

    private function render_sidebar_editor()
    {
    ?>
        <div id="cb-editor" class="cb-card" style="display:none;">
            <div class="cb-card-header cb-flex-between">
                <h3>Edit Properties</h3>
                <button id="cb-cancel-edit" class="cb-close-icon">&times;</button>
            </div>
            <div class="cb-card-body">
                <input type="hidden" id="edit-field-id">
                <div class="cb-form-group">
                    <label>Label</label>
                    <input type="text" id="edit-label" class="cb-input-control">
                </div>
                <div class="cb-form-group">
                    <label>Meta Key (Name)</label>
                    <input type="text" id="edit-name" class="cb-input-control cb-code-font">
                </div>
                <div class="cb-form-group" id="edit-placeholder-wrapper">
                    <label>Placeholder</label>
                    <input type="text" id="edit-placeholder" class="cb-input-control">
                </div>
                <div class="cb-form-group" id="edit-options-wrapper">
                    <label>Options (comma separated)</label>
                    <textarea id="edit-options" class="cb-input-control" rows="3"></textarea>
                </div>
                <div class="cb-form-group cb-toggle-group">
                    <label class="cb-switch">
                        <input type="checkbox" id="edit-required">
                        <span class="cb-slider"></span>
                    </label>
                    <span>Required</span>
                </div>
                <button id="cb-save-field" class="cb-btn cb-btn-success cb-btn-block">Apply Changes</button>
            </div>
        </div>
    <?php
    }

    private function print_styles()
    {
    ?>
        <style>
            .cb-app-root {
                font-family: -apple-system, sans-serif;
                color: #334155;
                background-color: #f8fafc;
                padding: 20px;
                border-radius: 8px;
                margin-block: 40px;
            }

            .cb-app-root * {
                box-sizing: border-box;
            }

            .cb-app-inner {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }

            .cb-main-col {
                flex: 1;
                min-width: 300px;
            }

            .cb-sidebar-col {
                flex: 0 0 300px;
            }

            .cb-paper {
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                margin-bottom: 20px;
            }

            .cb-field-wrapper {
                background: #fff;
                border: 1px solid #e2e8f0;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 15px;
            }

            .cb-field-wrapper:hover {
                border-color: #94a3b8;
            }

            .cb-field-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
            }

            .cb-fh-left {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .cb-input-control,
            .cb-input-view {
                width: 100%;
                padding: 10px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 14px;
            }

            .cb-tools-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                padding: 15px;
            }

            .cb-tool-btn {
                background: #fff;
                border: 1px solid #e2e8f0;
                padding: 10px;
                border-radius: 6px;
                cursor: pointer;
                text-align: left;
                color: #475569;
            }

            .cb-tool-btn:hover {
                border-color: #2563eb;
                color: #2563eb;
                background: #eff6ff;
            }

            .cb-btn {
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                padding: 10px 16px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .cb-btn-primary {
                background: #2563eb;
                color: white;
            }

            .cb-btn-primary:disabled {
                background: #94a3b8;
                cursor: not-allowed;
            }

            .cb-btn-success {
                background: #10b981;
                color: white;
                width: 100%;
            }

            .cb-card {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .cb-card-header {
                padding: 15px;
                border-bottom: 1px solid #e2e8f0;
                background: #f8fafc;
                font-weight: 600;
            }

            .cb-card-body {
                padding: 15px;
            }

            .cb-req {
                color: #ef4444;
            }

            .cb-form-group {
                margin-bottom: 15px;
            }

            .cb-form-group label {
                display: block;
                font-weight: 600;
                font-size: 12px;
                margin-bottom: 5px;
                color: #475569;
            }

            .cb-meta-tag {
                font-size: 11px;
                text-align: right;
                color: #94a3b8;
                margin-top: 5px;
            }

            .cb-meta-tag span {
                font-family: monospace;
                background: #f1f5f9;
                padding: 2px 4px;
                border-radius: 3px;
            }

            .cb-close-icon {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
            }

            .cb-flex-between {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* Toggle Switch */
            .cb-toggle-group {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .cb-switch {
                position: relative;
                display: inline-block;
                width: 34px;
                height: 18px;
            }

            .cb-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .cb-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }

            .cb-slider:before {
                position: absolute;
                content: "";
                height: 14px;
                width: 14px;
                left: 2px;
                bottom: 2px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked+.cb-slider {
                background-color: #2563eb;
            }

            input:checked+.cb-slider:before {
                transform: translateX(16px);
            }
        </style>
<?php
    }
}

new Contract_Builder_System();
