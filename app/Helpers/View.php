<?php

namespace App\Helpers;

class View
{
    private string $layout = 'main';
    private array  $data   = [];

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function render(string $template, array $data = []): void
    {
        $this->data = $data;
        // Use a prefixed variable to avoid extract() collision with key named 'data'
        $__viewData = $data;
        extract($__viewData, EXTR_SKIP);

        $viewFile = ROOT_PATH . '/app/Views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $template");
        }

        // Buffer inner content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render inside layout
        $layoutFile = ROOT_PATH . '/app/Views/layouts/' . $this->layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /** Render a partial (no layout) */
    public static function partial(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        $viewFile = ROOT_PATH . '/app/Views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            return '';
        }
        ob_start();
        require $viewFile;
        return ob_get_clean();
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
