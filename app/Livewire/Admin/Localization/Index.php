<?php

namespace App\Livewire\Admin\Localization;

use Livewire\Component;
use Illuminate\Support\Facades\File;

class Index extends Component
{
    public array $availableLocales = [
        'en' => 'English',
        'sv' => 'Svenska (Swedish)',
    ];

    public string $defaultLocale;
    public array $enabledLocales = [];
    public string $selectedFile = 'admin';
    public array $translations = [];
    public array $translationFiles = [];

    public function mount()
    {
        $this->defaultLocale = config('app.locale', 'en');
        $this->enabledLocales = config('app.available_locales', ['en', 'sv']);
        $this->loadTranslationFiles();
        $this->loadTranslations();
    }

    public function loadTranslationFiles()
    {
        $langPath = lang_path('en');
        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $this->translationFiles[] = $file->getFilenameWithoutExtension();
                }
            }
        }
    }

    public function loadTranslations()
    {
        $this->translations = [];

        foreach ($this->availableLocales as $locale => $name) {
            $filePath = lang_path("{$locale}/{$this->selectedFile}.php");
            if (File::exists($filePath)) {
                $this->translations[$locale] = include $filePath;
            } else {
                $this->translations[$locale] = [];
            }
        }
    }

    public function updatedSelectedFile()
    {
        $this->loadTranslations();
    }

    public function save()
    {
        foreach ($this->availableLocales as $locale => $name) {
            $filePath = lang_path("{$locale}/{$this->selectedFile}.php");
            $content = "<?php\n\nreturn " . $this->varExport($this->translations[$locale] ?? [], true) . ";\n";
            File::put($filePath, $content);
        }

        session()->flash('message', __('Translations saved successfully.'));
    }

    protected function varExport($expression, $return = false)
    {
        $export = var_export($expression, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $export = implode(PHP_EOL, array_filter(["["] + $array));

        if ($return) {
            return $export;
        } else {
            echo $export;
        }
    }

    public function render()
    {
        return view('livewire.admin.localization.index')
            ->layout('components.layouts.admin');
    }
}
