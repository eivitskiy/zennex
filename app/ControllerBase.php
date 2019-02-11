<?php

namespace app;

abstract class ControllerBase
{

    public $layoutFile = 'Views/Layout.php';

    public function renderLayout($body)
    {
        ob_start();
        require APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layout.php';
        return ob_get_clean();

    }

    /**
     * @param $viewName
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function render($viewName, array $params = [])
    {

        $viewFile = APP_PATH . 'views' . DIRECTORY_SEPARATOR . $viewName . '.php';
        extract($params);

        ob_start();

        if (!file_exists($viewFile)) {
            throw new \Exception('Файла отображения не существует');
        }

        require $viewFile;

        $body = ob_get_clean();
        ob_end_clean();

        return $this->renderLayout($body);

    }

}