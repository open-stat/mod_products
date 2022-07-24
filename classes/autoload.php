<?php
namespace Core2\Mod\Products;


/**
 *
 */
spl_autoload_register(function ($class) {

    if (strpos($class, __NAMESPACE__) === 0) {
        $class_explode   = explode("\\", $class);
        $class_name      = end($class_explode);
        $class_path      = [];
        $count_namespace = count(explode("\\", __NAMESPACE__));

        foreach ($class_explode as $key => $item) {
            if ($key >= $count_namespace && $key < (count($class_explode) - 1)) {
                $class_path[] = $item;
            }
        }

        $class_path_implode = implode('/', $class_path);
        $class_path_implode = $class_path_implode ?: '';

        $file_path = __DIR__ . "/{$class_path_implode}/{$class_name}.php";

        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});