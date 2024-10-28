<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class PluginClass extends acymClass
{
    private $plugins;

    public function __construct()
    {
        parent::__construct();

        $this->table = 'plugin';
        $this->pkey = 'id';

        global $acymPluginByFolderName;
        if (empty($acymPluginByFolderName)) {
            $acymPluginByFolderName = $this->getAll('folder_name');
        }

        $this->plugins = $acymPluginByFolderName;
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

    public function getOnePluginByFolderName(string $folderName)
    {
        return $this->plugins[$folderName] ?? null;
    }

    public function getNotUptoDatePlugins()
    {
        $result = acym_loadResult('SHOW TABLES LIKE "%_acym_plugin"');
        if (empty($result)) {
            return [];
        }

        return acym_loadResultArray('SELECT folder_name FROM #__acym_plugin WHERE uptodate = 0');
    }

    public function getSettings($addon)
    {
        $settings = acym_loadResult('SELECT settings FROM #__acym_plugin WHERE folder_name = '.acym_escapeDB($addon));

        return empty($settings) ? [] : json_decode($settings, true);
    }

    public function addIntegrationIfMissing($plugin)
    {
        if (empty($plugin->pluginDescription->name)) {
            return;
        }

        $data = $this->plugins[$plugin->name] ?? null;

        $newPlugin = new \stdClass();
        $newPlugin->title = $plugin->pluginDescription->name;
        $newPlugin->folder_name = $plugin->name;
        $newPlugin->version = '1.0';
        $newPlugin->active = 1;
        $newPlugin->category = $plugin->pluginDescription->category;
        $newPlugin->level = 'starter';
        $newPlugin->uptodate = 1;
        $newPlugin->description = $plugin->pluginDescription->description;
        $newPlugin->latest_version = '1.0';
        $newPlugin->type = 'PLUGIN';

        if (!empty($data)) {
            $newPlugin->id = $data->id;
            $newPlugin->settings = $data->settings;

            if ($data->type !== 'ADDON') {
                return;
            }


            if (file_exists(ACYM_ADDONS_FOLDER_PATH.$plugin->name)) {
                acym_deleteFolder(ACYM_ADDONS_FOLDER_PATH.$plugin->name);
            }
        }

        $this->save($newPlugin);
    }

    public function enable($folderName)
    {
        $plugin = $this->getOnePluginByFolderName($folderName);
        if (empty($plugin)) return;

        $plugin->active = 1;
        $this->save($plugin);
    }

    public function disable($folderName)
    {
        $plugin = $this->getOnePluginByFolderName($folderName);
        if (empty($plugin)) return;

        $plugin->active = 0;
        $this->save($plugin);
    }

    public function deleteByFolderName($folderName)
    {
        $plugin = $this->getOnePluginByFolderName($folderName);
        if (empty($plugin)) return;

        parent::delete($plugin->id);
    }

    public function updateAddon(string $addon)
    {
        $plugin = $this->getOnePluginByFolderName($addon);

        if (empty($plugin)) {
            return false;
        }

        $pluginClass = new PluginClass();
        $pluginClass->downloadAddon($addon);

        $pluginToSave = new \stdClass();
        $pluginToSave->id = $plugin->id;
        $pluginToSave->version = $plugin->latest_version;
        $pluginToSave->uptodate = 1;

        return $this->save($pluginToSave);
    }

    public function downloadAddon(string $name, bool $ajax = true)
    {

        return true;
    }

    private function handleError($error, $ajax)
    {
        if ($ajax) {
            acym_sendAjaxResponse(acym_translation($error), [], false);
        } else {
            return acym_translation($error);
        }
    }
}
