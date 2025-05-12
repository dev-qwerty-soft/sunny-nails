<?php

/**
 * Main synchronization runner for Altegio API
 *
 * @package AltegioSync
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__) . '/categories.php';
require_once dirname(__FILE__) . '/services.php';
require_once dirname(__FILE__) . '/masters.php';

/**
 * Class for running complete Altegio synchronization
 */
class AltegioSyncRunner
{
    /**
     * @var AltegioLogger
     */
    protected $logger;

    /**
     * @var AltegioClient
     */
    protected $api_client;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = new AltegioLogger();

        if (!class_exists('AltegioClient')) {
            require_once dirname(dirname(__DIR__)) . '/api/altegio-client.php';
        }

        $this->api_client = new AltegioClient();
    }

    /**
     * Run complete synchronization
     * 
     * @return array Synchronization results
     */
    public function runCompleteSync()
    {
        $categories_sync = new AltegioSyncCategories($this->api_client, $this->logger);
        $services_sync = new AltegioSyncServices($this->api_client, $this->logger);
        $masters_sync = new AltegioSyncMasters($this->api_client, $this->logger);

        $categories_result = $categories_sync->sync();
        $services_result = $services_sync->sync();
        $masters_result = $masters_sync->sync();

        return [
            'categories' => $categories_result,
            'services' => $services_result,
            'masters' => $masters_result
        ];
    }

    /**
     * Run categories synchronization only
     * 
     * @return array Synchronization results
     */
    public function syncCategories()
    {
        $categories_sync = new AltegioSyncCategories($this->api_client, $this->logger);
        return $categories_sync->sync();
    }

    /**
     * Run services synchronization only
     * 
     * @return array Synchronization results
     */
    public function syncServices()
    {
        $services_sync = new AltegioSyncServices($this->api_client, $this->logger);
        return $services_sync->sync();
    }

    /**
     * Run masters synchronization only
     * 
     * @return array Synchronization results
     */
    public function syncMasters()
    {
        $masters_sync = new AltegioSyncMasters($this->api_client, $this->logger);
        return $masters_sync->sync();
    }

    /**
     * Schedule cron job for automatic synchronization
     */
    public function scheduleCronSync()
    {
        if (!wp_next_scheduled('altegio_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'altegio_daily_sync');
        }
    }

    /**
     * Unschedule cron job
     */
    public function unscheduleCronSync()
    {
        $timestamp = wp_next_scheduled('altegio_daily_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'altegio_daily_sync');
        }
    }
}

/**
 * Helper function to run complete synchronization
 * 
 * @return array Synchronization results
 */
function run_complete_altegio_sync()
{
    $runner = new AltegioSyncRunner();
    return $runner->runCompleteSync();
}

/**
 * Helper function to sync categories only
 * 
 * @return array Synchronization results
 */
function sync_altegio_categories()
{
    $runner = new AltegioSyncRunner();
    return $runner->syncCategories();
}

/**
 * Helper function to sync services only
 * 
 * @return array Synchronization results
 */
function sync_altegio_services()
{
    $runner = new AltegioSyncRunner();
    return $runner->syncServices();
}

/**
 * Helper function to sync masters only
 * 
 * @return array Synchronization results
 */
function sync_altegio_masters()
{
    $runner = new AltegioSyncRunner();
    return $runner->syncMasters();
}

// Add hook for cron job
add_action('altegio_daily_sync', 'run_complete_altegio_sync');
