<?php

/**
 * sendPushes.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 12/21/22 @ 07:41
 */

class SendPushes
{
    private $_container;
    private $_logger;
    private $_db;

    private $_authKeyPath;
    private $_authKeyId;
    private $_teamId;
    private $_bundleId;
    private $_payload;

    public function __construct()
    {
        $this->_container = new Container();
        $this->_logger = $this->_container->getLogger();
        $this->_db = $this->_container->getMySQLDBConnect();

        global $earthquakesAPNS;
        $this->_authKeyPath = $earthquakesAPNS['authKeyPath'];
        $this->_authKeyId = $earthquakesAPNS['authKeyId'];
        $this->_teamId = $earthquakesAPNS['teamId'];
        $this->_bundleId = $earthquakesAPNS['bundleId'];
    }

    public function sendPushes($debug, $title, $payload, $magnitude, $eqLatitude, $eqLongitude) {

        $start = microtime(true);
        $apns = new APNS($this->_logger);

        $alert = [
            'title' => $title,
            'body' => $payload
        ];
        $aps = [
            'alert' => $alert,
            'sound' => 'default',
            'content-available' => 1
        ];
        $this->_payload = ['aps' => $aps];
        $encodedPayload = json_encode($this->_payload);

        $server = $debug == 1 ? 'api.development' : 'api';
        $tokens = Tokens::getTokensToReceiveNotification($this->_logger, $this->_db, $debug, $magnitude);

        $this->_logger->info('Processing ' . count($tokens) . ' tokens.');

        $headers = [
            'apns-topic: ' . $this->_bundleId,
            'authorization: bearer ' . $this->generateAuthenticationHeader(),
            'apns-push-type: alert'
        ];

        $tokensDeleted = 0;
        $notificationsSent = 0;
        $notificationsCheckedForLocation = 0;
        $notificationsSendingForLocation = 0;
        foreach ($tokens as $token) {
            if ($token['location'] == 1) {
                $notificationsCheckedForLocation++;
                $tokenUnits = $token['units'];
                $tokenRadius = $token['radius'];
                $tokenLatitude = floatval($token['latitude']);
                $tokenLongitude = floatval($token['longitude']);

                if ($tokenUnits == 'miles') {
                    $tokenRadius = Earthquakes::convertMilesToMeters($tokenRadius);
                } else if ($tokenUnits == 'kilometers') {
                    $tokenRadius = Earthquakes::convertKilometersToMeters($tokenRadius);
                }

                $R = 6371e3; //Earth's mean radius in metres
                $minLatitude = $tokenLatitude - $tokenRadius / $R * 180 / pi();
                $maxLatitude = $tokenLatitude + $tokenRadius / $R * 180 / pi();
                $minLongitude = $tokenLongitude - $tokenRadius / $R * 180 / pi() / cos($tokenLatitude * pi() / 180);
                $maxLongitude = $tokenLongitude + $tokenRadius / $R * 180 / pi() / cos($tokenLatitude * pi() / 180);
//                $this->_logger->error('TokenLatitude: ' . $tokenLatitude . ', TokenLongitude: ' . $tokenLongitude . ', TokenRadius: ' . $tokenRadius . PHP_EOL);
                if ($eqLatitude > $maxLatitude) {
                    continue;
                } else {
                    if ($eqLatitude < $minLatitude) {
                        continue;
                    } else {
                        if ($eqLongitude > $maxLongitude) {
                            continue;
                        } else {
                            if ($eqLongitude < $minLongitude) {
                                continue;
                            } else {
                                $notificationsSendingForLocation++;
                            }
                        }
                    }
                }
            }
            $url = 'https://' . $server . '.push.apple.com/3/device/' . $token['token'];
            $response = $apns->sendNotifications($url, $headers, $encodedPayload);
            if ($response['output'] === false) {
                $this->_logger->error("curl_exec failed: " . $response['status']);
                continue;
            } else {
                $notificationsSent++;
            }
            if ($response['status'] === 400 || $response['status'] === 410) {
                $json = @json_decode($response['output']);
                if ($json->reason === 'BadDeviceToken' || $json->reason === 'Unregistered') {
                    Token::deleteToken($this->_logger, $this->_db, $token['token']);
                    $tokensDeleted++;
                } else {
                    var_dump($json);
                }
            }
        }
        $this->_logger->info('Deleted ' . $tokensDeleted . ' tokens.');
        $this->_logger->info('Sent ' . $notificationsSent . ' total notifications!');
        $this->_logger->info('Sent ' . $notificationsSendingForLocation . ' notifications for location!');
        $time = (microtime(true) - $start);
        $this->_logger->info('Time to send pushes: ' . $time);
    }

    private function generateAuthenticationHeader() {
        $header = base64_encode(json_encode([
            'alg' => 'ES256',
            'kid' => $this->_authKeyId
        ]));
        $claims = base64_encode(json_encode([
            'iss' => $this->_teamId,
            'iat' => time()
        ]));
        $pkey = openssl_pkey_get_private('file://' . $this->_authKeyPath);
        openssl_sign("$header.$claims", $signature, $pkey, 'sha256');
        $signed = base64_encode($signature);
        return "$header.$claims.$signed";
    }

}