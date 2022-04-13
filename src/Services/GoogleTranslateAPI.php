<?php

namespace Stev\GoogleCloudTranslateBundle\Services;

use Google\Cloud\Translate\V2\TranslateClient;

class GoogleTranslateAPI
{

    /**
     * @var TranslateClient
     */
    private $googleTranslateAPIClient;


    public function __construct(array $config = [])
    {
        $defaultConfigs = [
            'requestTimeout' => 5,
        ];

        $this->googleTranslateAPIClient = new TranslateClient(array_replace($defaultConfigs, $config));
    }

    public function getClient()
    {
        return $this->googleTranslateAPIClient;
    }


}