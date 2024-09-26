<?php

namespace App\Http\Controllers;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GptController extends Controller
{
    public function refreshToken()
    {
        try {
            $url = 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth';
            $client = new Client();

            $response = $client->post($url, [
                'headers' => [
                    'RqUID' => '6f0b1291-c7f3-43c6-bb2e-9f3efb2dc98e',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic MDAzYjM5ZWQtYmI2MS00Y2MyLWE4NDEtNTQwZWY2MjJmNmU3OjZkMTdhOTAwLWI5NTMtNDhmNy1hYmM2LWIzMzdjMWZkOGU0NQ==',
                ],
                'form_params' => [
                    'scope' => 'GIGACHAT_API_PERS'
                ],
                'verify' => false,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Throwable $th) {
            Log::error('Ошибка при обновлении токена: ' . $th->getMessage());
            return $th->getMessage();
        }
    }



    public function query(Request $request)
    {
        try {
            $context = $this->parseUrls(['https://support.helpdeskeddy.com/ru/knowledge_base/article/365/category/53/', 'https://support.helpdeskeddy.com/ru/knowledge_base/article/134/category/53/']);
            Log::info("context: " .   $context);

            $newToken = $this->refreshToken()['access_token'];
            $url = 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions';

            $baseJson = [
                "model" => "GigaChat-Pro",
                "stream" => false,
                "update_interval" => 0,
                "messages" => [
                    [
                        "role" => "system",
                        "content" => "Используя информацию из этих статей: '" . $context . "', ответь на вопрос: "
                    ],
                    [
                        "role" => "user",
                        "content" => $request->input('message')
                    ]
                ]
            ];

            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $newToken,
            ])->post($url, $baseJson);

            $responseBody = $response->json();

            return response()->json([
                'status' => 'success',
                'answer' => $responseBody['choices'][0]['message']['content'],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    private function parseUrls(array $urls)
    {
        $result = '';

        foreach ($urls as $index => $url) {
            $html = file_get_contents($url);

            // Проверяем, удалось ли загрузить HTML
            if ($html === false) {
                continue; // Пропускаем эту итерацию в случае ошибки
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            $elements = $xpath->query('//p');

            $context = '';

            foreach ($elements as $element) {
                $context .= $dom->saveHTML($element);
            }

            $context = strip_tags($context);
            $context = trim($context);

            // Добавляем разделитель перед текстом следующей статьи
            if ($index > 0) { // Не добавляем разделитель перед первой статьей
                $result .= "\n--- Начало следующей статьи ---\n";
            }

            $result .= $context;
        }

        return $result;
    }
}
