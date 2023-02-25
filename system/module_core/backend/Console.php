<?php

##################################################################
### Copyright © 2017—2023 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

use stdClass;

abstract class Console {

    const DIRECTORY = DIR_DYNAMIC.'logs/';
    const IS_VISIBLE_FOR_NOBODY   = 0b00;
    const IS_VISIBLE_FOR_ADMIN    = 0b01;
    const IS_VISIBLE_FOR_EVERYONE = 0b10;

    static $is_ignore_duplicates = false;
    static $duplicates = [];
    static protected $data = [];
    static protected $file_log_err = null;
    static protected $file_log_wrn = null;
    static protected $is_write_to_file_log_wrn = false;
    static protected $is_init = false;
    static protected $visible_mode = self::IS_VISIBLE_FOR_NOBODY;

    static function init($reset = false) {
        if (!static::$is_init || $reset) {
            static::$is_init = true;
            static::$data[] = (object)['object' => 'file', 'action' => 'insertion', 'description' => 'system/boot.php',                                      'value' => 'ok', 'time' => 0, 'ram_dynamics' => memory_get_usage(true), 'args' => [], 'info' => []];
            static::$data[] = (object)['object' => 'file', 'action' => 'insertion', 'description' => 'system/module_core/backend/Core.php',                  'value' => 'ok', 'time' => 0, 'ram_dynamics' => memory_get_usage(true), 'args' => [], 'info' => []];
            static::$data[] = (object)['object' => 'file', 'action' => 'insertion', 'description' => 'system/module_storage/backend/interfaces/markers.php', 'value' => 'ok', 'time' => 0, 'ram_dynamics' => memory_get_usage(true), 'args' => [], 'info' => []];
            static::$file_log_err = new File(static::DIRECTORY.Core::date_get().'/'.  'error--'.Core::date_get().'.log');
            static::$file_log_wrn = new File(static::DIRECTORY.Core::date_get().'/'.'warning--'.Core::date_get().'.log');
            static::$visible_mode = static::IS_VISIBLE_FOR_NOBODY;
            if (Module::is_enabled('develop')) {
                $settings = Module::settings_get('page');
                if ($settings->console_visibility === static::IS_VISIBLE_FOR_EVERYONE                                                           ) static::$visible_mode = static::IS_VISIBLE_FOR_EVERYONE;
                if ($settings->console_visibility === static::IS_VISIBLE_FOR_ADMIN && Access::check((object)['roles' => ['admins' => 'admins']])) static::$visible_mode = static::IS_VISIBLE_FOR_ADMIN;
            }
        }
    }

    static function visible_mode_get() {
        static::init();
        return static::$visible_mode;
    }

    static function logs_select() {
        static::init();
        return static::$data;
    }

    static function &log_insert($object, $action, $description = null, $value = '', $time = 0, $args = [], $info = []) {
        static::init();
        $new_log = new stdClass;
        $new_log->object       = $object;
        $new_log->action       = $action;
        $new_log->description  = $description;
        $new_log->value        = $value;
        $new_log->time         = $time;
        $new_log->args         = $args;
        $new_log->info         = $info;
        $new_log->ram_dynamics = memory_get_usage(true);
        if (static::visible_mode_get()) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            if ($stack[0]['function'] === 'log_insert'            ) array_shift($stack);
            if ($stack[0]['function'] === 'report_about_duplicate') array_shift($stack);
            $new_log->info['stack'] = static::render_stack($stack);
        }
        static::$data[] = $new_log;

