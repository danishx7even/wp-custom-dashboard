<?php


if ( ! defined( 'ABSPATH' ) ) exit;

class Contract_Builder {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'contract_builder', array( $this, 'render_builder' ) );

        // AJAX
        add_action( 'wp_ajax_cb_get_field_html', array( $this, 'ajax_get_field_html' ) );
        add_action( 'wp_ajax_nopriv_cb_get_field_html', array( $this, 'ajax_get_field_html' ) );
        add_action( 'wp_ajax_cb_save_contract', array( $this, 'ajax_save_contract' ) );
    }

    public function register_cpt() {
        register_post_type( 'contract', array(
            'labels' => array( 'name' => 'Contracts', 'singular_name' => 'Contract' ),
            'public' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'show_in_rest' => false,
        ));

        if (!get_role('student')) add_role('student', 'Student', ['read'=>true]);
        if (!get_role('teacher')) add_role('teacher', 'Teacher', ['read'=>true]);
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' );
        wp_enqueue_script( 'jquery' );
        
        // Inline script to pass params
        wp_register_script( 'cb-script', false ); 
        wp_enqueue_script( 'cb-script' );
        wp_localize_script( 'cb-script', 'cbParams', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cb_builder_nonce' )
        ));
    }

    private function get_user_options( $role, $selected_id = '' ) {
        if( empty($role) ) return '<option value="">-- No Role Defined --</option>';
        $users = get_users( array( 'role' => $role ) );
        $html = '<option value="">-- Select ' . ucfirst($role) . ' --</option>';
        foreach ( $users as $user ) {
            $sel = ( (string)$selected_id === (string)$user->ID ) ? 'selected' : '';
            $html .= sprintf( '<option value="%d" %s>%s (%s)</option>', 
                $user->ID, $sel, $user->display_name, $user->user_email 
            );
        }
        return $html;
    }

    public function render_field_view( $field ) {
        $id          = esc_attr( $field['id'] );
        $name        = isset($field['name']) ? esc_attr( $field['name'] ) : $id;
        $type        = esc_attr( $field['type'] );
        $label       = esc_html( $field['label'] );
        $placeholder = isset($field['placeholder']) ? esc_attr( $field['placeholder'] ) : '';
        $value       = isset($field['value']) ? $field['value'] : '';
        $required    = !empty($field['required']) ? 'required' : '';
        $req_star    = !empty($field['required']) ? '<span class="cb-req">*</span>' : '';
        
        $icons = [
            'text' => 'fa-font', 'textarea' => 'fa-align-left', 'number' => 'fa-hashtag',
            'email' => 'fa-envelope', 'date' => 'fa-calendar', 'select' => 'fa-list',
            'checkbox' => 'fa-check-square', 'user_select' => 'fa-user', 'image' => 'fa-image'
        ];
        $icon_class = isset($icons[$type]) ? $icons[$type] : 'fa-pen';

        ob_start();
        ?>
        <div class="cb-field-wrapper" id="wrapper-<?php echo $id; ?>" data-id="<?php echo $id; ?>">
            <div class="cb-field-header">
                <div class="cb-fh-left">
                    <span class="cb-field-icon"><i class="fa <?php echo $icon_class; ?>"></i></span>
                    <label class="cb-label-display"><?php echo $label . $req_star; ?></label>
                </div>
                <div class="cb-field-actions">
                    <button type="button" class="cb-action-btn cb-btn-edit" title="Edit"><i class="fa fa-cog"></i></button>
                    <button type="button" class="cb-action-btn cb-btn-del" title="Delete"><i class="fa fa-trash"></i></button>
                </div>
            </div>
            
            <div class="cb-field-body">
                <?php if ( $type === 'textarea' ): ?>
                    <textarea name="<?php echo $name; ?>" class="cb-input-view" placeholder="<?php echo $placeholder; ?>" <?php echo $required; ?>><?php echo esc_textarea($value); ?></textarea>
                
                <?php elseif ( $type === 'select' ): ?>
                    <select name="<?php echo $name; ?>" class="cb-input-view" <?php echo $required; ?>>
                        <?php 
                        if ( !empty( $field['options'] ) ) {
                            $options = explode( ',', $field['options'] );
                            foreach ( $options as $opt ) {
                                $opt = trim( $opt );
                                $selected = ( $value === $opt ) ? 'selected' : '';
                                echo '<option value="' . esc_attr( $opt ) . '" ' . $selected . '>' . esc_html( $opt ) . '</option>';
                            }
                        }
                        ?>
                    </select>

                <?php elseif ( $type === 'user_select' ): ?>
                    <?php $role = isset($field['role_limit']) ? $field['role_limit'] : 'subscriber'; ?>
                    <select name="<?php echo $name; ?>" class="cb-input-view" <?php echo $required; ?>>
                        <?php echo $this->get_user_options( $role, $value ); ?>
                    </select>

                <?php elseif ( $type === 'checkbox' ): ?>
                     <label class="cb-checkbox-label">
                        <input type="checkbox" name="<?php echo $name; ?>" value="1" <?php checked($value, '1'); ?> <?php echo $required; ?>>
                        <span class="cb-desc"><?php echo $placeholder ? $placeholder : 'Yes, I agree'; ?></span>
                     </label>

                <?php elseif ( $type === 'image' ): ?>
                    <input type="file" name="<?php echo $name; ?>" class="cb-input-file" accept="image/*" <?php echo $required; ?>>
                    <?php if(!empty($value)): ?>
                        <div class="cb-img-preview">
                            <div class="cb-img-box"><?php echo wp_get_attachment_image( $value, 'thumbnail' ); ?></div>
                            <small>ID: <?php echo esc_html($value); ?></small>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <input type="<?php echo $type; ?>" name="<?php echo $name; ?>" class="cb-input-view" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $required; ?>>
                <?php endif; ?>
                
                <div class="cb-meta-tag">Key: <span><?php echo $name; ?></span></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

        public function render_builder( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts );
        $post_id = intval( $atts['id'] );
        $structure = [];
        $values = [];

        // 1. Load Structure
        if ( $post_id > 0 ) {
            $saved_structure = get_post_meta( $post_id, '_contract_form_structure', true );
            if ( $saved_structure ) {
                $structure = json_decode( $saved_structure, true );
            }
            if ( is_array($structure) ) {
                foreach($structure as $f) {
                    if(isset($f['name'])) {
                        $values[$f['id']] = get_post_meta($post_id, $f['name'], true);
                    }
                }
            }
        }

        // 2. Default Structure
        if ( empty( $structure ) ) {
            $structure = [
                ['id' => 'f_title', 'name' => 'post_title', 'type' => 'text', 'label' => 'Contract Title', 'placeholder' => 'Enter title', 'required' => true],
                ['id' => 'f_content', 'name' => 'post_content', 'type' => 'textarea', 'label' => 'Contract Content', 'placeholder' => 'Terms...', 'required' => true],
                ['id' => 'f_student', 'name' => 'contract_student', 'type' => 'user_select', 'label' => 'Student', 'role_limit' => 'student', 'required' => false],
                ['id' => 'f_teacher', 'name' => 'contract_teacher', 'type' => 'user_select', 'label' => 'Teacher', 'role_limit' => 'teacher', 'required' => false],
                ['id' => 'f_image',   'name' => '_thumbnail_id', 'type' => 'image', 'label' => 'Featured Image', 'required' => false],
            ];
        }
        
        // Merge values
        foreach ($structure as &$field) {
            if(isset($values[$field['id']])) {
                $field['value'] = $values[$field['id']];
            }
        }
        unset($field); 

        ob_start();
        ?>
        <!-- Main ID wrapper for CSS Specificity -->
        <div id="cb-app-root">
            <div class="cb-app-inner">
                
                <!-- Left: Form Canvas -->
                <div class="cb-main-col">
                    <div class="cb-paper">
                        <div class="cb-paper-header">
                            <h2>Contract Editor</h2>
                            <p>Build your form structure and input content.</p>
                        </div>
                        <form id="cb-contract-form" enctype="multipart/form-data">
                            <div id="cb-canvas-area">
                                <?php foreach ( $structure as $field ) echo $this->render_field_view( $field ); ?>
                            </div>
                        </form>
                    </div>
                    
                    <div class="cb-toolbar-bottom">
                        <button id="cb-save-contract" class="cb-btn cb-btn-xl cb-btn-primary">
                            <i class="fa fa-save"></i> Save Contract
                        </button>
                        <span id="cb-message"></span>
                    </div>
                </div>

                <!-- Right: Sidebar -->
                <div class="cb-sidebar-col">
                    
                    <!-- Tools Palette -->
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
                        <div class="cb-tools-divider">User Roles</div>
                        <div class="cb-tools-grid">
                            <button class="cb-tool-btn" data-type="user_select" data-role="student"><i class="fa fa-user-graduate"></i> Student</button>
                            <button class="cb-tool-btn" data-type="user_select" data-role="teacher"><i class="fa fa-chalkboard-teacher"></i> Teacher</button>
                        </div>
                    </div>

                    <!-- Properties Editor -->
                    <div id="cb-editor" class="cb-card" style="display:none;">
                        <div class="cb-card-header cb-flex-between">
                            <h3>Edit Properties</h3>
                            <button id="cb-cancel-edit" class="cb-close-icon">&times;</button>
                        </div>
                        <div class="cb-card-body">
                            <input type="hidden" id="edit-field-id">
                            
                            <div class="cb-form-group">
                                <label>Label Text</label>
                                <input type="text" id="edit-label" class="cb-input-control" autocomplete="off">
                            </div>

                            <div class="cb-form-group">
                                <div class="cb-flex-between mb-5">
                                    <label>Meta Key</label>
                                    <button type="button" id="cb-sync-btn" class="cb-badge-btn" title="Click to toggle sync">
                                        <i class="fa fa-link"></i> Sync: ON
                                    </button>
                                </div>
                                <input type="text" id="edit-name" class="cb-input-control cb-code-font" autocomplete="off">
                            </div>
                            
                            <!-- Placeholder Group (Visible for Text, Textarea, Email, Number, Checkbox) -->
                            <div class="cb-form-group" id="edit-placeholder-wrapper">
                                <label id="edit-placeholder-label">Placeholder</label>
                                <input type="text" id="edit-placeholder" class="cb-input-control">
                            </div>

                            <!-- Options Group (Visible only for Select) -->
                            <div class="cb-form-group" id="edit-options-wrapper">
                                <label>Options (comma separated)</label>
                                <textarea id="edit-options" class="cb-input-control" rows="3"></textarea>
                                <small style="color:#94a3b8; font-size:11px;">Example: Option 1, Option 2, Option 3</small>
                            </div>

                            <div class="cb-form-group cb-toggle-group">
                                <label class="cb-switch">
                                    <input type="checkbox" id="edit-required">
                                    <span class="cb-slider"></span>
                                </label>
                                <span>Required Field</span>
                            </div>

                            <button id="cb-save-field" class="cb-btn cb-btn-success cb-btn-block">
                                <i class="fa fa-check"></i> Apply Changes
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- High Specificity CSS -->
        <style>
            /* Reset & Scope using ID selector */
            #cb-app-root {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                color: #334155;
                background-color: #f8fafc;
                padding: 20px;
                border-radius: 8px;
                box-sizing: border-box;
            }
            #cb-app-root * { box-sizing: border-box; }

            /* Layout */
            #cb-app-root .cb-app-inner { display: flex; gap: 20px; flex-wrap: wrap; }
            #cb-app-root .cb-main-col { flex: 1 1 500px; }
            #cb-app-root .cb-sidebar-col { flex: 0 0 300px; width: 300px; }

            /* Canvas / Paper */
            #cb-app-root .cb-paper { background: #fff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
            #cb-app-root .cb-paper-header { border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; padding-bottom: 10px; }
            #cb-app-root .cb-paper-header h2 { margin: 0; font-size: 24px; color: #1e293b; }
            #cb-app-root .cb-paper-header p { margin: 5px 0 0; color: #64748b; font-size: 14px; }

            /* Cards (Sidebar) */
            #cb-app-root .cb-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
            #cb-app-root .cb-card-header { background: #f8fafc; padding: 15px; border-bottom: 1px solid #e2e8f0; }
            #cb-app-root .cb-card-header h3 { margin: 0; font-size: 16px; font-weight: 600; color: #334155; }
            #cb-app-root .cb-card-body { padding: 20px; }

            /* Field Items (Left Side) */
            #cb-app-root .cb-field-wrapper {
                position: relative;
                background: #fff;
                border: 1px solid transparent;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 15px;
                transition: all 0.2s;
            }
            #cb-app-root .cb-field-wrapper:hover {
                border-color: #cbd5e1;
                box-shadow: 0 0 0 4px #f1f5f9;
            }
            #cb-app-root .cb-field-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
            #cb-app-root .cb-fh-left { display: flex; align-items: center; gap: 8px; }
            #cb-app-root .cb-field-icon { color: #94a3b8; }
            #cb-app-root .cb-label-display { font-weight: 600; font-size: 14px; color: #1e293b; }
            #cb-app-root .cb-req { color: #ef4444; margin-left: 2px; }
            
            #cb-app-root .cb-field-actions { opacity: 0; transition: opacity 0.2s; }
            #cb-app-root .cb-field-wrapper:hover .cb-field-actions { opacity: 1; }
            
            #cb-app-root .cb-action-btn {
                background: transparent; border: none; cursor: pointer; color: #94a3b8; font-size: 14px; padding: 4px 8px;
            }
            #cb-app-root .cb-action-btn:hover { color: #2563eb; }
            #cb-app-root .cb-btn-del:hover { color: #ef4444; }

            /* Inputs inside Canvas (View Mode) */
            #cb-app-root .cb-input-view, 
            #cb-app-root textarea.cb-input-view,
            #cb-app-root select.cb-input-view {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 14px;
                color: #334155;
                background: #fff;
                margin: 0;
            }
            #cb-app-root .cb-input-view:focus { outline: none; border-color: #2563eb; ring: 2px solid rgba(37,99,235,0.2); }
            #cb-app-root .cb-meta-tag { font-size: 11px; color: #94a3b8; text-align: right; margin-top: 4px; }
            #cb-app-root .cb-meta-tag span { font-family: monospace; background: #f1f5f9; padding: 2px 4px; border-radius: 3px; }

            /* Tools Grid */
            #cb-app-root .cb-tools-grid { padding: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            #cb-app-root .cb-tool-btn {
                display: flex; align-items: center; gap: 8px;
                width: 100%; text-align: left;
                background: #fff; border: 1px solid #e2e8f0;
                padding: 10px; border-radius: 6px;
                font-size: 13px; color: #475569;
                cursor: pointer; transition: all 0.2s;
            }
            #cb-app-root .cb-tool-btn:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
            #cb-app-root .cb-tools-divider { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; padding: 10px 15px 0; font-weight: 700; }

            /* Sidebar Editor Controls */
            #cb-app-root .cb-flex-between { display: flex; justify-content: space-between; align-items: center; }
            #cb-app-root .mb-5 { margin-bottom: 5px; }
            #cb-app-root .cb-close-icon { background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
            #cb-app-root .cb-form-group { margin-bottom: 15px; }
            #cb-app-root .cb-form-group label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; }
            
            #cb-app-root .cb-input-control {
                width: 100%; padding: 8px 10px;
                border: 1px solid #cbd5e1; border-radius: 5px;
                font-size: 13px; color: #1e293b;
            }
            #cb-app-root .cb-code-font { font-family: monospace; color: #d63384; background: #fff5f9; border-color: #fce7f3; }
            
            /* Sync Badge Button */
            #cb-app-root .cb-badge-btn {
                font-size: 10px; text-transform: uppercase; font-weight: 700;
                background: #e2e8f0; color: #64748b;
                border: none; padding: 2px 8px; border-radius: 4px;
                cursor: pointer; letter-spacing: 0.5px;
            }
            #cb-app-root .cb-badge-btn.active { background: #dbeafe; color: #2563eb; }

            /* Toggle Switch */
            #cb-app-root .cb-toggle-group { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
            #cb-app-root .cb-switch { position: relative; display: inline-block; width: 34px; height: 18px; margin: 0; }
            #cb-app-root .cb-switch input { opacity: 0; width: 0; height: 0; }
            #cb-app-root .cb-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
            #cb-app-root .cb-slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
            #cb-app-root input:checked + .cb-slider { background-color: #2563eb; }
            #cb-app-root input:checked + .cb-slider:before { transform: translateX(16px); }

            /* Buttons */
            #cb-app-root .cb-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
            #cb-app-root .cb-btn-primary { background: #2563eb; color: #fff; padding: 12px 20px; font-size: 15px; }
            #cb-app-root .cb-btn-primary:hover { background: #1d4ed8; }
            #cb-app-root .cb-btn-success { background: #10b981; color: #fff; padding: 10px; }
            #cb-app-root .cb-btn-success:hover { background: #059669; }
            #cb-app-root .cb-btn-block { width: 100%; }

            /* Image Preview */
            #cb-app-root .cb-img-preview { margin-top: 5px; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 6px; background: #f8fafc; text-align: center; }
            #cb-app-root .cb-img-box img { max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        </style>

        <!-- Logic -->
        <script>
        jQuery(document).ready(function($) {
            var formState = <?php echo json_encode( $structure ); ?>;
            var postId = $('#cb-app-root').closest('.cb-builder-container, body').find('[data-post-id]').addBack().data('post-id') || <?php echo $post_id; ?>;
            var isNameSynced = false;

            function slugify(text) {
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '_')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '_')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            }

            function getFieldData(id) { return formState.find(f => f.id === id); }

            function updateSyncUI() {
                var btn = $('#cb-sync-btn');
                if(isNameSynced) {
                    btn.addClass('active').html('<i class="fa fa-link"></i> Sync: ON');
                    $('#edit-name').prop('readonly', true).css('opacity', 0.7);
                } else {
                    btn.removeClass('active').html('<i class="fa fa-unlink"></i> Sync: OFF');
                    $('#edit-name').prop('readonly', false).css('opacity', 1);
                }
            }

            // Function to Show/Hide inputs based on Field Type
            function updateEditorUI(type) {
                // Default: Reset all to visible/default state
                $('#edit-placeholder-wrapper').show();
                $('#edit-options-wrapper').hide();
                $('#edit-placeholder-label').text('Placeholder');

                if (type === 'select') {
                    // Select: Needs Options, No placeholder
                    $('#edit-options-wrapper').show();
                    $('#edit-placeholder-wrapper').hide();
                } 
                else if (type === 'checkbox') {
                    // Checkbox: Placeholder acts as "Description"
                    $('#edit-placeholder-label').text('Checkbox Text (e.g. Yes, I agree)');
                    $('#edit-options-wrapper').hide();
                } 
                else if (type === 'image' || type === 'user_select' || type === 'date') {
                    // Image, User Select, Date: No placeholder, No options
                    $('#edit-placeholder-wrapper').hide();
                    $('#edit-options-wrapper').hide();
                }
                // Else (Text, Number, Email, Textarea): Default applies (Show Placeholder, Hide Options)
            }

            // 1. Add Field
            $('.cb-tool-btn').on('click', function(e) {
                e.preventDefault();
                var type = $(this).data('type');
                var role = $(this).data('role') || '';
                var ts = Date.now();
                var baseName = role ? role : type;
                
                var newField = {
                    id: 'f_' + ts,
                    name: 'meta_' + baseName + '_' + ts,
                    type: type,
                    label: 'New ' + (role ? 'Student/Teacher' : type),
                    placeholder: '',
                    required: false,
                    options: '',
                    role_limit: role,
                    value: ''
                };

                $.post(cbParams.ajaxurl, {
                    action: 'cb_get_field_html',
                    nonce: cbParams.nonce,
                    field_data: newField
                }, function(res) {
                    if(res.success) {
                        $('#cb-canvas-area').append(res.data.html);
                        formState.push(newField);
                        $('html, body').animate({ scrollTop: $("#cb-canvas-area").height() }, 500);
                    }
                });
            });

            // 2. Delete Field
            $(document).on('click', '.cb-btn-del', function() {
                var wrapper = $(this).closest('.cb-field-wrapper');
                var id = wrapper.data('id');
                formState = formState.filter(f => f.id !== id);
                wrapper.fadeOut(300, function(){ $(this).remove(); });
                if($('#edit-field-id').val() === id) $('#cb-cancel-edit').click();
            });

            // 3. Edit Field
            $(document).on('click', '.cb-btn-edit', function() {
                var id = $(this).closest('.cb-field-wrapper').data('id');
                var field = getFieldData(id);
                if (!field) return;

                $('#edit-field-id').val(id);
                $('#edit-label').val(field.label);
                $('#edit-name').val(field.name);
                $('#edit-placeholder').val(field.placeholder || '');
                $('#edit-required').prop('checked', field.required);
                $('#edit-options').val(field.options || '');
                
                // Show/Hide inputs based on type
                updateEditorUI(field.type);

                // Determine Sync State
                var currentSlug = slugify(field.label);
                if( field.name.startsWith('meta_') || (field.name === currentSlug && field.name !== '') ) {
                    isNameSynced = true;
                } else {
                    isNameSynced = false;
                }
                updateSyncUI();

                $('#cb-palette').hide();
                $('#cb-editor').fadeIn(200);
            });

            // 4. Toggle Sync Click
            $('#cb-sync-btn').on('click', function() {
                isNameSynced = !isNameSynced;
                if(isNameSynced) {
                    $('#edit-name').val( slugify($('#edit-label').val()) );
                }
                updateSyncUI();
            });

            // 5. Live Typing Sync
            $('#edit-label').on('input', function() {
                if(isNameSynced) {
                    $('#edit-name').val( slugify($(this).val()) );
                }
            });

            // 6. Close Editor
            $('#cb-cancel-edit').on('click', function() {
                $('#cb-editor').hide();
                $('#cb-palette').fadeIn(200);
                $('#edit-field-id').val('');
            });

            // 7. Save Field Change
            $('#cb-save-field').on('click', function() {
                var id = $('#edit-field-id').val();
                var field = getFieldData(id);
                if (field) {
                    field.label = $('#edit-label').val();
                    field.name = $('#edit-name').val();
                    field.placeholder = $('#edit-placeholder').val();
                    field.required = $('#edit-required').is(':checked');
                    field.options = $('#edit-options').val();

                    var $wrapper = $('#wrapper-' + id);
                    $wrapper.css('opacity', 0.5);

                    $.post(cbParams.ajaxurl, {
                        action: 'cb_get_field_html',
                        nonce: cbParams.nonce,
                        field_data: field
                    }, function(res) {
                        if(res.success) {
                            $wrapper.replaceWith(res.data.html);
                            $('#cb-cancel-edit').click();
                        }
                    });
                }
            });

            // 8. Save Contract
            $('#cb-save-contract').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

                var formData = new FormData(document.getElementById('cb-contract-form'));
                formData.append('action', 'cb_save_contract');
                formData.append('nonce', cbParams.nonce);
                formData.append('post_id', postId);
                formData.append('structure', JSON.stringify(formState));

                $.ajax({
                    url: cbParams.ajaxurl, type: 'POST', data: formData,
                    processData: false, contentType: false,
                    success: function(res) {
                        btn.html('<i class="fa fa-save"></i> Save Contract').prop('disabled', false);
                        if(res.success) {
                            postId = res.data.post_id;
                            $('#cb-message').css('color', '#10b981').text('Saved successfully!');
                            setTimeout(function(){ $('#cb-message').text(''); }, 3000);
                        } else {
                            $('#cb-message').css('color', '#ef4444').text('Error.');
                        }
                    },
                    error: function() {
                        btn.html('<i class="fa fa-save"></i> Save Contract').prop('disabled', false);
                    }
                });
            });

        });
        </script>
        <?php
        return ob_get_clean();
    }

    // AJAX: Get HTML
    public function ajax_get_field_html() {
        check_ajax_referer( 'cb_builder_nonce', 'nonce' );
        $data = isset($_POST['field_data']) ? $_POST['field_data'] : [];
        $field = array(
            'id' => sanitize_text_field( $data['id'] ),
            'name' => sanitize_text_field( $data['name'] ),
            'type' => sanitize_text_field( $data['type'] ),
            'label' => sanitize_text_field( $data['label'] ),
            'placeholder' => sanitize_text_field( $data['placeholder'] ),
            'required' => ( $data['required'] === 'true' || $data['required'] === true ),
            'options' => sanitize_textarea_field( $data['options'] ),
            'role_limit' => isset($data['role_limit']) ? sanitize_text_field($data['role_limit']) : '',
            'value' => '' 
        );
        wp_send_json_success( array( 'html' => $this->render_field_view( $field ) ) );
    }

    // AJAX: Save
    public function ajax_save_contract() {
        check_ajax_referer( 'cb_builder_nonce', 'nonce' );
        $post_id = intval( $_POST['post_id'] );
        $structure = json_decode( stripslashes( $_POST['structure'] ), true );
        
        $p_title = isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : 'Contract '.date('Y-m-d');
        $p_content = isset($_POST['post_content']) ? wp_kses_post($_POST['post_content']) : '';

        $args = array('post_title' => $p_title, 'post_content' => $p_content, 'post_type' => 'contract', 'post_status' => 'publish');
        if($post_id > 0) { $args['ID'] = $post_id; wp_update_post($args); }
        else { $post_id = wp_insert_post($args); }

        if(is_wp_error($post_id)) wp_send_json_error('Save failed');

        if(is_array($structure)) {
            foreach($structure as $field) {
                $key = $field['name'];
                if($key === 'post_title' || $key === 'post_content') continue;

                if($field['type'] === 'image' && !empty($_FILES[$key])) {
                    if($_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                        require_once(ABSPATH . 'wp-admin/includes/media.php');
                        $aid = media_handle_upload($key, $post_id);
                        if(!is_wp_error($aid)) {
                            if($key === '_thumbnail_id') set_post_thumbnail($post_id, $aid);
                            else update_post_meta($post_id, $key, $aid);
                        }
                    }
                } elseif(isset($_POST[$key])) {
                    $val = $_POST[$key];
                    if(is_array($val)) $val = array_map('sanitize_text_field', $val);
                    else $val = sanitize_text_field($val);
                    update_post_meta($post_id, $key, $val);
                }
            }
            update_post_meta($post_id, '_contract_form_structure', json_encode($structure));
        }
        wp_send_json_success(array('post_id' => $post_id));
    }
}

// new Contract_Builder();