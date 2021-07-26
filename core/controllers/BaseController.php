<?php

namespace App\Controllers;

class BaseController {
    private $twig;
    
    protected array $params = [];

    public function __construct() {
        $loader = new \Twig\Loader\FilesystemLoader(config['base_dir'] . '/core/views');
        $this->twig = new \Twig\Environment($loader, [
            'cache' => config['dev_mode'] ? false : config['base_dir'].'/cache/views'
        ]);
        
        // if(config['dev_mode'] == true) {
        //     $filter = new \Twig\TwigFilter('asset', function ($string) {
        //         return config['base_link'] . $string . '?' . time();
        //     });
            
        // } else {
            $filter = new \Twig\TwigFilter('asset', function ($string) {
                return config['base_link'] . $string . '?' . config['asset_version'];
            });
        // }

        $this->twig->addFilter($filter);

        $this->twig->addGlobal('time', time());
        $this->twig->addGlobal('config', config);
    }

    protected function render(string $file) {
        return $this->twig->render($file, $this->params);
    }
}