        # store errors and warnings to the file
        if ($value === 'error' || ($value === 'warning' && static::$is_write_to_file_log_wrn === true)) {
            $c_info = $new_log->description;
            if ($value === 'error') $c_file = static::$file_log_err;
            if ($value !== 'error') $c_file = static::$file_log_wrn;
            foreach ($new_log->args as $c_key => $c_value) $c_info = str_replace('%%_'.$c_key, $c_value, $c_info);
            $c_line = Core::time_get().' | uid: '.(User::get_current()->id ?: 0).
                                       ' | '.$new_log->object.
                                       ' | '.$new_log->action.
                                       ' | '.str_replace(BR, ' | ', $c_info).NL;
            if (!$c_file->append_direct($c_line)) {
                Message::insert(new Text_multiline([
                    'File "%%_file" was not written to disc!',
                    'File permissions (if the file exists) and directory permissions should be checked.'], [
                    'file' => $c_file->path_get_relative()]), 'error'
                );
            }
        }
        return $new_log;
    }

    static function report_about_duplicate($type, $id, $module_id, $firstinit = null) {
        if ($firstinit !== null && !empty($firstinit->module_id))
            static::$duplicates[$type][$id][$firstinit->module_id] = $firstinit->module_id;
            static::$duplicates[$type][$id][           $module_id] =            $module_id;
        # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦
        if (static::$is_ignore_duplicates === false) {
            Message::insert(new Text(                    'Duplicate of type "%%_type" with ID = "%%_id" was found in module with ID = "%%_module_id"!',            ['type' => $type, 'id' => $id, 'module_id' => $module_id]), 'error');
            return static::log_insert('storage', 'load', 'duplicate of type "%%_type" with ID = "%%_id" was found in module with ID = "%%_module_id"', 'error', 0, ['type' => $type, 'id' => $id, 'module_id' => $module_id]);
        }
    }

    # ─────────────────────────────────────────────────────────────────────
    # console output as markup
    # ─────────────────────────────────────────────────────────────────────

    static function markup_get() {
        return new Markup('x-console', [], [
            static::block_markup__information (),
            static::block_markup__diagram_load(),
            static::block_markup__logs        ()
        ]);
    }

    static function block_markup__information() {
        $user = User::get_current();
        $user_roles = $user->roles;
        $user_permissions = Role::related_permissions_by_roles_select($user_roles);
        $decorator = new Decorator('table-dl');
        $decorator->id = 'page_information';
        $decorator->data = [[
            'gen_time'    => ['title' => 'Total generation time', 'value' => Locale::format_msecond(Timer::period_get('total', 0, 1))  ],
            'memory'      => ['title' => 'Memory for PHP',        'value' => Locale::format_bytes(memory_get_usage(true))              ],
            'language'    => ['title' => 'Current language',      'value' => Language::code_get_current()                              ],
            'roles'       => ['title' => 'User roles',            'value' => $user_roles       ? implode(', ', $user_roles      ) : '—'],
            'permissions' => ['title' => 'User permissions',      'value' => $user_permissions ? implode(', ', $user_permissions) : '—'] ]];
        return new Block('Current page information', ['data-id' => 'block__info', 'data-style' => 'title-is-simple'], [$decorator]);
    }

    static function block_markup__diagram_load() {
        $statistics = [];
        $total = 0;
        foreach (static::$data as $c_log) {
            if (floatval($c_log->time)) {
                if (!isset($statistics[$c_log->object]))
                           $statistics[$c_log->object] = 0;
                $statistics[$c_log->object] += floatval($c_log->time);
                $total += floatval($c_log->time); }}
        $diagram = new Diagram(null, 'linear');
        foreach ($statistics as    $c_key => $c_value)
            $diagram->slice_insert($c_key,   $c_value / $total * 100, (new Text('%%_number sec.', ['number' => Locale::format_msecond($c_value)]))->render(), 'black', ['data-id' => $c_key]);
        return new Block('CPU load time', ['data-id' => 'block__diagram_load'], [$diagram]);
    }

    static function block_markup__logs() {
        $total_sequence_hash = '';
        $total_data_hash     = '';
        $total_by_actions    = [];
        $logs = static::logs_select();
        $decorator = new Decorator('table');
        $decorator->id = 'logs';
        $decorator->result_attributes = ['data-style' => 'compact'];
        foreach (static::logs_select() as $c_row_id => $c_log) {
            $c_sequence_hash      = Core::hash_get(['time' => 0, 'args' => []] + (array)$c_log);
            $c_data_hash          = Core::hash_get(['time' => 0              ] + (array)$c_log);
            $total_sequence_hash  = Core::hash_get($total_sequence_hash.$c_sequence_hash);
            $total_data_hash      = Core::hash_get($total_data_hash    .$c_data_hash    );
            if (isset($total_by_actions[$c_log->object][$c_log->action]))
                      $total_by_actions[$c_log->object][$c_log->action]++;
            else      $total_by_actions[$c_log->object][$c_log->action] = 1;
            $c_row_attributes  = ['data-hash-sequence' => Core::hash_get_mini($c_sequence_hash)];
            $c_row_attributes += ['data-hash-data'     => Core::hash_get_mini($c_data_hash    )];
            $c_row_attributes += ['data-object'        => Core::sanitize_id($c_log->object ? trim($c_log->object, '.') : '')];
            $c_row_attributes += ['data-action'        => Core::sanitize_id($c_log->action ? trim($c_log->action, '.') : '')];
            $c_row_attributes += ['data-value'         => Core::sanitize_id($c_log->value  ? trim($c_log->value,  '.') : '')];
            $c_info = !empty($c_log->info) ? static::render_info_markup($c_log->info) : '';
            if ($c_log->time  >= .000099) $c_row_attributes['data-loading-level'] = 1;
            if ($c_log->time  >=  .00099) $c_row_attributes['data-loading-level'] = 2;
            if ($c_log->time  >=   .0099) $c_row_attributes['data-loading-level'] = 3;
            if ($c_log->time  >=    .099) $c_row_attributes['data-loading-level'] = 4;
            if ($c_log->time  >=     .99) $c_row_attributes['data-loading-level'] = 5;
            $decorator->data[] = [
                'attributes'   => $c_row_attributes,
                'time'         => ['title' => 'Time',              'value' => Locale::format_msecond($c_log->time)                                   ],
                'ram_dynamics' => ['title' => 'RAM load dynamics', 'value' => Locale::format_bytes  ($c_log->ram_dynamics)                           ],
                'object'       => ['title' => 'Object',            'value' =>    new Text           ($c_log->object)                                 ],
                'action'       => ['title' => 'Action',            'value' =>    new Text           ($c_log->action)                                 ],
                'description'  => ['title' => 'Description',       'value' =>    new Text_multiline([$c_log->description, $c_info], $c_log->args, '')],
                'value'        => ['title' => 'Val.',              'value' =>    new Text           ($c_log->value)                                  ]
            ];
        }
        $markup_total = new Markup('x-total', [], [
            new Markup('x-param', ['data-id' => 'shash'], [new Markup('x-title', [], 'Sequence hash'), new Markup('x-value', [], $total_sequence_hash)]),
            new Markup('x-param', ['data-id' => 'dhash'], [new Markup('x-title', [], 'Data hash'    ), new Markup('x-value', [], $total_data_hash    )]),
            new Markup('x-param', ['data-id' => 'count'], [new Markup('x-title', [], 'Total'        ), new Markup('x-value', [], count($logs)        )]),
        ]);
        foreach ($total_by_actions as $c_object_name => $c_object_total) {
            foreach ($c_object_total as $c_action_name => $c_total) {
                $markup_total->child_insert(
                    new Markup('x-param', ['data-id' => $c_object_name.'-'.$c_action_name], [
                        new Markup('x-title', [], new Text_multiline(['— ', $c_object_name, ' | ', $c_action_name], [], '')),
                        new Markup('x-value', [], $c_total)
                    ])
                );
            }
        }
        return new Block('Execution plan', ['data-id' => 'block__logs', 'data-style' => 'title-is-simple'], [
            $decorator, $markup_total
        ]);
    }

    static function render_info_opener() {
        return (new Markup_simple('input', [
            'type'             => 'checkbox',
            'role'             => 'button',
            'data-opener-type' => 'info',
            'title'            => new Text('press to show more information')
        ]))->render();
    }

    static function render_info_markup($data) {
        $info = new Markup('x-info');
        foreach ($data as $c_title => $c_value)
            $info->child_insert(
                new Markup('x-param', [], [
                new Markup('x-title', [], $c_title),
                new Markup('x-value', [], $c_value)]), $c_title);
        return static::render_info_opener().$info->render();
    }

    static function render_stack($data) {
        $result = [];
        foreach ($data as $c_info)
            $result[] = ($c_info['class'   ] ?? '').
                        ($c_info['type'    ] ?? '').
                         $c_info['function'];
        return implode(' → ', array_reverse($result));
    }

    # ─────────────────────────────────────────────────────────────────────
    # console output as text (for *.jsd | *.cssd)
    # ─────────────────────────────────────────────────────────────────────

    static function text_get() {
        return static::block_text__information ().
               static::block_text__diagram_load().
               static::block_text__logs        ();
    }

    static function block_text__information() {
        $information = [];
        $information['Total generation time'] = Core::format_msecond(Timer::period_get('total', 0, 1));
        $information['Memory for PHP'] = Core::format_bytes(memory_get_usage(true));
        $result = '  CURRENT PAGE INFORMATION'.NL.NL;
        foreach ($information as $c_key => $c_value) {
            $result.= '  '.str_pad($c_key, 38, ' ', STR_PAD_LEFT).' : ';
            $result.=      $c_value.NL; }
        return NL.$result.NL;
    }

    static function block_text__diagram_load() {
        $statistics = [];
        $total = 0;
        foreach (static::$data as $c_log) {
            if (floatval($c_log->time)) {
                if (!isset($statistics[$c_log->object]))
                           $statistics[$c_log->object] = 0;
                $statistics[$c_log->object] += floatval($c_log->time);
                $total += floatval($c_log->time); }}
        $result = '  CPU LOAD TIME'.NL.NL;
        foreach ($statistics as $c_key => $c_value) {
            $c_percent = $c_value / $total * 100;
            $result.= '  '.str_pad($c_key, 15, ' ', STR_PAD_LEFT)                           .  ' | ';
            $result.=      str_pad(str_repeat('#', (int)($c_percent / 10)), 10, '-')        .  ' | ';
            $result.=      str_pad(Core::format_number($c_percent, 2), 5, ' ', STR_PAD_LEFT).' % | ';
            $result.=      Core::format_msecond($c_value).' sec.'.NL; }
        return NL.$result.NL;
    }

    static function block_text__logs() {
        $total_sequence_hash = '';
        $total_data_hash     = '';
        $total_by_actions    = [];
        $logs = static::logs_select();
        $result = '  EXECUTION PLAN'.NL.NL;
        $result.= '  ------------------------------------------------------------'.NL;
        $result.= '  Time     | Object     | Action     | Value | Description    '.NL;
        $result.= '  ------------------------------------------------------------'.NL;
        foreach (static::logs_select() as $c_log) {
            $c_sequence_hash      = Core::hash_get(['time' => 0, 'args' => []] + (array)$c_log);
            $c_data_hash          = Core::hash_get(['time' => 0              ] + (array)$c_log);
            $total_sequence_hash  = Core::hash_get($total_sequence_hash.$c_sequence_hash);
            $total_data_hash      = Core::hash_get($total_data_hash    .$c_data_hash    );
            if (isset($total_by_actions[$c_log->object][$c_log->action]))
                      $total_by_actions[$c_log->object][$c_log->action]++;
            else      $total_by_actions[$c_log->object][$c_log->action] = 1;
            $result.= '  '.str_pad(Core::format_msecond($c_log->time), 8).' | ';
            $result.=      str_pad($c_log->object ?? '', 10)             .' | ';
            $result.=      str_pad($c_log->action ?? '', 10)             .' | ';
            $result.=      str_pad($c_log->value  ?? '',  5)             .' | ';
            if (!empty($c_log->info))
                 $result.= (new Text($c_log->description.'   …   '.static::render_info_text($c_log->info), $c_log->args, false))->render().NL;
            else $result.= (new Text($c_log->description,                                                  $c_log->args, false))->render().NL;
        }
        $result.= '  ------------------------------------------------------------'.NL;
        $result.= NL.'  '.str_pad('Sequence hash: ', 25).$total_sequence_hash;
        $result.= NL.'  '.str_pad('Data hash: ',     25).$total_data_hash;
        $result.= NL.'  '.str_pad('Total: ',         25).count($logs);
        foreach ($total_by_actions as $c_object_name => $c_objstatistic) {
            foreach ($c_objstatistic as $c_action_name => $c_total) {
                $result.= NL.'  '.str_pad('- '.$c_object_name.' | '.$c_action_name.': ', 25).$c_total;
            }
        }
        return NL.$result.NL;
    }

    static function render_info_text($data) {
        $result = new Node;
        foreach ($data as $c_title => $c_value)
            $result->child_insert(new Text($c_title.': '.$c_value.'; '), $c_title);
        return $result->render();
    }

}
