<?php
/**
 * Plugin Name: WPGraphQL SCF Field
 * Description: Adds a custom field to WPGraphQL to output all custom fields grouped by field group name for posts and pages.
 * Version: 1.0
 * Author: Skybin Technology Private Limited
 * License: GPLv2 or later
 * License URI: GPLv2 or later
 * Requires Plugins: advanced-custom-fields, wp-graphql
 */

if (!defined('ABSPATH')) exit;

add_action('graphql_register_types', 'gsf_register_custom_json_type');
add_action('graphql_register_types', 'gsf_register_scf_field_with_groups');

/**
 * Register a custom JSON type for GraphQL
 */
function gsf_register_custom_json_type() {
    register_graphql_scalar('JSON', [
        'description' => __('The JSON scalar type represents JSON values as structured data, such as objects or arrays', 'graphql-scf-field'),
        'serialize' => function($value) {
            return $value;
        },
        'parseValue' => function($value) {
            return $value;
        },
        'parseLiteral' => function($ast) {
            return $ast->value;
        }
    ]);
}

/**
 * Register the custom SCF field in the GraphQL schema.
 */
function gsf_register_scf_field_with_groups() {
    
    /** For Posts */
    register_graphql_field(
        'Post',  // Apply to the 'Post' GraphQL type
        'scf',   // The custom field name
        [
            'type' => ['list_of' => 'JSON'], // Expect an array of JSON objects
            'description' => __('Grouped custom fields for the post', 'graphql-scf-field'),
            'resolve' => function($post) {
                $custom_fields = get_post_meta($post->ID);
    
                $grouped_fields = [];
                
                foreach ($custom_fields as $key => $value) {
                    if (!is_protected_meta($key, 'post')) {
                        // Get the field group name (adjust `gsf_get_field_group_name` as needed)
                        $group_name = gsf_get_field_group_name($key);

                        if($group_name !== false){
                            // Initialize a group object if it doesn't exist
                            if (!isset($grouped_fields[$group_name])) {
                                $grouped_fields[$group_name] = [];
                            }

                            // Add field data to the group object
                            $grouped_fields[$group_name][$key] = maybe_unserialize($value[0]);
                        }
                        
                    }
                }
    
                // Reformat grouped fields to match the desired structure
                $formatted_fields = [];
                foreach ($grouped_fields as $group_name => $fields) {
                    $formatted_fields[] = array_merge($fields, ["sectionType" => $group_name]);
                }

                return $formatted_fields;
            }
        ]
    );
    
    /** For Pages */
    register_graphql_field(
        'Page',  // Apply to the 'Page' GraphQL type
        'scf',   // The custom field name
        [
            'type' => ['list_of' => 'JSON'], // Expect an array of JSON objects
            'description' => __('Grouped custom fields for the page', 'graphql-scf-field'),
            'resolve' => function($post) {
                $custom_fields = get_post_meta($post->ID);
    
                $grouped_fields = [];
                
                foreach ($custom_fields as $key => $value) {
                    if (!is_protected_meta($key, 'page')) {
                        // Get the field group name (adjust `gsf_get_field_group_name` as needed)
                        $group_name = gsf_get_field_group_name($key);

                        if($group_name !== false){
                            // Initialize a group object if it doesn't exist
                            if (!isset($grouped_fields[$group_name])) {
                                $grouped_fields[$group_name] = [];
                            }

                            // Add field data to the group object
                            $grouped_fields[$group_name][$key] = maybe_unserialize($value[0]);
                        }
                        
                    }
                }
    
                // Reformat grouped fields to match the desired structure
                $formatted_fields = [];
                foreach ($grouped_fields as $group_name => $fields) {
                    $formatted_fields[] = array_merge($fields, ["sectionType" => $group_name]);
                }

                return $formatted_fields;
            }
        ]
    );
    
}

/**
 * Getting Group Name for the key
 */

 function gsf_get_field_group_name($field_key) {
    // Check if ACF is active
    if (function_exists('acf_get_field')) {
        // Get field data by field key
        $field = acf_get_field($field_key);

        // If the field exists, retrieve the field group associated with it
        if ($field) {
            // Get all field groups
            $field_groups = acf_get_field_groups();

            // Loop through field groups to find the one containing this field
            foreach ($field_groups as $group) {

                // Match the field group id to the field parent
                if($group['ID'] === $field['parent']){
                    return $group['title'];
                }
            }
        }
    }

    return false;
}

add_action('admin_init', 'gsf_check_acf_wpgraphql_dependency');

// Hook into admin_init to check if ACF and WPGraphQL plugins are active
add_action('admin_init', 'gsf_check_acf_wpgraphql_dependency');

function gsf_check_acf_wpgraphql_dependency() {
    global $gsf_message; // Declare the global message variable once
    
    // Check if both plugins are inactive
    if (!is_plugin_active('advanced-custom-fields/acf.php') && !is_plugin_active('wp-graphql/wp-graphql.php')) {
        $gsf_message = __('WPGraphQL SCF Field plugin requires both <b>Secure Custom Fields (SCF)</b> and <b>WPGraphQL</b> to be installed and activated. Please install and activate these plugins to use this plugin.', 'graphql-scf-field');
    }
    // Check if only ACF is inactive
    elseif (!is_plugin_active('advanced-custom-fields/acf.php')) {
        $gsf_message = __('WPGraphQL SCF Field plugin requires <b>Secure Custom Fields (SCF)</b> to be installed and activated. Please install and activate the plugin to use this plugin.', 'graphql-scf-field');
    }
    // Check if only WPGraphQL is inactive
    elseif (!is_plugin_active('wp-graphql/wp-graphql.php')) {
        $gsf_message = __('WPGraphQL SCF Field plugin requires <b>WPGraphQL</b> to be installed and activated. Please install and activate the plugin to use this plugin.', 'graphql-scf-field');
    }

    // Show admin notice if any plugin is missing
    if (isset($gsf_message)) {
        add_action('admin_notices', 'gsf_show_dependency_notice');
    }
}

function gsf_show_dependency_notice() {
    global $gsf_message;
    ?>
    <div class="notice notice-error">
        <p><?php echo wp_kses_post($gsf_message); ?></p>
    </div>
    <?php
}
