<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use Google_Client;
use Illuminate\Http\Request;

class FirebaseHelperController extends Controller
{
    public static function SendGenericNotification($user_token, $title, $text, $extras = null, $type = 1)
    {
        if ($user_token && $user_token != "") {
            try {
                // pasta config/firebase/fcm.json
                $credentialsFilePath = base_path('config/firebase/fcm.json');
                $client = new Google_Client();
                $client->setAuthConfig($credentialsFilePath);
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
                $http_client = $client->authorize();

                $message = [
                    'message' => [
                        'token' => $user_token,
                        'notification' => [
                            'title' => $title,
                            'body' => $text,
                        ],
                        'data' => [
                            // 'title' => $title,
                            // 'body' => $text,
                            'extras' => $extras
                        ],
                    ],
                ];

                $project = '1093242658722';

                $response = $http_client->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", ['json' => $message]);

                $error = $response->getBody();

                if (stripos($error, "error") !== false) {
                    report(new OperationException("Não foi possível enviar a notificação", 500, $error));
                }
            } catch (OperationException $e) {
                report($e);
            }
        }
    }
}
