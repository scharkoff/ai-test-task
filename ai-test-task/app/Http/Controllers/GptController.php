<?php

namespace App\Http\Controllers;

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
            $newToken = $this->refreshToken()['access_token'];
            $url = 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions';

            $baseJson = [
                "model" => "GigaChat",
                "stream" => false,
                "update_interval" => 0,
                "messages" => [
                    [
                        "role" => "system",
                        "content" => "Используя информацию из этих статей: https://support.helpdeskeddy.com/ru/knowledge_base/article/365/category/53/ и https://support.helpdeskeddy.com/ru/knowledge_base/article/134/category/53/, ответь на вопрос: "
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
}
