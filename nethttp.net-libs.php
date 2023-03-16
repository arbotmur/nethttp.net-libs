<?php

/**
 * Plugin Name: nethttp.net-libs
 * Description: To handle css and js libs. Usefull for dev. And to try my first wp plugin! bootstrap and jquery-ui are included by default.
 * Version: 0.1.0
 * Author: arbotmur
 * Tag: JS, CSS, included libs, bootstrap, jquery-ui
 */

namespace nethttp;

defined('ABSPATH') || exit;

class Libs
{
    private const PLUGIN_VERSION = '0.0.1';
    private const PLUGIN_NAME = 'nethttp.net-libs';

    /**
     * The absolute path to the directory containing this plugin file.
     */
    private const PLUGIN_PATH = __DIR__ . '/';

    /**
     * The name of the option that stores the selected net-libs.
     */
    private const OPTION = 'nethttp.net-libs';

    /**
     * The default options for this plugin.
     *
     * @var array
     * An array of arrays, each containing the following keys:
     *   - name (string): The name of the library.
     *   - website (string): The website URL of the library.
     *   - version (string): The version number of the library.
     *   - url (string): The URL pattern to use for downloading the library. Uses %1$s as a placeholder for the version number.
     *   - active (bool): Whether this library is active by default.
     *   - dependencies (string): A comma-separated list of library names that this library depends on.
     *   - files (string): A newline-separated list of file paths within the library archive to load.
     */
    private $default_option = [
        [
            'name' => 'bootstrap',
            'website' => 'https://getbootstrap.com/',
            'version' => '5.2.3',
            'url' => 'https://github.com/twbs/bootstrap/releases/download/v%1$s/bootstrap-%1$-dist.zip',
            'active' => true,
            'dependencies' => '',
            'files' => 'bootstrap-%1$s-dist/css/bootstrap.min.css' . "\n" . 'bootstrap-%1$s-dist/js/bootstrap.min.js',
            'front' => true,
            'admin' => true,
            'condition' => '',
            'adminCondition' => 'isset($_GET[\'page\']) && (stristr($_GET[\'page\'],\'label-des-singes\')!=-1 || $_GET[\'page\']==\'nethttp.net-libs\' )'
        ],
        [
            'name' => 'jquery-ui',
            'website' => 'https://jqueryui.com/',
            'version' => '1.13.2',
            'url' => 'https://github.com/jquery/jquery-ui/archive/refs/tags/%1$s.zip',
            'active' => true,
            'dependencies' => 'jquery',
            'files' => 'jquery-ui-%1$s/dist/jquery-ui.min.js' . "\n" . 'jquery-ui-%1$s/dist/themes/base/jquery-ui.min.css' . "\n" . 'jquery-ui-%1$s/dist/themes/base/theme.css',
            'front' => false,
            'admin' => true,
            'condition' => '',
            'adminCondition' => 'isset($_GET[\'page\']) && stristr($_GET[\'page\'],\'label-des-singes\')!=-1'
        ]
    ];

    public function __construct()
    {
        //Fired on plugin activation
        register_activation_hook(__FILE__, [$this, 'install']);

        //Add a menu link in admin
        add_action('admin_menu', [$this, 'admin_Link']);

        //Include libs if exists
        if (is_file(__DIR__ . '/scripts.php')) {
            include __DIR__ . '/scripts.php';
        }
    }

    public function selfLibs()
    {
        wp_enqueue_style(self::PLUGIN_NAME . '-css', plugin_dir_url(__FILE__) . '/style.css', [], WP_DEBUG ? time() : self::PLUGIN_VERSION);
        wp_enqueue_script(self::PLUGIN_NAME . '-js', plugin_dir_url(__FILE__) . '/script.js', ['jquery'], WP_DEBUG ? time() : self::PLUGIN_VERSION, true);
    }

    /**
     * Installs the plugin and adds the default option if it does not already exist.
     * @return void
     */
    public function install(): void
    {
        if (!get_option(self::OPTION)) {
            add_option(self::OPTION, $this->default_option);
        }
    }

