<?php

namespace Justmd5\DeeplX;

use Exception;

/**
 * @method zh2en(string $string, bool $raw = false):array|string
 * @method en2zh(string $string, bool $raw = false):array|string
 */
class DeepLTranslator
{
    protected $response;

    private $langMap = [
        'AUTO', 'DE', 'EN', 'ES', 'FR', 'IT', 'JA', 'KO', 'NL', 'PL', 'PT', 'RU', 'ZH',
        'BG', 'CS', 'DA', 'EL', 'ET', 'FI', 'HU', 'LT', 'LV', 'RO', 'SK', 'SL', 'SV',
    ];

    /**
     * @throws Exception
     */
    public function __call($method, $args)
    {
        [$from, $to] = explode('2', $method);

        return $this->translate($args[0], $from, $to)->result($args[1] ?? false);
    }

    /**
     * @return array|DeepLTranslator|string
     *
     * @throws Exception
     */
    public function translate(string $query, string $from, string $to, bool $resultReturn = false, bool $raw = false)
    {
        if (empty($from) || empty($to)) {
            throw new Exception('params error');
        }
        $from = strtoupper($from);
        $to = strtoupper($to);
        $targetLang = in_array($to, $this->langMap, true) ? $to : 'auto';
        $sourceLang = in_array($from, $this->langMap, true) ? $from : 'auto';
        if ($targetLang == $sourceLang) {
            throw new Exception('params error');
        }
        $translateText = $query ?: '';
        if (empty($translateText)) {
            throw new Exception('please input translate text');
        }
        $url = 'https://www2.deepl.com/jsonrpc';
        $id = rand(100000, 999999) * 1000;
        $postData = static::initData($sourceLang, $targetLang);
        $text = [
            'text' => $translateText,
            'requestAlternatives' => 3,
        ];
        $postData['id'] = $id;
        $postData['params']['texts'] = [$text];
        $postData['params']['timestamp'] = static::getTimeStamp($translateText);
        $postStr = json_encode($postData);
        $replace = ($id + 5) % 29 === 0 || ($id + 3) % 13 === 0 ? '"method" : "' : '"method": "';
        $postStr = str_replace('"method":"', $replace, $postStr);
        try {
            $response = $this->postData($url, $postStr);
            $this->response = $response;
        } catch (Exception $e) {
            throw new Exception('request error :'.$e->getMessage(), 0, $e);
        }
        if ($resultReturn) {
            return $this->result($raw);
        }

        return $this;
    }

    /**
     * @return array|string
     */
    public function result(bool $raw = false)
    {
        if ($raw) {
            return $this->raw();
        }
        $responseData = json_decode($this->response, true);
        if (isset($responseData['error'])) {
            $error = $responseData['error'];

            return [
                'status' => 0, 'code' => $error['code'],
                'message' => sprintf('%s,what:%s', $error['message'], $error['data']['what']), 'data' => [],
            ];
        }

        return [
            'status' => 1, 'code' => 1000, 'message' => 'ok', 'data' => $responseData['result']['texts'][0]['text'],
        ];
    }

    public function raw(): string
    {
        return $this->response;
    }

    private static function initData(string $sourceLang, string $targetLang): array
    {
        return [
            'jsonrpc' => '2.0',
            'method' => 'LMT_handle_texts',
            'params' => [
                'splitting' => 'newlines',
                'lang' => [
                    'source_lang_user_selected' => $sourceLang,
                    'target_lang' => $targetLang,
                ],
            ],
        ];
    }

    private static function getTimeStamp(string $translateText): int
    {
        $ts = time();
        $iCount = substr_count($translateText, 'i');
        if ($iCount !== 0) {
            $iCount = $iCount + 1;

            return $ts - ($ts % $iCount) + $iCount;
        }

        return $ts;
    }

    /**
     * @return bool|string
     */
    private function postData(string $url, string $data)
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
