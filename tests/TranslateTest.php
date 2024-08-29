<?php

use Justmd5\DeeplX\DeepLTranslator;
use PHPUnit\Framework\TestCase;

class TranslateTest extends TestCase
{
    /**
     * @var DeepLTranslator
     */
    protected static DeepLTranslator $translator;

    public static function setUpBeforeClass(): void
    {
        static::$translator = DeepLTranslator::withoutVerifying();
    }

    public function testTranslate()
    {
        $result = static::$translator->zh2en('电脑鼠标', 'en');
        $this->assertArrayNotHasKey('error', $result);

        return $result;
    }

    /**
     * @depends testTranslate
     */
    public function testSuccess($result)
    {
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals(1000, $result['status']);

        return $result;
    }

    /**
     * @depends testSuccess
     */
    public function testTranslateZh2En($result)
    {
        $this->assertEquals('computer mouse', $result['data']);
    }

    /**
     * @depends testSuccess
     */
    public function testEN2ZH_TW($result)
    {
        $result = static::$translator->en2zh_tw($result['data']);
        $this->assertEquals('電腦滑鼠', $result['data']);
    }
}
