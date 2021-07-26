<?php

namespace App\Facades;

class Discord {
    public static function sendMessage($text, $fields) {
        // echo "666";

        $timestamp = date("c", strtotime("now"));

        $json_data = json_encode([
            // "content" => 'test',
            "embeds" => [
                [
                    "description" => $text,
                    "color" => 11411914,
                    "timestamp" => $timestamp,
                    "footer" => [
                        "text" => "Alderney"
                    ],
                    "fields" => $fields
                ]
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


        $ch = curl_init( config['webhook'] );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec( $ch );
        // echo $response;
        curl_close( $ch );
    }
}