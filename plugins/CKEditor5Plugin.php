<?php

class CKEditor5Plugin extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const CODE_DIR = '/CKEditor5Plugin/';

    /*
     *  Inherited variables
     */
    public $name = 'CKEditor5 plugin';
    public $editorProvider = true;
    public $authors = 'Tatevik Grigoryan';
    public $description = 'Provides the CKEditor5 for editing messages and templates.';
    public $documentationUrl = 'https://resources.phplist.com/plugin/ckeditor5';
    public $enabled = 1;

    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        $pluginPath = substr(PLUGIN_ROOTDIR, 0, 1) == '/' ? PLUGIN_ROOTDIR : $GLOBALS['pageroot'] . '/admin/' . PLUGIN_ROOTDIR;
        $elPath = $pluginPath . self::CODE_DIR . 'elFinder';
        $ckeditorJsPath = $pluginPath . self::CODE_DIR . 'ckeditor5/ckeditor5.umd.js';
        $ckeditorSccPath = $pluginPath . self::CODE_DIR . 'ckeditor5/ckeditor5-editor.css';

        $this->settings = array(
            'ckeditor5_js_url' => array(
                'value' => $ckeditorJsPath,
                'description' => 'URL of ckeditor.js',
                'type' => 'text',
                'allowempty' => 0,
                'category' => 'CKEditor',
            ),
            'ckeditor5_css_url' => array(
                'value' => $ckeditorSccPath,
                'description' => 'URL of ckeditor.css',
                'type' => 'text',
                'allowempty' => 0,
                'category' => 'CKEditor',
            ),
            'ckeditor5_license_key' => array(
                'value' => ' ',
                'description' => 'Licence key from ckeditor.js',
                'type' => 'text',
                'allowempty' => 0,
                'category' => 'CKEditor',
            ),
            'ckeditor5_width' => [
                'value' => 600,
                'description' => 'Width in px of CKeditor Area',
                'type' => 'integer',
                'allowempty' => 0,
                'min' => 100,
                'max' => 800,
                'category' => 'CKEditor',
            ],
            'ckeditor5_height' => [
                'value' => 600,
                'description' => 'Height in px of CKeditor Area',
                'type' => 'integer',
                'allowempty' => 0,
                'min' => 100,
                'max' => 800,
                'category' => 'CKEditor',
            ],
            'elfinder_path' => array(
                'value' => $elPath,
                'description' => 'path to elFinder',
                'type' => 'text',
                'allowempty' => 0,
                'category' => 'TinyMCE',
            ),
            'elfinder_image_directory' => array(
                'value' => getConfig('kcfinder_image_directory') ?? 'image',
                'description' => 'Name of the image subdirectory of the file upload directory',
                'type' => 'text',
                'allowempty' => 0,
                'category' => 'CKEditor',
            ),
            'elfinder_files_directory' => array(
                'value' => getConfig('kcfinder_files_directory') ?? 'files',
                'description' => 'Name of the files subdirectory of the file upload directory',
                'type' => 'text',
                'allowempty' => 0,
                'category' => 'CKEditor',
            ),
        );

        parent::__construct();
    }

    public function editor($fieldName, $content): string
    {
        $width = getConfig('ckeditor5_width') ?? 900;
        $height = getConfig('ckeditor5_height') ?? 450;
        $licenseKey = getConfig('ckeditor5_license_key');
        $licenseKeyScript = "licenseKey: '$licenseKey'";
        $editorUrl = getConfig('ckeditor5_js_url');
        $cssUrl = getConfig('ckeditor5_css_url');

        $htmlSupport = "htmlSupport: {
            disallow: [
                { name: 'script' },
                { name: /.*/, attributes: { key: /^on.*/ } },
                { name: 'iframe' }
            ]
        }";

        $script = $this->editorScript($fieldName, $width, $height, $licenseKeyScript, $editorUrl, $htmlSupport);
        $fieldName = htmlspecialchars($fieldName);
        $content = htmlspecialchars($content);

        return $this->textArea($fieldName, $content, $cssUrl) . $this->scriptForSyncLoad($editorUrl, $script);
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

    private function textArea(string $fieldName, string $content, string $cssUrl): string
    {
        return "
		<link rel='stylesheet' href=" . $cssUrl . " crossorigin>
<textarea id=\"$fieldName\" name=\"$fieldName\">$content</textarea>";
    }

    private function editorScript(string $fieldName, $width, $height, $licenseKeyScript, $editorUrl, $htmlSupport): string
    {
        $pluginUrl = './?pi=CKEditor5Plugin&page=serve_elfinder';
        $script = <<<END
<script src="$editorUrl"></script>
<script>
function MinHeightPlugin(editor) {
  this.editor = editor;
}
const { ClassicEditor,Essentials,Alignment,AutoLink,Autosave,BlockQuote,Bold,Code,FontBackgroundColor,FontColor,FontFamily,
	FontSize,GeneralHtmlSupport,Heading,Highlight,HorizontalLine,HtmlComment,HtmlEmbed,Indent,IndentBlock,Italic,Link,
	Paragraph,PlainTableOutput,RemoveFormat,Strikethrough,Subscript,Superscript,Table,TableCaption,TableCellProperties,
	TableColumnResize,TableLayout,TableProperties,TableToolbar,Underline, SourceEditing,MediaEmbed,PictureEditing,
	AutoImage,ImageBlock,TextTransformation,TodoList,ImageCaption,ImageInsert,ImageInsertViaUrl,ImageResize,ImageStyle,
	ImageTextAlternative,ImageToolbar,ImageUpload, LinkImage, ImageInsertUI,ImageInline,List,ListProperties} = CKEDITOR;

MinHeightPlugin.prototype.init = function() {
  this.editor.ui.view.editable.extendTemplate({
    attributes: {
      style: {
        minHeight: '{$height}px'
      }
    }
  });
};

document.addEventListener("DOMContentLoaded", function () {
    ClassicEditor.create(document.querySelector('textarea#$fieldName'), {
        extraPlugins: [ MinHeightPlugin,Essentials,Alignment,AutoLink,Autosave,BlockQuote,Bold,Code,
            FontBackgroundColor,FontColor,FontFamily,FontSize,GeneralHtmlSupport,Heading,Highlight,HorizontalLine,
            HtmlComment,HtmlEmbed,Indent,IndentBlock,Italic,Link,Paragraph,PlainTableOutput,RemoveFormat,Strikethrough,
            Subscript,Superscript,Table,TableCaption,TableCellProperties,TableColumnResize,TableLayout,TableProperties,
            TableToolbar,Underline, SourceEditing,ImageInsert,ImageInsertUI, ImageInline,List,ListProperties,MediaEmbed,
            PictureEditing,TextTransformation,TodoList,

        ],
        plugins: [
		    AutoImage,Autosave,BlockQuote,Bold,Essentials,GeneralHtmlSupport,Heading,HtmlEmbed,ImageBlock,ImageCaption,
		    ImageInline,ImageInsert,ImageInsertViaUrl,ImageStyle,ImageTextAlternative,ImageToolbar,ImageUpload,Indent,
		    IndentBlock,Italic,Link,LinkImage,List,ListProperties,MediaEmbed,Paragraph,PictureEditing,SourceEditing,
		    Table,TableCaption,TableCellProperties,TableColumnResize,TableProperties,TableToolbar,TextTransformation,
		    TodoList,Underline
	    ],
        $licenseKeyScript,
        $htmlSupport,
        image: {
            toolbar: ['toggleImageCaption', 'imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:wrapText', 'imageStyle:breakText']
        },
        toolbar: {
		    items: [
                'insertImage','sourceEditing','undo','redo','|',
                'heading','|',
                'fontSize','fontFamily','fontColor','fontBackgroundColor','|',
                'bold','italic','underline','strikethrough','subscript','superscript','code','removeFormat','|',
                'horizontalLine','link','insertTable','insertTableLayout','highlight','blockQuote','htmlEmbed','|',
                'alignment','|',
                'outdent','indent'
            ],
            fontFamily: { supportAllValues: true },
            fontSize: {
                options: [10, 12, 14, 'default', 18, 20, 22],
                supportAllValues: true
            },
            heading: {
                options: [
                    {
                        model: 'paragraph',
                        title: 'Paragraph',
                        class: 'ck-heading_paragraph'
                    },
                    {
                        model: 'heading1',
                        view: 'h1',
                        title: 'Heading 1',
                        class: 'ck-heading_heading1'
                    },
                    {
                        model: 'heading2',
                        view: 'h2',
                        title: 'Heading 2',
                        class: 'ck-heading_heading2'
                    },
                    {
                        model: 'heading3',
                        view: 'h3',
                        title: 'Heading 3',
                        class: 'ck-heading_heading3'
                    },
                    {
                        model: 'heading4',
                        view: 'h4',
                        title: 'Heading 4',
                        class: 'ck-heading_heading4'
                    },
                    {
                        model: 'heading5',
                        view: 'h5',
                        title: 'Heading 5',
                        class: 'ck-heading_heading5'
                    },
                    {
                        model: 'heading6',
                        view: 'h6',
                        title: 'Heading 6',
                        class: 'ck-heading_heading6'
                    }
                ]
            },
            shouldNotGroupWhenFull: false
        },
        })
        .then(editor => {
            editor.ui.view.toolbar.element.addEventListener("click", (event) => {
                if (event.target.accept && event.target.accept.includes("image")) {
                    openElFinder(editor, 'image');
                }
            });

            function openElFinder(editor, fileType) {
                const fileManager = window.open(
                    '$pluginUrl',
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
            "ckeditor5_settings" => "Ckedotor5 Settings",
        );
    }

    public function display($action)
    {
        switch ($action) {
            case "ckeditor5_settings":
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
