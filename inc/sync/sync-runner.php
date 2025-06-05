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
     * Run complete synchronization in proper order
     * 
     * @return array Synchronization results
     */
    public function runCompleteSync()
    {
        $this->logger->log('Starting complete Altegio synchronization', AltegioLogger::INFO);

        // IMPORTANT: Sync in this order to ensure proper relationships
        // 1. Categories first (services need categories to exist)
        $this->logger->log('Step 1: Syncing categories', AltegioLogger::INFO);
        $categories_sync = new AltegioSyncCategories($this->api_client, $this->logger);
        $categories_result = $categories_sync->sync();

        // 2. Services second (masters need services to exist for relationships)  
        $this->logger->log('Step 2: Syncing services', AltegioLogger::INFO);
        $services_sync = new AltegioSyncServices($this->api_client, $this->logger);
        $services_result = $services_sync->sync();

        // 3. Masters last (they reference services)
        $this->logger->log('Step 3: Syncing masters', AltegioLogger::INFO);
        $masters_sync = new AltegioSyncMasters($this->api_client, $this->logger);
        $masters_result = $masters_sync->sync();

        $total_results = [
            'categories' => $categories_result,
            'services' => $services_result,
            'masters' => $masters_result
        ];

        $this->logger->log('Complete Altegio synchronization finished', AltegioLogger::INFO, $total_results);

        return $total_results;
    }

    /**
     * Run categories synchronization only
     * 
     * @return array Synchronization results
     */
    public function syncCategories()
    {
        $this->logger->log('Starting categories-only synchronization', AltegioLogger::INFO);
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
        $this->logger->log('Starting services-only synchronization', AltegioLogger::INFO);
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
        $this->logger->log('Starting masters-only synchronization', AltegioLogger::INFO);
        $masters_sync = new AltegioSyncMasters($this->api_client, $this->logger);
        return $masters_sync->sync();
    }

    /**
     * Run synchronization with proper dependencies
     * This ensures categories exist before services, and services exist before masters
     * 
     * @param array $sync_types Types to sync ['categories', 'services', 'masters']
     * @return array Synchronization results
     */
    public function syncWithDependencies($sync_types = ['categories', 'services', 'masters'])
    {
        $results = [];

        // Always sync in dependency order
        $ordered_types = ['categories', 'services', 'masters'];

        foreach ($ordered_types as $type) {
            if (in_array($type, $sync_types)) {
                switch ($type) {
                    case 'categories':
                        $results['categories'] = $this->syncCategories();
                        break;
                    case 'services':
                        $results['services'] = $this->syncServices();
                        break;
                    case 'masters':
                        $results['masters'] = $this->syncMasters();
                        break;
                }
            }
        }

        return $results;
    }

    /**
     * Schedule cron job for automatic synchronization
     */
    public function scheduleCronSync()
    {
        if (!wp_next_scheduled('altegio_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'altegio_daily_sync');
            $this->logger->log('Scheduled daily Altegio sync cron job', AltegioLogger::INFO);
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
            $this->logger->log('Unscheduled daily Altegio sync cron job', AltegioLogger::INFO);
        }
    }

    /**
     * Get sync statistics summary
     * 
     * @return array Current data counts
     */
    public function getStats()
    {
        $categories_count = wp_count_terms('service_category', ['hide_empty' => false]);
        $services_count = wp_count_posts('service');
        $masters_count = wp_count_posts('master');

        return [
            'categories' => is_wp_error($categories_count) ? 0 : $categories_count,
            'services' => isset($services_count->publish) ? $services_count->publish : 0,
            'masters' => isset($masters_count->publish) ? $masters_count->publish : 0,
        ];
    }

    /**
     * Validate API connection
     * 
     * @return bool Whether API is accessible
     */
    public function validateApiConnection()
    {
        try {
            if (!class_exists('AltegioClient')) {
                return false;
            }

            // Try to fetch a small amount of data to test connection
            $categories = $this->api_client::getServiceCategories();

            return isset($categories['success']) && $categories['success'];
        } catch (Exception $e) {
            $this->logger->log('API connection validation failed', AltegioLogger::ERROR, [
                'error' => $e->getMessage()
            ]);
            return false;
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

/**
 * Helper function to sync with dependencies
 * 
 * @param array $sync_types Types to sync
 * @return array Synchronization results
 */
function sync_altegio_with_dependencies($sync_types = ['categories', 'services', 'masters'])
{
    $runner = new AltegioSyncRunner();
    return $runner->syncWithDependencies($sync_types);
}

// Add hook for cron job
add_action('altegio_daily_sync', 'run_complete_altegio_sync');

// Add hook for forced sync
add_action('force_altegio_daily_sync', 'run_complete_altegio_sync');
