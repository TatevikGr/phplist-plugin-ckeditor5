<?php

class CKEditor5Plugin extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const CODE_DIR = '/CKEditor5Plugin/';
    const CDN =  '//cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js';

    /*
     *  Inherited variables
     */
    public $name = 'CKEditor5 plugin';
    public $editorProvider = true;
    public $authors = 'Tatevik Grigoryan';
    public $description = 'Provides the CKEditor5 for editing messages and templates.';
    public $documentationUrl = 'https://resources.phplist.com/plugin/ckeditor5';
    public $enabled = 1;
    private $elEnabled;

    public function __construct()
    {
        $this->elEnabled = defined('UPLOADIMAGES_DIR') && UPLOADIMAGES_DIR !== false;
        $this->coderoot = dirname(__FILE__) . self::CODE_DIR;
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        $this->settings = array(
            'ckeditor5_url' => array(
                'value' => '//cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js',
                'description' => 'URL of ckeditor.js',
                'type' => 'text',
                'allowempty' => 0,
                'category' => 'CKEditor',
            ),
            'ckeditor_disallow' => array(
                'description' => 'Disallow javascript',
                'type' => 'boolean',
                'value' => '1',
                'allowempty' => true,
                'category' => 'CKEditor',
            ),
        );

        if (isset($_SESSION['ui']) && $_SESSION['ui'] == 'dressprow') {
            $this->settings += [
                'ckeditor_width' => [
                    'value' => 600,
                    'description' => 'Width in px of CKeditor Area',
                    'type' => 'integer',
                    'allowempty' => 0,
                    'min' => 100,
                    'max' => 800,
                    'category' => 'CKEditor',
                ],
                'ckeditor_height' => [
                    'value' => 600,
                    'description' => 'Height in px of CKeditor Area',
                    'type' => 'integer',
                    'allowempty' => 0,
                    'min' => 100,
                    'max' => 800,
                    'category' => 'CKEditor',
                ],
            ];
        }

        if ($this->elEnabled) {
            $this->settings += array(
                'kcfinder_path' => array(
                    'value' => PLUGIN_ROOTDIR . self::CODE_DIR . 'elfinder',
                    'description' => 'Path to ElFinder',
                    'type' => 'text',
                    'allowempty' => 0,
                    'category' => 'CKEditor',
                ),
                'kcfinder_uploaddir' => array(
                    'value' => '',
                    'description' => 'File system path to the upload image directory. Usually leave this empty.',
                    'type' => 'text',
                    'allowempty' => 1,
                    'category' => 'CKEditor',
                ),
                'kcfinder_image_directory' => array(
                    'value' => 'image',
                    'description' => 'Name of the image subdirectory of the file upload directory',
                    'type' => 'text',
                    'allowempty' => 0,
                    'category' => 'CKEditor',
                ),
                'kcfinder_files_directory' => array(
                    'value' => 'files',
                    'description' => 'Name of the files subdirectory of the file upload directory',
                    'type' => 'text',
                    'allowempty' => 0,
                    'category' => 'CKEditor',
                ),
            );
        }
        parent::__construct();
    }

    public function editor($fieldName, $content): string
    {
        $width = getConfig('ckeditor_width') ?? 900;
        $height = getConfig('ckeditor_height') ?? 450;
        $licenseKey = getConfig('ckeditor_license_key');
        $licenseKeyScript = "licenseKey: '$licenseKey'";
        $editorUrl = getConfig('ckeditor5_url') ? getConfig('ckeditor5_url') : self::CDN;
        $configVersion = $this->getCkeditorVersion($editorUrl) ?? '0.0.0';
        $cdnVersion = $this->getCkeditorVersion(self::CDN) ?? '0.0.0';

        if (version_compare($configVersion, $cdnVersion, '<')) {
            $editorUrl = self::CDN;
        }
        $htmlSupport = '';
        if (getConfig('ckeditor_disallow')) {
            $htmlSupport = "htmlSupport: {
                disallow: [
                    { name: 'script' },
                    { name: /.*/, attributes: { key: /^on.*/ } },
                    { name: 'iframe' }
                ]
            }";
        }

        $script = $this->editorScript($fieldName, $width, $height, $licenseKeyScript, $editorUrl, $htmlSupport);
        $fieldName = htmlspecialchars($fieldName);
        $content = htmlspecialchars($content);

        return $this->textArea($fieldName, $content)
            . $this->scriptForSyncLoad($editorUrl, $script);
    }

    private function scriptForSyncLoad(string $editorUrl, $ckScript): string
    {
        return <<<END
<script type="text/javascript" src="$editorUrl"></script>
<script>
$ckScript
</script>
END;
    }

    private function textArea(string $fieldName, string $content): string
    {
        return "<textarea id=\"$fieldName\" name=\"$fieldName\">$content</textarea>";
    }

    private function editorScript(string $fieldName, $width, $height, $licenseKeyScript, $editorUrl, $htmlSupport): string
    {
        $pluginUrl = substr(PLUGIN_ROOTDIR, 0, 1) == '/' ? PLUGIN_ROOTDIR : $GLOBALS['pageroot'] . '/admin/' . PLUGIN_ROOTDIR;

        $script = <<<END
<script src="$editorUrl"></script>
<script>
function MinHeightPlugin(editor) {
  this.editor = editor;
}

MinHeightPlugin.prototype.init = function() {
  this.editor.ui.view.editable.extendTemplate({
    attributes: {
      style: {
        minHeight: '{$height}px'
      }
    }
  });
};

ClassicEditor.builtinPlugins.push(MinHeightPlugin);
document.addEventListener("DOMContentLoaded", function () {
    ClassicEditor
        .create(document.querySelector('textarea#$fieldName'), {
            $licenseKeyScript,
            $htmlSupport,
            toolbar: [
                'heading', 'undo', 'redo', '|', 
                'bold', 'italic',  'bulletedList', 'numberedList', '|',
                'imageUpload', 'link', 'mediaEmbed',  '|',
                'blockQuote', 'insertTable'
            ]
        })
        .then(editor => {
            editor.ui.view.toolbar.element.addEventListener("click", (event) => {
                if (event.target.accept && event.target.accept.includes("image")) {
                    openElFinder(editor, 'image');
                }
            });

            function openElFinder(editor, fileType) {
                const fileManager = window.open(
                    '$pluginUrl/CKEditor5Plugin/elFinder/elfinder.html',
                    'File Manager',
                    'width=$width,height=$height'
                );

                fileManager.addEventListener('message', function handleFile(event) {
                    if (event.origin !== window.location.origin) return;

                    const data = event.data;
                    const imageExtensions = [ 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp' ];

                    if (data.mceAction === 'fileSelected') {
                        const fileUrl = new URL(data.data.url, window.location.origin).href;

                        if (imageExtensions.includes(data.data.url.split('.').pop().toLowerCase())) {
                            editor.model.change(writer => {
                                const imageElement = writer.createElement('imageBlock', {
                                    src: fileUrl,
                                    alt: data.data.name
                                });
                                editor.model.insertContent(imageElement, editor.model.document.selection);
                            });
                        } else {
                            // display data.data.url in editor page
                            editor.model.change(writer => {
                                const linkText = data.data.name || fileUrl;
                                const textNode = writer.createText(linkText, { linkHref: fileUrl });
                                editor.model.insertContent(textNode, editor.model.document.selection);
                            });
                        }

                        fileManager.close();
                        window.removeEventListener('message', handleFile);
                    }
                });
            }
        })
        .catch(error => {
            console.error('CKEditor5 initialization failed:', error);
        });
});
</script>

END;
        return $script;
    }

    public function adminMenu()
    {
        return array(
            "ckeditor_settings" => "Ckedotor5 Settings",
        );
    }

    public function display($action)
    {
        switch ($action) {
            case "ckeditor_settings":
                echo '<h1>Ckedotor5 Configuration</h1>';
                echo '<p>Configure your Ckedotor5 integration here.</p>';
                break;
        }
    }

    public function getCkeditorVersion($url) {
        preg_match('/ckeditor5\/([\d\.]+)\//', $url, $matches);
        return $matches[1] ?? null;
    }

    public function dependencyCheck()
    {
        global $editorplugin;

        return array(
            'No other editor enabled' => empty($editorplugin) || $editorplugin == __CLASS__,
            'phpList version 3.5.5 or later' => version_compare(VERSION, '3.5.5-RC1') >= 0,
        );
    }
}
