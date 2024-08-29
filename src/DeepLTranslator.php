<?php

namespace Justmd5\DeeplX;

use Exception;

/**
 * @method zh2en(string $string, bool $type = self::TYPE_FORMAT):array|string
 * @method en2zh(string $string, bool $type = self::TYPE_FORMAT):array|string
 * @method en2zh_tw(string $string, bool $type = self::TYPE_FORMAT):array|string
 * @method zh_tw2en(string $string, bool $type = self::TYPE_FORMAT):array|string
 */
class DeepLTranslator
{
    const SUCCESS = 1000;

    const ERROR = 1001;

    const TYPE_FORMAT = 0;

    const TYPE_JSON = 1;

    const TYPE_ARRAY = 2;

    /**
     * @var string
     */
    protected $response;

    /**
     * @var string
     */
    protected $rpcUrl = 'https://www2.deepl.com/jsonrpc';

    /**
     * @var string[]
     */
    private $langMap = [
        'AUTO', 'AR', 'BG', 'CS', 'DA', 'DE', 'EL', 'EN', 'ES', 'ET', 'FI', 'FR', 'HU',
        'ID', 'IT', 'JA', 'KO', 'LT', 'LV', 'NB', 'NL', 'PL', 'PT', 'RO', 'RU', 'SK',
        'SL', 'SV', 'TR', 'UK', 'ZH', 'ZH-TW',
    ];

    /**
     * @var array[]
     */
    protected $changeTargetArray = [
        'ZH-TW' => ['name' => 'ZH', 'regionalVariant' => 'zh-Hant'],
    ];

    /**
     * @var int
     */
    protected $timeout = 5;

    /**
     * @var bool
     */
    protected static $verify = true;

    public function __construct(int $timeout = 5)
    {

        $this->timeout = $timeout;
    }

    public static function withoutVerifying(): DeepLTranslator
    {
        static::$verify = false;

        return new static;
    }

    /**
     * @return array|string
     *
     * @throws Exception
     */
    public function __call(string $method, array $args)
    {
        [$from, $to] = explode('2', $method);

        return $this->translate(
            $args[0], str_replace('_', '-', $to),
            str_replace('_', '-', $from)
        )
            ->result(isset($args[1]) && $args[1] == self::TYPE_JSON ? self::TYPE_JSON : self::TYPE_FORMAT);
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function translate(string $query, string $to, string $from = 'auto'): DeepLTranslator
    {
        $this->response = '[]';
        if (empty($from) || empty($to)) {
            throw new Exception('params error', __LINE__);
        }
        $from = strtoupper($from);
        $to = strtoupper($to);
        $targetLang = in_array($to, $this->langMap, true) ? $to : 'auto';
        $sourceLang = in_array($from, $this->langMap, true) ? $from : 'auto';
        if ($targetLang == $sourceLang) {
            throw new Exception('params error', __LINE__);
        }
        $translateText = $query ?: '';
        if (empty($translateText)) {
            throw new Exception('please input translate text', __LINE__);
        }
        $id = rand(100000, 999999) * 1000;
        $postData = $this->initData($sourceLang, $targetLang, $translateText);
        $postData['id'] = $id;
        $postData['params']['timestamp'] = static::getTimeStamp($translateText);
        $postStr = json_encode($postData);
        $replace = ($id + 5) % 29 === 0 || ($id + 3) % 13 === 0 ? '"method" : "' : '"method": "';
        $postStr = str_replace('"method":"', $replace, $postStr);
        $this->response = $this->postData($this->rpcUrl, $postStr);

        return $this;
    }

    /**
     * @param  int  $type 0:format 1:json string 2:array
     * @return array|string
     */
    public function result(int $type = self::TYPE_FORMAT)
    {
        if ($type > self::TYPE_FORMAT) {
            return $this->raw($type);
        }
        $responseData = json_decode($this->response, true);
        if (isset($responseData['error'])) {
            $error = $responseData['error'];

            return [
                'status' => self::ERROR, 'code' => $error['code'],
                'message' => sprintf('%s,what:%s', $error['message'] ?? '', $error['data']['what'] ?? ''), 'data' => [],
            ];
        }
        $result = $responseData['result']['texts'][0]['text'] ?? ($responseData['result']['translations'][0]['beams'][0]['sentences'][0]['text'] ?? '');
        if (empty($result)) {
            return [
                'status' => self::ERROR, 'code' => self::ERROR, 'message' => 'translate error', 'data' => [],
            ];
        }

        return [
            'status' => self::SUCCESS, 'code' => self::SUCCESS, 'message' => 'ok', 'data' => $result,
        ];
    }

    /**
     * @return string|array
     */
    public function raw(int $type = self::TYPE_JSON)
    {
        return $type == self::TYPE_JSON ? $this->response : json_decode($this->response, true);
    }

    /**
     * @return array|string
     */
    public function rawJson()
    {
        return $this->raw();
    }

    /**
     * @return array|string
     */
    public function rawArray()
    {
        return $this->raw(self::TYPE_ARRAY);
    }

    private function initData(string $sourceLang, string $targetLang, string $translateText): array
    {
        $baseParams = [
            'jsonrpc' => '2.0',
            'method' => 'LMT_handle_texts',
            'params' => [
                'splitting' => 'newlines',
                'texts' => [
                    [
                        'text' => $translateText,
                        'requestAlternatives' => 3,
                    ],
                ],
                'lang' => [
                    'source_lang_user_selected' => $sourceLang,
                    'target_lang' => $targetLang,
                ],
            ],
        ];
        if (! array_key_exists($targetLang, $this->changeTargetArray)) {
            return $baseParams;
        }

        return [
            'jsonrpc' => '2.0',
            'method' => 'LMT_handle_jobs',
            'params' => [
                'jobs' => [
                    [
                        'kind' => 'default',
                        'sentences' => [
                            [
                                'text' => $translateText,
                                'id' => 1,
                                'prefix' => '',
                            ],
                        ],
                        'raw_en_context_before' => [],
                        'raw_en_context_after' => [],
                        'preferred_num_beams' => 4,
                    ],
                ],
                'lang' => [
                    'source_lang_computed' => $sourceLang,
                    'target_lang' => $this->changeTargetArray[$targetLang]['name'],
                    'preference' => [
                        'weight' => new \stdClass(),
                        'default' => 'default',
                    ],
                ],
                'priority' => -1,
                'commonJobParams' => [
                    'quality' => 'fast',
                    'regionalVariant' => $this->changeTargetArray[$targetLang]['regionalVariant'],
                    'mode' => 'translate',
                    'browserType' => 1,
                    'textType' => 'richtext',
                    'advancedMode' => false,
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
        $opt = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_RETURNTRANSFER => true,
        ];

        if (! static::$verify) {
            $opt[CURLOPT_SSL_VERIFYPEER] = false;
            $opt[CURLOPT_SSL_VERIFYHOST] = false;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $opt);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
