<?php
add_filter('cron_schedules', function ($schedules) {
    $schedules['daily_at_sgt_midnight'] = [
        'interval' => 86400,
        'display'  => __('Once Daily at 00:00 SGT')
    ];
    return $schedules;
});

function schedule_force_sync_sgt_midnight()
{
    $timestamp = strtotime('today 16:00 UTC');
    if (!wp_next_scheduled('force_altegio_daily_sync')) {
        wp_schedule_event($timestamp, 'daily_at_sgt_midnight', 'force_altegio_daily_sync');
    }
}
add_action('init', 'schedule_force_sync_sgt_midnight');