    /**
     * Extracts files from a zip archive to the specified target directory.
     * @param string $archive The path to the zip archive.
     * @param string $target The path to the target directory.
     * @return bool Returns true if the archive was successfully extracted, false otherwise.
     */
    private function unzip($archive, $target)
    {
        $zip = new \ZipArchive();
        if ($zip->open($archive) === true) {
            $zip->extractTo($target);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Adds a menu link and page to WordPress admin panel for managing libraries.
     */
    public function admin_Link()
    {
        //Include self libs
        if (isset($_GET['page']) && $_GET['page'] == 'nethttp.net-libs') {
            add_action('admin_enqueue_scripts', [$this, 'selfLibs']);
        }

        add_menu_page(
            'nethttp.net libs', // Title of the page
            'Libs', // Text to show on the menu link
            'administrator', // Capability requirement to see the link
            'nethttp.net-libs', // The 'slug' - file to display when clicking the link,
            [$this, 'config'], // Callback function to generate page content
            'dashicons-embed-generic' // Icon to display next to the menu item
        );
    }

    /**
     * Generates the configuration page for managing libraries and saves the form data.
     */
    public function config()
    {
        echo '<div class="container">
        <h1>Libs</h1>';
        if (!empty($_POST)) {
            $this->save($_POST);
        }
        if (isset($_GET['download'])) {
            $this->download();
        }
        if (isset($_GET['generate_code'])) {
            $this->generate_code();
        }
        $this->form();
        echo '<a class="btn btn-secondary" href="?page=nethttp.net-libs&download">Download libs</a>
        <a class="btn btn-secondary" href="?page=nethttp.net-libs&generate_code">Generate included file</a>
        </div>';
    }

    /**
     * Generates the HTML form for managing libraries.
     */
    private function form()
    {
        $libs = get_option(self::OPTION);
        echo '
        Lib should have at least an url.<br/> In url %1$s are replaced by version.<br/>
        <button id="addLib" class="components-button is-primary">Add a lib +</button><br/><br/>
        <form method="post" id="libs-form"></form>
        <input type="submit" value="Save" class="components-button is-primary" form="libs-form"/>
        <script>var libs = ' . json_encode($libs) . '</script>
        ';
    }

    /**
     * Save library settings and update the option
     *
     * @param array $data The library data to save
     * @return void
     */
    private function save($data)
    {
        $default = [
            'name' => '',
            'website' => '',
            'version' => '',
            'url' => '',
            'files' => '',
            'dependencies' => '',
            'active' => false,
            'front' => false,
            'admin' => false,
            'condition' => '',
            'adminCondition' => '',
        ];
        foreach ($data as &$lib) {
            if (empty($lib['url'])) {
                continue;
            }
            foreach ($lib as $k => &$field) {
                $field = stripslashes($field);
            }
            $lib = array_merge($default, $lib);
        }
        if (update_option(self::OPTION, $data)) {
            $this->notice('Libs settings saved !');
        } else {
            $this->error('Libs settings not saved or unchanged !');
        }
        $this->download();
    }

    /**
     * Downloads and saves library files to the server.
     */
    private function download()
    {
        // Create 'libs' directory if it does not exist
        if (!is_dir(__DIR__ . '/libs')) {
            mkdir(__DIR__ . '/libs');
        } else {
            $this->notice('Libs dir exists !');
        }

        // Get libraries from options and download files
        $libs = get_option(self::OPTION);

        $error = false;

        foreach ($libs as $lib) {
            $url = sprintf($lib['url'], $lib['version']);
            $file = @file_get_contents($url);

            if ($file !== false) {
                // Download successful, save file
                $this->notice('Download of ' . $url . ' successfull !');
                $filename = __DIR__ . '/libs/' . basename($url);
                file_put_contents($filename, $file);

                if (isset($filename)) {
                    // Save successful
                    $this->notice('Save of ' . $filename . ' successfull !');

                    if (strtolower(substr($filename, -4, 4) == '.zip')) {
                        // Unzip file
                        if ($this->unzip($filename, __DIR__ . '/libs')) {
                            $this->notice('Unzipping of ' . $filename . ' successfull !');
                            unlink($filename);
                        } else {
                            $error = true;
                            $this->error('Error while unzipping ' . $filename . ' !');
                        }
                    }
                } else {
                    // Error saving file
                    $error = true;
                    $this->error('Error while saving ' . $filename . ' !');
                }
            } else {
                // Error downloading file
                $error = true;
                $this->error('Error while downloading ' . $url . ' !');
            }
            flush();
        }
        if (!$error) {
            $this->generate_code();
        }
    }

    /**
     * Generates a PHP file that includes the styles and scripts of the libraries.
     */
    private function generate_code()
    {
        $libs = get_option(self::OPTION);
        $libsToincludeFront = "<?php\n//This file is autogenerated, do not modify\nfunction nethttp_net_libs_addStyleAndScript()\n{\n";
        $libsToincludeAdmin = "function nethttp_net_libs_addStyleAndScript_admin()\n{\n";

        foreach ($libs as $lib) {
            if (!$lib['active']) {
                $this->error('Library ' . $lib['name'] . ' is not active!');
                continue;
            }

            $cond = trim($lib['condition']) != '' && $this->isValidPhpCodeAndReturnBoolean($lib['condition']) ? "if(" . $lib['condition'] . "){\n\t" : '';
            if ($cond) {
                $libsToincludeFront .= $cond;
            }

            $admincond = trim($lib['adminCondition']) != '' && $this->isValidPhpCodeAndReturnBoolean($lib['adminCondition']) ? "if(" . $lib['adminCondition'] . "){\n\t" : '';
            if ($admincond) {
                $libsToincludeAdmin .= $admincond;
            }

            $files = explode("\n", $lib['files']);
            $dependencies = array_filter(explode(" ", trim(str_replace(',', ' ', $lib['dependencies']))));

            $names=[];

            foreach ($files as $k => $f) {
                if (trim($f) == '') {
                    continue;
                }



                $f = sprintf(trim($f), $lib['version']);
                if (is_file(__DIR__ . '/libs/' . $f)) {
                    if (substr($f, -3, 3) == 'css') {
                        $name = $lib['name'];
                        $i=0;
                        while (in_array($name, $names)) {
                            $name = $lib['name'].'-'.$i;
                            $i++;
                        }
                        $names[]=$name;

                        $code = "\twp_enqueue_style('" . $name. "', '" . plugin_dir_url(__FILE__) . "libs/" . $f . "'," . var_export($dependencies, true) . ", WP_DEBUG ? time() : '" . $lib['version'] . "');\n\n";
                    } elseif (substr($f, -2, 2) == 'js') {
                        $name = $lib['name'].'-js';
                        $i=0;
                        while (in_array($name, $names)) {
                            $name = $lib['name'].'-js'.'-'.$i;
                            $i++;
                        }
                        $names[]=$name;

                        $code = "\twp_enqueue_script('" . $name."', '" . plugin_dir_url(__FILE__) . "libs/" . $f . "', " . var_export($dependencies, true) . ", WP_DEBUG ? time() : '" . $lib['version'] . "', true);\n\n";
                    }

                    $libsToincludeFront .= $lib['front'] ? $code : '';
                    $libsToincludeAdmin .= $lib['admin'] ? $code : '';
                } else {
                    $this->error('file  ' . $f . ' not exists !');
                }
            }
            if ($cond) {
                $libsToincludeFront .= "\n}\n";
            }
            if ($admincond) {
                $libsToincludeAdmin .= "\n}\n";
            }
        }

        $libsToinclude = $libsToincludeFront . "\n}\n" . $libsToincludeAdmin . "\n}\nadd_action('admin_enqueue_scripts', 'nethttp_net_libs_addStyleAndScript_admin');\nadd_action('wp_enqueue_scripts', 'nethttp_net_libs_addStyleAndScript');\n";

        if (file_put_contents(__DIR__ . '/scripts.php', $libsToinclude)) {
            $this->notice('File to include successfully generated !');

            ini_set("highlight.comment", "#008000");
            ini_set("highlight.default", "#000000");
            ini_set("highlight.html", "#808080");
            ini_set("highlight.keyword", "#0000BB; font-weight: bold");
            ini_set("highlight.string", "#DD0000");

            echo '<h2>Generated file</h2>' . highlight_string($libsToinclude, true);
        } else {
            $this->error('Error while saving the file to include');
        }
    }

    /**
     * Vérifie si une chaîne de caractères contient du code PHP valide et renvoie un booléen.
     *
     * @param string $code La chaîne de caractères à vérifier.
     *
     * @return bool true si la chaîne de caractères contient du code PHP valide et renvoie un booléen, false sinon.
     *
     * @throws Exception Si l'évaluation du code génère une exception.
     */
    private function isValidPhpCodeAndReturnBoolean(string $code): bool
    {
        // Ajoute des balises PHP si elles ne sont pas présentes dans le code
        if (substr(trim($code), 0, 5) !== '<?php') {
            $code = '<?php return ' . $code;
        }
        if (substr(trim($code), -2) !== '?>') {
            $code .= ' ?>';
        }

        // Exécute le code dans un contexte séparé pour éviter les collisions de noms de variables
        $result = null;
        $temp = tmpfile();
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];
        fwrite($temp, $code);
        fseek($temp, 0);
        try {
            $result = require $path;
        } catch (\Throwable $e) {
            throw new \Exception('Une exception a été générée lors de l\'évaluation du code : ' . $e->getMessage());
        } finally {
            fclose($temp);
        }

        // Retourne un booléen si l'évaluation s'est faite sans erreur
        return is_bool($result);
    }

    /**
     * Outputs a notice message to the user.
     * @param string $msg The message to display.
     * @param string $type The type of notice to display, defaults to 'success'.
     * @return void
     */
    private function notice($msg, $type = 'success')
    {
        echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . $msg . '</p></div>';
    }

    /**
     * Outputs an error message to the user.
     * @param string $msg The error message to display.
     * @return void
     */
    private function error($msg)
    {
        return $this->notice($msg, 'error');
    }
}

new Libs();
