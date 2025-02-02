<?php
/**
 * Plugin Name: TinyMCE Plugin for phpList
 * Description: Integrates TinyMCE as the WYSIWYG editor for phpList.
 * Version: 1.0
 */

if (!defined('PHPLISTINIT')) {
    die('Access denied');
}

class TinyMcePlugin extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const CODE_DIR = '/TinyMcePlugin/';
    const CDN = '//cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.0/tinymce.min.js';
    public $name = 'TinyMcePlugin plugin with elFinder';
    public $editorProvider = true;
    public $description = 'Provides the TinyMce for editing messages and templates.';
    public $documentationUrl = 'https://resources.phplist.com/plugin/tinymce';
    public $enabled = 1;

    public function editor($fieldName, $content): string
    {
        $width = getConfig('ckeditor_width') ?? 900;
        $height = getConfig('ckeditor_height') ?? 450;

        list($script, $html) = $this->editorScript($fieldName, $width, $height);
        $ckeditorUrl = getConfig('ckeditor_url') ?? self::CDN;

        return $this->textArea($fieldName, $content)
            . $html
            . $this->scriptForSyncLoad($ckeditorUrl, $script);
    }

    private function scriptForSyncLoad($ckeditorUrl, $ckScript): string
    {
        return <<<END
<script type="text/javascript" src="$ckeditorUrl"></script>
<script>
$ckScript
</script>
END;
    }

    private function textArea(string $fieldName, string $content): string
    {
        $fieldName = htmlspecialchars($fieldName);
        $content = htmlspecialchars($content);
        return "<textarea id=\"$fieldName\" name=\"$fieldName\">$content</textarea>";
    }

    private function editorScript(string $fieldName, int $width, int $height): array
    {
        $ckeditorUrl = getConfig('ckeditor_url');
        unset($_SESSION['KCFINDER']);
        $html = '';
        $uploadUrl = sprintf('%s://%s/%s', 'http', 'localhost', ltrim(UPLOADIMAGES_DIR, '/'));

        $script = <<<END
<script src="$ckeditorUrl"></script>
<script>
tinymce.init({
    selector: 'textarea#$fieldName',
    plugins: 'image media link',
    paste_data_images: false,
        toolbar: [
        { name: 'history', items: [ 'undo', 'redo' ] },
        { name: 'styles', items: [ 'styles' ] },
        { name: 'formatting', items: [ 'bold', 'italic' ] },
        { name: 'alignment', items: [ 'alignleft', 'aligncenter', 'alignright', 'alignjustify' ] },
        { name: 'indentation', items: [ 'outdent', 'indent' ] }
    ],
    
    file_picker_callback: function(callback, value, meta) {
        tinymce.activeEditor.windowManager.openUrl({
            title: 'elFinder File Manager',
            url: '/lists/admin/plugins/elFinder/elfinder.src.html',
            allow_script_urls: true,
            width: $width,
            height: $height,
            onMessage: (api, data) => {
              if (data.mceAction === 'fileSelected') {
                const baseUrl = window.location.origin + '/';
                console.log(baseUrl)
                const fileUrl = new URL(data.data.url, baseUrl).href;
                callback(fileUrl);
                api.close();
              }
            },
            oninsert: (file, fm) => {
              var url, reg, info;
        
              url = fm.convAbsUrl(file.url);
              
              info = file.name + ' (' + fm.formatSize(file.size) + ')';
        
              if (meta.filetype === 'file') {
                callback(url, {text: info, title: info});
              } else if (meta.filetype === "file" && data.data.url.endsWith('.pdf')) {
                callback(data.data.url, { text: "Download PDF", title: "PDF File" });
              } 

              if (meta.filetype === 'image') {
                callback(url, {alt: info});
              }
        
              if (meta.filetype === 'media') {
                callback(url);
              }
            }
        })
    }
});
</script>

END;
        return [$script, $html];
    }

    public function adminMenu()
    {
        return array(
            "tinymce_settings" => "TinyMCE Settings",
        );
    }

    public function display($action)
    {
        switch ($action) {
            case "tinymce_settings":
                echo '<h1>TinyMCE Configuration</h1>';
                echo '<p>Configure your TinyMCE integration here.</p>';
                break;
        }
    }
}
