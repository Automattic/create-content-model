<?php
class Content_Model_Initializer {
    private $content_models = [];

    public function __construct($json_files) {
        $this->load_content_models($json_files);
    }

    private function load_content_models($json_files) {
        foreach ($json_files as $file) {
            $json_content = file_get_contents($file);
            $data = json_decode($json_content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in file: ' . $file);
            }

            $this->content_models = array_merge($this->content_models, $data);
        }
    }

    public function initialize() {
        add_action('init', [$this, 'register_post_types_and_fields']);
    }

    public function register_post_types_and_fields() {
        foreach ($this->content_models as $model) {
            $this->register_post_type($model);
            $this->register_meta_fields($model);
        }
    }

    private function register_post_type($model) {
        register_post_type($model['postType'], [
            'label' => $model['label'],
            'public' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
        ]);
    }

    private function register_meta_fields($model) {
        if (isset($model['fields'])) {
            foreach ($model['fields'] as $field) {
                register_post_meta(
                    $model['postType'],
                    $field['slug'],
                    [
                        'show_in_rest' => true,
                        'single' => true,
                        'type' => $field['type'],
                    ]
                );
            }
        }
    }
}