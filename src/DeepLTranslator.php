<?php

namespace Justmd5\DeeplX;

use Exception;

class DeepLTranslator
{
    private $supportedLanguages = [
        ['auto', 'auto'],
        ['de', 'DE'],
        ['en', 'EN'],
        ['es', 'ES'],
        ['fr', 'FR'],
        ['it', 'IT'],
        ['ja', 'JA'],
        ['ko', 'KO'],
        ['nl', 'NL'],
        ['pl', 'PL'],
        ['pt', 'PT'],
        ['ru', 'RU'],
        ['zh', 'ZH'],
        ['zh', 'ZH'],
        ['bg', 'BG'],
        ['cs', 'CS'],
        ['da', 'DA'],
        ['el', 'EL'],
        ['et', 'ET'],
        ['fi', 'FI'],
        ['hu', 'HU'],
        ['lt', 'LT'],
        ['lv', 'LV'],
        ['ro', 'RO'],
        ['sk', 'SK'],
        ['sl', 'SL'],
        ['sv', 'SV'],
    ];

    protected $response;

    private $langMap;

    public function __construct()
    {
        $this->langMap = array_column($this->supportedLanguages, 1, 0);
    }

    /**
     * @return array|string
     *
     * @throws Exception
     */
    public function zh2en($query, bool $raw = false): array
    {
        return $this->translate($query, 'zh', 'en')->result($raw);
    }

    /**
     * @return array|string
     *
     * @throws Exception
     */
    public function en2zh($query, bool $raw = false): array
    {
        return $this->translate($query, 'en', 'zh')->result($raw);
    }

    /**
     * @return array|deepLTranslator|string
     *
     * @throws Exception
     */
    public function translate(string $query, string $from, string $to, bool $resultReturn = false, bool $raw = false)
    {
        $targetLanguage = $this->langMap[$to] ?? 'auto';
        $sourceLanguage = $this->langMap[$from] ?? 'auto';
        if ($targetLanguage == $sourceLanguage) {
            throw new Exception('参数错误', 0, null);
        }
        if (! $targetLanguage) {
            throw new Exception('不支持该语种', 0, null);
        }
        $translateText = $query ?: '';
        if (empty($translateText)) {
            throw new Exception('请输入内容', 0, null);
        }
        $url = 'https://www2.deepl.com/jsonrpc';
        $id = rand(100000, 999999) * 1000;
        $postData = $this->initData($sourceLanguage, $targetLanguage);
        $text = [
            'text' => $translateText,
            'requestAlternatives' => 3,
        ];
        $postData['id'] = $id;
        $postData['params']['texts'] = [$text];
        $postData['params']['timestamp'] = $this->getTimeStamp($this->getICount($translateText));
        $postStr = json_encode($postData);
        $replace = ($id + 5) % 29 === 0 || ($id + 3) % 13 === 0 ? '"method" : "' : '"method": "';
        $postStr = str_replace('"method":"', $replace, $postStr);
        try {
            $response = $this->postData($url, $postStr);
            $this->response = $response;
        } catch (Exception $e) {
            throw new Exception('接口请求错误 - '.$e->getMessage(), 0, $e);
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

    private function initData($sourceLang, $targetLang): array
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

    private function getTimeStamp(int $iCount): int
    {
        $ts = time();
        if ($iCount !== 0) {
            $iCount = $iCount + 1;

            return $ts - ($ts % $iCount) + $iCount;
        }

        return $ts;
    }

    private function getICount(string $translateText): int
    {
        return substr_count($translateText, 'i');
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
