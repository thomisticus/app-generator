<?php

use Illuminate\Support\Str;

if (!function_exists('generate_tab')) {
    /**
     * Generates tab with spaces.
     *
     * @param int $spaces
     * @return string
     */
    function generate_tab($spaces = 4)
    {
        return str_repeat(' ', $spaces);
    }
}

if (!function_exists('generate_tabs')) {
    /**
     * Generates tab with spaces.
     *
     * @param int $tabs
     * @param int $spaces
     * @return string
     */
    function generate_tabs($tabs, $spaces = 4)
    {
        return str_repeat(generate_tab($spaces), $tabs);
    }
}

if (!function_exists('generate_new_line')) {
    /**
     * Generates new line char.
     *
     * @param int $count
     *
     * @return string
     */
    function generate_new_line($count = 1)
    {
        return str_repeat(PHP_EOL, $count);
    }
}

if (!function_exists('generate_new_lines')) {
    /**
     * Generates new line char.
     *
     * @param int $count
     * @param int $nls
     * @return string
     */
    function generate_new_lines($count, $nls = 1)
    {
        return str_repeat(generate_new_line($nls), $count);
    }
}

if (!function_exists('generate_new_line_tab')) {
    /**
     * Generates new line char.
     *
     * @param int $lns
     * @param int $tabs
     * @return string
     */
    function generate_new_line_tab($lns = 1, $tabs = 1)
    {
        return generate_new_lines($lns) . generate_tabs($tabs);
    }
}

if (!function_exists('get_template_file_path')) {
    /**
     * Get path for template file.
     *
     * @param string $templateName
     * @param string $templateType
     * @return string
     */
    function get_template_file_path($templateName, $templateType)
    {
        $templateName = str_replace('.', '/', $templateName);

        $templatesPath = config(
            'app-generator.path.templates_dir',
            base_path('resources/thomisticus/app-generator-templates/')
        );

        $path = $templatesPath . $templateName . '.stub';

        if (file_exists($path)) {
            return $path;
        }

        return base_path('vendor/thomisticus/' . $templateType . '/templates/' . $templateName . '.stub');
    }
}

if (!function_exists('get_template')) {
    /**
     * Get template contents.
     *
     * @param string $templateName
     * @param string $templateType
     * @return string
     */
    function get_template($templateName, $templateType)
    {
        $path = get_template_file_path($templateName, $templateType);

        return file_get_contents($path);
    }
}

if (!function_exists('fill_template')) {
    /**
     * Fill template with variable values.
     *
     * @param array $variables
     * @param string $template
     * @return string
     */
    function fill_template($variables, $template)
    {
        foreach ($variables as $variable => $value) {
            $template = str_replace($variable, $value, $template);
        }

        return $template;
    }
}

if (!function_exists('fill_field_template')) {
    /**
     * Fill field template with variable values.
     *
     * @param array $variables
     * @param string $template
     * @param \Thomisticus\Generator\Utils\Database\Field $field
     * @return string
     */
    function fill_field_template($variables, $template, $field)
    {
        foreach ($variables as $variable => $key) {
            $template = str_replace($variable, $field->$key, $template);
        }

        return $template;
    }
}

if (!function_exists('fill_template_with_field_data')) {
    /**
     * Fill template with field data.
     *
     * @param array $variables
     * @param array $fieldVariables
     * @param string $template
     * @param \Thomisticus\Generator\Utils\Database\Field $field
     * @return string
     */
    function fill_template_with_field_data($variables, $fieldVariables, $template, $field)
    {
        $template = fill_template($variables, $template);

        return fill_field_template($fieldVariables, $template, $field);
    }
}

if (!function_exists('fill_template_with_field_data')) {
    /**
     * Fill template with field data.
     *
     * @param array $variables
     * @param array $fieldVariables
     * @param string $template
     * @param \Thomisticus\Generator\Utils\Database\Field $field
     * @return string
     */
    function fill_template_with_field_data($variables, $fieldVariables, $template, $field)
    {
        $template = fill_template($variables, $template);

        return fill_field_template($fieldVariables, $template, $field);
    }
}

if (!function_exists('model_name_from_table_name')) {
    /**
     * Generates model name from table name.
     *
     * @param string $tableName
     * @return string
     */
    function model_name_from_table_name($tableName)
    {
//      $inflector = Inflector::get('pt');
//
//      $tableName         = $inflector->singularize(strtolower($tableName));
//      $prefixesToReplace = ['tb_', 'td_', 'ta_'];
//      $tableNamePrefix   = substr($tableName, 0, 3);
//
//      if (in_array($tableNamePrefix, $prefixesToReplace)) {
//          $tableName = substr($tableName, 3);
//      }
//
//      return ucfirst(camel_case($tableName));
        return Str::ucfirst(Str::camel(Str::singular($tableName)));
    }

}

if (!function_exists('remove_duplicated_empty_lines')) {
    /**
     * Removes duplicated empty lines by a single empty line
     * @param string $templateDate
     * @return string|string[]|null
     */
    function remove_duplicated_empty_lines($templateDate)
    {
        return preg_replace("/\n\n+/s", "\n\n", $templateDate);
    }

}
